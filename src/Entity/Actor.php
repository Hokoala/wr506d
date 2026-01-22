<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ActorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\ExistsFilter;
use DateTime;
use DateTimeInterface;
use DateTimeImmutable;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @SuppressWarnings(PHPMD.ShortVariable)
 */

#[ORM\Entity(repositoryClass: ActorRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['actor:list']]
        ),
        new Get(
            normalizationContext: ['groups' => ['actor:read', 'movie:list']]
        ),
        new Post(
            normalizationContext: ['groups' => ['actor:read', 'movie:list']],
            denormalizationContext: ['groups' => ['actor:write']]
        ),
        new Put(
            normalizationContext: ['groups' => ['actor:read', 'movie:list']],
            denormalizationContext: ['groups' => ['actor:write']]
        ),
        new Patch(
            normalizationContext: ['groups' => ['actor:read', 'movie:list']],
            denormalizationContext: ['groups' => ['actor:write']]
        ),
        new Delete()
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(SearchFilter::class, properties: ['lastname' => 'start', 'firstname' => 'start'])]
#[ApiFilter(DateFilter::class, properties: ['dod'])]
#[ApiFilter(ExistsFilter::class, properties: ['dob'])]

#[GetCollection]
#[Post(security: "is_granted('ROLE_ADMIN')")]
#[Delete(security: "is_granted('ROLE_ADMIN')")]
#[Put(security: "is_granted('ROLE_ADMIN')")]
#[Get(security: "is_granted('ROLE_USER')")]


class Actor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['actor:list', 'actor:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['actor:read', 'actor:write'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['actor:read', 'actor:write'])]
    private ?string $firstname = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['actor:read', 'actor:write'])]
    private ?DateTime $dob = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dod = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;



    /**
     * @var Collection<int, Movie>
     */
    #[ORM\ManyToMany(targetEntity: Movie::class, inversedBy: 'actors')]
    #[Groups(['actor:read'])]
    private Collection $movies;

    #[ORM\Column]
    private ?DateTimeImmutable $createAt = null;

    #[ORM\ManyToOne(inversedBy: 'actors')]
    private ?MediaObject $photo = null;


    public function __construct()
    {
        $this->movies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Nom complet (virtuel)
     */
    #[Groups(['actor:list', 'actor:read'])]
    public function getFullName(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Âge calculé (virtuel)
     */
    #[Groups(['actor:list'])]
    public function getAge(): ?int
    {
        if ($this->dob === null) {
            return null;
        }
        // Si décédé, calcule l'âge au moment du décès
        $reference = $this->dod ?? new DateTime();

        return $this->dob->diff($reference)->y;
    }

    /**
     * Est décédé (virtuel)
     */
    #[Groups(['actor:list'])]
    public function getIsDead(): bool
    {
        return $this->dod !== null;
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

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getDob(): ?DateTime
    {
        return $this->dob;
    }

    public function setDob(?DateTime $dob): static
    {
        $this->dob = $dob;

        return $this;
    }

    public function getDod(): ?DateTimeInterface
    {
        return $this->dod;
    }

    public function setDod(?DateTimeInterface $dod): static
    {
        $this->dod = $dod;

        return $this;
    }


    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    /**
     * @return Collection<int, Movie>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movie $movie): static
    {
        if (!$this->movies->contains($movie)) {
            $this->movies->add($movie);
        }

        return $this;
    }

    public function removeMovie(Movie $movie): static
    {
        $this->movies->removeElement($movie);

        return $this;
    }

    public function getCreateAt(): ?DateTimeImmutable
    {
        return $this->createAt;
    }
    public function setCreateAt(DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }
    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createAt = new DateTimeImmutable();
    }

    public function getPhoto(): ?MediaObject
    {
        return $this->photo;
    }

    public function setPhoto(?MediaObject $photo): static
    {
        $this->photo = $photo;

        return $this;
    }
}
