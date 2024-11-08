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
use App\Service\CartService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProductController extends AbstractController
{
	private const UPLOAD_FOLDER = 'products';

	public function store(Request $request, ProductModel $productModel): Response
	{
		$slug = $request->get('slug');
		$product = $productModel->getOrCreateProduct($slug);

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
				$productModel->setOrUpdateImage($product, $image, self::UPLOAD_FOLDER);
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
		$productQuantityInCart = $cartService->getItemQuantityInCart($product->getId());

		return $this->render('product/product.html.twig', [
			'product' => $product,
			'categoryProducts' => $categoryProducts,
			'productQuantityInCart' => $productQuantityInCart
		]);
	}
}