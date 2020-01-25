<?php

namespace App\Entity;

use App\Entity\Ministry\Category;
use App\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Ministry
 *
 * @ORM\Entity
 * @ORM\Table(name="ministry")
 */
class Ministry
{
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
     * @ORM\Column(type="string", length=60)
     * @Assert\Length(max=60)
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $position;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Ministry\Category", inversedBy="ministries")
     * @ORM\JoinColumn(name="category_id", nullable=false, onDelete="CASCADE")
     *
     * @var Category
     */
    private $category;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Person", inversedBy="ministries")
     * @ORM\JoinTable(name="ministry_responsible")
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var ArrayCollection|Person[]
     */
    private $responsibles;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->responsibles = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Ministry
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Set category
     *
     * @param Category $category
     * @return Ministry
     */
    public function setCategory(Category $category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get category
     *
     * @return Category 
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Add responsible persons
     *
     * @param Person $person
     */
    public function addResponsible(Person $person)
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
     * @param Person $person
     */
    public function removeResponsible(Person $person)
    {
        $this->responsibles->removeElement($person);
        $person->getMinistries()->removeElement($this);
    }

    /**
     * Get responsible persons
     *
     * @return ArrayCollection|Person[]
     */
    public function getResponsibles()
    {
        return $this->responsibles;
    }
}
