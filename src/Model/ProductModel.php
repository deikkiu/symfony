<?php

namespace App\Model;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\ProductRepository;
use App\Service\FileUploader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductModel
{
	private CategoryModel $categoryModel;

	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly ProductRepository      $productRepository,
		private readonly RequestStack           $requestStack,
		private readonly Security               $security,
		private readonly Filesystem             $filesystem,
		private readonly CacheManager           $cacheManager,
		private readonly FileUploader           $fileUploader,
		private readonly string                 $uploadsDirectory,
		private readonly string                 $uploadsFolder
	)
	{
	}

	public function getOrCreateProduct(?string $slug): ?Product
	{
		if (!$slug) return new Product();

		return $this->entityManager->getRepository(Product::class)->findOneBy(['slug' => $slug]);
	}

	public function saveOrUpdateProduct(Product $product, ArrayCollection $colors = null): void
	{
		$message = $this->preSaveOrUpdateProduct($product);

		if ($colors) {
			foreach ($colors as $color) {
				if ($product->getColors()->contains($color) === false) {
					$this->entityManager->remove($color);
				}
			}
		}

		$this->entityManager->persist($product);
		$this->entityManager->flush();

		$this->setSessionAttribute('lastProduct', $product->getSlug());
		$this->addFlash('success', $message);
	}

	public function preSaveOrUpdateProduct(Product $product, int $userId = null): string
	{
		$message = 'Product has been updated!';

		if (!$userId) {
			$user = $this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId());
		} else {
			$user = $this->entityManager->getRepository(User::class)->find($userId);
		}

		if (!$product->getId()) {
			$product->setUser($user);
			$message = 'Product has been created!';
		}

		return $message;
	}

	public function deleteProduct(Product $product): void
	{
		$this->entityManager->remove($product);
		$this->entityManager->flush();

		if ($product->getImagePath()) {
			$this->deleteImage($product->getImagePath());
		}

		$lastProduct = $this->getSessionAttribute('lastProduct');

		if ($lastProduct === $product->getSlug()) {
			$this->removeSessionAttribute('lastProduct');
		}

		if ($product->getCategory()) {
			$this->categoryModel->updateCountProducts($product->getCategory());
		}

		$this->addFlash('success', 'Product has been deleted!');
	}

	private function deleteImage(string $path): void
	{
		$fullFilePath = $this->uploadsDirectory . $path;

		if ($this->filesystem->exists($fullFilePath)) {
			$this->filesystem->remove($fullFilePath);
			$this->cacheManager->remove($this->uploadsFolder . $path);
		}
	}

	public function setOrUpdateImage(Product $product, UploadedFile $file, string $folder): void
	{
		$existImage = $product->getImagePath();

		if ($existImage) $this->deleteImage($existImage);

		$product->setImagePath($this->fileUploader->upload($file, $folder));
	}

	public function countProductsByCategory(Category $category): ?int
	{
		return $this->productRepository->countProductsByCategory($category);
	}

	private function setSessionAttribute(string $name, mixed $value): void
	{
		$session = $this->requestStack->getSession();
		$session->set($name, $value);
	}

	private function removeSessionAttribute(string $name): void
	{
		$session = $this->requestStack->getSession();
		$session->remove($name);
	}

	private function getSessionAttribute(string $name): mixed
	{
		$session = $this->requestStack->getSession();
		return $session->get($name);
	}

	private function addFlash(string $type, string $message): void
	{
		$this->requestStack->getSession()->getFlashBag()->add($type, $message);
	}

	public function setCategoryModel(CategoryModel $categoryModel): void
	{
		$this->categoryModel = $categoryModel;
	}
}