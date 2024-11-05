<?php

namespace App\Entity;

use App\Repository\ImportProductMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportProductMessageRepository::class)]
class ImportProductMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ImportProduct $importProduct = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getImportProduct(): ?ImportProduct
    {
        return $this->importProduct;
    }

    public function setImportProduct(?ImportProduct $importProduct): static
    {
        $this->importProduct = $importProduct;

        return $this;
    }
}
