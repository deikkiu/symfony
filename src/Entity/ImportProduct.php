<?php

namespace App\Entity;

use App\Repository\ImportProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportProductRepository::class)]
class ImportProduct
{
	public const STATUS_PENDING = 1;
	public const STATUS_SUCCESS = 2;
	public const STATUS_ERROR = 3;

	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	private ?string $name = null;

	#[ORM\Column(length: 255)]
	private ?string $path = null;

	#[ORM\Column]
	private ?int $status = null;

	#[ORM\Column]
	private ?\DateTimeImmutable $createdAt = null;

	#[ORM\Column]
	private ?\DateTimeImmutable $updatedAt = null;

	/**
	 * @var Collection<int, ImportProductMessage>
	 */
	#[ORM\OneToMany(targetEntity: ImportProductMessage::class, mappedBy: 'importProduct', orphanRemoval: true)]
	private Collection $messages;

	#[ORM\Column(nullable: true)]
	private ?int $countImportedProducts = null;

	public function __construct()
	{
		$this->messages = new ArrayCollection();
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

	public function getPath(): ?string
	{
		return $this->path;
	}

	public function setPath(string $path): static
	{
		$this->path = $path;

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
	 * @return Collection<int, ImportProductMessage>
	 */
	public function getMessages(): Collection
	{
		return $this->messages;
	}

	public function addMessage(ImportProductMessage $message): static
	{
		if (!$this->messages->contains($message)) {
			$this->messages->add($message);
			$message->setImportProduct($this);
		}

		return $this;
	}

	public function removeMessage(ImportProductMessage $message): static
	{
		if ($this->messages->removeElement($message)) {
			// set the owning side to null (unless already changed)
			if ($message->getImportProduct() === $this) {
				$message->setImportProduct(null);
			}
		}

		return $this;
	}

	public function getCountImportedProducts(): ?int
	{
		return $this->countImportedProducts;
	}

	public function setCountImportedProducts(?int $countImportedProducts): static
	{
		$this->countImportedProducts = $countImportedProducts;

		return $this;
	}

	public function getImportStatus(): array
	{
		return [
			self::STATUS_PENDING => 'Pending',
			self::STATUS_SUCCESS => 'Success',
			self::STATUS_ERROR => 'Error'
		];
	}

	public function getImportStatusMessage(): array
	{
		return [
			self::STATUS_PENDING => 'The import of products is pending.',
			self::STATUS_SUCCESS => 'The import of the products was completed successfully. ' . $this->getCountImportedProducts() . ' products were imported',
			self::STATUS_ERROR => 'The import of the products was completed with errors. Please try again.'
		];
	}
}
