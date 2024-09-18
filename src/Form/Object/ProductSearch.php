<?php

namespace App\Form\Object;

use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Assert;

class ProductSearch
{
	#[Assert\Length(min: 2, max: 255)]
	private ?string $name = null;

	private ?Category $category = null;

	#[Assert\PositiveOrZero]
	private ?int $minPrice = null;

	#[Assert\PositiveOrZero]
	#[Assert\GreaterThan([
		'propertyPath' => 'minPrice',
		'message' => 'This max price value should be greater than min price value: {{ compared_value }}.'
	])]
	private ?int $maxPrice = null;

	private ?bool $isAmount = null;

	#[Assert\PositiveOrZero]
	private ?int $weight = null;

	private ?string $sort = null;

	public function getSort(): ?string
	{
		return $this->sort;
	}

	public function setSort(?string $sort): void
	{
		$this->sort = $sort;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(?string $name): void
	{
		$this->name = $name;
	}

	public function getCategory(): ?Category
	{
		return $this->category;
	}

	public function setCategory(?Category $category): static
	{
		$this->category = $category;

		return $this;
	}

	public function getMinPrice(): ?int
	{
		return $this->minPrice;
	}

	public function setMinPrice(?int $minPrice): void
	{
		$this->minPrice = $minPrice;
	}

	public function getMaxPrice(): ?int
	{
		return $this->maxPrice;
	}

	public function setMaxPrice(?int $maxPrice): void
	{
		$this->maxPrice = $maxPrice;
	}

	public function getIsAmount(): ?bool
	{
		return $this->isAmount;
	}

	public function setIsAmount(?bool $isAmount): void
	{
		$this->isAmount = $isAmount;
	}

	public function getWeight(): ?int
	{
		return $this->weight;
	}

	public function setWeight(?int $weight): void
	{
		$this->weight = $weight;
	}
}