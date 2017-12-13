<?php

namespace AppBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use AppBundle\Entity\Address;
use AppBundle\Entity\Person;
use AppBundle\Helper\PersonHelper;

/**
 * AppBundle\Event\DoctrineEventSubscriber
 *
 * @author naitsirch
 */
class DoctrineEventSubscriber implements EventSubscriber
{
    /**
     * @var PersonHelper
     */
    private $personHelper;

    private $fileRenames = [];

    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
            'postFlush',
        );
    }

    /**
     * Set the person helper.
     * Called from service container.
     *
     * @param PersonHelper $personHelper
     * @return $this
     */
    public function setPersonHelper(PersonHelper $personHelper)
    {
        $this->personHelper = $personHelper;
        return $this;
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Address) {
            $changeSet = $args->getEntityChangeSet();
            if (isset($changeSet['familyName'])) {
                foreach ($entity->getPersons() as $person) {
                    $oldFotoFilename = $args->getOldValue('familyName') . '_'
                        . $person->getFirstname() . '_'
                        . $person->getDob()->format('Y-m-d') . '.jpg';

                    $newFotoFilename = $this->personHelper->getPersonPhotoFilename($person);

                    $this->schedulePersonPhotoFilenameChange($oldFotoFilename, $newFotoFilename);
                }
            }
        } else if ($entity instanceof Person) {
            $changeSet = $args->getEntityChangeSet();

            if (isset($changeSet['address']) || isset($changeSet['firstname']) || isset($changeSet['dob'])) {
                $familyName = $entity->getAddress()->getFamilyName();
                $firstname = $entity->getFirstname();
                $dob = $entity->getDob();

                if (isset($changeSet['address'])) {
                    $familyName = $args->getOldValue('address')->getFamilyName();
                }
                if (isset($changeSet['firstname'])) {
                    $firstname = $args->getOldValue('firstname');
                }
                if (isset($changeSet['dob'])) {
                    $dob = $args->getOldValue('dob');
                }

                $oldFotoFilename = $familyName . '_' . $firstname . '_' . $dob->format('Y-m-d') . '.jpg';
                $newFotoFilename = $this->personHelper->getPersonPhotoFilename($entity);

                $this->schedulePersonPhotoFilenameChange($oldFotoFilename, $newFotoFilename);
            }
        }
    }
    
    public function postFlush($args)
    {
        foreach ($this->fileRenames as $rename) {
            if (file_exists($rename[0])) {
                rename($rename[0], $rename[1]);
            }
        }

        $this->fileRenames = [];
    }

    private function schedulePersonPhotoFilenameChange($oldFotoFilename, $newFotoFilename)
    {
        $personHelper = $this->personHelper;

        if ($oldFotoFilename !== $newFotoFilename) {
            // The files should get renamed AFTER the changes have been persisted
            // int the post-flush event. Just for the case that something fails.
            $this->fileRenames[] = [
                $personHelper->getPersonPhotoPath() . '/' . $oldFotoFilename,
                $personHelper->getPersonPhotoPath() . '/' . $newFotoFilename,
            ];
        }
    }
}
