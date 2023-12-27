<?php

namespace App\Entity;

use App\Entity\Ministry\Category;
use App\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Ministry
 */
#[ORM\Entity]
#[ORM\Table(name: 'ministry')]
class Ministry
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 60)]
    #[Assert\Length(max: 60)]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?string $description = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?int $position = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'ministries')]
    #[ORM\JoinColumn(name: 'category_id', nullable: false, onDelete: 'CASCADE')]
    private ?Category $category = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\ManyToMany(targetEntity: Person::class, inversedBy: 'ministries')]
    #[ORM\JoinTable(name: 'ministry_responsible')]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private Collection $responsibles;

    public function __construct()
    {
        $this->responsibles = new ArrayCollection();
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
    public function setName(string $name): static
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return $this
     */
    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * @return $this
     */
    public function setPosition(?int $position): static
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Set category
     *
     * @return $this
     */
    public function setCategory(Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get category
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Add responsible persons
     *
     * @return $this
     */
    public function addResponsible(Person $person): static
    {
        $this->responsibles->add($person);

        if (!$person->getMinistries()->contains($this)) {
            $person->getMinistries()->add($this);
        }
        return $this;
    }

    /**
     * Remove responsible persons
     *
     * @return $this
     */
    public function removeResponsible(Person $person): void
    {
        $this->responsibles->removeElement($person);
        $person->getMinistries()->removeElement($this);
    }

    /**
     * Get responsible persons
     *
     * @return Collection<int, Person>
     */
    public function getResponsibles(): Collection
    {
        return $this->responsibles;
    }
}
