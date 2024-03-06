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
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

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

        $jsonBuyerList = $cache->get($idCache, function (ItemInterface $item) use ($buyerRepository, $page, $limit, $serializer) {
            $item->tag('buyersCache');
            $item->expiresAfter(3600);
            $buyerList = $buyerRepository->findAllWithPagination($page, $limit);
            $context = SerializationContext::create()->setGroups(['buyer']);
            return $serializer->serialize($buyerList, 'json', $context);
        });

        return new JsonResponse($jsonBuyerList, Response::HTTP_OK, [], true);
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
    #[Route('/api/buyer/{id}', name: 'detailBuyer', methods: ['GET'])]
    public function getDetailBuyer
    (
        Buyer               $buyer,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['buyer']);
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

    /**
     * Updates an existing buyer in the system.
     *
     * @param Request $request The HTTP request object.
     * @param SerializerInterface $serializer The serializer.
     * @param Buyer $currentBuyer The current buyer object to update.
     * @param EntityManagerInterface $em The entity manager.
     * @param UserRepository $userRepository The user repository.
     * @param ValidatorInterface $validator The validator.
     * @param TagAwareCacheInterface $cache The cache.
     *
     * @return JsonResponse The HTTP response.
     * @throws ExceptionInterface|InvalidArgumentException If an error occurs during deserialization.
     */
    #[Route('/api/buyer/update/{id}', name: "updateBuyer", methods: ['PUT'])]
/*    #[IsGranted('ROLES_ADMIN', message: 'You do not have sufficient rights to edit a buyer')]*/
    public function updateBuyer(
        Request                $request,
        SerializerInterface    $serializer,
        Buyer                  $currentBuyer,
        EntityManagerInterface $em,
        UserRepository         $userRepository,
        ValidatorInterface     $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $newBuyer = $serializer->deserialize($request->getContent(), Buyer::class, 'json');
        $currentBuyer->setFirstname($newBuyer->getFirstname());
        $currentBuyer->setLastname($newBuyer->getLastname());
        $currentBuyer->setEmail($newBuyer->getEmail());
        $currentBuyer->setAddress($newBuyer->getAddress());
        $currentBuyer->setPhone($newBuyer->getPhone());

        // We check for errors
        $errors = $validator->validate($currentBuyer);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idCompany = $content['company_associated']['id'] ?? -1;

        $currentBuyer->setCompanyAssociated($userRepository->find($idCompany));

        $em->persist($currentBuyer);
        $em->flush();

        // We clear the cache
        $cache->invalidateTags(["buyersCache"]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Deletes a buyer from the system.
     *
     * @param Buyer $buyer The buyer to delete.
     * @param EntityManagerInterface $em The entity manager.
     * @param TagAwareCacheInterface $cachePool The cache pool.
     *
     * @return JsonResponse The JSON response.
     * @throws InvalidArgumentException
     */
    #[Route('/api/buyer/{id}', name: 'deleteBuyer', methods: ['DELETE'])]
    /*#[IsGranted('ROLES_ADMIN', message: 'You do not have sufficient rights to delete a buyer')]*/
    public function deleteBuyer
    (
        Buyer                  $buyer,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $cachePool->invalidateTags(['buyersCache']);
        $em->remove($buyer);
        $em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
