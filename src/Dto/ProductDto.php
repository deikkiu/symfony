<?php

namespace App\Dto;

use App\Entity\Product;

readonly class ProductDto
{
	public function __construct(
		private Product $product,
		private int     $quantity,
		private bool    $inStock
	)
	{
	}

	public function getId(): ?int
	{
		return $this->product->getId();
	}

	public function getName(): ?string
	{
		return $this->product->getName();
	}

	public function getSlug(): ?string
	{
		return $this->product->getSlug();
	}

	public function getPrice(): ?int
	{
		return $this->product->getPrice();
	}

	public function getAmount(): ?int
	{
		return $this->product->getAmount();
	}

	public function getQuantity(): ?int
	{
		return $this->quantity;
	}

	public function isInStock(): bool
	{
		return $this->inStock;
	}

	public function getImagePath(): ?string
	{
		return $this->product->getImagePath();
	}
}
