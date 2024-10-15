<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
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
	#[Assert\NotBlank(groups: ['Default', 'draft'])]
	#[Assert\Length(min: 2, max: 255, groups: ['Default', 'draft'])]
	#[Groups(['serialize', 'deserialize'])]
	private ?string $name = null;

	#[ORM\Column(length: 255)]
	#[Slug(fields: ['name'], unique: true)]
	private ?string $slug = null;

	#[ORM\Column]
	#[Assert\PositiveOrZero(groups: ['Default', 'draft'])]
	#[Assert\NotBlank(groups: ['Default', 'draft'])]
	#[Groups(['serialize', 'deserialize'])]
	private ?int $price = null;

	#[ORM\Column(nullable: true)]
	#[Assert\NotBlank]
	#[Assert\PositiveOrZero]
	#[Groups(['serialize', 'deserialize'])]
	private ?int $amount = null;

	#[ORM\Column(type: Types::TEXT, nullable: true)]
	#[Groups(['serialize', 'deserialize'])]
	private ?string $descr = null;

	#[ORM\ManyToOne(inversedBy: 'products')]
	#[ORM\JoinColumn(nullable: true)]
	#[Assert\NotNull]
	#[Groups(['serialize', 'deserialize'])]
	private ?Category $category = null;

	#[ORM\OneToOne(cascade: ['persist', 'remove'])]
	#[ORM\JoinColumn(nullable: true)]
	#[Assert\Valid]
	#[Groups(['serialize', 'deserialize'])]
	private ?ProductAttr $product_attr = null;

	#[ORM\Column]
	#[Groups(['serialize'])]
	private ?\DateTime $created_at = null;

	#[ORM\Column]
	#[Groups(['serialize'])]
	private ?\DateTime $updated_at = null;

	/**
	 * @var Collection<int, Color>
	 */
	#[ORM\OneToMany(targetEntity: Color::class, mappedBy: 'product', cascade: ['persist', 'remove'])]
	#[Assert\Valid]
	#[Groups(['serialize', 'deserialize'])]
	private Collection $colors;

	#[ORM\ManyToOne]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $user = null;

	#[ORM\Column(length: 255, nullable: true)]
	#[Groups(['serialize', 'deserialize'])]
	private ?string $imagePath = null;

	#[ORM\Column]
	#[Groups(['serialize', 'deserialize'])]
	private ?bool $isDraft = null;

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

	public function setName(?string $name): static
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

	public function setAmount(?int $amount): static
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

	public function getUser(): ?User
	{
		return $this->user;
	}

	public function setUser(?User $user): static
	{
		$this->user = $user;

		return $this;
	}

	public function getImagePath(): ?string
	{
		return $this->imagePath;
	}

	public function setImagePath(?string $imagePath): static
	{
		$this->imagePath = $imagePath;

		return $this;
	}

	public function isDraft(): ?bool
	{
		return $this->isDraft;
	}

	public function setDraft(bool $isDraft): static
	{
		$this->isDraft = $isDraft;

		return $this;
	}
}
