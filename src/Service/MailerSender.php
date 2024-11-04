<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

readonly class MailerSender
{
	public function __construct(
		private MailerInterface $mailer,
		private string          $adminEmail,
	)
	{
	}

	public function send($email): void
	{
		try {
			$this->mailer->send($email);
		} catch (TransportExceptionInterface $e) {
			throw new \Exception($e->getMessage());
		}
	}

	// @TODO: common method only for send email
	public function templatedEmail(Order $order): void
	{
		$email = (new TemplatedEmail())
			->from($this->adminEmail)
			->to($this->adminEmail)
			->subject('Market | Order id: ' . $order->getId())
			->htmlTemplate('order/email.html.twig')
			->context([
				'id' => $order->getId(),
				'owner' => $order->getOwner()->getEmail(),
				'price' => $order->getTotalPrice(),
				'status' => $order::getOrderStatus()[$order->getStatus()],
				'date' => $order->getCreatedAt()->format('d/m/Y | H:i'),
				'orderProducts' => $order->getOrderProducts()
			]);

		$this->send($email);
	}
}