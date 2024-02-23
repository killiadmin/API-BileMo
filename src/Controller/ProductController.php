<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ProductController extends AbstractController
{
    /**
     * Retrieves all products from the database.
     *
     * @param ProductRepository $productRepository The product repository.
     * @param SerializerInterface $serializer The serializer used to convert the products list to JSON.
     *
     * @return JsonResponse The response containing the list of products in JSON format.
     */
    #[Route('/api/products', name: 'app_products')]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $productsList = $productRepository->findAll();
        $jsonProductsList = $serializer->serialize($productsList, 'json');

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
    #[Route('/api/product/{id}', name: 'app_product', methods: ['GET'])]
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
