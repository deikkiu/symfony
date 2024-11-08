<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\SoftDeleteable;
use Gedmo\Mapping\Annotation\Timestampable;

#[ORM\Table(name: '`order`')]
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class Order
{
	public const STATUS_CREATED = 1;
	public const STATUS_PROCESSED = 2;
	public const STATUS_DELIVERED = 3;
	public const STATUS_COMPLECTED = 4;
	public const STATUS_DENIED = 5;

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\ManyToOne(inversedBy: 'orders')]
	#[ORM\JoinColumn(nullable: false)]
	private ?User $owner = null;

	#[ORM\Column]
	private ?int $status = null;

	#[ORM\Column]
	private ?int $totalPrice = null;

	#[ORM\Column]
	#[Timestampable]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\Column]
	#[Timestampable]
	private ?\DateTimeImmutable $updatedAt = null;

	/**
	 * @var Collection<int, OrderItem>
	 */
	#[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'appOrder')]
	private Collection $orderProducts;

	#[ORM\Column(nullable: true)]
	private ?\DateTimeImmutable $deletedAt = null;

	public function __construct()
	{
		$this->orderProducts = new ArrayCollection();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getOwner(): ?User
	{
		return $this->owner;
	}

	public function setOwner(?User $owner): static
	{
		$this->owner = $owner;

		return $this;
	}

	public function getStatus(): ?int
	{
		return $this->status;
	}

	public function setStatus(int $status): static
	{
		$this->status = $status;

		return $this;
	}

	public function getTotalPrice(): ?int
	{
		return $this->totalPrice;
	}

	public function setTotalPrice(int $totalPrice): static
	{
		$this->totalPrice = $totalPrice;

		return $this;
	}

	public function getCreatedAt(): ?\DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(\DateTimeImmutable $createdAt): static
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): ?\DateTimeImmutable
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	/**
	 * @return Collection<int, OrderItem>
	 */
	public function getOrderProducts(): Collection
	{
		return $this->orderProducts;
	}

	public function addOrderProduct(OrderItem $orderProduct): static
	{
		if (!$this->orderProducts->contains($orderProduct)) {
			$this->orderProducts->add($orderProduct);
			$orderProduct->setAppOrder($this);
		}

		return $this;
	}

	public function removeOrderProduct(OrderItem $orderProduct): static
	{
		if ($this->orderProducts->removeElement($orderProduct)) {
			// set the owning side to null (unless already changed)
			if ($orderProduct->getAppOrder() === $this) {
				$orderProduct->setAppOrder(null);
			}
		}

		return $this;
	}

	public function getDeletedAt(): ?\DateTimeImmutable
	{
		return $this->deletedAt;
	}

	public function setDeletedAt(\DateTimeImmutable $deletedAt): static
	{
		$this->deletedAt = $deletedAt;

		return $this;
	}

	public static function getOrderStatus(): array
	{
		return [
			self::STATUS_CREATED => 'Created',
			self::STATUS_PROCESSED => 'Processed',
			self::STATUS_COMPLECTED => 'Complected',
			self::STATUS_DELIVERED => 'Delivered',
			self::STATUS_DENIED => 'Denied',
		];
	}
}
