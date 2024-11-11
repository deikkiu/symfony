<?php

namespace App\Controller;

use App\Entity\Import;
use App\Form\Object\ProductImport;
use App\Form\ProductImportType;
use App\Messenger\Message\ImportProductsMessage;
use App\Model\ImportModel;
use App\Service\FileUploader;
use App\Utils\CSVUtil;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_ADMIN")]
class ImportController extends AbstractController
{
	private const IMPORT_FOLDER = 'import';

	public function __construct(
		private readonly Security            $security,
		private readonly MessageBusInterface $bus,
		private readonly FileUploader        $fileUploader,
		private readonly ImportModel         $importModel,
	)
	{
	}

	public function import(Request $request): Response
	{
		$userId = $this->security->getUser()->getId();
		$imports = $this->importModel->getAllImportProducts();

		$form = $this->createForm(ProductImportType::class, new ProductImport());
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form->get('file')->getData();
			$filePath = $this->fileUploader->upload($file, self::IMPORT_FOLDER);

			$importProduct = $this->importModel->createImportProduct($filePath);

			try {
				$this->bus->dispatch(new ImportProductsMessage($importProduct->getSlug(), $userId));
			} catch (ExceptionInterface $e) {
				$this->addFlash('warning', 'Import product failed: ' . $e->getMessage());
				return $this->redirectToRoute('import');
			}

			$this->addFlash('notice', 'The products are being loaded. After the full download, you will receive a notification.');
			return $this->redirectToRoute('import');
		}

		return $this->render('import/index.html.twig', [
			'form' => $form,
			'imports' => $imports,
		]);
	}

	public function reimport(Request $request): Response
	{
		$slug = $request->get('slug');
		$userId = $this->security->getUser()->getId();

		$importProduct = $this->importModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException('No import product found for slug = ' . $slug);
		}

		if ($importProduct->getStatus() !== Import::STATUS_ERROR) {
			return $this->redirectToRoute('import');
		}

		$this->importModel->clearAllMessages($importProduct);
		$this->importModel->updateStatus($importProduct, Import::STATUS_PENDING);

		try {
			$this->bus->dispatch(new ImportProductsMessage($importProduct->getSlug(), $userId));
		} catch (ExceptionInterface $e) {
			$this->addFlash('warning', 'Import product failed: ' . $e->getMessage());
			return $this->redirectToRoute('import');
		}

		$this->addFlash('notice', 'The products are being loaded. After the full download, you will receive a notification.');
		return $this->redirectToRoute('import');
	}

	public function delete(Request $request): Response
	{
		$slug = $request->get('slug');
		$importProduct = $this->importModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException('No import product found for slug = ' . $slug);
		}

		$this->importModel->deleteImportProduct($importProduct);

		return $this->redirectToRoute('import');
	}

	// TODO:refactor method
	public function edit(Request $request): Response
	{
		$slug = $request->get('slug');
		$importProduct = $this->importModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException(sprintf('No import product found for slug = %s', $slug));
		}

		$columns = [
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
		$filePath = $this->getParameter('app.uploads_directory') . $importProduct->getPath();
		$rows = CSVUtil::readCSV($filePath, $columns);

		if ($request->isMethod('POST')) {
			$file = $request->get('csv');

			if (!$file) {
				return $this->redirectToRoute('import');
			}

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
}