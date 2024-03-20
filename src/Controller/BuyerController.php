<?php

namespace App\Controller;

use App\Entity\Buyer;
use App\Repository\BuyerRepository;
use App\Repository\UserRepository;
use App\Service\TokenExtractorService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

class BuyerController extends AbstractController
{
    private TokenExtractorService $tokenExtractorService;

    public function __construct(TokenExtractorService $tokenExtractorService)
    {
        $this->tokenExtractorService = $tokenExtractorService;
    }

    /**
     * Retrieves a list of all buyers with pagination.
     *
     * @param BuyerRepository $buyerRepository The buyer repository.
     * @param SerializerInterface $serializer The serializer.
     * @param Request $request The HTTP request object.
     * @param TagAwareCacheInterface $cache The cache.
     * @return JsonResponse The JSON response containing the list of buyers.
     * @throws InvalidArgumentException
     * @throws \JsonException|JWTDecodeFailureException
     */
    #[Route('/api/buyers', name: 'app_buyers', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return the list of an buyers associated an company',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Buyer::class, groups: ['buyer']))
        )
    )]
    #[OA\Response(response: 400, description: 'There was a problem with the request')]
    #[OA\Response(response: 401, description: 'You are not authorized to perform this action')]
    #[OA\Response(response: 403, description: 'You are not authorized to interact with this buyer')]
    #[OA\Response(response: 404, description: 'Page not found')]
    #[OA\Parameter(
        name: 'page',
        description: 'The field used to paginate buyers',
        in: 'query',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'The field used to limit buyers',
        in: 'query',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Buyers')]
    public function getAllBuyers
    (
        UserRepository         $userRepository,
        BuyerRepository        $buyerRepository,
        SerializerInterface    $serializer,
        Request                $request,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $authorization = $this->authorizeAction($request, $userRepository, null,true);

        if ($authorization instanceof Response) {
            return $authorization;
        }

        $idCompany = json_decode($authorization, true, 512, JSON_THROW_ON_ERROR);
        
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllBuyers-" . $page . "-" . $limit;

        $jsonBuyerList = $cache->get(
            $idCache, function (ItemInterface $item) use ($idCompany, $buyerRepository, $page, $limit, $serializer) {
            $item->tag('buyersCache');
            $item->expiresAfter(3600);
            $buyerList = $buyerRepository->findAllWithPagination($page, $limit, $idCompany);
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
     * @throws JWTDecodeFailureException
     */
    #[Route('/api/buyer/{id}', name: 'detailBuyer', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return one buyer with your id',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Buyer::class))
        )
    )]
    #[OA\Response(response: 400, description: 'There was a problem with the request')]
    #[OA\Response(response: 401, description: 'You are not authorized to perform this action')]
    #[OA\Response(response: 403, description: 'You are not authorized to interact with this buyer')]
    #[OA\Response(response: 404, description: 'Page not found')]
    #[OA\Tag(name: 'Buyers')]
    public function getDetailBuyer
    (
        Request             $request,
        UserRepository      $userRepository,
        Buyer               $buyer,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $authorization = $this->authorizeAction($request, $userRepository, $buyer,true);

        if ($authorization instanceof Response) {
            return $authorization;
        }
        
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
     *
     * @return Response The HTTP response.
     * @throws \JsonException|JWTDecodeFailureException
     */
    #[Route('/api/buyer', name: 'newBuyer', methods: ['POST'])]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                ref: new Model(type: Buyer::class, groups: ["createBuyer"])
            )
        )
    )]
    #[OA\Response(response: 400, description: 'There was a problem creating the buyer')]
    #[OA\Response(response: 401, description: 'You are not authorized to perform this action')]
    #[OA\Response(response: 404, description: 'Page not found')]
    #[OA\Tag(name: 'Buyers')]
    public function addBuyer
    (
        Request                $request,
        EntityManagerInterface $entityManager,
        UserRepository         $userRepository,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        UrlGeneratorInterface  $urlGenerator
    ): Response
    {
        $data = $request->getContent();
        $buyer = $serializer->deserialize($data, Buyer::class, 'json');

        // Extracting the token from Authentication header
        $token = $this->tokenExtractorService->extractToken($request);

        if (null === $token) {
            return new JsonResponse([
                'error' => 'You are not authorized to perform this action'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Decoding the token
        $decodedToken = $this->tokenExtractorService->decodeToken($token);

        if (null === $decodedToken) {
            return new JsonResponse([
                'error' => 'There was a problem creating the buyer'
            ], Response::HTTP_NOT_FOUND);
        }

        // Extracting company email from token
        $companyName = $decodedToken['username'];

        $company = $userRepository->findOneBy(['email' => $companyName]);

        if ($company !== null) {
            $buyer->setCompanyAssociated($company);
        } else {
            return new JsonResponse(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        // Validate the buyer
        $errors = $validator->validate($buyer);

        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($buyer);
        $entityManager->flush();

        $location = $urlGenerator->generate('detailBuyer', ['id' => $buyer->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        // Serialize the buyer object to JSON
        $context = SerializationContext::create()->setGroups(['buyer']);
        $buyerJson = $serializer->serialize($buyer, 'json', $context);

        return new JsonResponse([
            'Buyer created' => json_decode($buyerJson, false, 512, JSON_THROW_ON_ERROR),
            'location' => $location
        ], Response::HTTP_CREATED);
    }

    /**
     * Updates an existing buyer in the system associated with a company
     *
     * @param Request $request The HTTP request object.
     * @param SerializerInterface $serializer The serializer.
     * @param Buyer $currentBuyer The current buyer object to update.
     * @param EntityManagerInterface $em The entity manager.
     * @param UserRepository $userRepository The user repository.
     * @param ValidatorInterface $validator The validator.
     * @param TagAwareCacheInterface $cache The cache.
     *
     * @return Response The HTTP response.
     * @throws ExceptionInterface|InvalidArgumentException If an error occurs during deserialization.
     * @throws JWTDecodeFailureException|\JsonException
     */
    #[Route('/api/buyer/{id}', name: "updateBuyer", methods: ['PUT'])]
    #[OA\RequestBody(
        content: new OA\MediaType(
            mediaType: "application/json",
            schema: new OA\Schema(
                ref: new Model(type: Buyer::class, groups: ["createBuyer"])
            )
        )
    )]
    #[OA\Response(response: 200, description: 'The buyer has been modified')]
    #[OA\Response(response: 400, description: 'There was a problem modified the buyer')]
    #[OA\Response(response: 401, description: 'You are not authorized to perform this action')]
    #[OA\Response(response: 403, description: 'You are not authorized to interact with this buyer')]
    #[OA\Response(response: 404, description: 'Page not found')]
    #[OA\Tag(name: 'Buyers')]
    public function updateBuyer(
        Request                $request,
        SerializerInterface    $serializer,
        Buyer                  $currentBuyer,
        EntityManagerInterface $em,
        UserRepository         $userRepository,
        ValidatorInterface     $validator,
        TagAwareCacheInterface $cache,
        UrlGeneratorInterface  $urlGenerator
    ): Response
    {
        $authorization = $this->authorizeAction($request, $userRepository, $currentBuyer, true);

        if ($authorization instanceof Response) {
            return $authorization;
        }

        $newBuyer = $serializer->deserialize($request->getContent(), Buyer::class, 'json');
        $currentBuyer->setFirstname($newBuyer->getFirstname());
        $currentBuyer->setLastname($newBuyer->getLastname());
        $currentBuyer->setEmail($newBuyer->getEmail());
        $currentBuyer->setAddress($newBuyer->getAddress());
        $currentBuyer->setPhone($newBuyer->getPhone());

        // We check for errors
        $errors = $validator->validate($currentBuyer);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'),
                Response::HTTP_BAD_REQUEST, [], true);
        }

        $idCompany = json_decode($authorization, true, 512, JSON_THROW_ON_ERROR);
        $currentBuyer->setCompanyAssociated($userRepository->find($idCompany));

        $em->persist($currentBuyer);
        $em->flush();

        $location = $urlGenerator->generate('updateBuyer', ['id' => $idCompany], UrlGeneratorInterface::ABSOLUTE_URL);

        // We clear the cache
        $cache->invalidateTags(["buyersCache"]);

        // Serialize the buyer object to JSON
        $context = SerializationContext::create()->setGroups(['buyer']);
        $currentBuyerJson = $serializer->serialize($currentBuyer, 'json', $context);
        $currentBuyerArray = json_decode($currentBuyerJson, true, 512, JSON_THROW_ON_ERROR);

        return new JsonResponse([
            'Buyer modified' => $currentBuyerArray,
            'location' => $location
        ], Response::HTTP_OK);
    }

    /**
     * Deletes a buyer from the system.
     *
     * @param Buyer $buyer The buyer entity to delete.
     * @param UserRepository $userRepository The user repository.
     * @param EntityManagerInterface $em The entity manager.
     * @param TagAwareCacheInterface $cachePool The cache pool.
     * @param Request $request The HTTP request object.
     *
     * @return Response The HTTP response.
     * @throws InvalidArgumentException|JWTDecodeFailureException
     */
    #[Route('/api/buyer/{id}', name: 'deleteBuyer', methods: ['DELETE'])]
    #[OA\Response(response: 204, description: 'The buyer has been deleted')]
    #[OA\Response(response: 400, description: 'There was a problem with the request')]
    #[OA\Response(response: 401, description: 'You are not authorized to perform this action')]
    #[OA\Response(response: 403, description: 'You are not authorized to interact with this buyer')]
    #[OA\Response(response: 404, description: 'Page not found')]
    #[OA\Tag(name: 'Buyers')]
    public function deleteBuyer
    (
        Buyer                  $buyer,
        UserRepository         $userRepository,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cachePool,
        Request                $request
    ): Response
    {
        $authorization = $this->authorizeAction($request, $userRepository, $buyer);

        if ($authorization instanceof Response) {
            return $authorization;
        }

        $cachePool->invalidateTags(['buyersCache']);
        $em->remove($buyer);
        $em->flush();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Authorizes the action based on the token and user company.
     *
     * @param Request $request The HTTP request object.
     * @param UserRepository $userRepository The user repository.
     * @param Buyer|null $buyer The buyer entity.
     * @param bool $returnCompany
     * @return int|JsonResponse|null The HTTP response if the action is not authorized, otherwise null.
     * @throws JWTDecodeFailureException
     */
    private function authorizeAction(
        Request        $request,
        UserRepository $userRepository,
        Buyer          $buyer = null,
        bool $returnCompany = false
    ): JsonResponse|int|null
    {
        $token = $this->tokenExtractorService->extractToken($request);

        if (null === $token) {
            return new JsonResponse([
                'error' => 'You are not authorized to perform this action'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Decoding the token
        $decodedToken = $this->tokenExtractorService->decodeToken($token);
        if (null === $decodedToken) {
            return new JsonResponse([
                'error' => 'There was a problem with the request'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Extracting company email from token
        $company = $userRepository->findOneBy(['email' => $decodedToken['username']]);
        $buyerCompany = $buyer?->getCompanyAssociated();

        if ($company && $buyerCompany && $buyerCompany->getEmail() !== $company->getEmail()) {
            return new JsonResponse([
                'error' => 'You are not authorized to interact with this buyer'
            ], Response::HTTP_FORBIDDEN);
        }

        // Return company if parameter returnCompany is true
        if ($returnCompany && $company) {
            return $company->getId();
        }

        return null;
    }
}
