<?php

namespace App\Entity;

use App\Entity\Person;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * App\Entity\Address
 *
 * @ORM\Entity(repositoryClass="App\Repository\AddressRepository")
 * @ORM\Table(name="address")
 */
class Address
{
    /**
     * Import timestampable behavior.
     */
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(name="family_name", type="string", length=50)
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $familyName;

    /**
     * @ORM\Column(name="name_prefix", type="string", length=20, nullable=true)
     * @Groups({"MinistryCategoryListing"})
     *
     * @var ?string
     */
    private $namePrefix;

    /**
     * @ORM\Column(type="string", length=30, nullable=true)
     *
     * @var string
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     *
     * @var string
     */
    private $street;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     *
     * @var string
     */
    private $zip;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     *
     * @var string
     */
    private $city;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Person", cascade={"persist", "remove"}, mappedBy="address", orphanRemoval=true)
     *
     * @var \Doctrine\Common\Collections\Collection
     */
    private $persons;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set familyName
     *
     * @param string $familyName
     * @return Address
     */
    public function setFamilyName($familyName)
    {
        $this->familyName = $familyName;

        return $this;
    }

    /**
     * Get familyName
     *
     * @return string 
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * Get the name prefix.
     *
     * @return string|null
     */
    public function getNamePrefix(): ?string
    {
        return $this->namePrefix;
    }

    /**
     * Set the name prefix.
     *
     * @param string|null $namePrefix
     */
    public function setNamePrefix(?string $namePrefix)
    {
        $this->namePrefix = $namePrefix;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return Address
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set street
     *
     * @param string $street
     * @return Address
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * Get street
     *
     * @return string 
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set zip
     *
     * @param string $zip
     * @return Address
     */
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string 
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string 
     */
    public function getCity()
    {
        return $this->city;
    }


    /**
     * Add persons
     *
     * @param \App\Entity\Person $person
     * @return Address
     */
    public function addPerson(\App\Entity\Person $person)
    {
        $this->persons[] = $person;
        $person->setAddress($this);
        return $this;
    }

    /**
     * Remove persons
     *
     * @param \App\Entity\Person $person
     */
    public function removePerson(\App\Entity\Person $person)
    {
        $this->persons->removeElement($person);
        return $this;
    }

    /**
     * Get persons
     *
     * @return Collection|Person[]
     */
    public function getPersons()
    {
        $persons = new ArrayCollection();
        foreach ($this->persons as $person) {
            // sorting for symbolization of Gods family order
            // the husband should be the "head" of the family
            if (count($persons) == 1
                && $persons->get(0)->getGender() == Person::GENDER_FEMALE
                && $person->getGender() == Person::GENDER_MALE
            ) {
                $dobDiff = $persons->get(0)->getDob()->diff($person->getDob()); /* @var $dobDiff \DateInterval */
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
    
    public function getDropdownLabel()
    {
        return implode(', ', array(
            $this->getFamilyName(),
            $this->getStreet(),
            $this->getCity(),
        ));
    }
}
