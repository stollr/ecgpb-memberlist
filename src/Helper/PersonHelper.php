<?php

namespace App\Helper;

use App\Entity\Person;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * App\Helper\PersonHelper
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

    /**
     * Get the path to the directory where member photos are stored.
     */
    public function getPersonPhotoPath(): string
    {
        return $this->parameters['ecgpb.members.photo_path'];
    }

    /**
     * Get the filename (without path) to the person's photo.
     */
    public function getPersonPhotoFilename(Person $person): string
    {
        return $person->getAddress()->getFamilyName() . '_'
            . $person->getFirstname() . '_'
            . $person->getDob()->format('Y-m-d') . '.jpg'
        ;
    }

    public function getPersonPhotoPathOptimized(): string
    {
        return $this->parameters['ecgpb.members.photo_path_optimized'];
    }

    public function getPersonIdsWithoutPhoto(): array
    {
        static $ids = null;

        if (is_array($ids)) {
            return $ids;
        }

        $repo = $this->doctrine->getRepository(Person::class);
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
