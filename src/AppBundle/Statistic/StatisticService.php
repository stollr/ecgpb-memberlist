<?php

namespace AppBundle\Statistic;

use Doctrine\ORM\Query;
use Symfony\Bridge\Doctrine\RegistryInterface;
use AppBundle\Entity\Person;
use AppBundle\Statistic\PersonStatistics;

/**
 * AppBundle\Statistic\StatisticService
 *
 * @author naitsirch
 */
class StatisticService
{
    private $doctrine;
    private $statistics;

    public function __construct(RegistryInterface $doctrine)
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
            $repo = $this->doctrine->getRepository('EcgpbMemberBundle:Person');
            $qb = $repo->createQueryBuilder('person'); /* @var $qb \Doctrine\ORM\QueryBuilder */
            $qb->select('person');

            $now = new \DateTime();
            $ageSum = 0;
            $total = 0;
            $femaleTotal = 0;
            $atLeast65YearsOld = 0;
            $atMaximum25YearsOld = 0;
            foreach ($qb->getQuery()->iterate(null, Query::HYDRATE_ARRAY) as $person) {
                $age = $person[0]['dob']->diff($now); /* @var $age \DateInterval */
                $ageSum += $age->y + ($age->m / 12);
                $total++;
                if (Person::GENDER_FEMALE === $person[0]['gender']) {
                    $femaleTotal++;
                }
                if ($age->y >= 65) {
                    $atLeast65YearsOld++;
                } else if ($age->y < 26) {
                    $atMaximum25YearsOld++;
                }
            }
            $this->statistics = new PersonStatistics(
                $total,             // total number of members
                $femaleTotal,       // number of female members
                $atLeast65YearsOld,
                $atMaximum25YearsOld,
                $ageSum / $total    // average age
            );
        }
        return $this->statistics;
    }
}
