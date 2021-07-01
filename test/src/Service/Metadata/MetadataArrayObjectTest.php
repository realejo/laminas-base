<?php

declare(strict_types=1);

namespace RealejoTest\Service\Metadata;

use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;
use Realejo\Service\Metadata\MetadataArrayObject;

class MetadataArrayObjectTest extends TestCase
{

    public function testGettersSetters(): void
    {
        $object = new MetadataArrayObject();
        self::assertEmpty($object);
        self::assertFalse(isset($object->one));
        self::assertFalse(isset($object['one']));

        $object->addMetadata(['one' => 'first']);
        self::assertNotEmpty($object);
        self::assertEquals('first', $object->one);
        self::assertEquals('first', $object['one']);

        $object->one = null;

        self::assertNotEmpty($object);
        self::assertEquals(null, $object->one);
        self::assertEquals(null, $object['one']);
        self::assertEquals(['one' => null], $object->toArray());

        $object->one = 'again';

        self::assertNotEmpty($object);
        self::assertEquals('again', $object->one);
        self::assertEquals('again', $object['one']);
        self::assertEquals(['one' => 'again'], $object->toArray());

        $object->one = 'oncemore';

        self::assertNotEmpty($object);
        self::assertEquals('oncemore', $object->one);
        self::assertEquals('oncemore', $object['one']);
        self::assertEquals(['one' => 'oncemore'], $object->toArray());

        $object->addMetadata(['two' => null]);
        self::assertNotEmpty($object);
        self::assertNull($object->two);
        self::assertNull($object['two']);

        self::assertTrue(isset($object->one));
        self::assertTrue(isset($object->two));

        self::assertFalse(isset($object->three));
        self::assertFalse(isset($object['three']));

        self::assertNotEmpty($object->one);
        self::assertEmpty($object->two);

        unset($object->one);

        self::assertEquals(['two' => null, 'one' => null], $object->toArray());

        self::assertTrue(isset($object->one));
        self::assertTrue(isset($object->two));

        self::assertEmpty($object->one);
        self::assertEmpty($object->two);
    }

    public function testCount(): void
    {
        $object = new MetadataArrayObject();
        self::assertEmpty($object);

        $object = new MetadataArrayObject(['one' => 'two']);
        self::assertNotEmpty($object);
        self::assertCount(1, $object);

        $object->one = null;
        self::assertCount(1, $object);

        $object->addMetadata(['two' => 'second']);
        self::assertCount(2, $object);

        $object->two = null;
        self::assertCount(2, $object);

        $object->one = 'first';
        self::assertCount(2, $object);

        $object->addMetadata(['three' => null]);
        self::assertCount(3, $object);
    }

    public function testPopulateToArray(): void
    {
        $object = new MetadataArrayObject();
        self::assertEmpty($object);
        self::assertEmpty($object->toArray());

        $object = new MetadataArrayObject(['one' => 'first']);
        self::assertNotEmpty($object);
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['one' => 'first'], $object->toArray());

        $object->populate(['two' => 'second']);
        self::assertNotEmpty($object);
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['two' => 'second'], $object->toArray());

        $object->populate(['third' => null]);
        self::assertNotEmpty($object);
        self::assertNotEmpty($object->toArray());
        self::assertEquals(['third' => null], $object->toArray());
    }

    public function testGetKeyNonExisting(): void
    {
        $object = new MetadataArrayObject();

        $this->expectException(Notice::class);

        $object['test'];
    }

    public function testGetPropertyNonExisting(): void
    {
        $object = new MetadataArrayObject();

        $this->expectException(Notice::class);

        $object->test;
    }

    public function testSetKeyNonExisting(): void
    {
        $object = new MetadataArrayObject();

        $this->expectException(Notice::class);

        $object['test'] = 'tessst';
    }

    public function testSetPropertyNonExisting(): void
    {
        $object = new MetadataArrayObject();

        $this->expectException(Notice::class);

        $object->test = 'tessst';
    }

    public function testUnsetKeyNonExisting(): void
    {
        $object = new MetadataArrayObject();

        $this->expectException(Notice::class);

        unset($object['test']);
    }

    public function testUnsetPropertyNonExisting(): void
    {
        $object = new MetadataArrayObject();

        $this->expectException(Notice::class);

        unset($object->test);
    }
}
