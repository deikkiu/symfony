<?php

namespace App\Model;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CategoryModel
{

	public function __construct(
		protected EntityManagerInterface $entityManager,
		protected CategoryRepository     $categoryRepository,
		protected ProductModel           $productModel,
		protected RequestStack           $requestStack,
	)
	{
	}

	public function getOrCreateCategory(?string $slug): ?Category
	{
		if (!$slug) {
			return new Category();
		}

		return $this->categoryRepository->findOneBy(['slug' => $slug]);
	}

	public function saveOrUpdateCategory(Category $category): void
	{
		$this->entityManager->persist($category);
		$this->entityManager->flush();

		if ($category->getId()) {
			$this->addFlash('success', 'Category has been updated!');
		} else {
			$this->addFlash('success', 'Category has been created!');
		}
	}

	public function deleteCategory(Category $category): void
	{
		$isCategoryDeleted = $this->removeCategory($category);

		if ($isCategoryDeleted) {
			$this->addFlash('success', 'Category has been deleted!');
		} else {
			$this->addFlash('warning', 'Category can not be deleted, because category has a subcategories or products!');
		}
	}

	public function updateCountProducts(Category $category): void
	{
		$productCountPublished = $this->categoryRepository->countPublishedProductsByCategory($category);
		$category->setProductCountPublished($productCountPublished);

		$productCount = $this->categoryRepository->countAllProductsByCategory($category);
		$category->setProductCount($productCount);

		$this->entityManager->persist($category);
		$this->entityManager->flush();
	}

	public function removeCategory(Category $category): bool
	{
		$parentCategory = $category->getParent();
		$countSubCategories = $this->categoryRepository->countSubcategories($category);
		$countProducts = $this->productModel->countProductsByCategory($category);

		if (!$parentCategory && ($countSubCategories > 0 || $countProducts > 0)) {
			return false;
		}

		$products = $this->getCategoryProducts($category);

		$this->setCategoryProducts($products, $parentCategory);

		$this->removeCategories($category);

		return true;
	}

	// @TODO: batch
	private function getCategoryProducts(Category $category): array
	{
		$products = $category->getProducts()->toArray();
		$subCategories = $category->getCategories()->toArray();

		foreach ($subCategories as $subCategory) {
			$products = array_merge($products, $this->getCategoryProducts($subCategory));
		}

		return $products;
	}

	private function setCategoryProducts(array $products, ?Category $category): void
	{
		$batchSize = 20;
		$i = 0;

		foreach ($products as $product) {
			$product->setCategory($category);
			$this->entityManager->persist($product);

			++$i;
			if (($i % $batchSize) === 0) {
				$this->entityManager->flush();
				$this->entityManager->clear();
			}
		}

		$this->entityManager->flush();
	}

	// @TODO: batch
	private function removeCategories(Category $category): void
	{
		$subCategories = $category->getCategories()->toArray();

		foreach ($subCategories as $subCategory) {
			if ($this->categoryRepository->countSubcategories($subCategory) > 0) {
				$this->removeCategories($subCategory);
			}

			$this->entityManager->remove($subCategory);
		}

		$this->entityManager->remove($category);
		$this->entityManager->flush();
	}

	public function addFlash(string $type, string $message): void
	{
		$this->requestStack->getSession()->getFlashBag()->add($type, $message);
	}
}