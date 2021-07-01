<?php

declare(strict_types=1);

namespace RealejoTest\Stdlib;

use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;
use Realejo\Stdlib\ArrayObject;
use RealejoTest\Enum\EnumConcrete;
use RealejoTest\Enum\EnumFlaggedConcrete;

class ArrayObjectTest extends TestCase
{
    public function testPopulateToArray(): void
    {
        $object = new ArrayObject();
        self::assertNotNull($object->toArray());
        self::assertEmpty($object->toArray());
        self::assertEquals([], $object->toArray());

        $originalArray = ['one' => 'first', 'three' => 'áéíóú', 'four' => '\\slashes\\'];

        $object->populate($originalArray);

        self::assertNotNull($object->toArray());
        self::assertNotEmpty($object->toArray());
        self::assertEquals($originalArray, $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertEquals('first', $object->one);
        self::assertEquals('first', $object['one']);
        self::assertEquals('áéíóú', $object->three);
        self::assertEquals('áéíóú', $object['three']);

        $object = new ArrayObject(['two' => 'second']);
        self::assertNotNull($object->toArray());
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['two' => 'second'], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertEquals('second', $object->two);
        self::assertEquals('second', $object['two']);

        $stdClass = (object)['three' => 'third'];
        $object = new ArrayObject(['two' => $stdClass]);
        self::assertNotNull($object->toArray());
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['two' => $stdClass], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertEquals($stdClass, $object->two);
        self::assertEquals($stdClass, $object['two']);
    }

    public function testSetGet(): void
    {
        $object = new ArrayObject();
        self::assertNotNull($object->toArray());
        self::assertEmpty($object->toArray());
        self::assertEquals([], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());

        // Desabilita o bloqueio de chaves
        self::assertInstanceof(get_class($object), $object->setLockedKeys(false));

        $object->one = 'first';
        self::assertNotNull($object->toArray());
        self::assertNotEmpty($object->toArray());
        self::assertEquals('first', $object->one);
        self::assertEquals('first', $object['one']);
        self::assertEquals(['one' => 'first'], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertTrue(isset($object->one));
        self::assertTrue(isset($object['one']));
        unset($object->one);
        self::assertNotNull($object->toArray());
        self::assertEmpty($object->toArray());
        self::assertEquals([], $object->toArray());
        self::assertFalse(isset($object->one));
        self::assertFalse(isset($object['one']));

        $object['two'] = 'second';
        self::assertNotNull($object->toArray());
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['two' => 'second'], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertEquals('second', $object->two);
        self::assertEquals('second', $object['two']);
        self::assertTrue(isset($object->two));
        self::assertTrue(isset($object['two']));
        unset($object['two']);
        self::assertNotNull($object->toArray());
        self::assertEmpty($object->toArray());
        self::assertEquals([], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertFalse(isset($object->two));
        self::assertFalse(isset($object['two']));

        $stdClass = (object)['three' => 'third'];

        $object['two'] = $stdClass;
        self::assertNotNull($object->toArray());
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['two' => $stdClass], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertEquals($stdClass, $object->two);
        self::assertEquals($stdClass, $object['two']);
        self::assertTrue(isset($object->two));
        self::assertTrue(isset($object['two']));
        unset($object['two']);
        self::assertNotNull($object->toArray());
        self::assertEmpty($object->toArray());
        self::assertEquals([], $object->toArray());
        self::assertEquals($object->toArray(), $object->entityToArray());
        self::assertEquals($object->toArray(), $object->getArrayCopy());
        self::assertFalse(isset($object->two));
        self::assertFalse(isset($object['two']));
    }

    public function testGetKeyNonExisting(): void
    {
        $object = new ArrayObject();
        self::assertFalse(isset($object['test']));

        $this->expectException(Notice::class);

        $object['test'];
    }

    public function testGetPropertyNonExisting(): void
    {
        $object = new ArrayObject();


        self::assertFalse(isset($object->test));

        $this->expectException(Notice::class);

        $object->test;
    }

    public function testGetKeyNonExistingWithNoLockedKeys(): void
    {
        $object = new ArrayObject();
        $object->setLockedKeys(false);
        self::assertFalse(isset($object['test']));

        $this->expectException(Notice::class);

        self::assertNull($object['test']);

        // Como testar isso ai em baixo?

        $object['test'];
    }

    public function testGetPropertyNonExistingWithNoLockedKeys(): void
    {
        $object = new ArrayObject();
        $object->setLockedKeys(false);
        self::assertFalse(isset($object->test));

        $this->expectException(Notice::class);

        self::assertNull($object->test);
    }

    public function testSetKeyNonExisting(): void
    {
        $object = new ArrayObject();
        self::assertFalse(isset($object['test']));

        $this->expectException(Notice::class);

        $object['test'] = 'tessst';
    }

    public function testSetPropertyNonExisting(): void
    {
        $object = new ArrayObject();
        self::assertFalse(isset($object->test));

        $this->expectException(Notice::class);

        $object->test = 'tessst';
    }

    public function testUnsetKeyNonExisting(): void
    {
        $object = new ArrayObject();
        self::assertFalse(isset($object['test']));

        $this->expectException(\Exception::class);

        unset($object['test']);
    }

    public function testUnsetPropertyNonExisting(): void
    {
        $object = new ArrayObject();
        self::assertFalse(isset($object->test));

        $this->expectException(\Exception::class);

        unset($object->test);
    }

    public function testMapping(): void
    {
        $object = new ArrayObject();
        self::assertEquals([], $object->getKeyMapping());
        self::assertInstanceof(get_class($object), $object->setMapping(['original' => 'mapped']));
        self::assertNotNull($object->getKeyMapping());
        self::assertEquals(['original' => 'mapped'], $object->getKeyMapping());

        $object->populate(['original' => 'realValue']);

        self::assertTrue(isset($object->original), 'A chave original será mapeada para a nova');
        self::assertTrue(isset($object->mapped), 'A chave mapeada está disponível');
        self::assertEquals('realValue', $object->original);
        self::assertEquals('realValue', $object->mapped);

        $objectArray = $object->toArray();
        self::assertCount(1, $objectArray);
        self::assertEquals(['original' => 'realValue'], $objectArray);

        $object = new ArrayObject();
        self::assertEquals([], $object->getKeyMapping());
        self::assertInstanceof(get_class($object), $object->setMapping(['one' => 'two']));
        self::assertNotNull($object->getKeyMapping());
        self::assertEquals(['one' => 'two'], $object->getKeyMapping());
        self::assertInstanceof(get_class($object), $object->setMapping(null));
        self::assertNull($object->getKeyMapping());
        self::assertEquals(null, $object->getKeyMapping());
    }

    public function testPopulateWithTypedKeys(): void
    {
        $object = new ArrayObjectTypedKeys();
        self::assertNotNull($object->toArray());
        self::assertEmpty($object->toArray());
        self::assertEquals([], $object->toArray());

        self::assertNull($object->populate(['one' => 'first']));

        // populate as it comes from database
        $originalArray = ['key' => 'value', 'unicode' => 'áéíóú😶çý', 'slashes' => '\\slashes\\'];
        $object = new ArrayObjectTypedKeys(
            [
                'booleanKey' => '1',
                'jsonObjectKey' => json_encode($originalArray),
                'jsonArrayKey' => json_encode($originalArray),
                'datetimeKey' => '2010-01-01 00:00:00',
                'intKey' => '1',
                'enum' => EnumConcrete::STRING1,
                'enumFlagged' => EnumFlaggedConcrete::WRITE
            ]
        );

        // check keys
        self::assertTrue($object->booleanKey);
        self::assertEquals(new \DateTime('2010-01-01'), $object->datetimeKey);
        self::assertSame($object->intKey, 1);

        self::assertInstanceOf(EnumConcrete::class, $object->enum);
        self::assertEquals(EnumConcrete::STRING1, $object->enum->getValue());
        self::assertTrue($object->enum->is(EnumConcrete::STRING1));

        self::assertInstanceOf(EnumFlaggedConcrete::class, $object->enumFlagged);
        self::assertEquals(EnumFlaggedConcrete::WRITE, $object->enumFlagged->getValue());
        self::assertTrue($object->enumFlagged->is(EnumFlaggedConcrete::WRITE));

        // get the array as it will be inserted on database
        $objectArray = $object->getArrayCopy();
        self::assertEquals(1, $objectArray['booleanKey']);
        self::assertEquals('2010-01-01 00:00:00', $objectArray['datetimeKey']);
        self::assertEquals(1, $objectArray['intKey']);
        self::assertEquals('S', $objectArray['enum']);
        self::assertEquals(2, $objectArray['enumFlagged']);

        // get the array as it will be inserted on database
        $objectArray = $object->setJsonEncodeOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->getArrayCopy();
        self::assertEquals(1, $objectArray['booleanKey']);
        self::assertEquals(
            json_encode($originalArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $objectArray['jsonArrayKey']
        );
        self::assertEquals(
            json_encode($originalArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $objectArray['jsonObjectKey']
        );
        self::assertEquals('2010-01-01 00:00:00', $objectArray['datetimeKey']);
        self::assertEquals(1, $objectArray['intKey']);
        self::assertEquals('S', $objectArray['enum']);
        self::assertEquals(2, $objectArray['enumFlagged']);
    }
}
