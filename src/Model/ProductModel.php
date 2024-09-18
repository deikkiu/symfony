<?php

namespace App\Model;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductModel
{
	public function __construct(
		protected EntityManagerInterface $entityManager,
		protected ProductRepository      $productRepository,
		protected RequestStack           $requestStack,
	)
	{
	}

	public function getOrCreateProduct(?string $slug): ?Product
	{
		if (!$slug) {
			return new Product();
		}

		return $this->entityManager->getRepository(Product::class)->findOneBy(['slug' => $slug]);
	}

	public function saveOrUpdateProduct(Product $product): void
	{
		$message = 'Product has been updated!';

		if (!$product->getId()) {
			$this->onCreateAt($product);

			$message = 'Product has been created!';
		}

		$this->onUpdateAt($product);

		$this->entityManager->persist($product);
		$this->entityManager->flush();

		$this->setSessionAttribute('lastProduct', $product->getSlug());
		$this->addFlash('success', $message);
	}

	public function deleteProduct(Product $product): void
	{
		$this->entityManager->remove($product);
		$this->entityManager->flush();

		$lastProduct = $this->getSessionAttribute('lastProduct');

		if ($lastProduct === $product->getSlug()) {
			$this->removeSessionAttribute('lastProduct');
		}

		$this->addFlash('success', 'Product has been deleted!');
	}

	public function onCreateAt(Product $product): void
	{
		$product->setCreatedAt(new \DateTime('now', new \DateTimeZone('Asia/Almaty')));
	}

	public function onUpdateAt(Product $product): void
	{
		$product->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Almaty')));
	}

	public function countProductsByCategory(Category $category): ?int
	{
		return $this->productRepository->countProductsByCategory($category);
	}

	public function setSessionAttribute(string $name, mixed $value): void
	{
		$session = $this->requestStack->getSession();
		$session->set($name, $value);
	}

	public function removeSessionAttribute(string $name): void
	{
		$session = $this->requestStack->getSession();
		$session->remove($name);
	}

	public function getSessionAttribute(string $name): mixed
	{
		$session = $this->requestStack->getSession();
		return $session->get($name);
	}

	public function addFlash(string $type, string $message): void
	{
		$this->requestStack->getSession()->getFlashBag()->add($type, $message);
	}
}