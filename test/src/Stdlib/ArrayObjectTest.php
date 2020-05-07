<?php

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
        $this->assertNotNull($object->toArray());
        $this->assertEmpty($object->toArray());
        $this->assertEquals([], $object->toArray());

        $originalArray = ['one' => 'first', 'three' => 'áéíóú', 'four' => '\\slashes\\'];

        $this->assertNull($object->populate($originalArray));

        $this->assertNotNull($object->toArray());
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals($originalArray, $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertEquals('first', $object->one);
        $this->assertEquals('first', $object['one']);
        $this->assertEquals('áéíóú', $object->three);
        $this->assertEquals('áéíóú', $object['three']);

        $object = new ArrayObject(['two' => 'second']);
        $this->assertNotNull($object->toArray());
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['two' => 'second'], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertEquals('second', $object->two);
        $this->assertEquals('second', $object['two']);

        $stdClass = (object)['three' => 'third'];
        $object = new ArrayObject(['two' => $stdClass]);
        $this->assertNotNull($object->toArray());
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['two' => $stdClass], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertEquals($stdClass, $object->two);
        $this->assertEquals($stdClass, $object['two']);
    }

    public function testSetGet(): void
    {
        $object = new ArrayObject();
        $this->assertNotNull($object->toArray());
        $this->assertEmpty($object->toArray());
        $this->assertEquals([], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());

        // Desabilita o bloqueio de chaves
        $this->assertInstanceof(get_class($object), $object->setLockedKeys(false));

        $object->one = 'first';
        $this->assertNotNull($object->toArray());
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals('first', $object->one);
        $this->assertEquals('first', $object['one']);
        $this->assertEquals(['one' => 'first'], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertTrue(isset($object->one));
        $this->assertTrue(isset($object['one']));
        unset($object->one);
        $this->assertNotNull($object->toArray());
        $this->assertEmpty($object->toArray());
        $this->assertEquals([], $object->toArray());
        $this->assertFalse(isset($object->one));
        $this->assertFalse(isset($object['one']));

        $object['two'] = 'second';
        $this->assertNotNull($object->toArray());
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['two' => 'second'], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertEquals('second', $object->two);
        $this->assertEquals('second', $object['two']);
        $this->assertTrue(isset($object->two));
        $this->assertTrue(isset($object['two']));
        unset($object['two']);
        $this->assertNotNull($object->toArray());
        $this->assertEmpty($object->toArray());
        $this->assertEquals([], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertFalse(isset($object->two));
        $this->assertFalse(isset($object['two']));

        $stdClass = (object)['three' => 'third'];

        $object['two'] = $stdClass;
        $this->assertNotNull($object->toArray());
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['two' => $stdClass], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertEquals($stdClass, $object->two);
        $this->assertEquals($stdClass, $object['two']);
        $this->assertTrue(isset($object->two));
        $this->assertTrue(isset($object['two']));
        unset($object['two']);
        $this->assertNotNull($object->toArray());
        $this->assertEmpty($object->toArray());
        $this->assertEquals([], $object->toArray());
        $this->assertEquals($object->toArray(), $object->entityToArray());
        $this->assertEquals($object->toArray(), $object->getArrayCopy());
        $this->assertFalse(isset($object->two));
        $this->assertFalse(isset($object['two']));
    }

    public function testGetKeyNonExisting(): void
    {
        $object = new ArrayObject();
        $this->assertFalse(isset($object['test']));

        $this->expectException(Notice::class);

        $object['test'];
    }

    public function testGetPropertyNonExisting(): void
    {
        $object = new ArrayObject();


        $this->assertFalse(isset($object->test));

        $this->expectException(Notice::class);

        $object->test;
    }

    public function testGetKeyNonExistingWithNoLockedKeys(): void
    {
        $object = new ArrayObject();
        $object->setLockedKeys(false);
        $this->assertFalse(isset($object['test']));

        $this->expectException(Notice::class);

        $this->assertNull($object['test']);

        // Como testar isso ai em baixo?

        $object['test'];
    }

    public function testGetPropertyNonExistingWithNoLockedKeys(): void
    {
        $object = new ArrayObject();
        $object->setLockedKeys(false);
        $this->assertFalse(isset($object->test));

        $this->expectException(Notice::class);

        $this->assertNull($object->test);
    }

    public function testSetKeyNonExisting(): void
    {
        $object = new ArrayObject();
        $this->assertFalse(isset($object['test']));

        $this->expectException(Notice::class);

        $object['test'] = 'tessst';
    }

    public function testSetPropertyNonExisting(): void
    {
        $object = new ArrayObject();
        $this->assertFalse(isset($object->test));

        $this->expectException(Notice::class);

        $object->test = 'tessst';
    }

    public function testUnsetKeyNonExisting(): void
    {
        $object = new ArrayObject();
        $this->assertFalse(isset($object['test']));

        $this->expectException(\Exception::class);

        unset($object['test']);
    }

    public function testUnsetPropertyNonExisting(): void
    {
        $object = new ArrayObject();
        $this->assertFalse(isset($object->test));

        $this->expectException(\Exception::class);

        unset($object->test);
    }

    public function testMapping(): void
    {
        $object = new ArrayObject();
        $this->assertNull($object->getKeyMapping());
        $this->assertInstanceof(get_class($object), $object->setMapping(['original' => 'mapped']));
        $this->assertNotNull($object->getKeyMapping());
        $this->assertEquals(['original' => 'mapped'], $object->getKeyMapping());

        $object->populate(['original' => 'realValue']);

        $this->assertTrue(isset($object->original), 'A chave original será mapeada para a nova');
        $this->assertTrue(isset($object->mapped), 'A chave mapeada está disponível');
        $this->assertEquals('realValue', $object->original);
        $this->assertEquals('realValue', $object->mapped);

        $objectArray = $object->toArray();
        $this->assertCount(1, $objectArray);
        $this->assertEquals(['original' => 'realValue'], $objectArray);

        $object = new ArrayObject();
        $this->assertNull($object->getKeyMapping());
        $this->assertInstanceof(get_class($object), $object->setMapping(['one' => 'two']));
        $this->assertNotNull($object->getKeyMapping());
        $this->assertEquals(['one' => 'two'], $object->getKeyMapping());
        $this->assertInstanceof(get_class($object), $object->setMapping(null));
        $this->assertNull($object->getKeyMapping());
        $this->assertEquals(null, $object->getKeyMapping());
    }

    public function testPopulateWithTypedKeys(): void
    {
        $object = new ArrayObjectTypedKeys();
        $this->assertNotNull($object->toArray());
        $this->assertEmpty($object->toArray());
        $this->assertEquals([], $object->toArray());

        $this->assertNull($object->populate(['one' => 'first']));

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
        $this->assertTrue($object->booleanKey);
        $this->assertEquals(new \DateTime('2010-01-01'), $object->datetimeKey);
        $this->assertSame($object->intKey, 1);

        $this->assertInstanceOf(EnumConcrete::class, $object->enum);
        $this->assertEquals(EnumConcrete::STRING1, $object->enum->getValue());
        $this->assertTrue($object->enum->is(EnumConcrete::STRING1));

        $this->assertInstanceOf(EnumFlaggedConcrete::class, $object->enumFlagged);
        $this->assertEquals(EnumFlaggedConcrete::WRITE, $object->enumFlagged->getValue());
        $this->assertTrue($object->enumFlagged->is(EnumFlaggedConcrete::WRITE));

        // get the array as it will be inserted on database
        $objectArray = $object->getArrayCopy();
        $this->assertEquals(1, $objectArray['booleanKey']);
        $this->assertEquals('2010-01-01 00:00:00', $objectArray['datetimeKey']);
        $this->assertEquals(1, $objectArray['intKey']);
        $this->assertEquals('S', $objectArray['enum']);
        $this->assertEquals(2, $objectArray['enumFlagged']);

        // get the array as it will be inserted on database
        $objectArray = $object->setJsonEncodeOptions(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->getArrayCopy();
        $this->assertEquals(1, $objectArray['booleanKey']);
        $this->assertEquals(
            json_encode($originalArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $objectArray['jsonArrayKey']
        );
        $this->assertEquals(
            json_encode($originalArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $objectArray['jsonObjectKey']
        );
        $this->assertEquals('2010-01-01 00:00:00', $objectArray['datetimeKey']);
        $this->assertEquals(1, $objectArray['intKey']);
        $this->assertEquals('S', $objectArray['enum']);
        $this->assertEquals(2, $objectArray['enumFlagged']);
    }
}
