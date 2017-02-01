<?php

namespace Ecgpb\MemberBundle\Entity\Ministry;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ResponsibleAssignment
 */
class ResponsibleAssignment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Ecgpb\MemberBundle\Entity\Ministry
     */
    private $ministry;

    /**
     * @var \Ecgpb\MemberBundle\Entity\Person
     */
    private $person;

    /**
     * @var \Ecgpb\MemberBundle\Entity\Ministry\Group
     */
    private $group;


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
     * @param \Ecgpb\MemberBundle\Entity\Ministry $ministry
     * @return ResponsibleAssignment
     */
    public function setMinistry(\Ecgpb\MemberBundle\Entity\Ministry $ministry)
    {
        $this->ministry = $ministry;

        return $this;
    }

    /**
     * Get ministry
     *
     * @return \Ecgpb\MemberBundle\Entity\Ministry 
     */
    public function getMinistry()
    {
        return $this->ministry;
    }

    /**
     * Set person
     *
     * @param \Ecgpb\MemberBundle\Entity\Person $person
     * @return Assignment
     */
    public function setPerson(\Ecgpb\MemberBundle\Entity\Person $person = null)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * Get person
     *
     * @return \Ecgpb\MemberBundle\Entity\Person 
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set group
     *
     * @param \Ecgpb\MemberBundle\Entity\Ministry\Group $group
     * @return ResponsibleAssignment
     */
    public function setGroup(\Ecgpb\MemberBundle\Entity\Ministry\Group $group = null)
    {
        $this->group = $group;
        return $this;
    }

    /**
     * Get group
     *
     * @return \Ecgpb\MemberBundle\Entity\Ministry\Group 
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @Assert\IsTrue(message="A ministry's contact assignment cannot be assigned to a person and at the same time to a group.")
     * @return boolean
     */
    public function validateGroupAndPersonNotSetBoth()
    {
        return !($this->getPerson() && $this->getGroup());
    }
}
