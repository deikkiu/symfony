<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SendOrderByEmailMessage;
use App\Repository\OrderRepository;
use App\Service\MailerSender;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'async')]
readonly class SendOrderByEmailMessageHandler
{
	public function __construct(
		private OrderRepository $orderRepository,
		private MailerSender    $mailerSender
	)
	{
	}

	public function __invoke(SendOrderByEmailMessage $message): void
	{
		$orderId = $message->getId();
		$order = $this->orderRepository->find($orderId);

		if (!$order) return;

		$this->mailerSender->createAdminOrderEmail($order);
		$this->mailerSender->createClientOrderEmail($order);
	}
}