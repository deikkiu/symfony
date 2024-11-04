<?php

namespace App\Service;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Product;
use App\Entity\ProductAttr;
use App\Model\ProductModel;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductImporter
{
	private int $BATCH_SIZE = 20;
	private array $messages = [];

	public function __construct(
		private readonly HttpClientInterface    $httpClient,
		private readonly EntityManagerInterface $entityManager,
		private readonly ValidatorInterface     $validator,
		private readonly ProductModel           $productModel,
		private readonly FileUploader           $fileUploader,
		private readonly SerializerInterface    $serializer
	)
	{
	}

	public function import(string $filePath, int $userId): void
	{
		if (($fp = fopen($filePath, "r")) === false) {
			$this->addWarningMessage('Cannot read the file, please upload in the format - csv');
			return;
		}

		$flag = true;
		$rowNumber = 1;
		$i = 0;

		$this->entityManager->beginTransaction();

		try {
			while (($row = fgetcsv($fp, 10000, ",")) !== false) {
				$data = [
					'name' => $row[0],
					'category' => $row[1],
					'price' => (int)$row[2],
					'amount' => (int)$row[3],
					'descr' => $row[4] ?? null,
					'imagePath' => $row[5] ?? null,
					'length' => $row[6] ?? null,
					'width' => $row[7] ?? null,
					'height' => $row[8] ?? null,
					'weight' => $row[9] ?? null,
					'colors' => array_slice($row, 10) ?? []
				];

				try {
					$product = $this->serializer->deserialize(json_encode($data), Product::class, 'json');
					$this->processRow($product, $data, $flag, $rowNumber);
				} catch (\Exception $e) {
					$this->addWarningMessage("Error deserializing row {$rowNumber}: " . $e->getMessage());
					$flag = false;
					continue;
				}

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

			if (!$flag) {
				$this->entityManager->rollback();
				return;
			}

			$this->entityManager->flush();
			$this->entityManager->commit();

			$this->entityManager->clear();

			$this->addSuccessMessage($i);
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			$this->addWarningMessage('Error while saving products: ' . $e->getMessage());;
		}
	}

	private function processRow(Product $product, array $data, bool &$flag, int $rowNumber): void
	{
		$preMessage = "Error [Row №{$rowNumber}] | ";

		try {
			$this->setProductColumns($product, $data);
		} catch (Exception $exception) {
			$this->addWarningMessage($preMessage . $exception->getMessage());
			$flag = false;
		}

		$errors = $this->validator->validate($product);
		foreach ($errors as $error) {
			$message = $preMessage . ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage();
			$this->addWarningMessage($message);
			$flag = false;
		}
	}

	private function setProductColumns(Product $product, array $data): void
	{
		$product->setName($data['name']);

		$category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $data['category']]);

		if (!$category) {
			throw new \Exception("Category '{$data['category']}' not found");
		}

		$product->setCategory($category);

		$product->setPrice((int)$data['price']);
		$product->setAmount((int)$data['amount']);
		$product->setDescr($data['descr'] ?? '');

		if (!empty($data['imagePath'])) {
			$imagePath = $this->fetchProductImage($data['imagePath']);

			if (!empty($imagePath)) {
				$product->setImagePath($imagePath);
			}
		}

		$this->setProductAttributes($product, $data);
		$this->setProductColors($product, $data['colors']);

		$product->setDraft(true);
	}

	private function setProductAttributes(Product $product, array $data): void
	{
		$productAttr = new ProductAttr();

		if (!empty($data['length'])) $productAttr->setLength((int)$data['length']);
		if (!empty($data['width'])) $productAttr->setWidth((int)$data['width']);
		if (!empty($data['height'])) $productAttr->setHeight((int)$data['height']);
		if (!empty($data['weight'])) $productAttr->setWeight((int)$data['weight']);

		$product->setProductAttr($productAttr);
	}

	private function setProductColors(Product $product, array $colors): void
	{
		foreach ($colors as $colorName) {
			if (!empty($colorName)) {
				$color = new Color();
				$color->setName($colorName);
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

	public function getImportMessages(): array
	{
		return $this->messages;
	}

	private function addSuccessMessage(int $countImportedProducts): void
	{
		$this->messages[] = ['type' => 'success', 'message' => "{$countImportedProducts} products imported successfully."];
	}

	private function addWarningMessage(string $message): void
	{
		$this->messages[] = ['type' => 'warning', 'message' => $message];
	}
}