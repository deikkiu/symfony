<?php

namespace App\Model;

use App\Entity\ImportProduct;
use App\Repository\ImportProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportProductModel
{
	private const STATUS = ImportProduct::STATUS_PENDING;

	public function __construct(
		private readonly ImportProductRepository $importProductRepository,
		private readonly EntityManagerInterface  $entityManager
	)
	{
	}

	public function createImportProduct(string $path): ImportProduct
	{
		$importProduct = new ImportProduct();

		$importProduct->setStatus(ImportProduct::STATUS_PENDING);
		$importProduct->setPath($path);

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();

		return $importProduct;
	}

	public function getImportProduct(?string $slug): ImportProduct|null
	{
		if (!$slug) return new ImportProduct();

		return $this->importProductRepository->findOneBy(['slug' => $slug]);
	}

	public function getAllImportProducts(): array
	{
		return $this->importProductRepository->findAll();
	}

	public function clearAllMessages(ImportProduct $importProduct): void
	{
		foreach ($importProduct->getMessages() as $message) {
			$this->entityManager->remove($message);
		}

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();
	}
}