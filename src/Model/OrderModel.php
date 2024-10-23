<?php

namespace App\Model;

use App\Dto\Cart;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Repository\ProductRepository;
use App\Services\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderModel
{
	public function __construct(
		protected ProductRepository      $productRepository,
		protected EntityManagerInterface $entityManager,
		protected Security               $security,
		protected CartService            $cartService,
		protected RequestStack           $requestStack
	)
	{
	}

	public function createOrder(Cart $cart): void
	{
		$this->cartService->validateCartOrFail($cart);

		$order = new Order();
		$order->setOwner($this->security->getUser());
		$order->setStatus(1);
		$order->setDeleted(false);
		$this->setOrderDates($order);
		$this->setOrderProducts($order, $cart);

		$this->entityManager->persist($order);
		$this->entityManager->flush();

		$this->updateProductStock($cart);

		$this->cartService->clearCart();
	}

	public function deleteOrder(Order $order): void
	{
		$order->setDeleted(true);
		$this->entityManager->flush();
	}

	private function setOrderProducts(Order $order, Cart $cart): void
	{
		$totalPrice = 0;

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct['id']);
			$orderProduct = new OrderProduct();
			$orderProduct->setAppOrder($order)
				->setPriceForOne($product->getPrice())
				->setQuantity($cartProduct['quantity'])
				->setProduct($product);

			$totalPrice += $cartProduct['quantity'] * $product->getPrice();
			$this->entityManager->persist($orderProduct);
		}

		$order->setTotalPrice($totalPrice);
	}

	private function updateProductStock(Cart $cart): void
	{
		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct['id']);
			$newAmount = $product->getAmount() - $cartProduct['quantity'];
			$product->setAmount($newAmount);

			$this->entityManager->persist($product);
		}

		$this->entityManager->flush();
	}

	private function setOrderDates(Order $order): void
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

