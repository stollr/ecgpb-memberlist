<?php

namespace Ecgpb\MemberBundle\Traits;

/**
 * Ecgpb\MemberBundle\Traits\EntityRemovalTrait
 *
 * @author naitsirch
 */
trait EntityRemovalTrait
{
    private $removedEntities = array();
    
    protected function addRemovedEntity($entity)
    {
        $this->removedEntities[] = $entity;
        return $this;
    }

    public function getRemovedEntities()
    {
        return $this->removedEntities;
    }
}
