<?php

namespace App\Service;

use App\Dto\MailerOptionsDto;
use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

readonly class MailerSender
{
	public function __construct(
		private MailerInterface $mailer,
		private LoggerInterface $logger,
		private string          $adminEmail,
	)
	{
	}

	public function sendTemplatedEmail($mailerOptions): void
	{
		$email = (new TemplatedEmail())
			->to($mailerOptions->getRecipient())
			->subject($mailerOptions->getSubject())
			->htmlTemplate($mailerOptions->getHtmlTemplate())
			->context($mailerOptions->getContext());

		if ($mailerOptions->getCc()) {
			$email->cc($mailerOptions->getCc());
		}

		try {
			$this->mailer->send($email);
		} catch (TransportExceptionInterface $e) {
			$this->logger->error($e->getMessage());
		}
	}

	public function sendTextEmail($mailerOptions): void
	{
		$email = (new Email())
			->to($mailerOptions->getRecipient())
			->subject($mailerOptions->getSubject())
			->text($mailerOptions->getText());

		if ($mailerOptions->getCc()) {
			$email->cc($mailerOptions->getCc());
		}

		try {
			$this->mailer->send($email);
		} catch (TransportExceptionInterface $e) {
			$this->logger->error($e->getMessage());
		}
	}

	public function createAdminOrderEmail(Order $order): void
	{
		$mailerOptions = new MailerOptionsDto();

		$mailerOptions->setRecipient($this->adminEmail);
		$mailerOptions->setSubject('Market | Order id: ' . $order->getId());
		$mailerOptions->setHtmlTemplate('order/email.html.twig');
		$mailerOptions->setContext([
			'id' => $order->getId(),
			'owner' => $order->getOwner()->getEmail(),
			'price' => $order->getTotalPrice(),
			'status' => $order::getOrderStatus()[$order->getStatus()],
			'date' => $order->getCreatedAt()->format('d/m/Y | H:i'),
			'orderProducts' => $order->getOrderProducts()
		]);

		$this->sendTemplatedEmail($mailerOptions);
	}

	public function createClientOrderEmail(Order $order): void
	{
		$mailerOptions = new MailerOptionsDto();

		$mailerOptions->setRecipient($order->getOwner()->getEmail());
		$mailerOptions->setSubject('Market | Order id: ' . $order->getId());
		$mailerOptions->setText('Order successfully created.\nOrder id: ' . $order->getId() . '.\nOrder status: ' . $order::getOrderStatus()[$order->getStatus()]);

		$this->sendTextEmail($mailerOptions);
	}
}