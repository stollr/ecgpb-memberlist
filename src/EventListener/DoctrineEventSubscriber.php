<?php

namespace App\EventListener;

use App\Entity\Address;
use App\Entity\Person;
use App\Helper\PersonHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(Events::preUpdate)]
#[AsDoctrineListener(Events::postFlush)]
#[AsDoctrineListener(Events::postRemove)]
class DoctrineEventSubscriber
{
    private array $fileRenames = [];

    public function __construct(
        private readonly PersonHelper $personHelper,
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getEntity();

        if ($entity instanceof Address) {
            $changeSet = $args->getEntityChangeSet();
            if (isset($changeSet['familyName'])) {
                foreach ($entity->getPersons() as $person) {
                    $formattedDob = $person->getDob() ? $person->getDob()->format('Y-m-d') : '0000-00-00';

                    $oldFotoFilename = $args->getOldValue('familyName') . '_'
                        . $person->getFirstname() . '_'
                        . $formattedDob . '.jpg';

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
                if (array_key_exists('dob', $changeSet)) {
                    $dob = $args->getOldValue('dob');
                }

                $formattedDob = $dob ? $dob->format('Y-m-d') : '0000-00-00';
                $oldFotoFilename = $familyName . '_' . $firstname . '_' . $formattedDob . '.jpg';
                $newFotoFilename = $this->personHelper->getPersonPhotoFilename($entity);

                $this->schedulePersonPhotoFilenameChange($oldFotoFilename, $newFotoFilename);
            }
        }
    }
    
    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->fileRenames as $rename) {
            if (file_exists($rename[0])) {
                rename($rename[0], $rename[1]);
            }
        }

        $this->fileRenames = [];
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        if ($args->getObject() instanceof Person) {
            $this->personHelper->removePersonPhoto($args->getObject());
        }
    }

    private function schedulePersonPhotoFilenameChange($oldFotoFilename, $newFotoFilename): void
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
