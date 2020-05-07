<?php

namespace RealejoTest\Service\Metadata;

use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;
use Realejo\Service\Metadata\ArrayObject;
use Realejo\Service\Metadata\MetadataArrayObject;
use Realejo\Stdlib\ArrayObject as StdlibArrayObject;
use RuntimeException;

class ArrayObjectTest extends TestCase
{
    public function testMetadata(): void
    {
        $object = new ArrayObject();
        $this->assertNotNull($object->getMetadata());
        $this->assertEmpty($object->getMetadata());
        $this->assertInstanceOf(StdlibArrayObject::class, $object);

        $metadata = new MetadataArrayObject(['one' => 'first']);

        $this->assertInstanceOf(ArrayObject::class, $object->setMetadata($metadata));
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());
        $this->assertEquals(['one' => 'first'], $object->getMetadata()->toArray());

        $this->assertTrue($object->hasMetadata('one'));
        $this->assertFalse($object->hasMetadata('two'));

        $metadata = ['two' => 'second'];

        $this->assertInstanceOf(ArrayObject::class, $object->setMetadata($metadata));
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());

        $this->assertTrue($object->hasMetadata('two'));
        $this->assertFalse($object->hasMetadata('one'));
    }

    public function testAddMetadata(): void
    {
        $object = new ArrayObject();
        $this->assertEmpty($object->getMetadata());
        $this->assertInstanceOf(StdlibArrayObject::class, $object);

        $metadata = new MetadataArrayObject(['one' => 'first']);

        $this->assertInstanceOf(ArrayObject::class, $object->setMetadata($metadata));
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());

        $this->assertFalse($object->hasMetadata('two'));
        $this->assertTrue($object->hasMetadata('one'));

        $metadata = ['two' => 'second'];

        $this->assertInstanceOf(ArrayObject::class, $object->addMetadata($metadata));
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());

        $this->assertTrue($object->hasMetadata('two'));
        $this->assertTrue($object->hasMetadata('one'));
    }

    public function testPopulateToArray(): void
    {
        $object = new ArrayObject(['one' => 'first']);
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());
        $this->assertCount(0, $object->getMetadata());
        $this->assertEmpty($object->getMetadata());

        $this->assertTrue(isset($object->one));
        $this->assertTrue(isset($object['one']));

        $this->assertFalse(isset($object->two));
        $this->assertFalse(isset($object['two']));

        $this->assertEquals(['one' => 'first'], $object->toArray());

        $object = new ArrayObject(['one' => 'first', 'metadata' => ['two' => 'second']]);
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());
        $this->assertCount(1, $object->getMetadata());

        $this->assertTrue(isset($object->one));
        $this->assertTrue(isset($object['one']));

        $this->assertTrue(isset($object->two));
        $this->assertTrue(isset($object['two']));

        $this->assertFalse(isset($object->three));
        $this->assertFalse(isset($object['three']));

        $this->assertEquals(['two' => 'second'], $object->getMetadata()->toArray());
        $this->assertEquals(['one' => 'first', 'metadata' => ['two' => 'second']], $object->toArray());

        $object = new ArrayObject(['one' => 'first', 'metadata' => '{"two":"second"}']);
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());
        $this->assertCount(1, $object->getMetadata());

        $this->assertTrue(isset($object->one));
        $this->assertTrue(isset($object['one']));

        $this->assertTrue(isset($object->two));
        $this->assertTrue(isset($object['two']));

        $this->assertFalse(isset($object->three));
        $this->assertFalse(isset($object['three']));

        $this->assertEquals(['two' => 'second'], $object->getMetadata()->toArray());
        $this->assertEquals(['one' => 'first', 'metadata' => ['two' => 'second']], $object->toArray());
    }

    public function testGetterSetter(): void
    {
        $object = new ArrayObject(['one' => 'first', 'metadata' => ['two' => 'second']]);
        $this->assertInstanceOf(MetadataArrayObject::class, $object->getMetadata());
        $this->assertCount(1, $object->getMetadata());

        $object->one = 'once';
        $this->assertEquals('once', $object->one);
        $this->assertEquals('once', $object['one']);

        $object['one'] = 'more';
        $this->assertEquals('more', $object->one);
        $this->assertEquals('more', $object['one']);

        $object->two = 'time';
        $this->assertEquals('time', $object->two);
        $this->assertEquals('time', $object['two']);

        $object['two'] = 'lets celebrate';
        $this->assertEquals('lets celebrate', $object->two);
        $this->assertEquals('lets celebrate', $object['two']);

        unset($object->two);
        $this->assertNull($object->two);
        $this->assertNull($object['two']);
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

        // E como testo isso?
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

        $this->expectException(RuntimeException::class);

        unset($object['test']);
    }

    public function testUnsetPropertyNonExisting(): void
    {
        $object = new ArrayObject();

        $this->expectException(RuntimeException::class);

        $this->assertFalse(isset($object->test));

        // E como testo isso?

        unset($object->test);
    }
}
