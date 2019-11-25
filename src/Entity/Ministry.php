<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Ministry\Category;
use AppBundle\Entity\Ministry\ResponsibleAssignment;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * AppBundle\Entity\Ministry
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
     * @ORM\Column(type="string", length=40)
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Ministry\Category", inversedBy="ministries")
     * @ORM\JoinColumn(name="category_id", nullable=false, onDelete="CASCADE")
     *
     * @var Category
     */
    private $category;

    /**
     * @ORM\OneToMany(targetEntity="AppBundle\Entity\Ministry\ResponsibleAssignment", mappedBy="ministry", cascade={"persist", "remove"})
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var \Doctrine\Common\Collections\Collection|ResponsibleAssignment[]
     */
    private $responsibleAssignments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->responsibleAssignments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add responsibleAssignments
     *
     * @param ResponsibleAssignment $responsibleAssignment
     * @return Ministry
     */
    public function addResponsibleAssignment(ResponsibleAssignment $responsibleAssignment)
    {
        $this->responsibleAssignments[] = $responsibleAssignment;
        $responsibleAssignment->setMinistry($this);
        return $this;
    }

    /**
     * Remove responsibleAssignments
     *
     * @param ResponsibleAssignment $responsibleAssignment
     */
    public function removeResponsibleAssignment(ResponsibleAssignment $responsibleAssignment)
    {
        $this->responsibleAssignments->removeElement($responsibleAssignment);
    }

    /**
     * Get responsibleAssignments
     *
     * @return \Doctrine\Common\Collections\Collection|ResponsibleAssignment[]
     */
    public function getResponsibleAssignments()
    {
        return $this->responsibleAssignments;
    }
}
