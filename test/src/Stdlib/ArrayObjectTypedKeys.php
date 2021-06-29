<?php

declare(strict_types=1);

namespace RealejoTest\Stdlib;

use DateTime;
use Realejo\Stdlib\ArrayObject;
use RealejoTest\Enum\EnumConcrete;
use RealejoTest\Enum\EnumFlaggedConcrete;
use stdClass;

/**
 * @property bool booleanKey
 * @property int intKey
 * @property stdClass jsonObjectKey
 * @property array jsonArrayKey
 * @property DateTime datetimeKey
 * @property EnumConcrete enum
 * @property EnumFlaggedConcrete enumFlagged
 */
class ArrayObjectTypedKeys extends ArrayObject
{
    protected array $booleanKeys = ['booleanKey'];

    protected array $intKeys = ['intKey'];

    protected array $jsonArrayKeys = ['jsonArrayKey'];

    protected array $jsonObjectKeys = ['jsonObjectKey'];

    protected array $dateKeys = ['datetimeKey'];

    protected array $enumKeys = [
        'enum' => EnumConcrete::class,
        'enumFlagged' => EnumFlaggedConcrete::class
    ];
}
