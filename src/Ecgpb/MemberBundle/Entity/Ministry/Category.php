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

    /**
     * Add ministries
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry $ministries
     * @return Category
     */
    public function addMinistry(\Ecgpb\MemberBundle\Entity\Ministry $ministries)
    {
        $this->ministries[] = $ministries;
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
