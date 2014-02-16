<?php

namespace Ecgpb\MemberBundle\Helper;

use Ecgpb\MemberBundle\Entity\Person;

/**
 * Ecgpb\MemberBundle\Helper\PersonHelper
 *
 * @author naitsirch
 */
class PersonHelper
{
    private $doctrine;
    private $parameters;

    public function __construct(array $parameters)
    {
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
}
