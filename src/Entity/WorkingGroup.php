<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Person;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * App\Entity\WorkingGroup
 *
 * @ORM\Entity(repositoryClass="App\Repository\WorkingGroupRepository")
 * @ORM\Table(name="working_group", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="uniqueGenderNumber", columns={"gender", "number"})
 * })
 */
class WorkingGroup
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=1)
     *
     * @var string
     */
    private $gender;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Person", mappedBy="workingGroup")
     *
     * @var Collection
     */
    private $persons;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Person", inversedBy="leaderOf")
     * @ORM\JoinColumn(name="leader_person_id", nullable=true)
     *
     * @var Person|null
     */
    private $leader;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->persons = new ArrayCollection();
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
    public function setGender(string $gender)
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
     * @return string|null
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Add persons
     *
     * @param Person $person
     * @return WorkingGroup
     */
    public function addPerson(Person $person)
    {
        if ($this->gender !== $person->getGender()) {
            throw new InvalidArgumentException('This working group is not compatible to the passed person.');
        }

        if (!$this->persons->contains($person)) {
            $this->persons->add($person);
        }

        $person->setWorkingGroup($this);

        return $this;
    }

    /**
     * Remove persons
     *
     * @param Person $person
     */
    public function removePerson(Person $person)
    {
        $this->persons->removeElement($person);
        $person->setWorkingGroup(null);
    }

    /**
     * Get persons
     *
     * @return Collection
     */
    public function getPersons()
    {
        return $this->persons;
    }

    /**
     * Set leader
     *
     * @param Person|null $leader
     * @return WorkingGroup
     */
    public function setLeader(Person $leader = null)
    {
        $this->leader = $leader;

        if ($leader) {
            $leader->setWorkingGroup($this);
        }

        return $this;
    }

    /**
     * Get leader
     *
     * @return Person|null
     */
    public function getLeader()
    {
        return $this->leader;
    }

    /**
     * Set number
     *
     * @param int $number
     * @return WorkingGroup
     */
    public function setNumber(int $number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * Get number
     *
     * @return int|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    public function getDisplayName(TranslatorInterface $translator = null)
    {
        $gender = $this->getGender() == Person::GENDER_FEMALE ? 'Female' : 'Male';

        if ($translator) {
            return $translator->trans($gender . ' Group') . ' ' . $this->getNumber();
        }

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
