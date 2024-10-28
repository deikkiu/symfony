<?php

namespace App\Model;

use App\Dto\CartDto;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderModel
{
	private int $STATUS_CREATED = Order::STATUS_CREATED;

	public function __construct(
		protected ProductRepository      $productRepository,
		protected EntityManagerInterface $entityManager,
		protected Security               $security
	)
	{
	}

	public function createOrder(CartDto $cart): void
	{
		$order = new Order();

		$order->setOwner($this->security->getUser());
		$order->setStatus($this->STATUS_CREATED);
		$order->setTotalPrice($this->calculateTotalPrice($cart));

		$this->setOrderProducts($order, $cart);

		$this->entityManager->persist($order);
		$this->entityManager->flush();
	}

	public function deleteOrder(Order $order): void
	{
		$this->entityManager->remove($order);
		$this->entityManager->flush();
	}

	private function calculateTotalPrice(CartDto $cart): int
	{
		return array_reduce($cart->getProducts(), function ($total, $cartProduct) {
			if ($cartProduct->isInStock()) {
				$productPrice = $this->productRepository->find($cartProduct->getId())->getPrice();
				return $total + ($productPrice * $cartProduct->getQuantity());
			}

			return $total;
		}, 0);
	}

	private function setOrderProducts(Order $order, CartDto $cart): void
	{
		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct->getId());

			if ($cartProduct->isInStock()) {
				$orderProduct = new OrderProduct();
				$orderProduct->setAppOrder($order)
					->setPriceForOne($product->getPrice())
					->setQuantity($cartProduct->getQuantity())
					->setProduct($product);

				$this->entityManager->persist($orderProduct);
			}
		}
	}
}

