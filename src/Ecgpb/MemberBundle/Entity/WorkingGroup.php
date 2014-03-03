<?php

namespace Ecgpb\MemberBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ecgpb\MemberBundle\Entity\WorkingGroup
 */
class WorkingGroup
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $gender;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $persons;

    /**
     * @var \Ecgpb\MemberBundle\Entity\Person
     */
    private $leader;

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
     * Set gender
     *
     * @param string $gender
     * @return WorkingGroup
     */
    public function setGender($gender)
    {
        if ($this->getId() > 0 && $this->getGender() != $gender) {
            throw new \RuntimeException('It is not possible to change the gender of a working group.');
        }
        $this->gender = $gender;
        return $this;
    }

    /**
     * Get gender
     *
     * @return string 
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Add persons
     *
     * @param \Ecgpb\MemberBundle\Entity\Person $person
     * @return WorkingGroup
     */
    public function addPerson(\Ecgpb\MemberBundle\Entity\Person $person)
    {
        $this->persons[] = $person;
        $person->setWorkingGroup($this);
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
        $person->setWorkingGroup(null);
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

    /**
     * Set leader
     *
     * @param \Ecgpb\MemberBundle\Entity\Person $leader
     * @return WorkingGroup
     */
    public function setLeader(\Ecgpb\MemberBundle\Entity\Person $leader = null)
    {
        $this->leader = $leader;
        return $this;
    }

    /**
     * Get leader
     *
     * @return \Ecgpb\MemberBundle\Entity\Person 
     */
    public function getLeader()
    {
        return $this->leader;
    }

    /**
     * Set number
     *
     * @param integer $number
     * @return WorkingGroup
     */
    public function setNumber($number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }
}
