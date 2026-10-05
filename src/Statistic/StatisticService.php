<?php

namespace App\Statistic;

use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Person;
use App\Statistic\PersonStatistics;

/**
 * App\Statistic\StatisticService
 *
 * @author naitsirch
 */
class StatisticService
{
    private $doctrine;
    private $statistics;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Returns the statistics about our members.
     * @return PersonStatistics
     */
    public function getPersonStatistics()
    {
        if (!$this->statistics) {
            $repo = $this->doctrine->getRepository(Person::class);

            $now = new \DateTime();
            $ageSum = 0;
            $total = 0;
            $totalWithDob = 0;
            $femaleTotal = 0;
            $atLeast65YearsOld = 0;
            $atMost25YearsOld = 0;
            $numberPerYearOfBirth = [];
            $numberPerAge = [];

            foreach ($repo->findAll() as $person) {
                /** @var Person $person */
                $total++;

                if (Person::GENDER_FEMALE === $person->getGender()) {
                    $femaleTotal++;
                }

                if (!$person->getDob()) {
                    continue;
                }

                $totalWithDob++;
                $age = $person->getDob()->diff($now); /* @var $age \DateInterval */
                $ageSum += $age->y + ($age->m / 12);

                if ($age->y >= 65) {
                    $atLeast65YearsOld++;
                } else if ($age->y < 26) {
                    $atMost25YearsOld++;
                }

                $numberPerAge[$age->y] = 1 + (isset($numberPerAge[$age->y]) ? $numberPerAge[$age->y] : 0);

                $yearOfBirth = $person->getDob()->format('Y');
                $numberPerYearOfBirth[$yearOfBirth] = 1 + ($numberPerYearOfBirth[$yearOfBirth] ?? 0);
            }

            ksort($numberPerYearOfBirth, SORT_NUMERIC);
            ksort($numberPerAge, SORT_NUMERIC);

            $this->statistics = new PersonStatistics(
                $total,             // total number of members
                $femaleTotal,       // number of female members
                $atLeast65YearsOld,
                $atMost25YearsOld,
                $ageSum / $totalWithDob,    // average age
                $numberPerYearOfBirth,
                $numberPerAge
            );
        }
        return $this->statistics;
    }
}
