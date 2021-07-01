<?php

declare(strict_types=1);

namespace Realejo\Enum;

use InvalidArgumentException;

/**
 * Enum class
 *
 * Extends Enum class to use bitwise values
 *
 * static protected $constDescription = [
 *  self::CONST => [name, description],
 *  self::CONST => name
 * ];
 *
 * @link      https://github.com/realejo/zf3-base
 * @copyright Copyright (c) 2018 Realejo (https://realejo.com.br)
 */
abstract class EnumFlagged extends Enum
{

    public function setValue($value = null): void
    {
        if ($value === '' || $value === null) {
            $value = 0;
        }

        parent::setValue($value);
    }

    /**
     * Return the name os the constant
     *
     * @param mixed $value
     * @param string $join
     *
     * @return string
     */
    public static function getName($value, string $join = '/'): ?string
    {
        if ($value === null || !is_int($value)) {
            return null;
        }

        $names = self::getNames();

        $name = [];
        foreach ($names as $k => $v) {
            if ($value & $k) {
                $name[$k] = $v;
            }
        }

        if (empty($name)) {
            return null;
        }

        return implode($join, $name);
    }

    /**
     * Return the name os the constant
     */
    public static function getNameArray(int $value = null): ?array
    {
        if ($value === null) {
            return null;
        }

        $names = self::getNames();

        $name = [];
        foreach ($names as $k => $v) {
            if ($value & $k) {
                $name[$k] = $v;
            }
        }

        return $name;
    }

    /**
     * Descrição dos status
     *
     * @param mixed $value
     * @param string $join
     *
     * @return string|null
     */
    public static function getDescription($value, string $join = '/'): ?string
    {
        if (!is_int($value)) {
            return null;
        }

        $descriptions = self::getDescriptions();

        $description = [];
        foreach ($descriptions as $k => $v) {
            if ($value & $k) {
                $description[$k] = $v;
            }
        }

        if (empty($description)) {
            return null;
        }

        return implode($join, $description);
    }

    /**
     * Descrição dos status
     */
    public static function getDescriptionArray(int $value = null): ?array
    {
        if ($value === null) {
            return null;
        }

        $descriptions = self::getDescriptions();

        $description = [];
        foreach ($descriptions as $k => $v) {
            if ($value & $k) {
                $description[$k] = $v;
            }
        }

        return $description;
    }

    /**
     * Return the name os the constant
     *
     * @param null $value
     * @param string $join
     *
     * @return string|array
     */
    public function getValueName($value = null, string $join = '/'): ?string
    {
        if ($value === null && $this->value !== null) {
            $value = $this->value;
        }

        return self::getName($value, $join);
    }

    public function getValueNameArray($value = null): ?array
    {
        if ($value === null && $this->value !== null) {
            $value = $this->value;
        }

        return self::getNameArray($value);
    }

    /**
     * Return the name os the constant
     *
     * @param null $value
     * @param string $join
     *
     * @return string|null
     */
    public function getValueDescription($value = null, string $join = '/'): ?string
    {
        if ($value === null && $this->value !== null) {
            $value = $this->value;
        }

        return self::getDescription($value, $join);
    }

    /**
     * Return the name os the constant
     */
    public function getValueDescriptionArray(int $value = null): ?array
    {
        if ($value === null && $this->value !== null) {
            $value = $this->value;
        }

        return self::getDescriptionArray($value);
    }

    public static function isValid($value): bool
    {
        if (!is_int($value)) {
            return false;
        }

        // ZERO is not a const but it's valid because default flagged is ZERO
        // And it also means 'no flag'
        if ($value === 0) {
            return true;
        }

        $const = self::getValues();
        if (empty($const)) {
            return false;
        }

        $maxFlaggedValue = max($const) * 2 - 1;

        return ($value <= $maxFlaggedValue);
    }

    public function has($value): bool
    {
        if (!is_int($value)) {
            return false;
        }

        if ($value === 0 && $this->value === 0) {
            return true;
        }

        return (($this->value & $value) === $value);
    }

    public function add(int $value): void
    {
        if (!static::isValid($value)) {
            throw new InvalidArgumentException("Value '$value' is not valid.");
        }

        if (!$this->has($value)) {
            $this->value += $value;
        }
    }

    public function remove(int $value): void
    {
        if (!static::isValid($value)) {
            throw new InvalidArgumentException("Value '$value' is not valid.");
        }

        if ($this->has($value)) {
            $this->value -= $value;
        }
    }
}
