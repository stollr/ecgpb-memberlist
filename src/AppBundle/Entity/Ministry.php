<?php

namespace AppBundle\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use AppBundle\Entity\Ministry\Category;

/**
 * AppBundle\Entity\Ministry
 */
class Ministry
{
    /**
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $id;

    /**
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $name;

    /**
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $description;

    /**
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $position;

    /**
     * @var Category
     */
    private $category;

    /**
     * @Groups({"MinistryCategoryListing"})
     *
     * @var \Doctrine\Common\Collections\Collection
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
     * @param \AppBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment
     * @return Ministry
     */
    public function addResponsibleAssignment(\AppBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment)
    {
        $this->responsibleAssignments[] = $responsibleAssignment;
        $responsibleAssignment->setMinistry($this);
        return $this;
    }

    /**
     * Remove responsibleAssignments
     *
     * @param \AppBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment
     */
    public function removeResponsibleAssignment(\AppBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment)
    {
        $this->responsibleAssignments->removeElement($responsibleAssignment);
    }

    /**
     * Get responsibleAssignments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getResponsibleAssignments()
    {
        return $this->responsibleAssignments;
    }
}
