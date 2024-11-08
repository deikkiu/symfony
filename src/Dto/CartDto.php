<?php

namespace App\Dto;

class CartDto
{
	/**
	 * @var array<int, CartItemDto>
	 */
	private array $list = [];
	private int $quantity = 0;

	public function getList(): array
	{
		return $this->list;
	}

	public function setList(array $list): void
	{
		$this->list = $list;
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