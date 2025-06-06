<?php

namespace App\Entity;

use App\Entity\Address;
use App\Entity\Ministry;
use App\Entity\WorkingGroup;
use App\Repository\PersonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use libphonenumber\PhoneNumber;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * App\Entity\Person
 */
#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[ORM\Table(name: 'person')]
#[Gedmo\Loggable]
class Person
{
    /**
     * Import timestampable behavior.
     */
    use TimestampableEntity;

    const GENDER_MALE = 'm';
    const GENDER_FEMALE = 'f';

    /**
     * Persons whose working status depends on their age.
     */
    const WORKER_STATUS_UNTIL_AGE_LIMIT = 1;

    /**
     * People younger than the age limit years, but not able to work.
     */
    const WORKER_STATUS_INVALID = 2;

    /**
     * People having other ministries like deacons or elders.
     */
    const WORKER_STATUS_OTHER_MINISTRIES = 3;

    /**
     * People not able to work, because of their residence.
     */
    const WORKER_STATUS_UNABLE_RESIDENCE = 4;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?int $id = null;

    /**
     * Last name can be empty. In that case the last name is taken from
     * address entity (family name)
     */
    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    #[Assert\Length(max: 30)]
    #[Gedmo\Versioned]
    private ?string $lastname = null;

    #[ORM\Column(type: 'string', length: 30)]
    #[Assert\Length(max: 30)]
    #[Gedmo\Versioned]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?string $firstname = null;

    /**
     * Date of birth
     */
    #[ORM\Column(type: 'date', nullable: true)]
    #[Gedmo\Versioned]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?\DateTime $dob = null;

    /**
     * Gender ('m' or 'f')
     */
    #[ORM\Column(type: 'string', length: 1)]
    #[Gedmo\Versioned]
    private ?string $gender = null;

    #[ORM\Column(type: 'phone_number', nullable: true)]
    #[Gedmo\Versioned]
    private ?PhoneNumber $mobile = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\Email]
    #[Assert\Length(max: 100)]
    #[Gedmo\Versioned]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Address::class, inversedBy: 'persons', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'address_id', nullable: false)]
    #[Gedmo\Versioned]
    #[Serializer\Groups(['MinistryCategoryListing'])]
    private ?Address $address = null;

    #[ORM\ManyToOne(targetEntity: 'WorkingGroup', inversedBy: 'persons')]
    #[ORM\JoinColumn(name: 'working_group_id', nullable: true, onDelete: 'SET NULL')]
    #[Gedmo\Versioned]
    private ?WorkingGroup $workingGroup = null;

    /**
     * Defines whether person is able to work or not.
     * See class constants self::WORKER_STATUS_*
     */
    #[ORM\Column(name: 'worker_status', type: 'smallint', nullable: false)]
    #[Gedmo\Versioned]
    private int $workerStatus = self::WORKER_STATUS_UNTIL_AGE_LIMIT;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notice = null;

    #[ORM\Column(type: 'integer', nullable: true, unique: true)]
    private ?int $churchToolsId = null;

    #[ORM\OneToOne(targetEntity: 'WorkingGroup', mappedBy: 'leader')]
    private ?WorkingGroup $leaderOf = null;

    #[ORM\ManyToMany(targetEntity: 'Ministry', mappedBy: 'responsibles', cascade: ['remove'])]
    private Collection $ministries;

    /**
     * Constructor
     */
    public function __construct(?Address $address = null)
    {
        $address && $this->setAddress($address);
        $this->ministries = new ArrayCollection();
    }

    /**
     * Get id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    /**
     * Get the firstname, name prefix and lastname concated.
     *
     * @return string
     */
    public function getFirstnameAndLastname(): string
    {
        return $this->getFirstname() . ' '
            . ($this->getAddress()->getNamePrefix() ? $this->getAddress()->getNamePrefix() . ' ' : '')
            . ($this->getLastname() ?: $this->getAddress()->getFamilyName());
    }

    /**
     * Get the display name (firstname and lastname) with year of birth.
     */
    public function getDisplayNameDob(): string
    {
        $year = $this->getDob() ? $this->getDob()->format('Y') : '0000';
        return $this->getFirstnameAndLastname() . ' (' . $year . ')';
    }

    /**
     * Get the concated lastname, firstname and name prefix.
     */
    public function getLastnameAndFirstname(): string
    {
        return ($this->getLastname() ?: $this->getAddress()->getFamilyName()) . ', ' . $this->getFirstname()
            . ($this->getAddress()->getNamePrefix() ? ' ' . $this->getAddress()->getNamePrefix() : '');
    }

    /**
     * Get the concated lastname, firstname, name prefix and year of birth.
     *
     * @return string
     */
    public function getLastnameFirstnameAndDob(): string
    {
        $year = $this->getDob() ? $this->getDob()->format('Y') : '0000';
        return $this->getLastnameAndFirstname() . ' (' . $year . ')';
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return Person
     */
    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * Set the date of birth
     *
     * @return $this
     */
    public function setDob(?\DateTime $dob): self
    {
        $this->dob = $dob;

        return $this;
    }

    /**
     * Get the date of birth
     */
    public function getDob(): ?\DateTime
    {
        return $this->dob;
    }

    /**
     * Get the current age of the person.
     */
    public function getAge(): ?int
    {
        if (!$this->dob) {
            return null;
        }

        $diff = $this->dob->diff(new \DateTime(), true);
        /* @var $diff \DateInterval */

        return $diff->y;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * Set mobile
     *
     * @return $this
     */
    public function setMobile(?PhoneNumber $mobile): self
    {
        if ($this->mobile && $mobile && $this->mobile->equals($mobile)) {
            return $this;
        }

        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     */
    public function getMobile(): ?PhoneNumber
    {
        return $this->mobile;
    }

    /**
     * Set email
     *
     * @return $this
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set address
     *
     * @return $this
     */
    public function setAddress(Address $address): self
    {
        $this->address = $address;
        if (!$address->getPersons()->contains($this)) {
            $address->getPersons()->add($this);
        }
        return $this;
    }

    /**
     * Get address
     */
    public function getAddress(): ?Address
    {
        return $this->address;
    }

    /**
     * Set workingGroup
     *
     * @return $this
     */
    public function setWorkingGroup(WorkingGroup $workingGroup = null): self
    {
        if ($this->gender && $workingGroup && $this->gender !== $workingGroup->getGender()) {
            throw new \InvalidArgumentException('This person is not compatible to the passed working group.');
        }

        $this->workingGroup = $workingGroup;

        if ($workingGroup && !$workingGroup->getPersons()->contains($this)) {
            $workingGroup->getPersons()->add($this);
        }

        return $this;
    }

    /**
     * Get workingGroup
     *
     * @return WorkingGroup
     */
    public function getWorkingGroup(): ?WorkingGroup
    {
        return $this->workingGroup;
    }

    public function getWorkerStatus(): int
    {
        return $this->workerStatus;
    }

    public function setWorkerStatus(int $workerStatus): self
    {
        $allStatus = self::getAllWorkerStatus();
        if (!isset($allStatus[$workerStatus])) {
            throw new \InvalidArgumentException('Given worker status is invalid.');
        }
        $this->workerStatus = $workerStatus;
        return $this;
    }

    /**
     * Get the notice.
     *
     * @return string|null
     */
    public function getNotice(): ?string
    {
        return $this->notice;
    }

    /**
     * Set the notice
     */
    public function setNotice(?string $notice)
    {
        $this->notice = $notice;
    }

    public function getChurchToolsId(): ?int
    {
        return $this->churchToolsId;
    }

    public function setChurchToolsId(int $churchToolsId): static
    {
        if ($this->churchToolsId && $this->churchToolsId !== $churchToolsId) {
            throw new \LogicException('The ChurchTools ID cannot be changed.');
        }

        $this->churchToolsId = $churchToolsId;
        return $this;
    }

    /**
     * @return array<int, string>
     */
    public static function getAllWorkerStatus(): array
    {
        return array(
            self::WORKER_STATUS_UNTIL_AGE_LIMIT => 'Until age limit',
            self::WORKER_STATUS_INVALID => 'Invalid/Sick',
            self::WORKER_STATUS_OTHER_MINISTRIES => 'Other Ministries',
            self::WORKER_STATUS_UNABLE_RESIDENCE => 'Residence far away',
        );
    }

    /**
     * Get the working group, whose leader is this person.
     */
    public function getLeaderOf(): ?WorkingGroup
    {
        return $this->leaderOf;
    }

    /**
     * This method exists only for documentation.
     *
     * @throws \RuntimeException
     */
    public function setLeaderOf(WorkingGroup $workingGroup = null): void
    {
        throw new \RuntimeException('The leading person of a working group cannot be changed within person entity.');
    }

    /**
     * @return Ministry[]
     */
    public function getMinistries(): Collection
    {
        return $this->ministries;
    }

    public function isMale(): bool
    {
        return $this->gender === self::GENDER_MALE;
    }
}
