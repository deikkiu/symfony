<?php

namespace App\Messenger\Message;

readonly class SendOrderByEmailMessage
{
	public function __construct(
		private int $id,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}
}
