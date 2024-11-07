<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Product;
use App\Entity\ProductAttr;
use App\Model\ImportProductModel;
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
	private int $BATCH_SIZE = 20;
	private bool $status = true;
	private array $messages = [];
	private array $images = [];

	public function __construct(
		private readonly HttpClientInterface    $httpClient,
		private readonly EntityManagerInterface $entityManager,
		private readonly ValidatorInterface     $validator,
		private readonly ProductModel           $productModel,
		private readonly FileUploader           $fileUploader,
		private readonly ImportProductModel     $importProductModel,
		private readonly Filesystem             $fileSystem,
		private readonly string                 $uploadsDirectory,
	)
	{
	}

	public function import(string $filePath, int $userId, string $importSlug): void
	{
		if (($fp = fopen($filePath, "r")) === false) {
			$this->status = false;
			$this->addWarningMessage('Cannot read the file, please upload in the format - csv');
			$this->updateImport($importSlug);
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

				if (($i % $this->BATCH_SIZE) === 0) {
					$this->entityManager->flush();
					$this->entityManager->clear();
				}
			}

			fclose($fp);

			if (!$this->status) {
				$this->entityManager->rollback();
				$this->entityManager->clear();
				$this->updateImport($importSlug);
				return;
			}

			$this->entityManager->flush();
			$this->entityManager->commit();
			$this->entityManager->clear();

			$this->updateImport($importSlug, $i);
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			$this->entityManager->clear();

			$this->addWarningMessage('Error while saving products: ' . $e->getMessage());

			$this->updateImport($importSlug);
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
		$errors = [];

		$product->setName($data['name']);
		$category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $data['category']]);

		if (!$category) {
			$errors[] = "Category '{$data['category']}' not found";
		}

		$product->setCategory($category);

		$price = $this->checkIsInteger('Price', $data['price'], $errors);
		$product->setPrice($price);

		$amount = $this->checkIsInteger('Amount', $data['amount'], $errors);
		$product->setAmount($amount);

		$product->setDescr($data['descr']);

		if (!empty($data['imagePath'])) {
			$imagePath = $this->fetchProductImage($data['imagePath']);

			if (!empty($imagePath)) {
				$product->setImagePath($imagePath);
				$this->images[] = $imagePath;
			}
		}

		$this->setProductAttributes($product, $data, $errors);
		$this->setProductColors($product, $data['colors']);
		$product->setDraft(true);

		foreach ($errors as $error) {
			$this->addWarningMessage($preMessage . $error);
			$this->status = false;
		}
	}

	private function setProductAttributes(Product $product, array $data, array $errors): void
	{
		$productAttr = new ProductAttr();

		if (!empty($data['length'])) {
			$length = $this->checkIsInteger('Length', $data['length'], $errors);
			$productAttr->setLength($length);
		}

		if (!empty($data['width'])) {
			$width = $this->checkIsInteger('Width', $data['width'], $errors);
			$productAttr->setWidth($width);
		}

		if (!empty($data['height'])) {
			$height = $this->checkIsInteger('Height', $data['height'], $errors);
			$productAttr->setHeight($height);
		}

		if (!empty($data['weight'])) {
			$weight = $this->checkIsInteger('Weight', $data['weight'], $errors);
			$productAttr->setWeight($weight);
		}

		$product->setProductAttr($productAttr);
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

	private function updateImport(string $importSlug, int $countImportedProducts = 0): void
	{
		$this->importProductModel->updateImportProduct($importSlug, $this->status, $this->messages, $countImportedProducts);

		$this->clearImages();
		$this->clearMessages();
		$this->updateStatus();
	}

	private function addWarningMessage(string $message): void
	{
		$this->messages[] = $message;
	}

	private function clearMessages(): void
	{
		$this->messages = [];
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

	private function updateStatus(): void
	{
		$this->status = true;
	}

	private function checkIsInteger(string $name, mixed $value, array &$errors): int
	{
		if (!is_numeric($value)) {
			$errors[] = "$name is not a integer";
			return 0;
		}

		return (int)$value;
	}
}