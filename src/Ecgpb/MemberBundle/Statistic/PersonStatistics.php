<?php

namespace Ecgpb\MemberBundle\Statistic;

/**
 * Ecgpb\MemberBundle\Statistic\PersonStatistics
 *
 * @author naitsirch
 */
class PersonStatistics
{
    /**
     * Total number of members.
     * @var int
     */
    private $total;

    /**
     * Number of female members.
     * @var int
     */
    private $femaleTotal;

    /**
     * Number of members who are at least 65 years old.
     * @var int
     */
    private $atLeast65YearsOld;

    /**
     * Number of members who are at maximum 25 years old.
     * @var int
     */
    private $atMaximum25YearsOld;
    
    /**
     * Average age over all members
     * @var float
     */
    private $averageAge;

    public function __construct(
        $total,
        $femaleTotal,
        $atLeast65YearsOld,
        $atMaximum25YearsOld,
        $averageAge
    ) {
        $this->total = $total;
        $this->femaleTotal = $femaleTotal;
        $this->atLeast65YearsOld = $atLeast65YearsOld;
        $this->atMaximum25YearsOld = $atMaximum25YearsOld;
        $this->averageAge = $averageAge;
    }

    /**
     * Get the total number of members.
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * Get the number of female members.
     * @return type
     */
    public function getFemaleTotal()
    {
        return $this->femaleTotal;
    }


    public function getMaleTotal()
    {
        return $this->getTotal() - $this->getFemaleTotal();
    }

    /**
     * Get the number of members who are at least 65 years old.
     * @return int
     */
    public function getAtLeast65YearsOld()
    {
        return $this->atLeast65YearsOld;
    }

    /**
     * Number of members who are at maximum 25 years old.
     * @return int
     */
    public function getAtMaximum25YearsOld()
    {
        return $this->atMaximum25YearsOld;
    }

    /**
     * Get the average age over all members.
     * @return float
     */
    public function getAverageAge()
    {
        return $this->averageAge;
    }
}
