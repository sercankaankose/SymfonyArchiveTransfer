<?php

namespace App\Entity;

use App\Repository\IssuesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: IssuesRepository::class)]
class Issues
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'issues')]
    private ?Journal $journal = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $volume = null;

    #[ORM\Column]
    private ?int $number = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fulltext = null;

    #[ORM\Column(length: 255)]
    private ?string $xml = null;

    #[ORM\OneToMany(mappedBy: 'issue', targetEntity: Articles::class)]
    private Collection $articles;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?array $errors = null;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJournal(): ?Journal
    {
        return $this->journal;
    }

    public function setJournal(?Journal $journal): static
    {
        $this->journal = $journal;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getVolume(): ?int
    {
        return $this->volume;
    }

    public function setVolume(?int $volume): static
    {
        $this->volume = $volume;

        return $this;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getFulltext(): ?string
    {
        return $this->fulltext;
    }

    public function setFulltext(?string $fulltext): static
    {
        $this->fulltext = $fulltext;

        return $this;
    }

    public function getXml(): ?string
    {
        return $this->xml;
    }

    public function setXml(string $xml): static
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * @return Collection<int, Articles>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Articles $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setIssue($this);
        }

        return $this;
    }

    public function removeArticle(Articles $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getIssue() === $this) {
                $article->setIssue(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(?array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }
}
