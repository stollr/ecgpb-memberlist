<?php

namespace Ecgpb\MemberBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ecgpb\MemberBundle\Entity\Person;

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

    public function getDisplayName()
    {
        $gender = $this->getGender() == Person::GENDER_FEMALE ? 'Female' : 'Male';
        return $gender . ' Group' . ' ' . $this->getNumber();
    }

    public function getAvgAge()
    {
        $years = 0;
        foreach ($this->getPersons() as $person) {
            $years += date('Y') - $person->getDob()->format('Y');
        }
        return $years / count($this->getPersons());
    }

    public function getVarianceOfAge()
    {
        $avg = $this->getAvgAge();
        $powedSum = 0;
        $sum = 0;
        foreach ($this->getPersons() as $person) {
            $year = date('Y') - $person->getDob()->format('Y');
            $powedSum += pow($year - $avg, 2);
            $sum = $year;
        }
        return $powedSum / $sum;
    }
}
