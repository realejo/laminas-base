<?php

declare(strict_types=1);

namespace Realejo\Stdlib;

use ArrayAccess;
use DateTime;
use Realejo\Enum\Enum;
use RuntimeException;
use stdClass;

class ArrayObject implements ArrayAccess
{
    protected array $storage = [];
    protected ?array $mappedKeys = [];

    /**
     * Define se pode usar propriedades/chaves que não estejam previamente definidas
     */
    protected bool $lockedKeys = true;

    protected array $intKeys = [];

    protected array $booleanKeys = [];

    protected array $dateKeys = [];

    protected array $jsonArrayKeys = [];
    protected array $jsonObjectKeys = [];
    protected int $jsonEncodeOptions = 0;

    /** @var Enum[] */
    protected array $enumKeys = [];

    public function __construct($data = null)
    {
        if (is_array($data) && !empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * @param string $key
     * @param bool $reverse
     * @return mixed
     */
    protected function getMappedKey(string $key, bool $reverse = false)
    {
        $map = $this->getKeyMapping();
        if (empty($map)) {
            return $key;
        }

        // Verifica se é para desfazer o map
        if ($reverse === true) {
            $map = array_flip($map);
        }

        return $map[$key] ?? $key;
    }

    public function populate(array $data): void
    {
        $useDateKeys = (is_array($this->dateKeys) && !empty($this->dateKeys));
        $useJsonArrayKeys = (is_array($this->jsonArrayKeys) && !empty($this->jsonArrayKeys));
        $useJsonObjectKeys = (is_array($this->jsonObjectKeys) && !empty($this->jsonObjectKeys));
        $useIntKeys = (is_array($this->intKeys) && !empty($this->intKeys));
        $useBooleanKeys = (is_array($this->booleanKeys) && !empty($this->booleanKeys));
        $useEnumKeys = (is_array($this->enumKeys) && !empty($this->enumKeys));

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if ($useDateKeys && in_array($key, $this->dateKeys) && !empty($value)) {
                    $value = new DateTime($value);
                } elseif ($useJsonArrayKeys && in_array($key, $this->jsonArrayKeys) && !empty($value)) {
                    $value = json_decode($value, true);
                } elseif ($useJsonObjectKeys && in_array($key, $this->jsonObjectKeys) && !empty($value)) {
                    $value = json_decode($value);
                } elseif ($useIntKeys && in_array($key, $this->intKeys) && !empty($value)) {
                    $value = (int)$value;
                } elseif ($useBooleanKeys && in_array($key, $this->booleanKeys) && !empty($value)) {
                    $value = (bool)$value;
                } elseif ($useEnumKeys && array_key_exists($key, $this->enumKeys)) {
                    $value = new $this->enumKeys[$key]($value);
                }

                $this->storage[$this->getMappedKey($key)] = $value;
            }
        }
    }

    public function toArray(bool $unMapKeys = true): array
    {
        $toArray = [];

        if (empty($this->storage)) {
            return $toArray;
        }

        foreach ($this->storage as $key => $value) {
            if ($value instanceof ArrayObject) {
                $value = $value->toArray($unMapKeys);
            }

            if ($unMapKeys === true) {
                $key = $this->getMappedKey($key, true);
            }

            $toArray[$key] = $value;
        }

        return $toArray;
    }

    public function entityToArray(): array
    {
        return $this->toArray();
    }

    public function getArrayCopy(): array
    {
        $toArray = $this->toArray(true);

        if (empty($toArray)) {
            return $toArray;
        }

        foreach ($toArray as $key => $value) {
            // desfaz datetime
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            // desfaz o json
            if (in_array($key, $this->jsonArrayKeys) && is_array($value)) {
                $value = json_encode($value, $this->jsonEncodeOptions);
            }
            if (in_array($key, $this->jsonObjectKeys) && $value instanceof stdClass) {
                $value = json_encode($value, $this->jsonEncodeOptions);
            }

            // desfaz o enum
            if ($value instanceof Enum) {
                $value = $value->getValue();
            }

            // desfaz bool e int
            if (is_bool($value) || is_int($value)) {
                $value = (int)$value;
            }

            $toArray[$key] = $value;
        }

        return $toArray;
    }

    public function offsetExists($offset): bool
    {
        $offset = $this->getMappedKey($offset);

        return (array_key_exists($offset, $this->storage));
    }

    public function offsetGet($offset)
    {
        $offset = $this->getMappedKey($offset);
        if (!array_key_exists($offset, $this->storage)) {
            trigger_error("Undefined index: $offset");
        }

        return $this->storage[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $offset = $this->getMappedKey($offset);
        if (!$this->getLockedKeys() || array_key_exists($offset, $this->storage)) {
            $this->storage[$offset] = $value;
        } else {
            trigger_error("Undefined index: $offset");
        }
    }

    public function offsetUnset($offset): void
    {
        if ($this->getLockedKeys()) {
            throw new RuntimeException("You cannot remove a property");
        }

        $offset = $this->getMappedKey($offset);
        unset($this->storage[$offset]);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->offsetGet($name);
    }

    /**
     *
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

    public function getKeyMapping(): ?array
    {
        return $this->mappedKeys;
    }

    public function setMapping(array $mappedKeys = null): self
    {
        $this->mappedKeys = $mappedKeys;

        return $this;
    }

    public function getLockedKeys(): bool
    {
        return $this->lockedKeys;
    }

    public function setLockedKeys($lockedKeys): ArrayObject
    {
        $this->lockedKeys = $lockedKeys;

        return $this;
    }

    public function getJsonEncodeOptions(): int
    {
        return $this->jsonEncodeOptions;
    }

    public function setJsonEncodeOptions(int $jsonEncodeOptions): self
    {
        $this->jsonEncodeOptions = $jsonEncodeOptions;

        return $this;
    }
}
