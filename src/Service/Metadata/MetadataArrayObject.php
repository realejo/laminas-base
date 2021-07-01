<?php

declare(strict_types=1);

namespace Realejo\Service\Metadata;

class MetadataArrayObject implements \ArrayAccess, \Countable
{
    protected array $storage = [];

    protected string $nullKeys = ':';

    public function __construct(array $data = null)
    {
        if (!empty($data)) {
            $this->populate($data);
        }
    }

    public function count()
    {
        return count($this->storage) + substr_count($this->nullKeys, ':') - 1;
    }

    public function populate(array $data): void
    {
        // remove as chaves vazias
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (is_null($value)) {
                    $this->nullKeys .= $key . ':';
                    unset($data[$key]);
                }
            }
        }
        $this->storage = $data;
    }

    public function toArray(): array
    {
        $toArray = $this->storage;
        if (strlen($this->nullKeys) > 1) {
            foreach (explode(':', trim($this->nullKeys, ':')) as $key) {
                $toArray[$key] = null;
            }
        }

        return $toArray;
    }

    public function addMetadata(array $metadata): void
    {
        // remove as chaves vazias
        if (!empty($metadata)) {
            foreach ($metadata as $key => $value) {
                if (is_null($value)) {
                    $this->nullKeys .= $key . ':';
                    unset($metadata[$key]);
                } else {
                    $this->storage[$key] = $value;
                }
            }
        }
    }

    public function offsetExists($offset): bool
    {
        if (array_key_exists($offset, $this->storage)) {
            return true;
        }

        if (isset($this->nullKeys) && strpos($this->nullKeys, ":$offset:") !== false) {
            return true;
        }

        return false;
    }

    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->storage)) {
            return $this->storage[$offset];
        }

        if (isset($this->nullKeys) && strpos($this->nullKeys, ":$offset:") !== false) {
            return null;
        }

        trigger_error("Undefined index: $offset");
    }

    public function offsetSet($offset, $value)
    {
        if (array_key_exists($offset, $this->storage)) {
            if (is_null($value)) {
                $this->nullKeys .= "$offset:";
                unset($this->storage[$offset]);
            } else {
                $this->storage[$offset] = $value;
            }

            return null;
        }

        if (isset($this->nullKeys) && strpos($this->nullKeys, ":$offset:") !== false) {
            $this->storage[$offset] = $value;
            $this->nullKeys = str_replace(":$offset:", ':', $this->nullKeys);

            return $this;
        }

        trigger_error("Undefined index: $offset");
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            $this->nullKeys .= "$offset:";
            unset($this->storage[$offset]);

            return;
        }

        trigger_error("Undefined index: $offset");
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __unset(string $name)
    {
        $this->offsetUnset($name);
    }

    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }
}
