<?php

namespace AppBundle\Event;

use Doctrine\Common\EventSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use AppBundle\Entity\Person;

/**
 * AppBundle\Event\DoctrineEventSubscriber
 *
 * @author naitsirch
 */
class DoctrineEventSubscriber implements EventSubscriber
{
    private $container;

    private $fileRenames = [];
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return array(
            'preUpdate',
            'postFlush',
        );
    }
    
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        
        if ($entity instanceof Person) {
            $changeSet = $args->getEntityChangeSet();
            if (isset($changeSet['address'])) {
                $personHelper = $this->container->get('person_helper');
                /* @var $personHelper \AppBundle\Helper\PersonHelper */

                $oldFotoFilename = $args->getOldValue('address')->getFamilyName() . '_'
                    . $entity->getFirstname() . '_'
                    . $entity->getDob()->format('Y-m-d') . '.jpg';

                $newFotoFilename = $personHelper->getPersonPhotoFilename($entity);

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
}
