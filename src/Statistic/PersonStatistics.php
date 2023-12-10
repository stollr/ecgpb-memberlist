<?php

namespace App\Statistic;

/**
 * App\Statistic\PersonStatistics
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
    private $atMost25YearsOld;
    
    /**
     * Average age over all members
     * @var float
     */
    private $averageAge;

    /**
     * The number of persons per year of birth.
     * @var array For example: ['1965' => 4, '1982' => 7]
     */
    private $numberPerYearOfBirth;

    /**
     * The number of persons per age.
     * @var array For example: [22 => 4, 35 => 7]
     */
    private $numberPerAge;

    public function __construct(
        $total,
        $femaleTotal,
        $atLeast65YearsOld,
        $atMost25YearsOld,
        $averageAge,
        $numberPerYearOfBirth,
        $numberPerAge
    ) {
        $this->total = $total;
        $this->femaleTotal = $femaleTotal;
        $this->atLeast65YearsOld = $atLeast65YearsOld;
        $this->atMost25YearsOld = $atMost25YearsOld;
        $this->averageAge = $averageAge;
        $this->numberPerYearOfBirth = $numberPerYearOfBirth;
        $this->numberPerAge = $numberPerAge;
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
    public function getAtMost25YearsOld()
    {
        return $this->atMost25YearsOld;
    }

    /**
     * Get the average age over all members.
     * @return float
     */
    public function getAverageAge()
    {
        return $this->averageAge;
    }

    public function getNumberPerYearOfBirth()
    {
        return $this->numberPerYearOfBirth;
    }

    public function getNumberPerAge()
    {
        return $this->numberPerAge;
    }

    public function getHighestAge()
    {
        end($this->numberPerAge);
        $age = key($this->numberPerAge);
        reset($this->numberPerAge);

        return $age;
    }

    public function getLowestAge()
    {
        reset($this->numberPerAge);

        return key($this->numberPerAge);
    }

    /**
     * Get the number of members per age groups like:
     * 10-14 years, 15-19 => $interval = 5.
     *
     * @param int $interval Has to be > 0
     * @param ?int $startAge
     * @return array
     */
    public function getNumberPerAgeGroup(int $interval, ?int $startAge): array
    {
        if ($interval <= 0) {
            throw new \InvalidArgumentException('$interval has to be > 0');
        }
        $grouped = [];
        $intervalStart = 0;
        $intervalEnd = $startAge - 1;
        foreach ($this->numberPerAge as $age => $numberOfMembers) {
            if ($startAge && $age < $startAge) {
                $key = "<$startAge";
            } else {
                $intervalEnd = ceil($age / $interval) * $interval;
                $intervalStart = $intervalEnd - $interval + 1;
                $key = "$intervalStart-$intervalEnd";
            }

            $grouped[$key] = ($grouped[$key] ?? 0) + $numberOfMembers;
        }

        return $grouped;
    }
}
