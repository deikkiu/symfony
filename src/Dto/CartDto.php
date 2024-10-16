<?php

namespace App\Dto;

class ProductDto
{
	public function __construct(
		private readonly int $id, 
		private readonly string $name,
		private readonly string $slug,
		private readonly string $category,
		private readonly int $price, 
		private readonly int $amount,
		private readonly int $quantity,
		private readonly ?string $imagePath, 
		private readonly ?array $colors
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getSlug(): string
	{
		return $this->slug;
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	public function getPrice(): int
	{
		return $this->price;
	}

	public function getAmount(): int
	{
		return $this->amount;
	}

	public function getQuantity(): int
	{
		return $this->quantity;
	}

	public function getImagePath(): ?string
	{
		return $this->imagePath;
	}

	public function getColors(): ?array
	{
		return $this->colors;
	}
}
