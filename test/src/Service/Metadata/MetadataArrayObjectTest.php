<?php

namespace RealejoTest\Service\Metadata;

use PHPUnit\Framework\Error\Notice;
use PHPUnit\Framework\TestCase;
use Realejo\Service\Metadata\MetadataArrayObject;

class MetadataArrayObjectTest extends TestCase
{

    public function testGettersSetters(): void
    {
        $object = new MetadataArrayObject();
        $this->assertEmpty($object);
        $this->assertFalse(isset($object->one));
        $this->assertFalse(isset($object['one']));

        $object->addMetadata(['one' => 'first']);
        $this->assertNotEmpty($object);
        $this->assertEquals('first', $object->one);
        $this->assertEquals('first', $object['one']);

        $object->one = null;

        $this->assertNotEmpty($object);
        $this->assertEquals(null, $object->one);
        $this->assertEquals(null, $object['one']);
        $this->assertEquals(['one' => null], $object->toArray());

        $object->one = 'again';

        $this->assertNotEmpty($object);
        $this->assertEquals('again', $object->one);
        $this->assertEquals('again', $object['one']);
        $this->assertEquals(['one' => 'again'], $object->toArray());

        $object->one = 'oncemore';

        $this->assertNotEmpty($object);
        $this->assertEquals('oncemore', $object->one);
        $this->assertEquals('oncemore', $object['one']);
        $this->assertEquals(['one' => 'oncemore'], $object->toArray());

        $object->addMetadata(['two' => null]);
        $this->assertNotEmpty($object);
        $this->assertNull($object->two);
        $this->assertNull($object['two']);

        $this->assertTrue(isset($object->one));
        $this->assertTrue(isset($object->two));

        $this->assertFalse(isset($object->three));
        $this->assertFalse(isset($object['three']));

        $this->assertNotEmpty($object->one);
        $this->assertEmpty($object->two);

        unset($object->one);

        $this->assertEquals(['two' => null, 'one' => null], $object->toArray());

        $this->assertTrue(isset($object->one));
        $this->assertTrue(isset($object->two));

        $this->assertEmpty($object->one);
        $this->assertEmpty($object->two);
    }

    public function testCount(): void
    {
        $object = new MetadataArrayObject();
        $this->assertEmpty($object);

        $object = new MetadataArrayObject(['one' => 'two']);
        $this->assertNotEmpty($object);
        $this->assertCount(1, $object);

        $object->one = null;
        $this->assertCount(1, $object);

        $object->addMetadata(['two' => 'second']);
        $this->assertCount(2, $object);

        $object->two = null;
        $this->assertCount(2, $object);

        $object->one = 'first';
        $this->assertCount(2, $object);

        $object->addMetadata(['three' => null]);
        $this->assertCount(3, $object);
    }

    public function testPopulateToArray(): void
    {
        $object = new MetadataArrayObject();
        $this->assertEmpty($object);
        $this->assertEmpty($object->toArray());

        $object = new MetadataArrayObject(['one' => 'first']);
        $this->assertNotEmpty($object);
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['one' => 'first'], $object->toArray());

        $object->populate(['two' => 'second']);
        $this->assertNotEmpty($object);
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['two' => 'second'], $object->toArray());

        $object->populate(['third' => null]);
        $this->assertNotEmpty($object);
        $this->assertNotEmpty($object->toArray());
        $this->assertEquals(['third' => null], $object->toArray());
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
