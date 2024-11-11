<?php

namespace App\Messenger\Message;

final readonly class ImportProductsMessage
{
	public function __construct(
		private string $slug,
		private int    $userId,
	)
	{
	}

	public function getSlug(): string
	{
		return $this->slug;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}
}
