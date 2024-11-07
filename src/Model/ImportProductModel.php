<?php

namespace App\Model;

use App\Entity\ImportProduct;
use App\Entity\ImportProductMessage;
use App\Repository\ImportProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

readonly class ImportProductModel
{
	public function __construct(
		private ImportProductRepository $importProductRepository,
		private EntityManagerInterface  $entityManager,
		private Filesystem              $filesystem,
		private string                  $uploadsDirectory,
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

	public function updateImportProduct(string $slug, bool $status, array $messages, int $countImportedProducts = 0): void
	{
		$importProduct = $this->entityManager->getRepository(ImportProduct::class)->findOneBy(['slug' => $slug]);

		if (!$importProduct) {
			throw new \RuntimeException("Import product with slug '{$slug}' not found.");
		}

		$importProduct->setStatus($status ? ImportProduct::STATUS_SUCCESS : ImportProduct::STATUS_ERROR);

		if ($status) {
			$importProduct->setCountImportedProducts($countImportedProducts);
		} else {
			$this->addImportMessages($importProduct, $messages);
		}

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();
	}

	public function addImportMessages(ImportProduct $importProduct, array $messages): void
	{
		foreach ($messages as $message) {
			$importProductMessage = new ImportProductMessage();

			$importProductMessage->setMessage($message);
			$importProduct->addMessage($importProductMessage);

			$this->entityManager->persist($importProductMessage);
		}
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

		$this->entityManager->flush();
	}

	public function deleteImportProduct(ImportProduct $importProduct): void
	{
		$this->entityManager->remove($importProduct);
		$this->entityManager->flush();

		$this->filesystem->remove($this->uploadsDirectory . $importProduct->getPath());
	}

	public function updateStatus(ImportProduct $importProduct, int $status): void
	{
		$importProduct->setStatus($status);

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();
	}
}