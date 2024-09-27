<?php

namespace App\Services;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Product;
use App\Entity\ProductAttr;
use App\Model\ProductModel;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductImporter
{
	private int $BATCH_SIZE = 20;

	public function __construct(
		protected HttpClientInterface    $httpClient,
		protected EntityManagerInterface $entityManager,
		protected ValidatorInterface     $validator,
		protected Filesystem             $filesystem,
		protected RequestStack           $requestStack,
		protected ProductModel           $productModel,
		protected FileUploader           $fileUploader
	)
	{
	}

	public function import(UploadedFile $file): bool
	{
		if (($fp = fopen($file->getPathname(), "r")) === false) {
			$this->addFlashWarning('Cannot read the file, please upload in the format - csv');
			return false;
		}

		$flag = true;
		$rowNumber = 1;
		$i = 0;

		$this->entityManager->beginTransaction();

		try {
			while (($row = fgetcsv($fp, 10000, ",")) !== false) {
				$product = new Product();
				$this->processRow($product, $row, $flag, $rowNumber);

				$this->productModel->preSaveOrUpdateProduct($product);
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
				return false;
			}

			$this->entityManager->flush();
			$this->entityManager->commit();

			$this->addFlashSuccess($i);

			return true;
		} catch (\Exception $e) {
			$this->entityManager->rollback();
			$this->addFlashWarning('Error while saving products: ' . $e->getMessage());
			return false;
		}
	}

	private function processRow(Product $product, array $row, bool &$flag, int $rowNumber): void
	{
		$preMessage = "Error [Row №{$rowNumber}] | ";

		try {
			$this->setProductColumns($product, $row);
		} catch (Exception $exception) {
			$this->addFlashWarning($preMessage . $exception->getMessage());
			$flag = false;
		}

		$errors = $this->validator->validate($product);

		foreach ($errors as $error) {
			$message = $preMessage . ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage();
			$this->addFlashWarning($message);
			$flag = false;
		}
	}

	private function setProductColumns(Product $product, array $row): void
	{
		$product->setName($row[0]);

		$category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $row[1]]);
		$product->setCategory($category);

		$product->setPrice((int)$row[2]);
		$product->setAmount((int)$row[3]);

		if (!empty($row[4])) {
			$product->setDescr($row[4]);
		}

		if (!empty($row[5])) {
			$imagePath = $this->fetchProductImage($row[5]);

			if (!empty($imagePath)) {
				$product->setImagePath($imagePath);
			}
		}

		$this->setProductAttributes($product, $row);
		$this->setProductColors($product, $row);

		$product->setDraft(true);
	}

	private function setProductAttributes(Product $product, array $row): void
	{
		$productAttr = new ProductAttr();

		if (!empty($row[6])) $productAttr->setLength((int)$row[6]);
		if (!empty($row[7])) $productAttr->setWidth((int)$row[7]);
		if (!empty($row[8])) $productAttr->setHeight((int)$row[8]);
		if (!empty($row[9])) $productAttr->setWeight((int)$row[9]);

		$product->setProductAttr($productAttr);
	}

	private function setProductColors(Product $product, array $row): void
	{
		for ($i = 10; $i < count($row); $i++) {
			if (!empty($row[$i])) {
				$color = new Color();
				$color->setName($row[$i]);
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
				]
			]);

			$content = $response->getContent();

			return $this->fileUploader->uploadAndDumpFile($content, pathinfo($url, PATHINFO_EXTENSION), 'products');
		} catch (Exception|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
			throw new Exception($e->getMessage());
		}
	}

	private function addFlashSuccess(int $countImportedProducts): void
	{
		$this->requestStack->getSession()->getFlashBag()->add('success', "{$countImportedProducts} products imported successfully.");
	}

	private function addFlashWarning(string $message): void
	{
		$this->requestStack->getSession()->getFlashBag()->add('warning', $message);
	}
}