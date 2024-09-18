<?php

namespace App\Entity;

use App\Repository\ProductAttrRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductAttrRepository::class)]
class ProductAttr
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $width = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $height = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $length = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private ?int $weight = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): static
    {
        $this->length = $length;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }
}
