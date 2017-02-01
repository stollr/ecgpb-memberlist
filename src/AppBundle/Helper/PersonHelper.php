<?php

namespace AppBundle\Helper;

use AppBundle\Entity\Person;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * AppBundle\Helper\PersonHelper
 *
 * @author naitsirch
 */
class PersonHelper
{
    private $doctrine;
    private $parameters;

    public function __construct(RegistryInterface $doctrine, array $parameters)
    {
        $this->doctrine = $doctrine;
        $this->parameters = $parameters;
    }

    public function getPersonPhotoPath()
    {
        return $this->parameters['ecgpb.members.photo_path'];
    }

    public function getPersonPhotoFilename(Person $person)
    {
        return $person->getAddress()->getFamilyName() . '_'
            . $person->getFirstname() . '_'
            . $person->getDob()->format('Y-m-d') . '.jpg'
        ;
    }

    public function getPersonPhotoPathOptimized()
    {
        return $this->parameters['ecgpb.members.photo_path_optimized'];
    }

    public function getPersonIdsWithoutPhoto()
    {
        static $ids = null;

        if (is_array($ids)) {
            return $ids;
        }

        $repo = $this->doctrine->getRepository('EcgpbMemberBundle:Person');
        $personDatas = $repo->createQueryBuilder('person')
            ->select('person.id', 'person.firstname', 'address.familyName', 'person.dob')
            ->join('person.address', 'address')
            ->getQuery()
            ->getResult(\PDO::FETCH_ASSOC)
        ;

        $ids = array();
        foreach ($personDatas as $personData) {
            $filename = $personData['familyName'] . '_' . $personData['firstname'] . '_' . $personData['dob']->format('Y-m-d') . '.jpg';

            if (!file_exists($this->getPersonPhotoPath() . '/' . $filename)) {
                $ids[] = $personData['id'];
            }
        }

        return $ids;
    }
}
