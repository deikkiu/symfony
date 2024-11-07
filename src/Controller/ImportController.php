<?php

namespace App\Controller;

use App\Entity\ImportProduct;
use App\Form\Object\ProductImport;
use App\Form\ProductImportType;
use App\Messenger\Message\ImportProductsMessage;
use App\Model\ImportProductModel;
use App\Service\FileUploader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ImportController extends AbstractController
{
	public function __construct(
		private readonly Security            $security,
		private readonly MessageBusInterface $bus,
		private readonly FileUploader        $fileUploader,
		private readonly ImportProductModel  $importProductModel,
	)
	{
	}

	#[IsGranted('ROLE_ADMIN')]
	public function import(Request $request): Response
	{
		$folder = 'import';

		$userId = $this->security->getUser()->getId();
		$imports = $this->importProductModel->getAllImportProducts();

		$form = $this->createForm(ProductImportType::class, new ProductImport());
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form->get('file')->getData();
			$filePath = $this->fileUploader->upload($file, $folder);

			$importProduct = $this->importProductModel->createImportProduct($filePath);

			$this->bus->dispatch(new ImportProductsMessage($this->getParameter('uploads_directory') . $filePath, $userId, $importProduct->getSlug()));

			$this->addFlash('notice', 'The products are being loaded. After the full download, you will receive a notification.');

			return $this->redirectToRoute('import');
		}

		return $this->render('import/index.html.twig', [
			'form' => $form,
			'imports' => $imports,
		]);
	}

	#[IsGranted('ROLE_ADMIN')]
	public function reimport(Request $request): Response
	{
		$slug = $request->get('slug');
		$userId = $this->security->getUser()->getId();

		$importProduct = $this->importProductModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException('No import product found for slug = ' . $slug);
		}

		if ($importProduct->getStatus() !== ImportProduct::STATUS_ERROR) {
			return $this->redirectToRoute('import');
		}

		$this->importProductModel->clearAllMessages($importProduct);
		$this->importProductModel->updateStatus($importProduct, ImportProduct::STATUS_PENDING);

		$path = $this->getParameter('uploads_directory') . $importProduct->getPath();
		$this->bus->dispatch(new ImportProductsMessage($path, $userId, $importProduct->getSlug()));

		$this->addFlash('notice', 'The products are being loaded. After the full download, you will receive a notification.');

		return $this->redirectToRoute('import');
	}

	public function delete(Request $request): Response
	{
		$slug = $request->get('slug');
		$importProduct = $this->importProductModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException('No import product found for slug = ' . $slug);
		}

		$this->importProductModel->deleteImportProduct($importProduct);

		return $this->redirectToRoute('import');
	}

	public function edit(Request $request): Response
	{
		$slug = $request->get('slug');
		$importProduct = $this->importProductModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException(sprintf('No import product found for slug = %s', $slug));
		}

		$columns = $this->initializeColumns();
		$filePath = $this->getParameter('uploads_directory') . $importProduct->getPath();
		$rows = $this->readCsvFile($filePath, $columns);

		if ($request->isMethod('POST')) {
			$file = $request->get('csv');

			if (is_array($file)) {
				$handle = fopen($filePath, 'w');

				foreach ($file as $row) {
					fputcsv($handle, $row);
				}

				fclose($handle);

				$this->addFlash('success', 'Import file updated successfully!');
				return $this->redirectToRoute('import');
			}

			$this->addFlash('error', 'Invalid data format.');

			return $this->redirectToRoute('import');
		}

		return $this->render('import/edit.html.twig', [
			'rows' => $rows,
			'columns' => $columns,
		]);
	}

	private function initializeColumns(): array
	{
		return [
			['label' => 'Name', 'show' => false],
			['label' => 'Category', 'show' => false],
			['label' => 'Price', 'show' => false],
			['label' => 'Amount', 'show' => false],
			['label' => 'Description', 'show' => false],
			['label' => 'Image', 'show' => false],
			['label' => 'Length', 'show' => false],
			['label' => 'Width', 'show' => false],
			['label' => 'Height', 'show' => false],
			['label' => 'Weight', 'show' => false],
			['label' => 'Colors', 'show' => false],
		];
	}

	private function readCsvFile(string $filePath, array &$columns): array
	{
		$rows = [];

		if (($handle = fopen($filePath, "r")) !== false) {
			while (($data = fgetcsv($handle, 1000, ",")) !== false) {
				$row = array_slice($data, 0, 11);
				$rows[] = $row;

				foreach ($row as $index => $value) {
					if (!empty($value) && $index < count($columns)) {
						$columns[$index]['show'] = true;
					}
				}
			}

			fclose($handle);
		}

		return $rows;
	}
}
