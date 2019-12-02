<?php

namespace App\Exception;

use App\Entity\Person;

/**
 * App\Exception\WorkingGroupWithoutLeaderException
 *
 * @author naitsirch
 */
class WorkingGroupWithoutLeaderException extends \RuntimeException
{
    private $messageTemplate = 'The working group {number} of the {gender} does not have a leader, yet.';
    private $groupNumber;
    private $groupGender;
    
    public function __construct($groupNumber, $groupGender)
    {
        $this->groupNumber = $groupNumber;
        $this->groupGender = $groupGender;
        $msg = str_replace(
            array('{number}', '{gender}'),
            array($groupNumber, $groupGender == Person::GENDER_FEMALE ? 'women' : 'men'),
            $this->messageTemplate
        );
        parent::__construct($msg);
    }

    public function getMessageTemplate()
    {
        return $this->messageTemplate;
    }

    public function getGroupNumber()
    {
        return $this->groupNumber;
    }

    public function getGroupGender()
    {
        return $this->groupGender;
    }
}
