<?php

namespace App\Model;

use App\Dto\Cart;
use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\User;
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
		$validationResult = $this->cartService->validateCart($cart);

		if (!$validationResult['isValid']) {
			$message = $this->cartService->generateCartUpdateMessage($validationResult['updatedProducts']);
			throw new \Exception($message);
		}

		$order = new Order();

		$order->setOwner($this->entityManager->getRepository(User::class)->find($this->security->getUser()->getId()));
		$order->setStatus(1);
		$order->setDeleted(false);

		$this->setOrderDate($order);
		$this->setOrderProducts($order, $cart);

		$this->entityManager->persist($order);
		$this->entityManager->flush();

		$this->updateProductStock($cart);

		$this->cartService->clearCart();
	}

	public function deleteOrder(Order $order): void
	{
		$order->setDeleted(true);

		$this->entityManager->remove($order);
		$this->entityManager->flush();

		$this->addFlash('success', 'Order has been deleted');
	}

	private function setOrderProducts(Order $order, Cart $cart): void
	{
		$totalPrice = 0;

		foreach ($cart->getProducts() as $cartProduct) {
			$product = $this->productRepository->find($cartProduct['id']);

			$orderProduct = new OrderProduct();
			$orderProduct->setAppOrder($order);
			$orderProduct->setPriceForOne($product->getPrice());
			$orderProduct->setQuantity($cartProduct['quantity']);
			$orderProduct->setProduct($product);

			$totalPrice += $cartProduct['quantity'] * $product->getPrice();
			$order->setTotalPrice($totalPrice);

			$this->entityManager->persist($orderProduct);
		}
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

	private function addFlash(string $type, string $message): void
	{
		$this->requestStack->getSession()->getFlashBag()->add($type, $message);
	}
}
