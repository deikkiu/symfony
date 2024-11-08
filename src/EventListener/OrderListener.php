<?php

namespace App\EventListener;

use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;


#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Order::class)]
final readonly class OrderListener
{
	public function __construct(
		private EntityManagerInterface $entityManager,
		private CartService            $cartService,
		private ProductRepository      $productRepository,
	)
	{
	}

	public function postPersist(): void
	{
		$cart = $this->cartService->getCart();

		foreach ($cart->getList() as $cartItem) {
			if ($cartItem->isInStock()) {
				$product = $this->productRepository->find($cartItem->getId());
				$newAmount = $product->getAmount() - $cartItem->getQuantity();
				$product->setAmount($newAmount);

				$this->entityManager->persist($product);
			}
		}

		$this->entityManager->flush();
	}
}
