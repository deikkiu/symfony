<?php

namespace App\Dto;

class MailerOptionsDto
{
	private string $recipient;
	private string $subject;
	private string $htmlTemplate = '';
	private array $context = [];
	private string $text = '';
	private ?string $cc = null;

	public function getRecipient(): string
	{
		return $this->recipient;
	}

	public function setRecipient(string $recipient): void
	{
		$this->recipient = $recipient;
	}

	public function getSubject(): string
	{
		return $this->subject;
	}

	public function setSubject(string $subject): void
	{
		$this->subject = $subject;
	}

	public function getHtmlTemplate(): string
	{
		return $this->htmlTemplate;
	}

	public function setHtmlTemplate(string $htmlTemplate): void
	{
		$this->htmlTemplate = $htmlTemplate;
	}

	public function getContext(): array
	{
		return $this->context;
	}

	public function setContext(array $context): void
	{
		$this->context = $context;
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function setText(string $text): void
	{
		$this->text = $text;
	}

	public function getCc(): ?string
	{
		return $this->cc;
	}

	public function setCc(?string $cc): void
	{
		$this->cc = $cc;
	}
}