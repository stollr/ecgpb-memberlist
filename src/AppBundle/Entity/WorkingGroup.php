<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\Person;

/**
 * AppBundle\Entity\WorkingGroup
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
     * @var \AppBundle\Entity\Person
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
     * @param \AppBundle\Entity\Person $person
     * @return WorkingGroup
     */
    public function addPerson(\AppBundle\Entity\Person $person)
    {
        if (!$this->persons->contains($person)) {
            $this->persons->add($person);
        }

        $person->setWorkingGroup($this);

        return $this;
    }

    /**
     * Remove persons
     *
     * @param \AppBundle\Entity\Person $person
     */
    public function removePerson(\AppBundle\Entity\Person $person)
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
     * @param \AppBundle\Entity\Person $leader
     * @return WorkingGroup
     */
    public function setLeader(\AppBundle\Entity\Person $leader = null)
    {
        $this->leader = $leader;

        $leader->setWorkingGroup($this);

        return $this;
    }

    /**
     * Get leader
     *
     * @return \AppBundle\Entity\Person 
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
        $currentYear = date('Y');
        $years = 0;
        $numberOfPersons = 0;

        if ($this->getLeader()) {
            $years += $currentYear - $this->getLeader()->getDob()->format('Y');
            $numberOfPersons++;
        }

        foreach ($this->getPersons() as $person) {
            if (!$this->getLeader() || $person->getId() != $this->getLeader()->getId()) {
                // The leader should not be counted again
                $years += $currentYear - $person->getDob()->format('Y');
                $numberOfPersons++;
            }
        }

        if (0 === $numberOfPersons) {
            return 0;
        }

        return $years / $numberOfPersons;
    }

    public function getVarianceOfAge()
    {
        $avg = $this->getAvgAge();
        $powedSum = 0;
        $numberOfPersons = 0;

        if ($this->getLeader()) {
            $year = date('Y') - $this->getLeader()->getDob()->format('Y');
            $powedSum += pow($year - $avg, 2);
            $numberOfPersons++;
        }

        foreach ($this->getPersons() as $person) {
            if (!$this->getLeader() || $person->getId() != $this->getLeader()->getId()) {
                // The leader should not be counted again
                $year = date('Y') - $person->getDob()->format('Y');
                $powedSum += pow($year - $avg, 2);
                $numberOfPersons++;
            }
        }
        return $powedSum / $numberOfPersons;
    }

    public function getStandardDeviationOfAge()
    {
        return sqrt($this->getVarianceOfAge());
    }
}
