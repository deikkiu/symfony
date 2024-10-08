<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
	public function login(AuthenticationUtils $authenticationUtils): Response
	{
		if ($this->isGranted('IS_AUTHENTICATED')) {
			return $this->redirectToRoute('home');
		}

		$error = $authenticationUtils->getLastAuthenticationError();

		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->render('login/login.html.twig', [
			'last_username' => $lastUsername,
			'error' => $error,
		]);
	}

	public function logout(): void
	{
		throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
	}
}
