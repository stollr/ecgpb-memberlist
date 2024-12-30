<?php

namespace App\Entity;

use App\Entity\Person;
use App\Repository\AddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use libphonenumber\PhoneNumber;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Address
 */
#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'address')]
#[Gedmo\Loggable]
class Address
{
    /**
     * Import timestampable behavior.
     */
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?int $id = null;

    #[ORM\Column(name: 'family_name', type: 'string', length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Gedmo\Versioned]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?string $familyName = null;

    #[ORM\Column(name: 'name_prefix', type: 'string', length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    #[Gedmo\Versioned]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?string $namePrefix = null;

    #[ORM\Column(type: 'phone_number', nullable: true)]
    #[Gedmo\Versioned]
    private ?PhoneNumber $phone = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Gedmo\Versioned]
    private ?string $street = null;

    #[ORM\Column(type: 'string', length: 5, nullable: true)]
    #[Assert\Length(max: 5)]
    #[Gedmo\Versioned]
    private ?string $zip = null;

    #[ORM\Column(type: 'string', length: 40, nullable: true)]
    #[Assert\Length(max: 40)]
    #[Gedmo\Versioned]
    private ?string $city = null;

    #[ORM\OneToMany(targetEntity: 'Person', cascade: ['persist', 'remove'], mappedBy: 'address', orphanRemoval: true)]
    #[Assert\Valid]
    private Collection $persons;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }

    /**
     * Get id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set familyName
     *
     * @return $this
     */
    public function setFamilyName(string $familyName): static
    {
        $this->familyName = $familyName;

        return $this;
    }

    /**
     * Get familyName
     */
    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    /**
     * Get the name prefix.
     */
    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }

    /**
     * Set the name prefix.
     */
    public function setNamePrefix(?string $namePrefix): void
    {
        $this->namePrefix = $namePrefix;
    }

    /**
     * Set phone
     *
     * @return $this
     */
    public function setPhone(?PhoneNumber $phone): static
    {
        if ($this->phone && $phone && $this->phone->equals($phone)) {
            return $this;
        }

        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     */
    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    /**
     * Set street
     *
     * @return $this
     */
    public function setStreet(?string $street): static
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     */
    public function getStreet(): ?string
    {
        return $this->street;
    }

    /**
     * Set zip
     *
     * @return $this
     */
    public function setZip(?string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     */
    public function getZip(): ?string
    {
        return $this->zip;
    }

    /**
     * Set city
     *
     * @return $this
     */
    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     */
    public function getCity(): ?string
    {
        return $this->city;
    }


    /**
     * Add persons
     *
     * @return $this
     */
    public function addPerson(Person $person): static
    {
        $this->persons[] = $person;
        $person->setAddress($this);
        return $this;
    }

    /**
     * Remove persons
     *
     * @return $this
     */
    public function removePerson(Person $person): static
    {
        $this->persons->removeElement($person);
        return $this;
    }

    /**
     * Get persons
     *
     * @return Collection<int, Person>
     */
    public function getPersons(): Collection
    {
        $persons = new ArrayCollection();
        foreach ($this->persons as $person) {
            // sorting for symbolization of Gods family order
            // the husband should be the "head" of the family
            if (count($persons) == 1
                && $persons->get(0)->getGender() == Person::GENDER_FEMALE
                && $person->getGender() == Person::GENDER_MALE
                && $persons->get(0)->getDob() !== null
                && $person->getDob() !== null
            ) {
                $dobDiff = $persons->get(0)->getDob()->diff($person->getDob());
                if ($dobDiff->y <= 15) {
                    $wife = $persons->get(0);
                    $persons = new ArrayCollection();
                    $persons->add($person);
                    $persons->add($wife);
                    continue;
                }
            }
            $persons->add($person);
        }
        return $persons;
    }
    
    public function getDropdownLabel(): string
    {
        return implode(', ', [
            $this->getFamilyName(),
            $this->getStreet(),
            $this->getCity(),
        ]);
    }
}
