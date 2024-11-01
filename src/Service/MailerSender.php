<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class MailerSender
{
	private const ADMIN_EMAIL = 'dnxpdrs@gmail.com';

	public function __construct(
		private readonly MailerInterface $mailer,
	)
	{
	}

	public function sendOrderEmail(Order $order): void
	{
		$email = (new TemplatedEmail())
			->from(self::ADMIN_EMAIL)
			->to(self::ADMIN_EMAIL)
			->subject('Market | Order id: ' . $order->getId())
			->htmlTemplate('order/email.html.twig')
			->context($this->orderContext($order));

		try {
			$this->mailer->send($email);
		} catch (TransportExceptionInterface $e) {
			throw new \Exception($e->getMessage());
		}
	}

	private function orderContext(Order $order): array
	{
		return [
			'id' => $order->getId(),
			'owner' => $order->getOwner()->getEmail(),
			'price' => $order->getTotalPrice(),
			'status' => $order::getOrderStatus()[$order->getStatus()],
			'date' => $order->getCreatedAt()->format('d/m/Y | H:i'),
			'orderProducts' => $order->getOrderProducts()
		];
	}

}