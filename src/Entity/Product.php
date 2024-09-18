<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation\Slug;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	#[Assert\NotBlank]
	#[Assert\Length(min: 2, max: 255)]
	private ?string $name = null;

	#[ORM\Column(length: 255)]
	#[Slug(fields: ['name'], unique: true)]
	private ?string $slug = null;

	#[ORM\Column]
	#[Assert\PositiveOrZero]
	private ?int $price = null;

	#[ORM\Column]
	#[Assert\PositiveOrZero]
	private ?int $amount = null;

	#[ORM\Column(type: Types::TEXT, nullable: true)]
	private ?string $descr = null;

	#[ORM\ManyToOne(inversedBy: 'products')]
	#[ORM\JoinColumn(nullable: false)]
	private ?Category $category = null;

	#[ORM\OneToOne(cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(nullable: false)]
	#[Assert\Valid]
	private ?ProductAttr $product_attr = null;

	#[ORM\Column]
	private ?\DateTime $created_at = null;

	#[ORM\Column]
	private ?\DateTime $updated_at = null;

	/**
	 * @var Collection<int, Color>
	 */
	#[ORM\OneToMany(targetEntity: Color::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
	#[Assert\Valid]
	private Collection $colors;

	public function __construct()
	{
		$this->colors = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(string $name): static
	{
		$this->name = $name;

		return $this;
	}

	public function getSlug(): ?string
	{
		return $this->slug;
	}

	public function setSlug(string $slug): static
	{
		$this->slug = $slug;

		return $this;
	}

	public function getPrice(): ?int
	{
		return $this->price;
	}

	public function setPrice(int $price): static
	{
		$this->price = $price;

		return $this;
	}

	public function getAmount(): ?int
	{
		return $this->amount;
	}

	public function setAmount(int $amount): static
	{
		$this->amount = $amount;

		return $this;
	}

	public function getDescr(): ?string
	{
		return $this->descr;
	}

	public function setDescr(?string $descr): static
	{
		$this->descr = $descr;

		return $this;
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

	public function getProductAttr(): ?ProductAttr
	{
		return $this->product_attr;
	}

	public function setProductAttr(ProductAttr $product_attr): static
	{
		$this->product_attr = $product_attr;

		return $this;
	}

	public function getCreatedAt(): ?\DateTime
	{
		return $this->created_at;
	}

	public function setCreatedAt(\DateTime $created_at): static
	{
		$this->created_at = $created_at;

		return $this;
	}

	public function getUpdatedAt(): ?\DateTime
	{
		return $this->updated_at;
	}

	public function setUpdatedAt(\DateTime $updated_at): static
	{
		$this->updated_at = $updated_at;

		return $this;
	}

	/**
	 * @return Collection<int, Color>
	 */
	public function getColors(): Collection
	{
		return $this->colors;
	}

	public function addColor(Color $color): static
	{
		if (!$this->colors->contains($color)) {
			$this->colors->add($color);
			$color->setProduct($this);
		}

		return $this;
	}

	public function removeColor(Color $color): static
	{
		if ($this->colors->removeElement($color)) {
			if ($color->getProduct() === $this) {
				$color->setProduct(null);
			}
		}

		return $this;
	}
}
