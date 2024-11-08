<?php

namespace App\Model;

use App\Entity\Import;
use App\Entity\ImportMessage;
use App\Repository\ImportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

readonly class ImportModel
{
	public function __construct(
		private ImportRepository       $importRepository,
		private EntityManagerInterface $entityManager,
		private Filesystem             $filesystem,
		private string                 $uploadsDirectory,
	)
	{
	}

	public function createImportProduct(string $path): Import
	{
		$importProduct = new Import();

		$importProduct->setStatus(Import::STATUS_PENDING);
		$importProduct->setPath($path);

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();

		return $importProduct;
	}

	public function getImportProduct(?string $slug): Import|null
	{
		return $this->importRepository->findOneBy(['slug' => $slug]);
	}

	public function updateImportProduct(string $slug, bool $status, array $messages, int $countImportedProducts = 0): void
	{
		$importProduct = $this->entityManager->getRepository(Import::class)->findOneBy(['slug' => $slug]);

		if (!$importProduct) {
			throw new \RuntimeException("Import product with slug '{$slug}' not found.");
		}

		$importProduct->setStatus($status ? Import::STATUS_SUCCESS : Import::STATUS_ERROR);

		if ($status) {
			$importProduct->setCountImportedProducts($countImportedProducts);
		} else {
			$this->addImportMessages($importProduct, $messages);
		}

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();
	}

	public function addImportMessages(Import $importProduct, array $messages): void
	{
		foreach ($messages as $message) {
			$importProductMessage = new ImportMessage();

			$importProductMessage->setMessage($message);
			$importProduct->addMessage($importProductMessage);

			$this->entityManager->persist($importProductMessage);
		}
	}

	public function getAllImportProducts(): array
	{
		return $this->importRepository->findBy([], orderBy: ['updatedAt' => 'DESC']);
	}

	public function clearAllMessages(Import $importProduct): void
	{
		foreach ($importProduct->getMessages() as $message) {
			$this->entityManager->remove($message);
		}

		$this->entityManager->flush();
	}

	public function deleteImportProduct(Import $importProduct): void
	{
		$this->entityManager->remove($importProduct);
		$this->entityManager->flush();

		$this->filesystem->remove($this->uploadsDirectory . $importProduct->getPath());
	}

	public function updateStatus(Import $importProduct, int $status): void
	{
		$importProduct->setStatus($status);

		$this->entityManager->persist($importProduct);
		$this->entityManager->flush();
	}
}