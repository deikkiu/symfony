<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
	public function show(Security $security): Response
	{
		$user = $security->getUser()->getUserIdentifier();
		$roles = $security->getUser()->getRoles();

		return $this->render('home.html.twig', [
			'user' => $user,
			'roles' => $roles,
		]);
	}
}