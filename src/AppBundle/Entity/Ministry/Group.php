<?php

namespace Ecgpb\MemberBundle\Entity\Ministry;

/**
 * Ecgpb\MemberBundle\Entity\Ministry\Group
 */
class Group
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
    private $persons;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->persons = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Group
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
     * Add persons
     *
     * @param \Ecgpb\MemberBundle\Entity\Person $person
     * @return Group
     */
    public function addPerson(\Ecgpb\MemberBundle\Entity\Person $person)
    {
        $this->persons[] = $person;
        $person->addMinistryGroup($this);
        return $this;
    }

    /**
     * Remove persons
     *
     * @param \Ecgpb\MemberBundle\Entity\Person $person
     */
    public function removePerson(\Ecgpb\MemberBundle\Entity\Person $person)
    {
        $this->persons->removeElement($person);
        $person->removeMinistryGroup($this);
    }

    /**
     * Get persons
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPersons()
    {
        return $this->persons;
    }
}
