<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\RoleRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $role_name = null;


    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'userRoles')]
    private \Doctrine\Common\Collections\Collection $users;

    #[ORM\ManyToMany(targetEntity: JournalUser::class, mappedBy: 'role')]
    private Collection $journalUsers;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->journalUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoleName(): ?string
    {
        return $this->role_name;
    }

    public function setRoleName(string $role_name): static
    {
        $this->role_name = $role_name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUsers(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addRoles($this);
        }

        return $this;
    }


    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeRole($this);
        }

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
            $journalUser->addRole($this);
        }

        return $this;
    }

    public function removeJournalUser(JournalUser $journalUser): static
    {
        if ($this->journalUsers->removeElement($journalUser)) {
            $journalUser->removeRole($this);
        }

        return $this;
    }

}