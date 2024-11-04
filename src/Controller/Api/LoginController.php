<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginController extends AbstractController
{
	public function index(): JsonResponse
	{
		return $this->json([
			'status' => 200,
			'message' => 'Authentication successful',
		]);
	}
}
