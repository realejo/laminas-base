<?php

declare(strict_types=1);

namespace RealejoTest\Service\Mptt;

use Exception;
use Realejo\Cache\CacheService;
use Realejo\Service;
use RealejoTest\BaseTestCase;

class MpttTest extends BaseTestCase
{
    /**
     * Árvore mptt completa e *correta*
     * com left,right ordenado pelo id
     */
    protected array $idOrderedTree = [
        [1, 'Food', null, 1, 24],
        [2, 'Fruit', 1, 2, 13],
        [3, 'Red', 2, 3, 6],
        [4, 'Yellow', 2, 7, 10],
        [5, 'Green', 2, 11, 12],
        [6, 'Cherry', 3, 4, 5],
        [7, 'Banana', 4, 8, 9],
        [8, 'Meat', 1, 14, 19],
        [9, 'Beef', 8, 15, 16],
        [10, 'Pork', 8, 17, 18],
        [11, 'Vegetable', 1, 20, 23],
        [12, 'Carrot', 11, 21, 22],
    ];

    /**
     * Árvore mptt completa e *correta*
     * com left,right ordenado pelo name
     */
    protected array $nameOrderedTree = [
        [1, 'Food', null, 1, 24],
        [2, 'Fruit', 1, 2, 13],
        [3, 'Red', 2, 5, 8],
        [4, 'Yellow', 2, 9, 12],
        [5, 'Green', 2, 3, 4],
        [6, 'Cherry', 3, 6, 7],
        [7, 'Banana', 4, 10, 11],
        [8, 'Meat', 1, 14, 19],
        [9, 'Beef', 8, 15, 16],
        [10, 'Pork', 8, 17, 18],
        [11, 'Vegetable', 1, 20, 23],
        [12, 'Carrot', 11, 21, 22],
    ];

    /**
     * Será populada com os valores da arvore completa
     */
    protected array $idOrderedRows = [];
    protected array $nameOrderedRows = [];

    /**
     * Será populada com os valores da arvore completa sem as informações left,right
     */
    protected array $defaultRows = [];

    protected array $tables = ['mptt'];

    protected function setUp(): void
    {
        parent::setUp();

        $fields = ['id', 'name', 'parent_id', 'lft', 'rgt'];
        foreach ($this->idOrderedTree as $values) {
            $row = array_combine($fields, $values);
            $this->idOrderedRows[] = $row;
            unset($row['lft'], $row['rgt']);
            $this->defaultRows[] = $row;
        }

        foreach ($this->nameOrderedTree as $values) {
            $row = array_combine($fields, $values);
            $this->nameOrderedRows[] = $row;
        }

        $this->dropTables()->createTables();
    }

    public function testConstruct(): void
    {
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());

        self::assertInstanceOf(Service\Mptt\MpttServiceAbstract::class, $mptt);
        self::assertInstanceOf(Service\ServiceAbstract::class, $mptt);
        self::assertInstanceOf(MapperConcrete::class, $mptt->getMapper());
    }

    public function testSetTraversalIncomplete(): void
    {
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());

        self::assertInstanceOf(Service\Mptt\MpttServiceAbstract::class, $mptt);
        self::assertInstanceOf(Service\ServiceAbstract::class, $mptt);

        $mptt = $mptt->setTraversal([]);

        self::assertInstanceOf(Service\Mptt\MpttServiceAbstract::class, $mptt);

        $this->expectException(Exception::class);
        $mptt->setTraversal(['invalid' => 'invalid']);
    }

    public function testGetColumns(): void
    {
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());
        // Laminas retorna em ordem alfabética e não do bd
        $columns = $mptt->getColumns();
        $expected = ['id', 'name', 'parent_id', 'lft', 'rgt'];
        self::assertCount(count($expected), $columns);
        foreach ($expected as $item) {
            self::assertContains($item, $expected);
        }
    }

    public function testSetTraversal(): void
    {
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());

        self::assertFalse($mptt->isTraversable());
        $mptt->setTraversal('parent_id');
        self::assertTrue($mptt->isTraversable());
    }

    public function testRebuildTreeTraversal(): void
    {
        // Cria a tabela com os valores padrões
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());

        self::assertNull($mptt->getMapper()->fetchAll());
        foreach ($this->defaultRows as $row) {
            $mptt->insert($row);
        }
        self::assertNotNull($mptt->getMapper()->fetchAll());
        self::assertCount(count($this->defaultRows), $mptt->getMapper()->fetchAll());

        // Set traversal
        self::assertFalse($mptt->isTraversable());
        $mptt->setTraversal('parent_id');
        self::assertTrue($mptt->isTraversable());

        // Rebuild Tree
        $mptt->rebuildTreeTraversal();

        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->idOrderedRows, $fetchArray);
        $mptt->setTraversal(['refColumn' => 'parent_id', 'order' => 'name']);

        // Rebuild Tree
        $mptt->rebuildTreeTraversal();
        self::assertTrue($mptt->isTraversable());

        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->nameOrderedRows, $fetchArray);
    }

    public function testInsert(): void
    {
        // Cria a tabela com os valores padrões
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());

        $mptt->getMapper()->setOrder('id');
        self::assertNull($mptt->getMapper()->fetchAll());

        // Set traversal
        self::assertFalse($mptt->isTraversable());
        $mptt->setTraversal(['refColumn' => 'parent_id', 'order' => 'name']);
        self::assertTrue($mptt->isTraversable());

        // Insert default rows
        foreach ($this->defaultRows as $row) {
            $mptt->insert($row);
        }
        self::assertNotNull($mptt->getMapper()->fetchAll());
        self::assertCount(count($this->defaultRows), $mptt->getMapper()->fetchAll());

        // Assert if left/right is correct
        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->nameOrderedRows, $fetchArray);

        // reset the table
        $this->dropTables()->createTables();
        self::assertNull($mptt->getMapper()->fetchAll());

        // Set traversal ordered by id
        $mptt->setTraversal(['refColumn' => 'parent_id']);
        self::assertTrue($mptt->isTraversable());

        // insert default rows
        foreach ($this->defaultRows as $row) {
            $mptt->insert($row);
        }
        self::assertNotNull($mptt->getMapper()->fetchAll());
        self::assertCount(count($this->defaultRows), $mptt->getMapper()->fetchAll());

        // Assert if left/right is correct
        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->idOrderedRows, $fetchArray);
    }

    public function testDelete(): void
    {
        // Cria a tabela com os valores padrões
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');

        // Cria a tabela sem a implementação do transversable
        $mapper = new MapperConcrete();
        $mapper->setCache($cacheService->getFrontend());

        $mptt = new ServiceConcrete($mapper, 'id');
        $mptt->setCache($cacheService->getFrontend());

        $mptt->getMapper()->setOrder('id');
        self::assertNull($mptt->getMapper()->fetchAll());

        // Set traversal
        self::assertFalse($mptt->isTraversable());
        $mptt->setTraversal(['refColumn' => 'parent_id', 'order' => 'name']);
        self::assertTrue($mptt->isTraversable());

        // Insert default rows
        foreach ($this->defaultRows as $row) {
            $mptt->insert($row);
        }
        self::assertNotNull($mptt->getMapper()->fetchAll());
        self::assertCount(count($this->defaultRows), $mptt->getMapper()->fetchAll());

        // Assert if left/right is correct
        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->nameOrderedRows, $fetchArray);

        // Remove a single node (Beef/9)
        $mptt->delete(9);

        // Verify its parent (Meat/8)
        $row = $mptt->getMapper()->fetchRow(8);
        self::assertNotNull($row);
        self::assertEquals(14, $row['lft']);
        self::assertEquals(17, $row['rgt']);

        // Verify its sibling (Pork/10)
        $row = $mptt->getMapper()->fetchRow(10);
        self::assertNotNull($row);
        self::assertEquals(15, $row['lft']);
        self::assertEquals(16, $row['rgt']);

        // Verify the root (Food/1)
        $row = $mptt->getMapper()->fetchRow(1);
        self::assertNotNull($row);
        self::assertEquals(1, $row['lft']);
        self::assertEquals(22, $row['rgt']);

        // Verify its uncle (Vegetable/11)
        $row = $mptt->getMapper()->fetchRow(11);
        self::assertNotNull($row);
        self::assertEquals(18, $row['lft']);
        self::assertEquals(21, $row['rgt']);

        // Verify its another uncle (Fruit/2)
        $row = $mptt->getMapper()->fetchRow(2);
        self::assertNotNull($row);
        self::assertEquals(2, $row['lft']);
        self::assertEquals(13, $row['rgt']);

        // Put it back
        $mptt->insert($this->defaultRows[9 - 1]);

        // Assert if left/right is correct
        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->nameOrderedRows, $fetchArray);

        // Remove a node with child (Meat/8)
        $mptt->delete(8);

        // Verify its childs is gone
        self::assertNull($mptt->getMapper()->fetchRow(8));
        self::assertNull($mptt->getMapper()->fetchRow(9));
        self::assertNull($mptt->getMapper()->fetchRow(10));

        // Verify the root (Food/1)
        $row = $mptt->getMapper()->fetchRow(1);
        self::assertNotNull($row);
        self::assertEquals(1, $row['lft']);
        self::assertEquals(18, $row['rgt']);

        // Verify its uncle (Vegetable/11)
        $row = $mptt->getMapper()->fetchRow(11);
        self::assertNotNull($row);
        self::assertEquals(14, $row['lft']);
        self::assertEquals(17, $row['rgt']);

        // Verify its another uncle (Fruit/2)
        $row = $mptt->getMapper()->fetchRow(2);
        self::assertNotNull($row);
        self::assertEquals(2, $row['lft']);
        self::assertEquals(13, $row['rgt']);

        // Put them back
        $mptt->insert($this->defaultRows[8 - 1]);
        $mptt->insert($this->defaultRows[10 - 1]);
        $mptt->insert($this->defaultRows[9 - 1]);

        // Assert if left/right is correct
        $fetchArray = [];
        foreach ($mptt->getMapper()->fetchAll() as $value) {
            $fetchArray[] = $value->toArray();
        }
        self::assertEquals($this->nameOrderedRows, $fetchArray);
    }
}
