<?php

namespace App\Entity;

use App\Repository\CitationsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CitationsRepository::class)]
class Citations
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $referance = null;

    #[ORM\Column(nullable: true)]
    private ?int $Row = null;

    #[ORM\ManyToOne(inversedBy: 'citations')]
    private ?Articles $article = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferance(): ?string
    {
        return $this->referance;
    }

    public function setReferance(?string $referance): static
    {
        $this->referance = $referance;

        return $this;
    }

    public function getRow(): ?int
    {
        return $this->Row;
    }

    public function setRow(?int $Row): static
    {
        $this->Row = $Row;

        return $this;
    }

    public function getArticle(): ?Articles
    {
        return $this->article;
    }

    public function setArticle(?Articles $article): static
    {
        $this->article = $article;

        return $this;
    }
}
