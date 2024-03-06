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
            $qb = $repo->createQueryBuilder('person'); /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb->select('person');

            $now = new \DateTime();
            $ageSum = 0;
            $total = 0;
            $totalWithDob = 0;
            $femaleTotal = 0;
            $atLeast65YearsOld = 0;
            $atMost25YearsOld = 0;
            $numberPerYearOfBirth = [];
            $numberPerAge = [];

            foreach ($qb->getQuery()->iterate(null, Query::HYDRATE_ARRAY) as $person) {
                $total++;

                if (Person::GENDER_FEMALE === $person[0]['gender']) {
                    $femaleTotal++;
                }

                if (!isset($person[0]['dob'])) {
                    continue;
                }

                $totalWithDob++;
                $age = $person[0]['dob']->diff($now); /* @var $age \DateInterval */
                $ageSum += $age->y + ($age->m / 12);

                if ($age->y >= 65) {
                    $atLeast65YearsOld++;
                } else if ($age->y < 26) {
                    $atMost25YearsOld++;
                }

                $numberPerAge[$age->y] = 1 + (isset($numberPerAge[$age->y]) ? $numberPerAge[$age->y] : 0);

                $yearOfBirth = $person[0]['dob']->format('Y');
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
