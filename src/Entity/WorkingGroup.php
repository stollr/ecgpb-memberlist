<?php

namespace App\Entity;

use App\Entity\Person;
use App\Repository\WorkingGroupRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * App\Entity\WorkingGroup
 */
#[ORM\Entity(repositoryClass: WorkingGroupRepository::class)]
#[ORM\Table(name: 'working_group')]
#[ORM\UniqueConstraint(name: 'uniqueGenderNumber', columns: ['gender', 'number'])]
class WorkingGroup
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $number = null;

    #[ORM\Column(type: 'string', length: 1)]
    private ?string $gender = null;

    /**
     * @var Collection<int, Person>
     */
    #[ORM\OneToMany(targetEntity: Person::class, mappedBy: 'workingGroup')]
    private Collection $persons;

    #[ORM\OneToOne(targetEntity: Person::class, inversedBy: 'leaderOf')]
    #[ORM\JoinColumn(name: 'leader_person_id', nullable: true)]
    private ?Person $leader = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->persons = new ArrayCollection();
    }

    /**
     * Get id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return $this
     */
    public function setGender(string $gender): static
    {
        if ($this->getId() > 0 && $this->getGender() != $gender) {
            throw new \RuntimeException('It is not possible to change the gender of a working group.');
        }
        $this->gender = $gender;
        return $this;
    }

    /**
     * Get gender
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * Add persons
     *
     * @return $this
     */
    public function addPerson(Person $person): static
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
     */
    public function removePerson(Person $person)
    {
        $this->persons->removeElement($person);
        $person->setWorkingGroup(null);
    }

    /**
     * Get persons
     *
     * @return Collection<int, Person>
     */
    public function getPersons(): Collection
    {
        return $this->persons;
    }

    /**
     * Set leader
     *
     * @return $this
     */
    public function setLeader(?Person $leader): static
    {
        $this->leader = $leader;

        if ($leader) {
            $leader->setWorkingGroup($this);
        }

        return $this;
    }

    /**
     * Get leader
     */
    public function getLeader(): ?Person
    {
        return $this->leader;
    }

    /**
     * Set number
     *
     * @return $this
     */
    public function setNumber(int $number): static
    {
        $this->number = $number;
        return $this;
    }

    /**
     * Get number
     */
    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getDisplayName(TranslatorInterface $translator = null): string
    {
        $gender = $this->getGender() == Person::GENDER_FEMALE ? 'Female' : 'Male';

        if ($translator) {
            return $translator->trans($gender . ' Group') . ' ' . $this->getNumber();
        }

        return $gender . ' Group' . ' ' . $this->getNumber();
    }

    public function getAvgAge(): float
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
            return 0.0;
        }

        return $years / $numberOfPersons;
    }

    public function getVarianceOfAge(): float
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

    public function getStandardDeviationOfAge(): float
    {
        return sqrt($this->getVarianceOfAge());
    }
}
