<?php

namespace App\Dto;

class ProductDto
{
	public function __construct(
		public int     $id,
		public string  $name,
		public string  $slug,
		public string  $category,
		public int     $price,
		public int     $amount,
		public int     $quantity,
		public ?string $imagePath,
		public ?array  $colors
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

	public function setQuantity(int $quantity): void
	{
		$this->quantity = $quantity;
	}
}
