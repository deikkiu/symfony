<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HelloController extends AbstractController
{
	public function show(Request $request): Response
	{
		$name = $request->get('name');

		if (preg_match('/\d/', $name)) {
			$name = preg_replace('/\d/', '', $name);

			return $this->redirectToRoute('hello', ['name' => $name]);
		}

		return $this->render('hello.html.twig', [
			'name' => $name
		]);
	}
}