<?php

namespace App\Model;

use App\Dto\Cart;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\User;
use App\Repository\ProductRepository;

class OrderModel
{

	public function __construct(
		protected ProductRepository $productRepository,
	)
	{
	}

	public function createOrder(Cart $cart, User $user): void
	{
		$order = new Order();

		$order->setOwner($user);
		$order->setStatus(1);
		$order->setDeleted(false);

		$this->setOrderDate($order);

		$this->setOrderProducts($order, $cart);


	}

	private function setOrderProducts(Order $order, Cart $cart): void
	{

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct['id']);

			$orderProduct = new OrderProduct();
			$orderProduct->setAppOrder($order);
			$orderProduct->setPriceForOne()
			$orderProduct->setProduct($cartProduct);
		}

	}

	private function setOrderDate(Order $order): void
	{
		$this->onCreateAt($order);
		$this->onUpdateAt($order);
	}

	private function onCreateAt(Order $order): void
	{
		$order->setCreatedAt(new \DateTime('now', new \DateTimeZone('Asia/Almaty')));
	}

	private function onUpdateAt(Order $order): void
	{
		$order->setUpdatedAt(new \DateTime('now', new \DateTimeZone('Asia/Almaty')));
	}
}