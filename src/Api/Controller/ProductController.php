<?php

namespace App\Api\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[IsGranted("ROLE_ADMIN")]
class ProductController extends AbstractController
{
	public function getProductAction(Request $request, ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
	{
		$id = $request->get('id');
		$product = $productRepository->find($id);

		if (!$product) {
			throw $this->createNotFoundException('Product not found for id = ' . $id);
		}

		$jsonContent = $serializer->serialize($product, 'json', [
			AbstractNormalizer::IGNORED_ATTRIBUTES => ['category' => 'products', 'user' => 'password', 'userIdentifier', 'roles', 'id'],
			DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s',
		]);

		return $this->json($jsonContent);
	}
}