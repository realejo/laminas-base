<?php

declare(strict_types=1);

namespace RealejoTest\Enum;

use PHPUnit\Framework\TestCase;

class EnumFlaggedTest extends TestCase
{

    public function testGetName(): void
    {
        $enum = new EnumFlaggedConcreteEmpty();
        self::assertEquals([], $enum::getNames());

        $enum = new EnumFlaggedConcrete();
        self::assertEquals(
            [
                1 => 'x',
                2 => 'w',
                4 => 'r'
            ],
            $enum::getNames()
        );
        self::assertNull($enum::getName('Z'));
        self::assertNull($enum::getName(8));
        self::assertEquals('x', $enum::getName(1));
        self::assertEquals('w', $enum::getName(2));
        self::assertEquals('r', $enum::getName(4));
        self::assertNull($enum::getName('1'));
        self::assertNull($enum::getName('2'));
        self::assertNull($enum::getName('4'));

        self::assertNull($enum->getValueName());
        self::assertNull($enum->getValueName('Z'));
        self::assertNull($enum->getValueName(8));
        self::assertEquals('x', $enum->getValueName(1));
        self::assertEquals('w', $enum->getValueName(2));
        self::assertEquals('r', $enum->getValueName(4));
        self::assertNull($enum->getValueName('1'));
        self::assertNull($enum->getValueName('2'));
        self::assertNull($enum->getValueName('4'));


        self::assertEquals('x/w', $enum::getName(1 | 2));
        self::assertEquals('xw', $enum::getName(1 | 2, ''));
        self::assertEquals(
            [
                1 => 'x',
                2 => 'w',
            ],
            $enum::getNameArray(1 | 2)
        );

        self::assertEquals('x/w', $enum->getValueName(1 | 2));
        self::assertEquals('xw', $enum->getValueName(1 | 2, ''));
        self::assertEquals(
            [
                1 => 'x',
                2 => 'w',
            ],
            $enum->getValueNameArray(1 | 2)
        );
    }

    public function testGetNameStatic(): void
    {
        self::assertEquals([], EnumFlaggedConcreteEmpty::getNames());

        self::assertEquals(
            [
                1 => 'x',
                2 => 'w',
                4 => 'r'
            ],
            EnumFlaggedConcrete::getNames()
        );
        self::assertNull(EnumFlaggedConcrete::getName('Z'));
        self::assertNull(EnumFlaggedConcrete::getName(8));
        self::assertEquals('x', EnumFlaggedConcrete::getName(1));
        self::assertEquals('w', EnumFlaggedConcrete::getName(2));
        self::assertEquals('r', EnumFlaggedConcrete::getName(4));
        self::assertNull(EnumFlaggedConcrete::getName('1'));
        self::assertNull(EnumFlaggedConcrete::getName('2'));
        self::assertNull(EnumFlaggedConcrete::getName('4'));

        self::assertEquals('x/w', EnumFlaggedConcrete::getName(1 | 2));
        self::assertEquals('xw', EnumFlaggedConcrete::getName(1 | 2, ''));
        self::assertEquals(
            [
                1 => 'x',
                2 => 'w',
            ],
            EnumFlaggedConcrete::getNameArray(1 | 2)
        );
    }

    public function testGetDescription(): void
    {
        $enum = new EnumFlaggedConcreteEmpty();
        self::assertEquals([], $enum::getDescriptions());
        self::assertNull($enum->getValueDescription());

        $enum = new EnumFlaggedConcrete();
        self::assertEquals(
            [
                1 => 'execute',
                2 => 'w',
                4 => 'r'
            ],
            $enum::getDescriptions()
        );
        self::assertNull($enum::getDescription('Z'));
        self::assertNull($enum::getDescription(8));
        self::assertEquals('execute', $enum::getDescription(1));
        self::assertEquals('w', $enum::getDescription(2));
        self::assertEquals('r', $enum::getDescription(4));
        self::assertNull($enum::getDescription('1'));
        self::assertNull($enum::getDescription('2'));
        self::assertNull($enum::getDescription('4'));

        self::assertNull($enum->getValueDescription());
        self::assertNull($enum->getValueDescription('Z'));
        self::assertNull($enum->getValueDescription(8));
        self::assertEquals('execute', $enum->getValueDescription(1));
        self::assertEquals('w', $enum->getValueDescription(2));
        self::assertEquals('r', $enum->getValueDescription(4));
        self::assertNull($enum->getValueDescription('1'));
        self::assertNull($enum->getValueDescription('2'));
        self::assertNull($enum->getValueDescription('4'));

        self::assertEquals('execute/w', $enum::getDescription(1 | 2));
        self::assertEquals('executew', $enum::getDescription(1 | 2, ''));
        self::assertEquals(
            [
                1 => 'execute',
                2 => 'w',
            ],
            $enum::getDescriptionArray(1 | 2)
        );

        self::assertEquals('execute/w', $enum->getValueDescription(1 | 2));
        self::assertEquals('executew', $enum->getValueDescription(1 | 2, ''));
        self::assertEquals(
            [
                1 => 'execute',
                2 => 'w',
            ],
            $enum->getValueDescriptionArray(1 | 2)
        );
    }

    public function testGetDescriptionStatic(): void
    {
        self::assertEquals([], EnumFlaggedConcreteEmpty::getDescriptions());

        self::assertEquals(
            [
                1 => 'execute',
                2 => 'w',
                4 => 'r'
            ],
            EnumFlaggedConcrete::getDescriptions()
        );
        self::assertNull(EnumFlaggedConcrete::getDescription('Z'));
        self::assertNull(EnumFlaggedConcrete::getDescription(8));
        self::assertEquals('execute', EnumFlaggedConcrete::getDescription(1));
        self::assertEquals('w', EnumFlaggedConcrete::getDescription(2));
        self::assertEquals('r', EnumFlaggedConcrete::getDescription(4));
        self::assertNull(EnumFlaggedConcrete::getDescription('1'));
        self::assertNull(EnumFlaggedConcrete::getDescription('2'));
        self::assertNull(EnumFlaggedConcrete::getDescription('4'));

        self::assertEquals('execute/w', EnumFlaggedConcrete::getDescription(1 | 2));
        self::assertEquals('executew', EnumFlaggedConcrete::getDescription(1 | 2, ''));
        self::assertEquals(
            [
                1 => 'execute',
                2 => 'w',
            ],
            EnumFlaggedConcrete::getDescriptionArray(1 | 2)
        );
    }

    public function testGetValueStatic(): void
    {
        self::assertEquals([], EnumFlaggedConcreteEmpty::getValues());
        self::assertEquals([1, 2, 4], EnumFlaggedConcrete::getValues());
        self::assertEquals([1 << 0, 1 << 1, 1 << 2], EnumFlaggedConcrete::getValues());
    }

    public function testIsValid(): void
    {
        $enum = new EnumFlaggedConcreteEmpty();
        self::assertTrue($enum::isValid(0));
        self::assertFalse($enum::isValid(null));
        self::assertFalse($enum::isValid(''));
        self::assertFalse($enum::isValid(1));

        $enum = new EnumFlaggedConcrete();
        self::assertTrue($enum::isValid(0));
        self::assertFalse($enum::isValid(null));
        self::assertFalse($enum::isValid(''));
        self::assertFalse($enum::isValid(false));
        self::assertFalse($enum::isValid(true));

        self::assertTrue($enum::isValid(1));
        self::assertFalse($enum::isValid('1'));
        self::assertFalse($enum::isValid(1.0));

        self::assertTrue($enum::isValid(2));
        self::assertFalse($enum::isValid('2'));
        self::assertFalse($enum::isValid(2.0));

        self::assertTrue($enum::isValid(3));
        self::assertFalse($enum::isValid('3'));
        self::assertFalse($enum::isValid(3.0));

        self::assertTrue($enum::isValid(7));
        self::assertFalse($enum::isValid('7'));
        self::assertFalse($enum::isValid(7.0));
    }

    public function testIsValidStatic(): void
    {
        self::assertTrue(EnumFlaggedConcreteEmpty::isValid(0));
        self::assertFalse(EnumFlaggedConcreteEmpty::isValid(null));
        self::assertFalse(EnumFlaggedConcreteEmpty::isValid(''));
        self::assertFalse(EnumFlaggedConcreteEmpty::isValid(1));

        self::assertTrue(EnumFlaggedConcrete::isValid(0));
        self::assertFalse(EnumFlaggedConcrete::isValid(null));
        self::assertFalse(EnumFlaggedConcrete::isValid(''));
        self::assertFalse(EnumFlaggedConcrete::isValid(false));
        self::assertFalse(EnumFlaggedConcrete::isValid(true));

        self::assertTrue(EnumFlaggedConcrete::isValid(1));
        self::assertFalse(EnumFlaggedConcrete::isValid('1'));
        self::assertFalse(EnumFlaggedConcrete::isValid(1.0));

        self::assertTrue(EnumFlaggedConcrete::isValid(2));
        self::assertFalse(EnumFlaggedConcrete::isValid('2'));
        self::assertFalse(EnumFlaggedConcrete::isValid(2.0));

        self::assertTrue(EnumFlaggedConcrete::isValid(3));
        self::assertFalse(EnumFlaggedConcrete::isValid('3'));
        self::assertFalse(EnumFlaggedConcrete::isValid(3.0));

        self::assertTrue(EnumFlaggedConcrete::isValid(7));
        self::assertFalse(EnumFlaggedConcrete::isValid('7'));
        self::assertFalse(EnumFlaggedConcrete::isValid(7.0));

        self::assertFalse(EnumFlaggedConcrete::isValid(8));
        self::assertFalse(EnumFlaggedConcrete::isValid('8'));
        self::assertFalse(EnumFlaggedConcrete::isValid(8.0));

        self::assertTrue(EnumFlaggedConcrete::isValid(EnumFlaggedConcrete::READ));
        self::assertTrue(EnumFlaggedConcrete::isValid(EnumFlaggedConcrete::WRITE));
        self::assertTrue(EnumFlaggedConcrete::isValid(EnumFlaggedConcrete::EXECUTE));

        self::assertTrue(
            EnumFlaggedConcrete::isValid(
                EnumFlaggedConcrete::READ | EnumFlaggedConcrete::WRITE | EnumFlaggedConcrete::EXECUTE
            )
        );
        self::assertFalse(
            EnumFlaggedConcrete::isValid(
                (EnumFlaggedConcrete::READ | EnumFlaggedConcrete::WRITE | EnumFlaggedConcrete::EXECUTE) + 1
            )
        );
    }

    public function testGetValue(): void
    {
        $enum = new EnumFlaggedConcreteEmpty();
        self::assertEquals(0, $enum->getValue());

        $enum = new EnumFlaggedConcrete();
        self::assertEquals(0, $enum->getValue());

        $enum = new EnumFlaggedConcrete(EnumFlaggedConcrete::EXECUTE);
        self::assertEquals(EnumFlaggedConcrete::EXECUTE, $enum->getValue());
        self::assertEquals('x', $enum::getName($enum->getValue()));
        self::assertEquals('execute', $enum::getDescription($enum->getValue()));
        self::assertEquals('x', $enum->getValueName());
        self::assertEquals('execute', $enum->getValueDescription());

        $enum = new EnumFlaggedConcrete(EnumFlaggedConcrete::WRITE);
        self::assertEquals(EnumFlaggedConcrete::WRITE, $enum->getValue());
        self::assertEquals('w', $enum::getName($enum->getValue()));
        self::assertEquals('w', $enum::getDescription($enum->getValue()));
        self::assertEquals('w', $enum->getValueName());
        self::assertEquals('w', $enum->getValueDescription());

        $enum = new EnumFlaggedConcrete(EnumFlaggedConcrete::READ);
        self::assertEquals(EnumFlaggedConcrete::READ, $enum->getValue());
        self::assertEquals('r', $enum::getName($enum->getValue()));
        self::assertEquals('r', $enum::getDescription($enum->getValue()));
        self::assertEquals('r', $enum->getValueName());
        self::assertEquals('r', $enum->getValueDescription());

        $enum = new EnumFlaggedConcrete(EnumFlaggedConcrete::READ | EnumFlaggedConcrete::WRITE);
        self::assertEquals(6, $enum->getValue());
        self::assertEquals('w/r', $enum::getName($enum->getValue()));
        self::assertEquals('w/r', $enum::getDescription($enum->getValue()));
        self::assertEquals('w/r', $enum->getValueName());
        self::assertEquals('w/r', $enum->getValueDescription());
    }

    public function testIsHas(): void
    {
        $enum = new EnumFlaggedConcreteEmpty();
        self::assertEquals(0, $enum->getValue());
        self::assertTrue($enum->is(0));
        self::assertFalse($enum->is(1));
        self::assertTrue($enum->has(0));
        self::assertFalse($enum->has(1));

        $enum = new EnumFlaggedConcrete();
        self::assertEquals(0, $enum->getValue());
        self::assertTrue($enum->is(0));
        self::assertFalse($enum->is(1));
        self::assertTrue($enum->has(0));
        self::assertFalse($enum->has(1));

        $enum = new EnumFlaggedConcrete(EnumFlaggedConcrete::EXECUTE);
        self::assertEquals(EnumFlaggedConcrete::EXECUTE, $enum->getValue());
        self::assertFalse($enum->is(0));
        self::assertTrue($enum->is(1));
        self::assertFalse($enum->is('1'));
        self::assertFalse($enum->is(2));
        self::assertFalse($enum->is(4));

        self::assertTrue($enum->has(0));
        self::assertTrue($enum->has(1));
        self::assertFalse($enum->has('1'));
        self::assertFalse($enum->has(2));
        self::assertFalse($enum->has(4));

        self::assertFalse($enum->has(3));
        self::assertFalse($enum->has(5));
        self::assertFalse($enum->has(7));

        $enum = new EnumFlaggedConcrete(EnumFlaggedConcrete::READ | EnumFlaggedConcrete::WRITE);
        self::assertEquals(EnumFlaggedConcrete::READ | EnumFlaggedConcrete::WRITE, $enum->getValue());
        self::assertFalse($enum->is(0));
        self::assertTrue($enum->is(6));
        self::assertFalse($enum->is('6'));
        self::assertFalse($enum->is(6.00));

        self::assertTrue($enum->has(0));
        self::assertFalse($enum->has(1));
        self::assertFalse($enum->has('1'));
        self::assertTrue($enum->has(2));
        self::assertTrue($enum->has(4));
        self::assertFalse($enum->has('2'));
        self::assertFalse($enum->has('4'));

        self::assertFalse($enum->has(3));
        self::assertFalse($enum->has(5));
        self::assertFalse($enum->has(7));
        self::assertFalse($enum->has(8));
    }

    public function testValue(): void
    {
        $empty = new EnumFlaggedConcrete();
        self::assertEquals(0, $empty->getValue());
        self::assertNull($empty->getValueName());
        self::assertNull($empty->getValueDescription());

        $write = new EnumFlaggedConcrete(EnumFlaggedConcrete::WRITE);
        self::assertEquals(EnumFlaggedConcrete::WRITE, $write->getValue());

        $read = new EnumFlaggedConcrete(EnumFlaggedConcrete::READ);
        self::assertEquals(EnumFlaggedConcrete::READ, $read->getValue());

        self::assertNotEquals(EnumFlaggedConcrete::READ, $write->getValue());
        self::assertNotEquals(EnumFlaggedConcrete::WRITE, $read->getValue());
    }

    public function testAddRemove(): void
    {
        $enum = new EnumFlaggedConcrete();
        self::assertEquals(0, $enum->getValue());
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::READ));

        $enum->add(EnumFlaggedConcrete::READ);
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::READ));

        $enum->add(EnumFlaggedConcrete::WRITE);
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::READ));

        $enum->add(0);
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::READ));
        $enum->remove(0);
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::READ));

        $enum->remove(EnumFlaggedConcrete::WRITE);
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::READ));

        $enum->add(EnumFlaggedConcrete::EXECUTE);
        $enum->add(EnumFlaggedConcrete::EXECUTE);
        $enum->add(EnumFlaggedConcrete::EXECUTE);
        self::assertTrue($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertTrue($enum->has(EnumFlaggedConcrete::READ));

        $enum->remove(EnumFlaggedConcrete::READ);
        self::assertTrue($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::READ));

        $enum->remove(EnumFlaggedConcrete::EXECUTE);
        $enum->remove(EnumFlaggedConcrete::EXECUTE);
        $enum->remove(EnumFlaggedConcrete::EXECUTE);
        self::assertFalse($enum->has(EnumFlaggedConcrete::EXECUTE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::WRITE));
        self::assertFalse($enum->has(EnumFlaggedConcrete::READ));

        /**
         * Testes considerando o valor direto
         */
        $enum = new EnumFlaggedConcrete();
        $enum->add(EnumFlaggedConcrete::EXECUTE);
        self::assertEquals(EnumFlaggedConcrete::EXECUTE, $enum->getValue());
        $enum->add(EnumFlaggedConcrete::EXECUTE);
        self::assertEquals(EnumFlaggedConcrete::EXECUTE, $enum->getValue());

        $enum->add(EnumFlaggedConcrete::WRITE);
        self::assertEquals(EnumFlaggedConcrete::EXECUTE | EnumFlaggedConcrete::WRITE, $enum->getValue());

        $enum->remove(EnumFlaggedConcrete::EXECUTE);
        self::assertEquals(EnumFlaggedConcrete::WRITE, $enum->getValue());
        $enum->remove(EnumFlaggedConcrete::EXECUTE);
        self::assertEquals(EnumFlaggedConcrete::WRITE, $enum->getValue());
    }

    public function testAddException(): void
    {
        $enum = new EnumFlaggedConcrete();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value '123' is not valid.");

        $enum->add(123);
    }

    public function testRemoveException(): void
    {
        $enum = new EnumFlaggedConcrete();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Value '123' is not valid.");

        $enum->remove(123);
    }
}
