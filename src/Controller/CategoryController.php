<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Model\CategoryModel;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractController
{
	public function store(Request $request, CategoryModel $categoryModel): Response
	{
		$slug = $request->get('slug');
		$category = $categoryModel->getOrCreateCategory($slug);

		if (!$category) {
			throw $this->createNotFoundException('No category found for slug = ' . $slug);
		}

		$form = $this->createForm(CategoryType::class, $category, [
			'category' => $category,
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$category = $form->getData();

			$categoryModel->saveOrUpdateCategory($category);

			return $this->redirectToRoute('category_list');
		}

		return $this->render('category/create.html.twig', [
			'form' => $form
		]);
	}

	public function delete(Request $request, EntityManagerInterface $entityManager, CategoryModel $categoryModel): Response
	{
		$id = $request->get('id');
		$category = $entityManager->getRepository(Category::class)->find($id);

		if (!$category) {
			throw $this->createNotFoundException('No category found for id = ' . $id);
		}

		$categoryModel->deleteCategory($category);

		return $this->redirectToRoute('category_list');
	}

	public function show(CategoryRepository $categoryRepository): Response
	{
		$categories = $categoryRepository->findAll();

		return $this->render('category/index.html.twig', [
			'categories' => $categories,
		]);
	}

	public function recentCategories(CategoryRepository $categoryRepository): Response
	{
		$categories = $categoryRepository->findAll();

		return $this->render('incs/sidebar.html.twig', [
			'categories' => $categories,
		]);
	}
}