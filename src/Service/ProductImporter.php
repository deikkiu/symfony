<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Product;
use App\Entity\ProductAttr;
use App\Model\ImportModel;
use App\Model\ProductModel;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductImporter
{
	private const BATCH_SIZE = 20;
	private bool $status = true;
	private array $messages = [];
	private array $images = [];

	public function __construct(
		private readonly HttpClientInterface    $httpClient,
		private readonly EntityManagerInterface $entityManager,
		private readonly ValidatorInterface     $validator,
		private readonly ProductModel           $productModel,
		private readonly FileUploader           $fileUploader,
		private readonly ImportModel            $importModel,
		private readonly Filesystem             $fileSystem,
		private readonly string                 $uploadsDirectory,
	)
	{
	}

	public function import(string $slug, int $userId): void
	{
		$import = $this->importModel->getImportProduct($slug);

		if (($fp = fopen($this->uploadsDirectory . $import->getPath(), "r")) === false) {
			$this->status = false;
			$this->addWarningMessage('Cannot read the file, please upload in the format - csv');
			$this->updateImport($slug);
			return;
		}

		$rowNumber = 1;
		$i = 0;

		$this->entityManager->beginTransaction();

		try {
			while (($row = fgetcsv($fp, 10000, ",")) !== false) {
				$data = [
					'name' => $row[0],
					'category' => $row[1],
					'price' => $row[2],
					'amount' => $row[3],
					'descr' => $row[4] ?? '',
					'imagePath' => $row[5] ?? null,
					'length' => $row[6] ?? null,
					'width' => $row[7] ?? null,
					'height' => $row[8] ?? null,
					'weight' => $row[9] ?? null,
					'colors' => $row[10] ?? '',
				];

				$product = new Product();
				$this->processRow($product, $data, $rowNumber);

				$this->productModel->preSaveOrUpdateProduct($product, $userId);
				$this->entityManager->persist($product);

				++$rowNumber;
				++$i;

				if (($i % self::BATCH_SIZE) === 0) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			}

			$this->entityManager->flush();
			$this->entityManager->clear();

			fclose($fp);

			if (!$this->status) {
				$this->entityManager->rollback();
				$this->updateImport($slug);
				return;
			}

			$this->entityManager->commit();

			$this->updateImport($slug, $i);
		} catch (Exception $e) {
			$this->entityManager->rollback();

			$this->updateImport($slug);
			$this->addWarningMessage('Error while saving products: ' . $e->getMessage());
		}
	}

	private function processRow(Product $product, array $data, int $rowNumber): void
	{
		$preMessage = "Error [Row №{$rowNumber}] | ";

		try {
			$this->setProductColumns($product, $data, $preMessage);
		} catch (Exception $e) {
			$this->addWarningMessage($preMessage . $e->getMessage());
			$this->status = false;
		}

		$errors = $this->validator->validate($product);
		foreach ($errors as $error) {
			$message = $preMessage . ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage();
			$this->addWarningMessage($message);
			$this->status = false;
		}
	}

	private function setProductColumns(Product $product, array $data, string $preMessage): void
	{
		$product->setName($data['name']);

		$category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $data['category']]);

		if ($category) {
			$product->setCategory($category);
		} else {
			$this->addWarningMessage($preMessage . sprintf('Category with slug: %s not found', $data['category']));
			$this->status = false;
		}

		if (is_numeric($data['price'])) {
			$product->setPrice($data['price']);
		}

		if (is_numeric($data['amount'])) {
			$product->setAmount($data['amount']);
		}

		$product->setDescr($data['descr']);

		if (!empty($data['imagePath'])) {
			$imagePath = $this->fetchProductImage($data['imagePath']);

			if (!empty($imagePath)) {
				$product->setImagePath($imagePath);
				$this->images[] = $imagePath;
			}
		}

		$this->setProductAttributes($product, $data, $preMessage);
		$this->setProductColors($product, $data['colors']);
		$product->setDraft(true);
	}

	private function fetchProductImage(string $url): string
	{
		try {
			$response = $this->httpClient->request('GET', $url, [
				'headers' => [
					'Accept' => 'image/png, image/jpeg, image/webp, image/svg+xml',
				],
			]);

			$content = $response->getContent();
			return $this->fileUploader->uploadAndDumpFile($content, pathinfo($url, PATHINFO_EXTENSION), 'products');
		} catch (Exception|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
			throw new Exception($e->getMessage());
		}
	}

	private function setProductAttributes(Product $product, array $data, string $preMessage): void
	{
		$productAttr = new ProductAttr();

		$valid = $this->validateProductAttribute($data['length'], 'length', $preMessage);
		if ($valid) $productAttr->setLength($data['length']);

		$valid = $this->validateProductAttribute($data['width'], 'width', $preMessage);
		if ($valid) $productAttr->setWidth($data['width']);

		$valid = $this->validateProductAttribute($data['height'], 'height', $preMessage);
		if ($valid) $productAttr->setHeight($data['height']);

		$valid = $this->validateProductAttribute($data['weight'], 'weight', $preMessage);
		if ($valid) $productAttr->setWeight($data['weight']);

		$product->setProductAttr($productAttr);
	}

	private function validateProductAttribute(mixed $value, string $name, string $preMessage): bool
	{
		if (empty($value)) return false;

		if (!is_numeric($value)) {
			$this->addWarningMessage($preMessage . sprintf('Value of %s must be a integer.', $name));
			$this->status = false;
			return false;
		}

		return true;
	}

	private function setProductColors(Product $product, string $colors): void
	{
		$colors = preg_split("/[\s,]+/", $colors);

		foreach ($colors as $name) {
			if (!empty($name)) {
				$color = new Color();
				$color->setName($name);
				$product->addColor($color);
			}
		}
	}

	private function updateImport(string $importSlug, int $countImportedProducts = 0): void
	{
		$this->importModel->updateImportProduct($importSlug, $this->status, $this->messages, $countImportedProducts);

		$this->clearImages();
		$this->clearMessages();
		$this->updateStatus();
	}

	private function clearImages(): void
	{
		if (!empty($this->images) && !$this->status) {
			foreach ($this->images as $image) {
				$this->fileSystem->remove($this->uploadsDirectory . $image);
			}
		}

		$this->images = [];
	}

	private function clearMessages(): void
	{
		$this->messages = [];
	}

	private function updateStatus(): void
	{
		$this->status = true;
	}

	private function addWarningMessage(string $message): void
	{
		$this->messages[] = $message;
	}
}