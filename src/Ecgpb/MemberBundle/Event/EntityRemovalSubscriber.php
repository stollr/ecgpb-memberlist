<?php

namespace Ecgpb\MemberBundle\Event;

use Doctrine\Common\EventSubscriber;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Ecgpb\MemberBundle\Event\EntityRemovalSubscriber
 *
 * @author naitsirch
 */
class EntityRemovalSubscriber implements EventSubscriber
{
    private $doctrine;
    private $removableEntities = array();
    
    public function __construct(RegistryInterface $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function getSubscribedEvents()
    {
        return array(
            'postUpdate',
            'postFlush',
        );
    }
    
    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $methods = get_class_methods($entity);
        if (isset($methods['getRemovedEntities']) && count($entity->getRemovedEntities())) {
            $this->removableEntities = array_merge(
                $this->removableEntities,
                $entity->getRemovedEntities()
            );
        }
    }
    
    public function postFlush($args)
    {
        if (count($this->removableEntities)) {
            $em = $this->doctrine->getManager();
            foreach ($this->removableEntities as $entity) {
                $em->remove($entity);
            }
            $em->flush();
        }
    }
}
