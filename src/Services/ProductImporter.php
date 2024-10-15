<?php

namespace App\Services;

use App\Entity\Category;
use App\Entity\Color;
use App\Entity\Product;
use App\Entity\ProductAttr;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProductImporter
{
    private int $BATCH_SIZE = 20;

    public function __construct(
        protected HttpClientInterface $httpClient,
        protected EntityManagerInterface $entityManager,
        protected ValidatorInterface $validator,
        protected Filesystem $filesystem,
        protected RequestStack $requestStack,
        protected SerializerInterface $serializer
    ) {
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
                    'colors' => array_slice($row, 10)
                ];

		try {
		    $product = $this->serializer->deserialize(json_encode($data), Product::class, 'json');
		} catch (\Exception $e) {
		    $this->addFlashWarning("Error deserializing row {$rowNumber}: " . $e->getMessage());
		    $flag = false;
		    continue;
		}
		    
                $this->processRow($product, $data, $flag, $rowNumber);
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

    private function processRow(Product $product, array $data, bool &$flag, int $rowNumber): void
    {
        $preMessage = "Error [Row №{$rowNumber}] | ";

        try {
            $this->setProductColumns($product, $data);
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

    private function setProductColumns(Product $product, array $data): void
    {
        $product->setName($data['name']);
	    
        $category = $this->entityManager->getRepository(Category::class)->findOneBy(['slug' => $data['category']]);
	    
	if (!$category) {
	    throw new \Exception("Category '{$data['category']}' not found");
	}

	$product->setCategory($category);
	    
        $product->setPrice($data['price']);
        $product->setAmount($data['amount']);
	    
        if (!empty($data['descr'])) {
            $product->setDescr($data['descr']);
        }
	    
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

    private function addFlashSuccess(int $countImportedProducts): void
    {
        $this->requestStack->getSession()->getFlashBag()->add('success', "{$countImportedProducts} products imported successfully.");
    }

    private function addFlashWarning(string $message): void
    {
        $this->requestStack->getSession()->getFlashBag()->add('warning', $message);
    }
}
