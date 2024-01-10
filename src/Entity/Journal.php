<?php

namespace App\Entity;

use App\Repository\JournalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: JournalRepository::class)]
class Journal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 9, nullable:true, unique: true)]
    private ?string $issn = null;

    #[ORM\Column(length: 9, nullable:true, unique: true)]
    private ?string $e_issn = null;

    #[ORM\OneToMany(mappedBy: 'journal', targetEntity: JournalUser::class)]
    private Collection $journalUsers;

    #[ORM\OneToMany(mappedBy: 'journal', targetEntity: Issues::class)]
    private Collection $issues;

    #[ORM\OneToMany(mappedBy: 'journal', targetEntity: Articles::class)]
    private Collection $articles;

    public function __construct()
    {

        $this->journalUsers = new ArrayCollection();
        $this->issues = new ArrayCollection();
        $this->articles = new ArrayCollection();
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

    public function getIssn(): ?string
    {
        return $this->issn;
    }

    public function setIssn(?string $issn): static
    {
        $this->issn = $issn;

        return $this;
    }

    public function getEIssn(): ?string
    {
        return $this->e_issn;
    }

    public function setEIssn(?string $e_issn): static
    {
        $this->e_issn = $e_issn;

        return $this;
    }

    /**
     * @return Collection<int, JournalUser>
     */
    public function getJournalUsers(): Collection
    {
        return $this->journalUsers;
    }

    public function addJournalUser(JournalUser $journalUser): static
    {
        if (!$this->journalUsers->contains($journalUser)) {
            $this->journalUsers->add($journalUser);
            $journalUser->setJournal($this);
        }

        return $this;
    }

    public function removeJournalUser(JournalUser $journalUser): static
    {
        if ($this->journalUsers->removeElement($journalUser)) {
            // set the owning side to null (unless already changed)
            if ($journalUser->getJournal() === $this) {
                $journalUser->setJournal(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Issues>
     */
    public function getIssues(): Collection
    {
        return $this->issues;
    }

    public function addIssue(Issues $issue): static
    {
        if (!$this->issues->contains($issue)) {
            $this->issues->add($issue);
            $issue->setJournal($this);
        }

        return $this;
    }

    public function removeIssue(Issues $issue): static
    {
        if ($this->issues->removeElement($issue)) {
            // set the owning side to null (unless already changed)
            if ($issue->getJournal() === $this) {
                $issue->setJournal(null);
            }
        }

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
            $article->setJournal($this);
        }

        return $this;
    }

    public function removeArticle(Articles $article): static
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getJournal() === $this) {
                $article->setJournal(null);
            }
        }

        return $this;
    }
}
