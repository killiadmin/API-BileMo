<?php

namespace App\Controller;

use App\Entity\Buyer;
use App\Repository\BuyerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class BuyerController extends AbstractController
{
    /**
     * Retrieves a list of all buyers from the system.
     *
     * @param BuyerRepository $buyerRepository The buyer repository.
     * @param SerializerInterface $serializer The serializer.
     * @param Request $request The HTTP request object.
     * @param TagAwareCacheInterface $cache
     * @return JsonResponse The JSON response containing the list of buyers.
     * @throws InvalidArgumentException
     */
    #[Route('/api/buyers', name: 'app_buyers')]
    public function getAllBuyers
    (
        BuyerRepository        $buyerRepository,
        SerializerInterface    $serializer,
        Request                $request,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBuyers-" . $page . "-" . $limit;

        $jsonBookList = $cache->get($idCache, function (ItemInterface $item) use ($buyerRepository, $page, $limit, $serializer) {
            $item->tag("buyersCache");
            $buyerList = $buyerRepository->findAllWithPagination($page, $limit);
            $context = ['groups' => ['buyer']];
            return $serializer->serialize($buyerList, 'json', $context);
        });

        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }


    /**
     * Retrieves the details of a specific buyer.
     *
     * @param Buyer $buyer The buyer object.
     * @param SerializerInterface $serializer The serializer for converting buyer data to JSON.
     *
     * @return JsonResponse  The JSON response containing the serialized buyer.
     *
     * @throws NotFoundHttpException If the buyer is not found.
     */
    #[Route('/api/buyer/{id}', name: 'app_buyer', methods: ['GET'])]
    public function getDetailBuyer
    (
        Buyer               $buyer,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $context = ['groups' => ['buyer']];
        $jsonBuyer = $serializer->serialize($buyer, 'json', $context);
        return new JsonResponse($jsonBuyer, Response::HTTP_OK, [], true);
    }

    /**
     * Adds a new buyer to the system.
     *
     * @param Request $request The HTTP request object.
     * @param EntityManagerInterface $entityManager The entity manager.
     * @param UserRepository $userRepository The user repository.
     * @param JWTEncoderInterface $jwtEncoder The JWT encoder.
     *
     * @return Response The HTTP response.
     * @throws \JsonException|JWTDecodeFailureException
     */
    #[Route('/api/buyer/new', name: 'newBuyer', methods: ['POST'])]
    public function addBuyer
    (
        Request                $request,
        EntityManagerInterface $entityManager,
        UserRepository         $userRepository,
        JWTEncoderInterface    $jwtEncoder
    ): Response
    {
        $data = $request->getContent();
        $dataArray = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        $buyer = new Buyer();
        $buyer->setFirstname($dataArray['firstname']);
        $buyer->setLastname($dataArray['lastname']);
        $buyer->setEmail($dataArray['email']);
        $buyer->setAddress($dataArray['address']);
        $buyer->setPhone($dataArray['phone']);

        // Extracting the token from Authentication header
        $token = explode(' ', $request->headers->get('Authorization'))[1];

        if (empty($token)) {
            return new JsonResponse([
                'error' => 'There was a problem creating the buyer'
            ], Response::HTTP_NOT_FOUND);
        }

        // Decoding the token
        $decodedToken = $jwtEncoder->decode($token);

        // Extracting company mail from token
        $companyName = $decodedToken['username'];

        $company = $userRepository->findOneBy(['email' => $companyName]);

        if ($company !== null) {
            $buyer->setCompanyAssociated($company);
        } else {
            return new JsonResponse(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->persist($buyer);
        $entityManager->flush();

        return new JsonResponse(['status' => 'Buyer created'], Response::HTTP_CREATED);
    }
}
