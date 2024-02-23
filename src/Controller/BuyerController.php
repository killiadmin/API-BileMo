<?php

namespace App\Controller;

use App\Entity\Buyer;
use App\Repository\BuyerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

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
    public function getAllProducts(BuyerRepository $buyerRepository, SerializerInterface $serializer): JsonResponse
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
    public function getDetailBuyer(Buyer $buyer, SerializerInterface $serializer): JsonResponse
    {
        $context = ['groups' => ['buyer']];
        $jsonBuyer = $serializer->serialize($buyer, 'json', $context);
        return new JsonResponse($jsonBuyer, Response::HTTP_OK, [], true);
    }

    /**
     * Adds a new buyer to the database.
     *
     * @param Request $request The HTTP request object.
     * @param EntityManagerInterface $entityManager The entity manager.
     * @param UserRepository $userRepository The repository for the User entity.
     *
     * @return Response The HTTP response indicating the status of the operation.
     * @throws \JsonException
     */
    #[Route('/api/buyer/new', name: 'newBuyer', methods: ['POST'])]
    public function addBuyer(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response {
        $data = $request->getContent();
        // Let's decode JSON data to an array
        $dataArray = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

        // Let's create an instance of the Buyer entity
        $buyer = new Buyer();
        $buyer->setFirstname($dataArray['firstname_identifier']);
        $buyer->setLastname($dataArray['lastname_identifier']);
        $buyer->setEmail($dataArray['email']);
        $buyer->setAddress($dataArray['address']);
        $buyer->setPhone($dataArray['phone']);


        // Let's get and add the User entity instance
        $user = $userRepository->find($dataArray['idClientAssociated']);

        if ($user === null) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $buyer->setCompanyAssociated($user);

        $entityManager->persist($buyer);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User created'], Response::HTTP_CREATED, []);
    }
}
