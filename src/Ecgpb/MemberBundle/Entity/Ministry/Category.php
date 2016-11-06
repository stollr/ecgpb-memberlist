<?php

namespace Ecgpb\MemberBundle\Entity\Ministry;

/**
 * Ecgpb\MemberBundle\Entity\Ministry\Category
 */
class Category
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
     * @var \Ecgpb\MemberBundle\Entity\Person
     */
    private $responsible;

    /**
     * @var integer
     */
    private $position;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $ministries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ministries = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return MinistryCategory
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

    public function getResponsible()
    {
        return $this->responsible;
    }

    public function setResponsible(\Ecgpb\MemberBundle\Entity\Person $responsible)
    {
        $this->responsible = $responsible;
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
     * Add ministries
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry $ministry
     * @return Category
     */
    public function addMinistry(\Ecgpb\MemberBundle\Entity\Ministry $ministry)
    {
        $this->ministries[] = $ministry;
        $ministry->setCategory($this);
        return $this;
    }

    /**
     * Remove ministries
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry $ministries
     */
    public function removeMinistry(\Ecgpb\MemberBundle\Entity\Ministry $ministries)
    {
        $this->ministries->removeElement($ministries);
    }

    /**
     * Get ministries
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMinistries()
    {
        return $this->ministries;
    }
}
