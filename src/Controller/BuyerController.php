<?php

namespace App\Controller;

use App\Entity\Buyer;
use App\Repository\BuyerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BuyerController extends AbstractController
{
    /**
     * Retrieves all products.
     *
     * @param BuyerRepository $buyerRepository The repository for accessing buyer data.
     * @param SerializerInterface $serializer The serializer for converting buyer data to JSON.
     *
     * @return JsonResponse  The JSON response containing the serialized buyer list.
     */
    #[Route('/api/buyers', name: 'app_buyers')]
    public function getAllProducts
    (
        BuyerRepository     $buyerRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $buyerList = $buyerRepository->findAll();
        $context = ['groups' => ['buyer']];
        $jsonBuyerList = $serializer->serialize($buyerList, 'json', $context);

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
        JWTEncoderInterface    $jwtEncoder,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        UrlGeneratorInterface  $urlGenerator
    ): Response
    {
        $data = $request->getContent();
        $buyer = $serializer->deserialize($data, Buyer::class, 'json');

        // Extracting the token from Authentication header
        $token = explode(' ', $request->headers->get('Authorization'))[1];

        if (empty($token)) {
            return new JsonResponse([
                'error' => 'There was a problem creating the buyer'
            ], Response::HTTP_NOT_FOUND);
        }

        // Decoding the token
        $decodedToken = $jwtEncoder->decode($token);

        // Extracting company email from token
        $companyName = $decodedToken['username'];

        $company = $userRepository->findOneBy(['email' => $companyName]);

        if ($company !== null) {
            $buyer->setCompanyAssociated($company);
        } else {
            return new JsonResponse(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        // validate the buyer
        $errors = $validator->validate($buyer);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($buyer);
        $entityManager->flush();

        $location = $urlGenerator->generate('buyer', ['id' => $buyer->getId()],  UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse(['status' => 'Buyer created', 'location' => $location], Response::HTTP_CREATED);
    }
}
