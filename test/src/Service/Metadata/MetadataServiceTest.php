<?php

declare(strict_types=1);

namespace RealejoTest\Service\Metadata;

use Exception;
use Laminas\Db\Sql\Expression;
use Realejo\Cache\CacheService;
use Realejo\Service\Metadata\MetadataMapper;
use Realejo\Service\Metadata\MetadataService;
use Realejo\Stdlib\ArrayObject;
use RealejoTest\BaseTestCase;
use ReflectionClass;

class MetadataServiceTest extends BaseTestCase
{
    private MetadataService $metadataService;

    private array $schema = [
        [
            'id_info' => 123,
            'type' => MetadataService::BOOLEAN,
            'nick' => 'bool',
        ],
        [
            'id_info' => 321,
            'type' => MetadataService::DATE,
            'nick' => 'date',
        ],
        [
            'id_info' => 159,
            'type' => MetadataService::DATETIME,
            'nick' => 'datetime',
        ],
        [
            'id_info' => 753,
            'type' => MetadataService::DECIMAL,
            'nick' => 'decimal',
        ],
        [
            'id_info' => 78,
            'type' => MetadataService::INTEGER,
            'nick' => 'integer',
        ]
        ,
        [
            'id_info' => 456,
            'type' => MetadataService::TEXT,
            'nick' => 'text',
        ],
    ];

    private string $cacheFetchAllKey;

    private string $cacheSchemaKey = 'metadataschema_metadata_schema';

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->metadataService = new MetadataService();
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');
        $this->metadataService->setCache($cacheService->getFrontend());

        $this->metadataService
            ->setMapper(MetadataMapperReference::class)
            ->setMetadataMappers('metadata_schema', 'metadata_value', 'fk_reference')
            ->setUseCache(true);

        $this->cacheFetchAllKey = 'fetchAll'
            . md5(
                var_export(false, true)
                . var_export(false, true)
                . var_export(null, true)
                . var_export(null, true)
                . var_export(null, true)
                . var_export(null, true)
            );

        // Grava no cache um fetchAll ficticio
        $fetchAll = [];
        foreach ($this->schema as $row) {
            $fetchAll[] = new ArrayObject($row);
        }
        $this->metadataService
            ->getCache()
            ->setItem($this->cacheFetchAllKey, $fetchAll);

        self::assertEquals($fetchAll, $this->metadataService->getCache()->getItem($this->cacheFetchAllKey));

        // Cria o schema associado pelo id
        $schemaById = [];
        foreach ($this->schema as $s) {
            $schemaById[$s['id_info']] = $s;
        }

        // Grava no cache um metadata ficticio
        $this->metadataService
            ->getCache()
            ->setItem($this->cacheSchemaKey, $schemaById);

        self::assertEquals($schemaById, $this->metadataService->getCache()->getItem($this->cacheSchemaKey));
    }

    private function createTableSchema(): void
    {
        $this->createTables(['metadata_schema', 'metadata_value']);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        $this->metadataService->cleanCache();
        unset($this->metadataService);
        $this->dropTables(['metadata_schema', 'metadata_value']);
        parent::tearDown();
    }

    public function testGetSchemaByKeyNames(): void
    {
        // Cria o schema exemplo para keyname
        $schemaByKeyName = [];
        foreach ($this->schema as $s) {
            $schemaByKeyName[$s['nick']] = $s;
        }
        self::assertEquals($schemaByKeyName, $this->metadataService->getSchemaByKeyNames());
        self::assertEquals($schemaByKeyName, $this->metadataService->getSchemaByKeyNames(true));
        self::assertEquals($schemaByKeyName, $this->metadataService->getSchemaByKeyNames(false));
    }

    /**
     * Tests MetadataService->getCorrectSetKey()
     */
    public function testGetCorrectSetKey(): void
    {
        $service = new MetadataService();
        $reflection = new ReflectionClass(get_class($service));
        $method = $reflection->getMethod('getCorrectSetKey');
        $method->setAccessible(true);

        self::assertEquals('value_boolean', $method->invokeArgs($service, [['type' => MetadataService::BOOLEAN]]));
        self::assertEquals('value_date', $method->invokeArgs($service, [['type' => MetadataService::DATE]]));
        self::assertEquals('value_datetime', $method->invokeArgs($service, [['type' => MetadataService::DATETIME]]));
        self::assertEquals('value_decimal', $method->invokeArgs($service, [['type' => MetadataService::DECIMAL]]));
        self::assertEquals('value_integer', $method->invokeArgs($service, [['type' => MetadataService::INTEGER]]));
        self::assertEquals('value_text', $method->invokeArgs($service, [['type' => MetadataService::TEXT]]));
    }

    /**
     * Tests MetadataService->getCorrectSetKey()
     */
    public function testGetCorrectSetValue(): void
    {
        $service = new MetadataService();
        $reflection = new ReflectionClass(get_class($service));
        $method = $reflection->getMethod('getCorrectSetValue');
        $method->setAccessible(true);

        self::assertEquals(1, $method->invokeArgs($service, [['type' => MetadataService::BOOLEAN], 1]));
        self::assertEquals(1, $method->invokeArgs($service, [['type' => MetadataService::BOOLEAN], true]));
        self::assertEquals(0, $method->invokeArgs($service, [['type' => MetadataService::BOOLEAN], 0]));
        self::assertEquals(0, $method->invokeArgs($service, [['type' => MetadataService::BOOLEAN], false]));

        self::assertNull($method->invokeArgs($service, [['type' => MetadataService::BOOLEAN], null]));
        self::assertEquals(0, $method->invokeArgs($service, [['type' => MetadataService::BOOLEAN], '']));

        self::assertEquals(
            '2016-12-10',
            $method->invokeArgs($service, [['type' => MetadataService::DATE], '10/12/2016'])
        );
        self::assertEquals(
            '2016-12-10',
            $method->invokeArgs($service, [['type' => MetadataService::DATE], '10/12/2016 14:25:24'])
        );
        self::assertEquals('0', $method->invokeArgs($service, [['type' => MetadataService::DATE], '0']));
        self::assertNull($method->invokeArgs($service, [['type' => MetadataService::DATE], null]));

        self::assertEquals(
            'value_datetime',
            $method->invokeArgs($service, [['type' => MetadataService::DATETIME], 'value_datetime'])
        );
        self::assertEquals(
            '2016-12-10 00:00:00',
            $method->invokeArgs($service, [['type' => MetadataService::DATETIME], '10/12/2016'])
        );
        self::assertEquals(
            '2016-12-10 13:13:12',
            $method->invokeArgs($service, [['type' => MetadataService::DATETIME], '10/12/2016 13:13:12'])
        );

        self::assertEquals(0, $method->invokeArgs($service, [['type' => MetadataService::DECIMAL], 'value_decimal']));
        self::assertEquals(
            '0',
            $method->invokeArgs($service, [['type' => MetadataService::DECIMAL], 'value_decimal'])
        );
        self::assertEquals(0, $method->invokeArgs($service, [['type' => MetadataService::INTEGER], 'value_integer']));
        self::assertEquals(
            '0',
            $method->invokeArgs($service, [['type' => MetadataService::INTEGER], 'value_integer'])
        );
        self::assertEquals(
            'value_text',
            $method->invokeArgs($service, [['type' => MetadataService::TEXT], 'value_text'])
        );

        self::assertNull($method->invokeArgs($service, [['type' => MetadataService::DECIMAL], null]));
        self::assertNull($method->invokeArgs($service, [['type' => MetadataService::INTEGER], null]));
        self::assertNull($method->invokeArgs($service, [['type' => MetadataService::TEXT], null]));
    }

    public function testGetMappersSchema(): void
    {
        $service = new MetadataService();
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');
        $service->setCache($cacheService->getFrontend());

        self::assertNull($service->getMapperSchema());
        self::assertNull($service->getMapperValue());
        self::assertInstanceOf(
            MetadataService::class,
            $service->setMetadataMappers(
                'schemaTable',
                'valuesTable',
                'foreignKeyName'
            )
        );
        self::assertInstanceOf(MetadataMapper::class, $service->getMapperSchema());
        self::assertEquals('schemaTable', $service->getMapperSchema()->getTableName());
        self::assertInstanceOf(MetadataMapper::class, $service->getMapperValue());
        self::assertEquals('valuesTable', $service->getMapperValue()->getTableName());
        self::assertEquals(['fk_info', 'foreignKeyName'], $service->getMapperValue()->getTableKey());
        self::assertEquals('fk_info', $service->getMapperValue()->getTableKey(true));
    }

    public function testCache(): void
    {
        $service = new MetadataService();
        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');
        $service->setCache($cacheService->getFrontend());

        self::assertInstanceOf(
            MetadataService::class,
            $service->setMetadataMappers('tableone', 'tablesecond', 'keyname')
        );
        $service->setMapper(MetadataMapperReference::class);

        self::assertFalse($service->getUseCache());
        self::assertFalse($service->getMapperSchema()->getUseCache());
        self::assertFalse($service->getMapperValue()->getUseCache());
        self::assertFalse($service->getMapper()->getUseCache());

        self::assertInstanceOf(MetadataService::class, $service->setUseCache(true));
        self::assertTrue($service->getUseCache());
        self::assertTrue($service->getMapperSchema()->getUseCache());
        self::assertTrue($service->getMapperValue()->getUseCache());

        self::assertInstanceOf(MetadataService::class, $service->setUseCache(false));

        self::assertFalse($service->getUseCache());
        self::assertFalse($service->getMapperSchema()->getUseCache());
        self::assertFalse($service->getMapperValue()->getUseCache());

        self::assertInstanceOf(MetadataService::class, $service->setUseCache(true));

        self::assertTrue($service->getCache()->setItem('servicekey', 'servicedata'));
        self::assertNotEmpty($service->getCache()->hasItem('servicekey'));
        self::assertEquals('servicedata', $service->getCache()->getItem('servicekey'));

        self::assertTrue($service->getMapperSchema()->getCache()->setItem('schemakey', 'schemadata'));
        self::assertNotEmpty($service->getMapperSchema()->getCache()->hasItem('schemakey'));
        self::assertNotEmpty($service->getCache()->hasItem('schemakey'));
        self::assertEquals('schemadata', $service->getMapperSchema()->getCache()->getItem('schemakey'));
        self::assertEquals('schemadata', $service->getCache()->getItem('schemakey'));

        self::assertTrue($service->getMapperValue()->getCache()->setItem('valuekey', 'valuedata'));
        self::assertNotEmpty($service->getMapperValue()->getCache()->hasItem('valuekey'));
        self::assertNotEmpty($service->getCache()->hasItem('valuekey'));
        self::assertEquals('valuedata', $service->getMapperValue()->getCache()->getItem('valuekey'));
        self::assertEquals('valuedata', $service->getCache()->getItem('valuekey'));

        self::assertTrue($service->getCache()->flush());

        self::assertFalse($service->getCache()->hasItem('servicekey'));
        self::assertNull($service->getCache()->getItem('servicekey'));
        self::assertFalse($service->getCache()->hasItem('schemakey'));
        self::assertNull($service->getCache()->getItem('schemakey'));
        self::assertFalse($service->getCache()->hasItem('valuekey'));
        self::assertNull($service->getCache()->getItem('valuekey'));
        self::assertFalse($service->getMapperSchema()->getCache()->hasItem('schemakey'));
        self::assertNull($service->getMapperSchema()->getCache()->getItem('schemakey'));
        self::assertFalse($service->getMapperValue()->getCache()->hasItem('valuekey'));
        self::assertNull($service->getMapperValue()->getCache()->getItem('valuekey'));
    }

    public function testGetSchema(): void
    {
        // Cria o schema associado pelo id
        $schemaById = [];
        foreach ($this->schema as $s) {
            $schemaById[$s['id_info']] = $s;
        }

        self::assertEquals($schemaById, $this->metadataService->getSchema());
        self::assertEquals($schemaById, $this->metadataService->getSchema(true));
        self::assertEquals($schemaById, $this->metadataService->getSchema(false));

        // apaga o cache do schema, mas mantem do fetchAll
        self::assertTrue($this->metadataService->getCache()->removeItem($this->cacheSchemaKey));

        self::assertEquals($schemaById, $this->metadataService->getSchema());
        self::assertEquals($schemaById, $this->metadataService->getSchema(true));
        self::assertEquals($schemaById, $this->metadataService->getSchema(false));
    }

    /**
     * Tests MetadataMapper->getWhere()
     * @depends testGetMappersSchema
     */
    public function testGetWhere(): void
    {
        self::assertIsArray($this->metadataService->getWhere([]));
        self::assertEquals([], $this->metadataService->getWhere([]));

        self::assertIsArray($this->metadataService->getWhere(['metadata' => []]));
        self::assertEquals([], $this->metadataService->getWhere(['metadata' => []]));

        self::assertIsArray($this->metadataService->getWhere(['metadata' => null]));
        self::assertEquals([], $this->metadataService->getWhere(['metadata' => null]));
    }

    /**
     * Tests MetadataMapper->getWhere()
     * @depends testGetMappersSchema
     */
    public function testGetWhereBoolean(): void
    {
        // Cria as tabelas
        $this->createTableSchema();

        /** @var $where Expression[] */
        $where = $this->metadataService->getWhere(['metadata' => ['bool' => true]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 1)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['bool' => true]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 1)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['bool' => false]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 0)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['bool' => false]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 0)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['bool' => 1]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 1)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['bool' => 1]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 1)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['bool' => 0]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 0)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['bool' => 0]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean = 0)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['bool' => null]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(123)})",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['bool' => null]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(123)} AND value_boolean IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(123)})",
            $where[0]->getExpression()
        );
        /*  array(
         'cd_info' => 321,
         'type' => MetadataService::DATE,
         'nick' => 'date'
         ),
         array(
         'cd_info' => 159,
         'type' => MetadataService::DATETIME,
         'nick' => 'datetime'
         ),
         array(
         'cd_info' => 753,
         'type' => MetadataService::DECIMAL,
         'nick' => 'decimal'
         ),
         ) */
    }

    /**
     * @depends testGetMappersSchema
     */
    public function testGetWhereInteger(): void
    {
        // Cria as tabelas
        $this->createTableSchema();

        /**
         * @var $where Expression[]
         */
        $where = $this->metadataService->getWhere(['metadata' => ['integer' => 10]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer = 10)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['integer' => 10]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer = 10)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['integer' => 0]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer = 0)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['integer' => 0]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer = 0)",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['integer' => null]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(78)})",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['integer' => null]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(78)})",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['integer' => -99]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(78)} AND value_integer = -99)",
            $where[0]->getExpression()
        );
    }

    /**
     * @depends testGetMappersSchema
     */
    public function testGetWhereString(): void
    {
        // Cria as tabelas
        $this->createTableSchema();

        /** @var $where Expression[] */
        $where = $this->metadataService->getWhere(['metadata' => ['text' => 10]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text = '10')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['text' => 10]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text = '10')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['text' => 0]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text = '0')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['text' => 0]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text = '0')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['text' => 'qwerty']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text = 'qwerty')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['text' => 'qwerty']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text = 'qwerty')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['text' => '']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(456)})",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['text' => '']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(456)})",
            $where[0]->getExpression()
        );


        $where = $this->metadataService->getWhere(['metadata' => ['text' => null]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(456)})",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['text' => null]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(456)} AND value_text IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(456)})",
            $where[0]->getExpression()
        );
    }

    /**
     * @depends testGetMappersSchema
     */
    public function testGetWhereDate(): void
    {
        // Cria as tabelas
        $this->createTableSchema();

        /** @var $where Expression[] */
        $where = $this->metadataService->getWhere(['metadata' => ['date' => '15/10/2016']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['date' => '15/10/2016']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['date' => '2016-10-15']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['date' => '2016-10-15']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['date' => '15/10/2016 14:24:35']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['date' => '15/10/2016 14:24:35']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['date' => '2016-10-15 14:24:35']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['date' => '2016-10-15 14:24:35']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '2016-10-15')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['date' => '']]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['date' => '']);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date = '')",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['metadata' => ['date' => null]]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(321)})",
            $where[0]->getExpression()
        );

        $where = $this->metadataService->getWhere(['date' => null]);
        self::assertIsArray($where);
        self::assertCount(1, $where);
        self::assertInstanceOf(Expression::class, $where[0]);
        self::assertEquals(
            "EXISTS ({$this->getSqlSchemaString(321)} AND value_date IS NULL)"
            . " OR NOT EXISTS ({$this->getSqlSchemaString(321)})",
            $where[0]->getExpression()
        );
    }

    private function getSqlSchemaString($idInfo): string
    {
        return "SELECT * FROM metadata_value"
            . " WHERE metadata_value.fk_info=$idInfo"
            . " AND tblreference.id_reference=metadata_value.fk_reference";
    }

    public function testSetSchemaMapper(): void
    {
        $service = new MetadataService();

        $this->expectExceptionMessage("schemaTable invalid");
        $this->expectException(Exception::class);

        $service->setMetadataMappers(
            '',
            '',
            ''
        );
    }

    public function testSetValuesMapper(): void
    {
        $service = new MetadataService();

        $this->expectExceptionMessage("valueTable invalid");
        $this->expectException(Exception::class);

        $service->setMetadataMappers(
            'tableone',
            '',
            ''
        );
    }

    public function testSetForeignKey(): void
    {
        $service = new MetadataService();

        $this->expectExceptionMessage("mapperForeignKey invalid");
        $this->expectException(Exception::class);

        $service->setMetadataMappers('tableone', 'tableone', '');
    }
}
