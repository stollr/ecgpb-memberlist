<?php

namespace AppBundle\Traits;

/**
 * AppBundle\Traits\EntityRemovalTrait
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
