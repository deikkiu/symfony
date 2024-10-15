<?php

namespace App\Controller\Api;

use App\Entity\Color;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

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

		$context = $this->getContext();

		return $this->json($product, context: $context);
	}

	private function getContext(): array
	{
		$colorsCallback = function (object $attributeValue): array {
			return array_map(function ($color) {
				return $color instanceof Color ? $color->getName() : null;
			}, $attributeValue->toArray());
		};

		return [
			AbstractNormalizer::GROUPS => ['serialize', 'category_basic'],
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object): ?int {
				return $object->getId();
			},
			AbstractNormalizer::CALLBACKS => ['colors' => $colorsCallback],
			DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s',
		];
	}
}
