<?php

namespace App\Dto;

class CartProductDto
{
	public function __construct(
		private int $id,
		private int $quantity,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	public function setQuantity(int $quantity): void
	{
		$this->quantity = $quantity;
	}
}