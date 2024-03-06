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
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class ProductController extends AbstractController
{
    /**
     * Retrieves all products with pagination.
     *
     * @OA\Response(
     *      response=200,
     *      description="Returns the list of products",
     *      @OA\JsonContent(
     *         type="array",
     *         @OA\Items(ref=@Model(type=Product::class, groups={"product"}))
     *      )
     *  )
     * @OA\Parameter(
     *      name="page",
     *      in="query",
     *      description="The page you want to retrieve",
     *      @OA\Schema(type="int")
     *  )
     * @OA\Parameter(
     *      name="limit",
     *      in="query",
     *      description="The number of elements you want to retrieve",
     *      @OA\Schema(type="int")
     *  )
     * @OA\Tag(name="Products")
     *
     * @param ProductRepository $productRepository The product repository.
     * @param SerializerInterface $serializer The serializer interface.
     * @param Request $request The request object.
     * @param TagAwareCacheInterface $cachePool The cache pool.
     *
     * @return JsonResponse The JSON response containing all products.
     * @throws InvalidArgumentException
     */
    #[Route('/api/products', name: 'products')]
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

        $jsonProductsList = $cachePool->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
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
     *
     * @return JsonResponse The response containing the product details in JSON format.
     */
    #[Route('/api/product/{id}', name: 'detailProduct', methods: ['GET'])]
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
