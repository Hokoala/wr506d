<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\Column]
    private ?int $limiter = null;

    #[ORM\Column(length: 64)]
    private ?string $apiKeyHash = null;

    #[ORM\Column(length: 16, nullable: true)]
    private ?string $apiKeyPrefix = null;

    #[ORM\Column]
    private ?bool $apiKeyEnabled = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $apiKeyCreatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $apiKeyLastUsedAt = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
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
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): static
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    public function getLimiter(): ?int
    {
        return $this->limiter;
    }

    public function setLimiter(int $limiter): static
    {
        $this->limiter = $limiter;

        return $this;
    }

    public function getApiKeyHash(): ?string
    {
        return $this->apiKeyHash;
    }

    public function setApiKeyHash(string $apiKeyHash): static
    {
        $this->apiKeyHash = $apiKeyHash;

        return $this;
    }

    public function getApiKeyPrefix(): ?string
    {
        return $this->apiKeyPrefix;
    }

    public function setApiKeyPrefix(?string $apiKeyPrefix): static
    {
        $this->apiKeyPrefix = $apiKeyPrefix;

        return $this;
    }

    public function isApiKeyEnabled(): ?bool
    {
        return $this->apiKeyEnabled;
    }

    public function setApiKeyEnabled(bool $apiKeyEnabled): static
    {
        $this->apiKeyEnabled = $apiKeyEnabled;

        return $this;
    }

    public function getApiKeyCreatedAt(): ?\DateTimeImmutable
    {
        return $this->apiKeyCreatedAt;
    }

    public function setApiKeyCreatedAt(?\DateTimeImmutable $apiKeyCreatedAt): static
    {
        $this->apiKeyCreatedAt = $apiKeyCreatedAt;

        return $this;
    }

    public function getApiKeyLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->apiKeyLastUsedAt;
    }

    public function setApiKeyLastUsedAt(?\DateTimeImmutable $apiKeyLastUsedAt): static
    {
        $this->apiKeyLastUsedAt = $apiKeyLastUsedAt;

        return $this;
    }
}
