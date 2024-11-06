<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Object\ProductImport;
use App\Form\Object\ProductSearch;
use App\Form\ProductImportType;
use App\Form\ProductSearchType;
use App\Form\ProductType;
use App\Messenger\Message\ImportProductsMessage;
use App\Model\ImportProductModel;
use App\Model\ProductModel;
use App\Repository\ProductRepository;
use App\Security\Voter\ProductVoter;
use App\Service\CartService;
use App\Service\FileUploader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProductController extends AbstractController
{
	public function store(Request $request, ProductModel $productModel): Response
	{
		$slug = $request->get('slug');
		$product = $productModel->getOrCreateProduct($slug);

		$uploadsFolder = 'products';

		if (!$product) {
			throw $this->createNotFoundException('Product not found for this slug: ' . $slug);
		}

		if ($product->getId()) {
			$this->denyAccessUnlessGranted(ProductVoter::EDIT, $product, 'You have not access to edit this product.');
		} else {
			$this->denyAccessUnlessGranted(ProductVoter::CREATE, $product, 'You have not access to create a new product.');
		}

		$colors = new ArrayCollection();

		foreach ($product->getColors() as $color) {
			$colors->add($color);
		}

		$form = $this->createForm(ProductType::class, $product);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$product = $form->getData();

			$product->setDraft($form->get('draft')->isClicked());

			$image = $form->get('imagePath')->getData();

			if ($image) {
				$productModel->setOrUpdateImage($product, $image, $uploadsFolder);
			}

			$productModel->saveOrUpdateProduct($product, $colors);

			return $this->redirectToRoute('product_list');
		}

		return $this->render('product/store.html.twig', [
			'form' => $form
		]);
	}

	#[IsGranted('ROLE_ADMIN')]
	public function delete(Request $request, EntityManagerInterface $entityManager, ProductModel $productModel): Response
	{
		$id = $request->get('id');
		$product = $entityManager->getRepository(Product::class)->find($id);

		if (!$product) {
			throw $this->createNotFoundException('Product not found for id = ' . $id);
		}

		$productModel->deleteProduct($product);

		return $this->redirectToRoute('product_list');
	}

	public function showAll(Request $request, EntityManagerInterface $entityManager): Response
	{
		$productSearch = new ProductSearch();
		$slug = $request->get('category');

		$form = $this->createForm(ProductSearchType::class, $productSearch, [
			'action' => $this->generateUrl('product_list'),
		]);
		$form->handleRequest($request);

		if ($request->isMethod('GET') && $slug) {
			$category = $entityManager->getRepository(Category::class)->findOneBy(['slug' => $slug]);

			if (!$category) {
				throw $this->createNotFoundException('Products not found for this category = ' . $slug);
			}

			$form->get('category')->setData($category);
			$productSearch->setCategory($category);
		}

		if ($form->isSubmitted() && $form->isValid()) {
			$productSearch = $form->getData();
		}

		// @TODO: checking role user
		$isUser = in_array('ROLE_USER', $this->getUser()->getRoles());

		$products = $entityManager->getRepository(Product::class)->findAllOrderedByAttr($productSearch, $isUser);

		return $this->render('product/index.html.twig', [
			'form' => $form,
			'products' => $products
		]);
	}

	public function show(Request $request, ProductRepository $productRepository, CartService $cartService): Response
	{
		$slug = $request->get('slug');
		$product = $productRepository->findOneBy(['slug' => $slug]);

		if (!$product) {
			throw $this->createNotFoundException('Product not found for slug = ' . $slug);
		}

		if ($product->isDraft()) {
			$this->denyAccessUnlessGranted(ProductVoter::SHOW, $product, 'You have not access to open this product.');
		}

		// @TODO: checking role user
		$isUser = in_array('ROLE_USER', $this->getUser()->getRoles());

		$categoryProducts = $productRepository->findProductsInCategory($product, $isUser, 3);
		$productQuantityInCart = $cartService->getProductQuantityInCart($product->getId());

		return $this->render('product/product.html.twig', [
			'product' => $product,
			'categoryProducts' => $categoryProducts,
			'productQuantityInCart' => $productQuantityInCart
		]);
	}

	/**
	 * @throws ExceptionInterface
	 */
	#[IsGranted('ROLE_ADMIN')]
	public function import(Request $request, ImportProductModel $importProductModel, MessageBusInterface $bus, FileUploader $fileUploader, Security $security): Response
	{
		$folder = 'import';

		$userId = $security->getUser()->getId();
		$imports = $importProductModel->getAllImportProducts();

		$form = $this->createForm(ProductImportType::class, new ProductImport());
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form->get('file')->getData();
			$filePath = $fileUploader->upload($file, $folder);

			$importProduct = $importProductModel->createImportProduct($filePath);

			$bus->dispatch(new ImportProductsMessage($this->getParameter('uploads_directory') . $filePath, $userId, $importProduct->getSlug()));

			$this->addFlash('notice', 'The products are being loaded. After the full download, you will receive a notification.');

			return $this->redirectToRoute('product_import');
		}

		return $this->render('product/import.html.twig', [
			'form' => $form,
			'imports' => $imports,
		]);
	}

	/**
	 * @throws ExceptionInterface
	 */
	#[IsGranted('ROLE_ADMIN')]
	public function reimport(Request $request, ImportProductModel $importProductModel, MessageBusInterface $bus, Security $security): Response
	{
		$slug = $request->get('slug');
		$userId = $security->getUser()->getId();

		$importProduct = $importProductModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException('No import product found for slug = ' . $slug);
		}

		if (strtolower($importProduct->getImportStatus()[$importProduct->getStatus()]) !== 'error') {
			return $this->redirectToRoute('product_import');
		}

		$path = $this->getParameter('uploads_directory') . $importProduct->getPath();
		$bus->dispatch(new ImportProductsMessage($path, $userId, $importProduct->getSlug()));

		$this->addFlash('notice', 'The products are being loaded. After the full download, you will receive a notification.');

		return $this->redirectToRoute('product_import');
	}

	public function importEdit(Request $request, ImportProductModel $importProductModel, MessageBusInterface $bus, Security $security): Response
	{
		$slug = $request->get('slug');
		$importProduct = $importProductModel->getImportProduct($slug);

		if (!$importProduct) {
			throw $this->createNotFoundException('No import product found for slug = ' . $slug);
		}

		$filePath = $this->getParameter('uploads_directory') . $importProduct->getPath();

		$rows = [];

		if (($handle = fopen($filePath, "r")) !== false) {
			while (($data = fgetcsv($handle, 1000, ",")) !== false) {
				$rows[] = $data;
			}

			fclose($handle);
		}

		if ($request->isMethod('POST')) {
			$newData = $request->request->get('csv');

			$handle = fopen($filePath, 'w');

			foreach ($newData as $row) {
				fputcsv($handle, $row);
			}

			fclose($handle);

			$this->addFlash('success', 'CSV updated successfully!');

			return $this->redirectToRoute('csv_edit');
		}

		return $this->render('csv/edit.html.twig', [
			'rows' => $rows,
		]);
	}
}