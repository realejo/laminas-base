<?php

declare(strict_types=1);

namespace RealejoTest\Service;

use PHPUnit\Framework\TestCase;
use Realejo\Service\PaginatorOptions;

class PaginatorOptionsTest extends TestCase
{

    public function testGettersAndSetters(): void
    {
        $paginator = new PaginatorOptions();

        self::assertEquals(1, $paginator->getCurrentPageNumber());
        self::assertInstanceOf(get_class($paginator), $paginator->setCurrentPageNumber(2));
        self::assertEquals(2, $paginator->getCurrentPageNumber());

        self::assertEquals(10, $paginator->getItemCountPerPage());
        self::assertInstanceOf(get_class($paginator), $paginator->setItemCountPerPage(20));
        self::assertEquals(20, $paginator->getItemCountPerPage());

        self::assertEquals(10, $paginator->getPageRange());
        self::assertInstanceOf(get_class($paginator), $paginator->setPageRange(30));
        self::assertEquals(30, $paginator->getPageRange());
    }
}
