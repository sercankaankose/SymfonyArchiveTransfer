<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Validator\Constraints\PasswordPolicy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $surname = null;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
//    #[ORM\JoinTable(name: "user_role")]
    private Collection $userRoles;

    /**
     * @var string The hashed password
     */
    /**
     * @Assert\NotBlank()
     * @PasswordPolicy
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    #[ORM\Column]
    private ?bool $is_admin = false;

    #[ORM\Column]
    private ?bool $is_active = true;

    #[ORM\OneToMany(mappedBy: 'person', targetEntity: JournalUser::class)]
    private Collection $journalUsers;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->journalUsers = new ArrayCollection();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */


    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }
    /**
     * @return Collection<int, Role>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->userRoles as $role) {
            $roles[] = $role->getRoleName();

        }

        return $roles;
    }

    public function addRoles(Role $role): static
    {
        if (!$this->userRoles->contains($role)) {
            $this->userRoles->add($role);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        $this->userRoles->removeElement($role);

        return $this;
    }
    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
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

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function isIsAdmin(): ?bool
    {
        return $this->is_admin;
    }

    public function setIsAdmin(?bool $is_admin): static
    {
        $this->is_admin = $is_admin;

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

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
            $journalUser->setPerson($this);
        }

        return $this;
    }

    public function removeJournalUser(JournalUser $journalUser): static
    {
        if ($this->journalUsers->removeElement($journalUser)) {
            // set the owning side to null (unless already changed)
            if ($journalUser->getPerson() === $this) {
                $journalUser->setPerson(null);
            }
        }

        return $this;
    }

}
