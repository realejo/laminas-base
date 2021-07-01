<?php

declare(strict_types=1);

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
        self::assertNotNull($object->getMetadata());
        self::assertEmpty($object->getMetadata());
        self::assertInstanceOf(StdlibArrayObject::class, $object);

        $metadata = new MetadataArrayObject(['one' => 'first']);

        $object->setMetadata($metadata);
        self::assertEquals(['one' => 'first'], $object->getMetadata()->toArray());

        self::assertTrue($object->hasMetadata('one'));
        self::assertFalse($object->hasMetadata('two'));

        $metadata = ['two' => 'second'];

        $object->setMetadata($metadata);

        self::assertTrue($object->hasMetadata('two'));
        self::assertFalse($object->hasMetadata('one'));
    }

    public function testAddMetadata(): void
    {
        $object = new ArrayObject();
        self::assertEmpty($object->getMetadata());
        self::assertInstanceOf(StdlibArrayObject::class, $object);

        $metadata = new MetadataArrayObject(['one' => 'first']);

        $object->setMetadata($metadata);

        self::assertFalse($object->hasMetadata('two'));
        self::assertTrue($object->hasMetadata('one'));

        $metadata = ['two' => 'second'];

        $object->addMetadata($metadata);

        self::assertTrue($object->hasMetadata('two'));
        self::assertTrue($object->hasMetadata('one'));
    }

    public function testPopulateToArray(): void
    {
        $object = new ArrayObject(['one' => 'first']);
        $object->getMetadata();
        self::assertCount(0, $object->getMetadata());
        self::assertEmpty($object->getMetadata());

        self::assertTrue(isset($object->one));
        self::assertTrue(isset($object['one']));

        self::assertFalse(isset($object->two));
        self::assertFalse(isset($object['two']));

        self::assertEquals(['one' => 'first'], $object->toArray());

        $object = new ArrayObject(['one' => 'first', 'metadata' => ['two' => 'second']]);
        self::assertCount(1, $object->getMetadata());

        self::assertTrue(isset($object->one));
        self::assertTrue(isset($object['one']));

        self::assertTrue(isset($object->two));
        self::assertTrue(isset($object['two']));

        self::assertFalse(isset($object->three));
        self::assertFalse(isset($object['three']));

        self::assertEquals(['two' => 'second'], $object->getMetadata()->toArray());
        self::assertEquals(['one' => 'first', 'metadata' => ['two' => 'second']], $object->toArray());

        $object = new ArrayObject(['one' => 'first', 'metadata' => '{"two":"second"}']);
        self::assertCount(1, $object->getMetadata());

        self::assertTrue(isset($object->one));
        self::assertTrue(isset($object['one']));

        self::assertTrue(isset($object->two));
        self::assertTrue(isset($object['two']));

        self::assertFalse(isset($object->three));
        self::assertFalse(isset($object['three']));

        self::assertEquals(['two' => 'second'], $object->getMetadata()->toArray());
        self::assertEquals(['one' => 'first', 'metadata' => ['two' => 'second']], $object->toArray());
    }

    public function testGetterSetter(): void
    {
        $object = new ArrayObject(['one' => 'first', 'metadata' => ['two' => 'second']]);
        self::assertCount(1, $object->getMetadata());

        $object->one = 'once';
        self::assertEquals('once', $object->one);
        self::assertEquals('once', $object['one']);

        $object['one'] = 'more';
        self::assertEquals('more', $object->one);
        self::assertEquals('more', $object['one']);

        $object->two = 'time';
        self::assertEquals('time', $object->two);
        self::assertEquals('time', $object['two']);

        $object['two'] = 'lets celebrate';
        self::assertEquals('lets celebrate', $object->two);
        self::assertEquals('lets celebrate', $object['two']);

        unset($object->two);
        self::assertNull($object->two);
        self::assertNull($object['two']);
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

        // E como testo isso?
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

        $this->expectException(RuntimeException::class);

        unset($object['test']);
    }

    public function testUnsetPropertyNonExisting(): void
    {
        $object = new ArrayObject();

        $this->expectException(RuntimeException::class);

        self::assertFalse(isset($object->test));

        // E como testo isso?

        unset($object->test);
    }
}
