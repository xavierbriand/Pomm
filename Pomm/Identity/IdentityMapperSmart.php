<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

class IdentityMapperSmart extends IdentityMapperStrict
{
    /**
     * getModelInstance
     *
     * @see IdentityMapperInterface
     **/
    public function getModelInstance(BaseObject $object, Array $pk_fields)
    {
        $crc = $this->getSignature(get_class($object), $object->get($pk_fields));

        if (array_key_exists($crc, $this->mapper))
        {
            if (!$this->mapper[$crc]->_getStatus() & BaseObject::EXIST)
            {
                $this->mapper[$crc] = $object;
            }
            elseif (!$this->mapper[$crc]->isModified())
            {
                $this->mapper[$crc]->hydrate($object->extract());
            }
        }
        else
        {
            $this->mapper[$crc] = $object;
        }

        return $this->mapper[$crc];
    }

    /**
     * checkModelInstance
     * @see IdentityMapperInterface 
     **/
    public function checkModelInstance($class_name, Array $primary_key)
    {
        $crc = $this->getSignature($class_name, $primary_key);

        return array_key_exists($crc, $this->mapper) && ($this->mapper[$crc]->_getStatus() & BaseObject::EXIST)
            ? $this->mapper[$crc]
            : false
            ;
    }
}
