<?php

namespace App\Dto;

class Cart
{
	private array $products = [];
	private int $quantity = 0;
	private int $totalPrice = 0;

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

	public function getTotalPrice(): int
	{
		return $this->totalPrice;
	}

	public function setTotalPrice(int $totalPrice): void
	{
		$this->totalPrice = $totalPrice;
	}
}