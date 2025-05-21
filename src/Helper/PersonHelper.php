<?php

namespace App\Helper;

use App\Entity\Person;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;

/**
 * App\Helper\PersonHelper
 *
 * @author stollr
 */
class PersonHelper
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly Filesystem $filesystem,
        private readonly array $parameters
    ) {
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
        $formattedDob = $person->getDob() ? $person->getDob()->format('Y-m-d') : '0000-00-00';

        $filename = $person->getAddress()->getFamilyName() . '_'
            . $person->getFirstname() . '_'
            . $formattedDob . '.jpg'
        ;

        return str_replace("'", '', $filename);
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
            $dob = $personData['dob'] ? $personData['dob']->format('Y-m-d') : '0000-00-00';
            $filename = $personData['familyName'] . '_' . $personData['firstname'] . '_' . $dob . '.jpg';
            $filename = str_replace(["'"], '', $filename);

            if (!file_exists($this->getPersonPhotoPath() . '/' . $filename)) {
                $ids[] = $personData['id'];
            }
        }

        return $ids;
    }

    public function removePersonPhoto(Person $person): void
    {
        $filename = $this->getPersonPhotoFilename($person);

        if ($this->filesystem->exists($filename)) {
            $this->filesystem->remove($filename);
        }
    }
}
