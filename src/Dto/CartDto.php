<?php

namespace App\Dto;

class CartDto
{
	public function __construct(public array $products, public int $quantity)
	{
	}

	public function getProducts(): array
	{
		return $this->products;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	public function setProducts(array $products): void
	{
		$this->products = $products;
	}

	public function setQuantity(int $quantity): void
	{
		$this->quantity = $quantity;
	}
}