<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Object\ProductSearch;
use App\Form\ProductSearchType;
use App\Form\ProductType;
use App\Model\ProductModel;
use App\Repository\ProductRepository;
use App\Security\Voter\ProductVoter;
use App\Services\CartService;
use App\Services\ProductImporter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Positive;

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

		return $this->render('product/create.html.twig', [
			'form' => $form
		]);
	}

	public function delete(Request $request, EntityManagerInterface $entityManager, ProductModel $productModel): Response
	{
		$id = $request->get('id');
		$product = $entityManager->getRepository(Product::class)->find($id);

		if (!$product) {
			throw $this->createNotFoundException('Product not found for id = ' . $id);
		}

		$this->denyAccessUnlessGranted(ProductVoter::DELETE, $product, 'You have not access to delete this product.');

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

		// @TODO
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

		// @TODO
		$isUser = in_array('ROLE_USER', $this->getUser()->getRoles());

		$categoryProducts = $productRepository->findProductsInCategory($product, $isUser, 3);

		// $productQuantityInCart = $cartService->getProductQuantityInCart($product->getId());

		$form = $this->createFormBuilder()
			->add('quantity', NumberType::class, [
				'required' => true,
				'empty_data' => 1,
				'data' => 1,
				'attr' => ['readonly' => 'readonly', 'class' => 'quantity_count'],
				'scale' => 0,
				'constraints' => [
					new Positive([
						'message' => 'Quantity must be a positive number'
					])
				],
			])
			->add('save', SubmitType::class, [
				'label' => 'Add in cart',
			])
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$quantity = $form->get('quantity')->getData();;

			return $this->redirectToRoute('cart_add', [
				'id' => $product->getId(),
				'quantity' => $quantity
			]);
		}

		return $this->render('product/product.html.twig', [
			'product' => $product,
			'categoryProducts' => $categoryProducts,
			'form' => $form
		]);
	}

	#[IsGranted('ROLE_ADMIN')]
	public function import(Request $request, ProductImporter $importer): Response
	{
		$form = $this->createFormBuilder()
			->add('file', FileType::class, [
				'required' => true,
				'label' => 'Choose a file',
				'help' => 'Format csv',
				'constraints' => [
					new File([
						'maxSize' => '1M',
						'mimeTypes' => [
							'text/csv'
						],
						'mimeTypesMessage' => 'Please upload a valid CSV file.'
					]),
					new NotNull([
						'message' => 'Please upload a file'
					])
				]
			])
			->add('import', SubmitType::class)
			->getForm();

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$file = $form->get('file')->getData();

			$isImported = $importer->import($file);

			if (!$isImported) {
				return $this->redirectToRoute('product_import');
			}

			return $this->redirectToRoute('product_list');
		}

		return $this->render('product/import.html.twig', [
			'form' => $form
		]);
	}
}