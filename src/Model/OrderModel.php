<?php

namespace App\Model;

use App\Dto\CartDto;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderModel
{
	private int $STATUS_CREATED = Order::STATUS_CREATED;

	public function __construct(
		private readonly ProductRepository      $productRepository,
		private readonly EntityManagerInterface $entityManager,
		private readonly Security               $security
	)
	{
	}

	public function createOrder(CartDto $cart): int
	{
		$order = new Order();

		$order->setOwner($this->security->getUser());
		$order->setStatus($this->STATUS_CREATED);
		$order->setTotalPrice($this->calculateTotalPrice($cart));

		$this->setOrderProducts($order, $cart);

		$this->entityManager->persist($order);
		$this->entityManager->flush();

		return $order->getId();
	}

	private function calculateTotalPrice(CartDto $cart): int
	{
		return array_reduce($cart->getList(), function ($total, $cartItem) {
			if ($cartItem->isInStock()) {
				$productPrice = $this->productRepository->find($cartItem->getId())->getPrice();
				return $total + ($productPrice * $cartItem->getQuantity());
			}

			return $total;
		}, 0);
	}

	private function setOrderProducts(Order $order, CartDto $cart): void
	{
		foreach ($cart->getList() as $cartItem) {
			$product = $this->productRepository->find($cartItem->getId());

			if ($cartItem->isInStock()) {
				$orderProduct = new OrderItem();
				$orderProduct->setAppOrder($order)
					->setPriceForOne($product->getPrice())
					->setQuantity($cartItem->getQuantity())
					->setProduct($product);

				$this->entityManager->persist($orderProduct);
			}
		}
	}

	public function deleteOrder(Order $order): void
	{
		$this->entityManager->remove($order);
		$this->entityManager->flush();
	}
}

