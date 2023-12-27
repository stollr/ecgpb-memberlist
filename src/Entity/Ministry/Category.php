<?php

namespace App\Entity\Ministry;

use App\Entity\Ministry;
use App\Entity\Person;
use App\Repository\Ministry\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Ministry\Category
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'ministry_category')]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?int $id = null;

    
    #[ORM\Column(type: 'string', length: 40)]
    #[Assert\Length(max: 40)]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?string $name = null;
    
    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(name: 'responsible_person_id', nullable: true, onDelete: 'SET NULL')]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?Person $responsible = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?int $position = null;

    #[ORM\OneToMany(targetEntity: Ministry::class, mappedBy: 'category', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private Collection $ministries;

    public function __construct()
    {
        $this->ministries = new ArrayCollection();
    }

    /**
     * Get id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function getResponsible(): ?Person
    {
        return $this->responsible;
    }

    public function setResponsible(?Person $responsible): self
    {
        $this->responsible = $responsible;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Add ministry
     */
    public function addMinistry(Ministry $ministry): self
    {
        $this->ministries[] = $ministry;
        $ministry->setCategory($this);
        return $this;
    }

    /**
     * Remove ministry
     */
    public function removeMinistry(Ministry $ministries): void
    {
        $this->ministries->removeElement($ministries);
    }

    /**
     * Get ministries
     * 
     * @return Collection<int, Ministry>
     */
    public function getMinistries(): Collection
    {
        return $this->ministries;
    }
}
