<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

class ProductController extends AbstractController
{
    /**
     * Returns the list of available products
     *
     * @param ProductRepository $productRepository
     * @param SerializerInterface $serializer
     * @param Request $request
     * @param TagAwareCacheInterface $cachePool
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/api/products', name: 'allProducts', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return the list of an products',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class))
        )
    )]
    #[OA\Parameter(
        name: 'page',
        description: 'The field used to paginate products',
        in: 'query',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'The field used to limit products',
        in: 'query',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'Products')]
    public function getAllProducts
    (
        ProductRepository      $productRepository,
        SerializerInterface    $serializer,
        Request                $request,
        TagAwareCacheInterface $cachePool
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $idCache = "getAllProducts-" . $page . "-" . $limit;

        $jsonProductsList = $cachePool->get(
            $idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
            $item->tag('productsCache');
            $item->expiresAfter(3600);
            $productsList = $productRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($productsList, 'json');
        });

        return new JsonResponse($jsonProductsList, Response::HTTP_OK, [], true);
    }

    /**
     * Retrieves the details of a specific product from the database.
     *
     * @param Product $product The product object to retrieve details for.
     * @param SerializerInterface $serializer The serializer used to convert the product details to JSON.
     * @return JsonResponse The response containing the product details in JSON format.
     */
    #[Route('/api/products/{id}', name: 'detailProduct', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Return one product with your id',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class))
        )
    )]
    #[OA\Tag(name: 'Products')]
    public function getDetailProduct
    (
        Product             $product,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
