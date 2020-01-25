<?php

namespace App\Entity\Ministry;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * App\Entity\Ministry\Category
 *
 * @ORM\Entity(repositoryClass="App\Repository\Ministry\CategoryRepository")
 * @ORM\Table(name="ministry_category")
 */
class Category
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
     * @ORM\Column(type="string", length=40)
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var string
     */
    private $name;

    /**
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(name="responsible_person_id", nullable=true, onDelete="SET NULL")
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var \App\Entity\Person
     */
    private $responsible;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     *
     * @Groups({"MinistryCategoryListing"})
     *
     * @var integer
     */
    private $position;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Ministry", mappedBy="category", cascade={"persist"}, orphanRemoval=true)
     *
     * @Groups({"MinistryCategoryListing"})
     *
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

    public function getResponsible()
    {
        return $this->responsible;
    }

    public function setResponsible(\App\Entity\Person $responsible)
    {
        $this->responsible = $responsible;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
        return $this;
    }

    /**
     * Add ministries
     *
     * @param \App\Entity\Ministry $ministry
     * @return Category
     */
    public function addMinistry(\App\Entity\Ministry $ministry)
    {
        $this->ministries[] = $ministry;
        $ministry->setCategory($this);
        return $this;
    }

    /**
     * Remove ministries
     *
     * @param \App\Entity\Ministry $ministries
     */
    public function removeMinistry(\App\Entity\Ministry $ministries)
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
