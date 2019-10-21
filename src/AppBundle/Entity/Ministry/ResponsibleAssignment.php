<?php

namespace AppBundle\Entity\Ministry;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ResponsibleAssignment
 *
 * @ORM\Entity
 * @ORM\Table(name="ministry_assignment_responsible")
 */
class ResponsibleAssignment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Ministry", inversedBy="responsibleAssignments")
     * @ORM\JoinColumn(name="ministry_id", nullable=false, onDelete="CASCADE")
     *
     * @var \AppBundle\Entity\Ministry
     */
    private $ministry;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person", inversedBy="ministryResponsibleAssignments")
     * @ORM\JoinColumn(name="person_id", nullable=true, onDelete="CASCADE")
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var \AppBundle\Entity\Person
     */
    private $person;


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
     * Set ministry
     *
     * @param \AppBundle\Entity\Ministry $ministry
     * @return ResponsibleAssignment
     */
    public function setMinistry(\AppBundle\Entity\Ministry $ministry)
    {
        $this->ministry = $ministry;

        return $this;
    }

    /**
     * Get ministry
     *
     * @return \AppBundle\Entity\Ministry 
     */
    public function getMinistry()
    {
        return $this->ministry;
    }

    /**
     * Set person
     *
     * @param \AppBundle\Entity\Person $person
     * @return Assignment
     */
    public function setPerson(\AppBundle\Entity\Person $person = null)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * Get person
     *
     * @return \AppBundle\Entity\Person 
     */
    public function getPerson()
    {
        return $this->person;
    }
}
