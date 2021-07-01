<?php

declare(strict_types=1);

/**
 * Estende as funcionalidades do ArrayObject com as informações disponíveis no metadata
 *
 * Ele deveria extender Realejo\Stdlib\ArrayObject mas como rescreve a maioria dos
 * métodos eu deixei numa classe a parte
 */

namespace Realejo\Service\Metadata;

use Realejo\Stdlib\ArrayObject as StdlibArrayObject;
use RuntimeException;

class ArrayObject extends StdlibArrayObject
{
    protected MetadataArrayObject $metadata;

    protected string $metadataKeyName = 'metadata';

    public function getMetadata(): MetadataArrayObject
    {
        if (!isset($this->metadata)) {
            $this->metadata = new MetadataArrayObject();
        }

        return $this->metadata;
    }

    /**
     * @param array|MetadataArrayObject $metadata
     */
    public function setMetadata($metadata): self
    {
        if (is_array($metadata)) {
            $metadata = new MetadataArrayObject($metadata);
        }

        $this->metadata = $metadata;

        return $this;
    }

    public function addMetadata(array $metadata): self
    {
        $this->getMetadata()->addMetadata($metadata);

        return $this;
    }

    public function hasMetadata(string $key): bool
    {
        return $this->getMetadata()->offsetExists($key);
    }

    public function populate(array $data): void
    {
        if (isset($data[$this->metadataKeyName])) {
            if (is_string($data[$this->metadataKeyName])) {
                $data[$this->metadataKeyName] = json_decode($data[$this->metadataKeyName], true);
            }
            if (!empty($data[$this->metadataKeyName])) {
                $this->setMetadata($data[$this->metadataKeyName]);
            }
            unset($data[$this->metadataKeyName]);
        }

        parent::populate($data);
    }

    public function toArray(bool $unMapKeys = true): array
    {
        $toArray = parent::toArray($unMapKeys);
        if (!empty($this->getMetadata()->count())) {
            $toArray[$this->metadataKeyName] = $this->getMetadata()->toArray();
        }

        return $toArray;
    }

    public function offsetExists($offset): bool
    {
        $offset = $this->getMappedKey($offset);
        if (parent::offsetExists($offset)) {
            return true;
        }

        return $this->hasMetadata($offset);
    }

    public function offsetGet($offset)
    {
        $offset = $this->getMappedKey($offset);

        if (parent::offsetExists($offset)) {
            return parent::offsetGet($offset);
        }

        if ($this->hasMetadata($offset)) {
            return $this->getMetadata()->offsetGet($offset);
        }

        trigger_error("Undefined index: $offset");
    }

    public function offsetSet($offset, $value): void
    {
        $offset = $this->getMappedKey($offset, true);

        if (parent::offsetExists($offset)) {
            parent::offsetSet($offset, $value);

            return;
        }

        if ($this->hasMetadata($offset)) {
            $this->getMetadata()->offsetSet($offset, $value);

            return;
        }

        // Verifica as chaves estão bloqueadas
        //@todo tem que testar isso!!
        if (!$this->getLockedKeys()) {
            $this->storage[$offset] = $value;

            return;
        }

        trigger_error("Undefined index: $offset");
    }

    public function offsetUnset($offset): void
    {
        if (parent::offsetExists($offset)) {
            parent::offsetUnset($offset);

            return;
        }

        if ($this->hasMetadata($offset)) {
            $this->getMetadata()->offsetUnset($offset);

            return;
        }

        throw new RuntimeException("You cannot remove a property");
    }
}
