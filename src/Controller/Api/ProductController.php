<?php

namespace App\Controller\Api;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ProductController extends AbstractController
{
	public function getProduct(Request $request, ProductRepository $productRepository): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			return new JsonResponse([
				'status' => 404,
				'message' => 'Product not found',
			], 404);
		}

		return $this->json($product, context: [AbstractNormalizer::GROUPS => ['serialize']]);
	}
}
