<?php

namespace App\Services;

class Cart
{
	private array $products = [];
	private int $quantity = 0;

	public function __constructor()
	{
		$this->products = $products;
		$this->quantity = $quantity;
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
