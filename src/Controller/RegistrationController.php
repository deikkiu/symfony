<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
	public function __construct(private readonly EmailVerifier $emailVerifier)
	{
	}

	public function register(Request $request, Security $security, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
	{
		if ($this->isGranted('IS_AUTHENTICATED')) {
			return $this->redirectToRoute('home');
		}

		$user = new User();
		$form = $this->createForm(RegistrationFormType::class, $user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			/** @var string $plainPassword */
			$plainPassword = $form->get('plainPassword')->getData();

			$user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

			$entityManager->persist($user);
			$entityManager->flush();

			// login
			return $security->login($user, 'form_login');
		}

		return $this->render('registration/register.html.twig', [
			'registrationForm' => $form,
		]);
	}

	public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
	{
		$this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

		// validate email confirmation link, sets User::isVerified=true and persists
		try {
			/** @var User $user */
			$user = $this->getUser();
			$this->emailVerifier->handleEmailConfirmation($request, $user);
		} catch (VerifyEmailExceptionInterface $exception) {
			$this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

			return $this->redirectToRoute('user_register');
		}

		// @TODO Change the redirect on success and handle or remove the flash message in your templates
		$this->addFlash('success', 'Your email address has been verified.');

		return $this->redirectToRoute('user_register');
	}
}
