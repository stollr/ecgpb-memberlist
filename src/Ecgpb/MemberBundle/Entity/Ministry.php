<?php

namespace Ecgpb\MemberBundle\Entity;

use Ecgpb\MemberBundle\Entity\Ministry\Category;

/**
 * Ecgpb\MemberBundle\Entity\Ministry
 */
class Ministry
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $contactAssignments;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $responsibleAssignments;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->contactAssignments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Add contactAssignments
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry\ContactAssignment $contactAssignment
     * @return Ministry
     */
    public function addContactAssignment(\Ecgpb\MemberBundle\Entity\Ministry\ContactAssignment $contactAssignment)
    {
        $this->contactAssignments[] = $contactAssignment;
        $contactAssignment->setMinistry($this);
        return $this;
    }

    /**
     * Remove contactAssignments
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry\ContactAssignment $contactAssignment
     */
    public function removeContactAssignment(\Ecgpb\MemberBundle\Entity\Ministry\ContactAssignment $contactAssignment)
    {
        $this->contactAssignments->removeElement($contactAssignment);
    }

    /**
     * Get contactAssignments
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getContactAssignments()
    {
        return $this->contactAssignments;
    }

    /**
     * Add responsibleAssignments
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment
     * @return Ministry
     */
    public function addResponsibleAssignment(\Ecgpb\MemberBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment)
    {
        $this->responsibleAssignments[] = $responsibleAssignment;
        $responsibleAssignment->setMinistry($this);
        return $this;
    }

    /**
     * Remove responsibleAssignments
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment
     */
    public function removeResponsibleAssignment(\Ecgpb\MemberBundle\Entity\Ministry\ResponsibleAssignment $responsibleAssignment)
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
