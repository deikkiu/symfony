<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;


#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Order::class)]
final class OrderListener
{
	public function __construct(
		protected EntityManagerInterface $entityManager,
		protected CartService            $cartService,
		protected ProductRepository      $productRepository,
	)
	{
	}

	public function postPersist(): void
	{
		[$cart] = $this->cartService->getCart();

		foreach ($cart->getProducts() as $cartProduct) {
			if ($cartProduct->isInStock()) {
				$product = $this->productRepository->find($cartProduct->getId());
				$newAmount = $product->getAmount() - $cartProduct->getQuantity();
				$product->setAmount($newAmount);

				$this->entityManager->persist($product);
			}
		}

		$this->entityManager->flush();
	}
}
