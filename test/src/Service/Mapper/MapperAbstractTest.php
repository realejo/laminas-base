<?php

declare(strict_types=1);

namespace RealejoTest\Service\Mapper;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use LogicException;
use Realejo\Cache\CacheService;
use Realejo\Service\MapperAbstract;
use Realejo\Stdlib\ArrayObject;
use RealejoTest\BaseTestCase;

class MapperAbstractTest extends BaseTestCase
{
    protected string $tableName = 'album';

    protected string $tableKeyName = 'id';

    protected array $tables = ['album'];

    private MapperConcrete $mapper;

    private MapperConcreteDeprecated $mapperDeprecated;

    protected array $defaultValues = [
        [
            'id' => 1,
            'artist' => 'Rush',
            'title' => 'Rush',
            'deleted' => 0,
        ],
        [
            'id' => 2,
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0,
        ],
        [
            'id' => 3,
            'artist' => 'Dream Theater',
            'title' => 'Images And Words',
            'deleted' => 0,
        ],
        [
            'id' => 4,
            'artist' => 'Claudia Leitte',
            'title' => 'Exttravasa',
            'deleted' => 1,
        ],
    ];

    public function insertDefaultRows(): self
    {
        foreach ($this->defaultValues as $row) {
            $this->getAdapter()->query(
                "INSERT into {$this->tableName}({$this->tableKeyName}, artist, title, deleted)
                                        VALUES (
                                            {$row[$this->tableKeyName]},
                                            '{$row['artist']}',
                                            '{$row['title']}',
                                            {$row['deleted']}
                                        );",
                Adapter::QUERY_MODE_EXECUTE
            );
        }

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTables()->createTables();
        $this->insertDefaultRows();

        // Remove as pastas criadas
        $this->clearApplicationData();

        // Configura o mapper
        $this->mapper = new MapperConcrete($this->tableName, $this->tableKeyName);
        $this->mapperDeprecated = new MapperConcreteDeprecated($this->tableName, $this->tableKeyName);

        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');
        $this->mapper->setCache($cacheService->getFrontend());
        $this->mapperDeprecated->setCache($cacheService->getFrontend());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropTables();

        $this->clearApplicationData();
    }

    public function testKeyNameInvalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mapper->setTableKey(null);
    }

    public function testOrderInvalida(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mapper->setOrder(null);
    }

    public function testFetchRowMultiKeyException(): void
    {
        // Cria a tabela com chave string
        $this->mapper->setTableKey([MapperConcrete::KEY_INTEGER => 'id_int', MapperConcrete::KEY_STRING => 'id_char']);

        $this->expectException(\InvalidArgumentException::class);

        $this->mapper->fetchRow(1);
    }

    public function testGettersSetters(): void
    {
        self::assertEquals('meuid', $this->mapper->setTableKey('meuid')->getTableKey());
        self::assertEquals('meuid', $this->mapper->setTableKey('meuid')->getTableKey(true));
        self::assertEquals('meuid', $this->mapper->setTableKey('meuid')->getTableKey(false));

        self::assertEquals(['meuid', 'com array'], $this->mapper->setTableKey(['meuid', 'com array'])->getTableKey());
        self::assertEquals(
            ['meuid', 'com array'],
            $this->mapper->setTableKey(['meuid', 'com array'])->getTableKey(false)
        );
        self::assertEquals('meuid', $this->mapper->setTableKey(['meuid', 'com array'])->getTableKey(true));

        self::assertInstanceOf(
            Expression::class,
            $this->mapper->setTableKey(new Sql\Expression('chave muito exotica!'))->getTableKey()
        );
        self::assertInstanceOf(
            Expression::class,
            $this->mapper->setTableKey(
                [
                    new Sql\Expression('chave muito mais exotica!'),
                    'não existo',
                ]
            )->getTableKey(true)
        );

        self::assertEquals('minhaordem', $this->mapper->setOrder('minhaordem')->getOrder());
        self::assertEquals(
            ['minhaordem', 'comarray'],
            $this->mapper->setOrder(['minhaordem', 'comarray'])->getOrder()
        );
        self::assertInstanceOf(
            Expression::class,
            $this->mapper->setOrder(new Sql\Expression('ordem muito exotica!'))->getOrder()
        );
    }

    public function testCreateBase(): void
    {
        $Base = new MapperConcrete($this->tableName, $this->tableKeyName);
        self::assertInstanceOf(MapperAbstract::class, $Base);
        self::assertEquals($this->tableKeyName, $Base->getTableKey());
        self::assertEquals($this->tableName, $Base->getTableName());

        $Base = new MapperConcrete($this->tableName, [$this->tableKeyName, $this->tableKeyName]);
        self::assertInstanceOf(MapperAbstract::class, $Base);
        self::assertEquals([$this->tableKeyName, $this->tableKeyName], $Base->getTableKey());
        self::assertEquals($this->tableName, $Base->getTableName());

        $Base = new MapperConcrete($this->tableName, $this->tableKeyName);
        self::assertInstanceOf(MapperAbstract::class, $Base);
        self::assertInstanceOf(
            get_class($this->getAdapter()),
            $Base->getTableGateway()->getAdapter(),
            'tem o Adapter padrão'
        );
        self::assertEquals(
            $this->getAdapter(),
            $Base->getTableGateway()->getAdapter(),
            'tem a mesma configuração do adapter padrão'
        );
    }

    public function testOrder(): void
    {
        // Verifica a ordem padrão
        self::assertNull($this->mapper->getOrder());

        // Define uma nova ordem com string
        $this->mapper->setOrder('id');
        self::assertEquals('id', $this->mapper->getOrder());

        // Define uma nova ordem com string
        $this->mapper->setOrder('title');
        self::assertEquals('title', $this->mapper->getOrder());

        // Define uma nova ordem com array
        $this->mapper->setOrder(['id', 'title']);
        self::assertEquals(['id', 'title'], $this->mapper->getOrder());
    }

    /**
     * Tests Base->getWhere()
     *
     * Apenas para ter o coverage completo
     */
    public function testWhere(): void
    {
        self::assertEquals(['123456789abcde'], $this->mapper->getWhere(['123456789abcde']));
    }

    public function testDeletedField(): void
    {
        // Verifica se deve remover o registro
        $this->mapper->setUseDeleted(false);
        self::assertFalse($this->mapper->getUseDeleted());
        self::assertTrue($this->mapper->setUseDeleted(true)->getUseDeleted());
        self::assertFalse($this->mapper->setUseDeleted(false)->getUseDeleted());
        self::assertFalse($this->mapper->getUseDeleted());

        // Verifica se deve mostrar o registro
        $this->mapper->setShowDeleted(false);
        self::assertFalse($this->mapper->getShowDeleted());
        self::assertFalse($this->mapper->setShowDeleted(false)->getShowDeleted());
        self::assertTrue($this->mapper->setShowDeleted(true)->getShowDeleted());
        self::assertTrue($this->mapper->getShowDeleted());
    }

    public function testGetSQlString(): void
    {
        // Verifica o padrão de não usar o campo deleted e não mostrar os removidos
        $this->mapper->setOrder('id');
        self::assertEquals(
            'SELECT `album`.* FROM `album` ORDER BY `id` ASC',
            $this->mapper->getSelect()->getSqlString($this->adapter->getPlatform()),
            'showDeleted=false, useDeleted=false'
        );

        // Marca para usar o campo deleted
        $this->mapper->setUseDeleted(true);
        self::assertEquals(
            'SELECT `album`.* FROM `album` WHERE `album`.`deleted` = \'0\' ORDER BY `id` ASC',
            $this->mapper->getSelect()->getSqlString($this->adapter->getPlatform()),
            'showDeleted=false, useDeleted=true'
        );

        // Marca para não usar o campo deleted
        $this->mapper->setUseDeleted(false);

        self::assertEquals(
            'SELECT `album`.* FROM `album` WHERE `album`.`id` = \'1234\' ORDER BY `id` ASC',
            $this->mapper->getSelect(['id' => 1234])->getSqlString($this->adapter->getPlatform())
        );
        self::assertEquals(
            'SELECT `album`.* FROM `album` WHERE `album`.`texto` = \'textotextotexto\' ORDER BY `id` ASC',
            $this->mapper->getSelect(['texto' => 'textotextotexto'])->getSqlString($this->adapter->getPlatform())
        );
    }

    public function testGetSQlSelectWithJoinLeft(): void
    {
        $select = $this->mapper->getTableSelect();
        self::assertInstanceOf(Select::class, $select);
        self::assertEquals($select->getSqlString(), $this->mapper->getTableSelect()->getSqlString());
        self::assertEquals('SELECT `album`.* FROM `album`', $select->getSqlString($this->adapter->getPlatform()));

        $this->mapper->setUseJoin(true);
        $select = $this->mapper->getTableSelect();
        self::assertEquals(
            'SELECT `album`.*, `test_table`.`test_column` AS `test_column`'
            . ' FROM `album` LEFT JOIN `test_table` ON `test_condition`',
            $select->getSqlString($this->adapter->getPlatform())
        );

        $this->mapperDeprecated->setUseJoin(true);
        $select = $this->mapperDeprecated->getTableSelect();
        self::assertEquals(
            'SELECT `album`.*, `test_table`.`test_column` AS `test_column`'
            . ' FROM `album` LEFT JOIN `test_table` ON `test_condition`',
            $select->getSqlString($this->adapter->getPlatform())
        );
    }

    public function testFetchAll(): void
    {
        self::assertFalse($this->mapper->isUseHydrateResultSet());

        // O padrão é não usar o campo deleted
        $this->mapper->setOrder('id');
        $albuns = $this->mapper->fetchAll();
        self::assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->mapper->setShowDeleted(true)->setUseDeleted(false);
        self::assertCount(4, $this->mapper->fetchAll(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostrar os removidos e usar o campo deleted
        $this->mapper->setShowDeleted(false)->setUseDeleted(true);
        self::assertCount(3, $this->mapper->fetchAll(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->mapper->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->mapper->fetchAll();
        self::assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');

        // Marca não mostrar os removios
        $this->mapper->setUseDeleted(true)->setShowDeleted(false);

        $albuns = $this->defaultValues;
        unset($albuns[3]); // remove o deleted=1

        $fetchAll = $this->mapper->fetchAll();
        foreach ($fetchAll as $id => $row) {
            $fetchAll[$id] = $row->toArray();
        }
        self::assertEquals($albuns, $fetchAll);

        // Marca mostrar os removios
        $this->mapper->setShowDeleted(true);

        $fetchAll = $this->mapper->fetchAll();
        foreach ($fetchAll as $id => $row) {
            $fetchAll[$id] = $row->toArray();
        }
        self::assertEquals($this->defaultValues, $fetchAll);
        self::assertCount(4, $this->mapper->fetchAll());
        $this->mapper->setShowDeleted(false);
        self::assertCount(3, $this->mapper->fetchAll());

        // Verifica o where
        self::assertCount(2, $this->mapper->fetchAll(['artist' => $albuns[0]['artist']]));
        self::assertNull($this->mapper->fetchAll(['artist' => $this->defaultValues[3]['artist']]));

        // Apaga qualquer cache
        self::assertTrue($this->mapper->getCache()->flush(), 'apaga o cache');

        // Define exibir os removidos
        $this->mapper->setShowDeleted(true);

        // Liga o cache
        $this->mapper->setUseCache(true);
        $fetchAll = $this->mapper->fetchAll();
        foreach ($fetchAll as $id => $row) {
            $fetchAll[$id] = $row->toArray();
        }
        self::assertEquals($this->defaultValues, $fetchAll, 'fetchAll está igual ao defaultValues');
        self::assertCount(4, $this->mapper->fetchAll(), 'Deve conter 4 registros');

        // Grava um registro "sem o cache saber"
        $this->mapper->getTableGateway()->insert(
            [
                'id' => 10,
                'artist' => 'nao existo por enquanto',
                'title' => 'bla bla',
                'deleted' => 0,
            ]
        );

        self::assertCount(
            4,
            $this->mapper->fetchAll(),
            'Deve conter 4 registros depois do insert "sem o cache saber"'
        );
        self::assertTrue($this->mapper->getCache()->flush(), 'limpa o cache');
        self::assertCount(5, $this->mapper->fetchAll(), 'Deve conter 5 registros');

        // Define não exibir os removidos
        $this->mapper->setShowDeleted(false);
        self::assertCount(4, $this->mapper->fetchAll(), 'Deve conter 4 registros com showDeleted=false');

        // Apaga um registro "sem o cache saber"
        $this->mapper->getTableGateway()->delete("id=10");
        $this->mapper->setShowDeleted(true);
        self::assertCount(5, $this->mapper->fetchAll(), 'Deve conter 5 registros');
        self::assertTrue($this->mapper->getCache()->flush(), 'apaga o cache');
        self::assertCount(4, $this->mapper->fetchAll(), 'Deve conter 4 registros 4');
    }

    public function testFetchAllHydrateResultSet(): void
    {
        $this->mapper->setUseHydrateResultSet(true);
        self::assertTrue($this->mapper->isUseHydrateResultSet());

        // O padrão é não usar o campo deleted
        $this->mapper->setOrder('id');
        $albuns = $this->mapper->fetchAll();
        self::assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->mapper->setShowDeleted(true)->setUseDeleted(false);
        self::assertCount(4, $this->mapper->fetchAll(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostrar os removidos e usar o campo deleted
        $this->mapper->setShowDeleted(false)->setUseDeleted(true);
        self::assertCount(3, $this->mapper->fetchAll(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->mapper->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->mapper->fetchAll();
        self::assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');

        // Marca não mostrar os removios
        $this->mapper->setUseDeleted(true)->setShowDeleted(false);

        $albuns = $this->defaultValues;
        unset($albuns[3]); // remove o deleted=1

        $fetchAll = $this->mapper->fetchAll();
        self::assertInstanceOf(HydratingResultSet::class, $fetchAll);
        $fetchAll = $fetchAll->toArray();
        self::assertEquals($albuns, $fetchAll);

        // Marca mostrar os removidos
        $this->mapper->setShowDeleted(true);

        $fetchAll = $this->mapper->fetchAll();
        self::assertInstanceOf(HydratingResultSet::class, $fetchAll);
        $fetchAll = $fetchAll->toArray();

        self::assertEquals($this->defaultValues, $fetchAll);
        self::assertCount(4, $this->mapper->fetchAll());
        $this->mapper->setShowDeleted(false);
        self::assertCount(3, $this->mapper->fetchAll());

        // Verifica o where
        self::assertCount(2, $this->mapper->fetchAll(['artist' => $albuns[0]['artist']]));
        self::assertNull($this->mapper->fetchAll(['artist' => $this->defaultValues[3]['artist']]));

        // Apaga qualquer cache
        self::assertTrue($this->mapper->getCache()->flush(), 'apaga o cache');

        // Define exibir os removidos
        $this->mapper->setShowDeleted(true);

        // Liga o cache
        $this->mapper->setUseCache(true);
        $fetchAll = $this->mapper->fetchAll();
        self::assertInstanceOf(HydratingResultSet::class, $fetchAll);
        $fetchAll = $fetchAll->toArray();

        self::assertEquals($this->defaultValues, $fetchAll, 'fetchAll está igual ao defaultValues');
        self::assertCount(4, $this->mapper->fetchAll(), 'Deve conter 4 registros');

        // Grava um registro "sem o cache saber"
        $this->mapper->getTableGateway()->insert(
            [
                'id' => 10,
                'artist' => 'nao existo por enquanto',
                'title' => 'bla bla',
                'deleted' => 0,
            ]
        );

        self::assertCount(
            4,
            $this->mapper->fetchAll(),
            'Deve conter 4 registros depois do insert "sem o cache saber"'
        );
        self::assertTrue($this->mapper->getCache()->flush(), 'limpa o cache');
        self::assertCount(5, $this->mapper->fetchAll(), 'Deve conter 5 registros');

        // Define não exibir os removidos
        $this->mapper->setShowDeleted(false);
        self::assertCount(4, $this->mapper->fetchAll(), 'Deve conter 4 registros com showDeleted=false');

        // Apaga um registro "sem o cache saber"
        $this->mapper->getTableGateway()->delete("id=10");
        $this->mapper->setShowDeleted(true);
        self::assertCount(5, $this->mapper->fetchAll(), 'Deve conter 5 registros');
        self::assertTrue($this->mapper->getCache()->flush(), 'apaga o cache');
        self::assertCount(4, $this->mapper->fetchAll(), 'Deve conter 4 registros 4');
    }

    public function testFetchRow(): void
    {
        self::assertFalse($this->mapper->isUseHydrateResultSet());

        // Marca pra usar o campo deleted
        $this->mapper->setUseDeleted(true);
        $this->mapper->setOrder('id');

        // Verifica os itens que existem
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(1));
        self::assertEquals($this->defaultValues[0], $this->mapper->fetchRow(1)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(2));
        self::assertEquals($this->defaultValues[1], $this->mapper->fetchRow(2)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(3));
        self::assertEquals($this->defaultValues[2], $this->mapper->fetchRow(3)->toArray());

        // Verifica o item removido
        $this->mapper->setShowDeleted(true);
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(4));
        self::assertEquals($this->defaultValues[3], $this->mapper->fetchRow(4)->toArray());
        $this->mapper->setShowDeleted(false);
    }

    public function testFetchRowHydrateResultaSet(): void
    {
        $this->mapper->setUseHydrateResultSet(true);
        self::assertTrue($this->mapper->isUseHydrateResultSet());

        // Marca pra usar o campo deleted
        $this->mapper->setUseDeleted(true);
        $this->mapper->setOrder('id');

        // Verifica os itens que existem
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(1));
        self::assertEquals($this->defaultValues[0], $this->mapper->fetchRow(1)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(2));
        self::assertEquals($this->defaultValues[1], $this->mapper->fetchRow(2)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(3));
        self::assertEquals($this->defaultValues[2], $this->mapper->fetchRow(3)->toArray());

        // Verifica o item removido
        $this->mapper->setShowDeleted(true);
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(4));
        self::assertEquals($this->defaultValues[3], $this->mapper->fetchRow(4)->toArray());
        $this->mapper->setShowDeleted(false);
    }

    public function testFetchRowWithIntegerKey(): void
    {
        $this->mapper->setTableKey([MapperConcrete::KEY_INTEGER => 'id']);

        // Marca pra usar o campo deleted
        $this->mapper->setUseDeleted(true);
        $this->mapper->setOrder('id');

        // Verifica os itens que existem
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(1));
        self::assertEquals($this->defaultValues[0], $this->mapper->fetchRow(1)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(2));
        self::assertEquals($this->defaultValues[1], $this->mapper->fetchRow(2)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(3));
        self::assertEquals($this->defaultValues[2], $this->mapper->fetchRow(3)->toArray());

        // Verifica o item removido
        $this->mapper->setShowDeleted(true);
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(4));
        self::assertEquals($this->defaultValues[3], $this->mapper->fetchRow(4)->toArray());
        $this->mapper->setShowDeleted(false);
    }

    public function testFetchRowWithStringKey(): void
    {
        $this->dropTables()->createTables(['album_string']);
        $defaultValues = [
            [
                'id' => 'A',
                'artist' => 'Rush',
                'title' => 'Rush',
                'deleted' => 0,
            ],
            [
                'id' => 'B',
                'artist' => 'Rush',
                'title' => 'Moving Pictures',
                'deleted' => 0,
            ],
            [
                'id' => 'C',
                'artist' => 'Dream Theater',
                'title' => 'Images And Words',
                'deleted' => 0,
            ],
            [
                'id' => 'D',
                'artist' => 'Claudia Leitte',
                'title' => 'Exttravasa',
                'deleted' => 1,
            ],
        ];
        foreach ($defaultValues as $row) {
            $this->getAdapter()->query(
                "INSERT into {$this->tableName}({$this->tableKeyName}, artist, title, deleted)
                                        VALUES (
                                        '{$row['id']}',
                                        '{$row['artist']}',
                                        '{$row['title']}',
                                        {$row['deleted']}
                                        );",
                Adapter::QUERY_MODE_EXECUTE
            );
        }

        $this->mapper->setTableKey([MapperConcrete::KEY_STRING => 'id']);

        // Marca pra usar o campo deleted
        $this->mapper->setUseDeleted(true);

        $this->mapper->setOrder('id');

        // Verifica os itens que existem
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow('A'));
        self::assertEquals($defaultValues[0], $this->mapper->fetchRow('A')->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow('B'));
        self::assertEquals($defaultValues[1], $this->mapper->fetchRow('B')->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow('C'));
        self::assertEquals($defaultValues[2], $this->mapper->fetchRow('C')->toArray());

        // Verifica o item removido
        $this->mapper->setShowDeleted(true);
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow('D'));
        self::assertEquals($defaultValues[3], $this->mapper->fetchRow('D')->toArray());
        $this->mapper->setShowDeleted(false);
    }

    public function testFetchRowWithMultipleKey(): void
    {
        $this->dropTables()->createTables(['album_array']);
        $defaultValues = [
            [
                'id_int' => 1,
                'id_char' => 'A',
                'artist' => 'Rush',
                'title' => 'Rush',
                'deleted' => 0,
            ],
            [
                'id_int' => 2,
                'id_char' => 'B',
                'artist' => 'Rush',
                'title' => 'Moving Pictures',
                'deleted' => 0,
            ],
            [
                'id_int' => 3,
                'id_char' => 'C',
                'artist' => 'Dream Theater',
                'title' => 'Images And Words',
                'deleted' => 0,
            ],
            [
                'id_int' => 4,
                'id_char' => 'D',
                'artist' => 'Claudia Leitte',
                'title' => 'Exttravasa',
                'deleted' => 1,
            ],
        ];
        foreach ($defaultValues as $row) {
            $this->getAdapter()->query(
                "INSERT into album (id_int, id_char, artist, title, deleted)
                                        VALUES (
                                        '{$row['id_int']}',
                                        '{$row['id_char']}',
                                        '{$row['artist']}',
                                        '{$row['title']}',
                                        {$row['deleted']}
                                        );",
                Adapter::QUERY_MODE_EXECUTE
            );
        }

        $this->mapper->setTableKey([MapperConcrete::KEY_STRING => 'id']);

        // Marca pra usar o campo deleted
        $this->mapper->setUseDeleted(true);

        $this->mapper->setOrder(['id_int', 'id_char']);

        // Verifica os itens que existem
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1]));
        self::assertEquals($defaultValues[0], $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1])->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2]));
        self::assertEquals($defaultValues[1], $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2])->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(['id_char' => 'C', 'id_int' => 3]));
        self::assertEquals($defaultValues[2], $this->mapper->fetchRow(['id_char' => 'C', 'id_int' => 3])->toArray());

        self::assertNull($this->mapper->fetchRow(['id_char' => 'C', 'id_int' => 2]));

        // Verifica o item removido
        $this->mapper->setShowDeleted(true);
        self::assertInstanceOf(ArrayObject::class, $this->mapper->fetchRow(['id_char' => 'D', 'id_int' => 4]));
        self::assertEquals($defaultValues[3], $this->mapper->fetchRow(['id_char' => 'D', 'id_int' => 4])->toArray());
        $this->mapper->setShowDeleted(false);
    }

    public function testInsert(): void
    {
        // Certifica que a tabela está vazia
        $this->dropTables()->createTables();
        $this->mapper->setOrder('id');
        self::assertNull($this->mapper->fetchAll(), 'Verifica se há algum registro pregravado');

        self::assertFalse($this->mapper->insert([]), 'Verifica inclusão inválida 1');
        self::assertFalse($this->mapper->insert(null), 'Verifica inclusão inválida 2');

        $row = [
            'artist' => 'Rush',
            'title' => 'Rush',
            'deleted' => '0',
        ];

        $id = $this->mapper->insert($row);
        self::assertEquals(1, $id, 'Verifica a chave criada=1');

        self::assertNotNull($this->mapper->fetchAll(), 'Verifica o fetchAll não vazio');
        self::assertEquals($row, $this->mapper->getLastInsertSet(), 'Verifica o set do ultimo insert');
        self::assertCount(1, $this->mapper->fetchAll(), 'Verifica se apenas um registro foi adicionado');

        $row = array_merge(['id' => $id], $row);

        self::assertEquals(
            [new ArrayObject($row)],
            $this->mapper->fetchAll(),
            'Verifica se o registro adicionado corresponde ao original pelo fetchAll()'
        );
        self::assertEquals(
            new ArrayObject($row),
            $this->mapper->fetchRow(1),
            'Verifica se o registro adicionado corresponde ao original pelo fetchRow()'
        );

        $row = [
            'id' => 2,
            'artist' => 'Rush',
            'title' => 'Test For Echos',
            'deleted' => '0',
        ];

        $id = $this->mapper->insert($row);
        self::assertEquals(2, $id, 'Verifica a chave criada=2');

        self::assertCount(2, $this->mapper->fetchAll(), 'Verifica que há DOIS registro');
        self::assertEquals(
            new ArrayObject($row),
            $this->mapper->fetchRow(2),
            'Verifica se o SEGUNDO registro adicionado corresponde ao original pelo fetchRow()'
        );
        self::assertEquals($row, $this->mapper->getLastInsertSet());

        $row = [
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => '0',
        ];
        $id = $this->mapper->insert($row);
        self::assertEquals(3, $id);
        self::assertEquals(
            $row,
            $this->mapper->getLastInsertSet(),
            'Verifica se o TERCEIRO registro adicionado corresponde ao original pelo getLastInsertSet()'
        );

        $row = array_merge(['id' => $id], $row);

        self::assertCount(3, $this->mapper->fetchAll());
        self::assertEquals(
            new ArrayObject($row),
            $this->mapper->fetchRow(3),
            'Verifica se o TERCEIRO registro adicionado corresponde ao original pelo fetchRow()'
        );

        // Teste com Zend_Db_Expr
        $id = $this->mapper->insert(['title' => new Expression('now()')]);
        self::assertEquals(4, $id);
    }

    public function testUpdate(): void
    {
        // Apaga as tabelas
        $this->dropTables()->createTables();
        $this->mapper->setOrder('id');
        self::assertEmpty($this->mapper->fetchAll(), 'tabela não está vazia');

        $row1 = [
            'id' => 1,
            'artist' => 'Não me altere',
            'title' => 'Presto',
            'deleted' => 0,
        ];

        $row2 = [
            'id' => 2,
            'artist' => 'Rush',
            'title' => 'Rush',
            'deleted' => 0,
        ];

        $this->mapper->insert($row1);
        $this->mapper->insert($row2);

        self::assertNotNull($this->mapper->fetchAll());
        self::assertCount(2, $this->mapper->fetchAll());
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 existe');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 existe');

        $rowUpdate = [
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
        ];

        $this->mapper->update($rowUpdate, 2);
        $rowUpdate['id'] = '2';
        $rowUpdate['deleted'] = '0';

        self::assertNotNull($this->mapper->fetchAll());
        self::assertCount(2, $this->mapper->fetchAll());
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($rowUpdate, $row->toArray(), 'Alterou o 2?');

        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'Alterou o 1?');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertNotEquals($row2, $row->toArray(), 'O 2 não é mais o mesmo?');

        $row = $row->toArray();
        unset($row['id'], $row['deleted']);
        self::assertEquals($row, $this->mapper->getLastUpdateSet(), 'Os dados diferentes foram os alterados?');
        self::assertEquals(
            ['title' => [$row2['title'], $row['title']]],
            $this->mapper->getLastUpdateDiff(),
            'As alterações foram detectadas corretamente?'
        );

        self::assertFalse($this->mapper->update([], 2));
        self::assertFalse($this->mapper->update(null, 2));
    }

    public function testDelete(): void
    {
        // Apaga as tabelas
        $this->dropTables()->createTables();
        $this->mapper->setOrder('id');
        self::assertEmpty($this->mapper->fetchAll(), 'tabela não está vazia');

        $row1 = [
            'id' => 1,
            'artist' => 'Rush',
            'title' => 'Presto',
            'deleted' => 0,
        ];
        $row2 = [
            'id' => 2,
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0,
        ];

        $this->mapper->insert($row1);
        $this->mapper->insert($row2);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 existe');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 existe');

        // Marca para usar o campo deleted
        $this->mapper->setUseDeleted(true)->setShowDeleted(true);

        // Remove o registro
        $this->mapper->delete(1);
        $row1['deleted'] = 1;

        // Verifica se foi removido
        $row = $this->mapper->fetchRow(1);
        self::assertEquals(1, $row['deleted'], 'row1 marcado como deleted');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe');

        // Marca para mostrar os removidos
        $this->mapper->setShowDeleted(true);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 ainda existe v1');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v1');

        // Marca para remover o registro da tabela
        $this->mapper->setUseDeleted(false);

        // Remove o registro qwue não existe
        $this->mapper->delete(3);

        // Verifica se ele foi removido
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 ainda existe v2');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v2');

        // Remove o registro
        $this->mapper->delete(1);

        // Verifica se ele foi removido
        self::assertNull($this->mapper->fetchRow(1), 'row1 não existe v3');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v3');
    }

    public function testDeleteIntegerKey(): void
    {
        $this->dropTables()->createTables();
        $this->mapper->setOrder('id');
        self::assertEmpty($this->mapper->fetchAll(), 'tabela não está vazia');

        $this->mapper->setTableKey([MapperConcrete::KEY_INTEGER => 'id']);

        // Abaixo é igual ao testDelete
        $row1 = [
            'id' => 1,
            'artist' => 'Rush',
            'title' => 'Presto',
            'deleted' => 0,
        ];
        $row2 = [
            'id' => 2,
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0,
        ];

        $this->mapper->insert($row1);
        $this->mapper->insert($row2);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 existe');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 existe');

        // Marca para usar o campo deleted
        $this->mapper->setUseDeleted(true)->setShowDeleted(true);

        // Remove o registro
        $this->mapper->delete(1);
        $row1['deleted'] = 1;

        // Verifica se foi removido
        $row = $this->mapper->fetchRow(1);
        self::assertEquals(1, $row['deleted'], 'row1 marcado como deleted');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe');

        // Marca para mostrar os removidos
        $this->mapper->setShowDeleted(true);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 ainda existe v1');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v1');

        // Marca para remover o registro da tabela
        $this->mapper->setUseDeleted(false);

        // Remove o registro qwue não existe
        $this->mapper->delete(3);

        // Verifica se ele foi removido
        $row = $this->mapper->fetchRow(1);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 ainda existe v2');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v2');

        // Remove o registro
        $this->mapper->delete(1);

        // Verifica se ele foi removido
        self::assertNull($this->mapper->fetchRow(1), 'row1 não existe v3');
        $row = $this->mapper->fetchRow(2);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v3');
    }

    public function testDeleteStringKey(): void
    {
        // Cria a tabela com chave string
        $this->mapper->setTableKey([MapperAbstract::KEY_STRING => 'id']);
        $this->dropTables()->createTables(['album_string']);
        $this->mapper->setOrder('id');

        // Abaixo é igual ao testDelete trocando 1, 2 por A, B
        $row1 = [
            'id' => 'A',
            'artist' => 'Rush',
            'title' => 'Presto',
            'deleted' => 0,
        ];
        $row2 = [
            'id' => 'B',
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0,
        ];

        $this->mapper->insert($row1);
        $this->mapper->insert($row2);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow('A');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 existe');
        $row = $this->mapper->fetchRow('B');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 existe');

        // Marca para usar o campo deleted
        $this->mapper->setUseDeleted(true)->setShowDeleted(true);

        // Remove o registro
        $this->mapper->delete('A');
        $row1['deleted'] = 1;

        // Verifica se foi removido
        $row = $this->mapper->fetchRow('A');
        self::assertEquals(1, $row['deleted'], 'row1 marcado como deleted');
        $row = $this->mapper->fetchRow('B');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe');

        // Marca para mostrar os removidos
        $this->mapper->setShowDeleted(true);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow('A');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 ainda existe v1');
        $row = $this->mapper->fetchRow('B');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v1');

        // Marca para remover o registro da tabela
        $this->mapper->setUseDeleted(false);

        // Remove o registro qwue não existe
        $this->mapper->delete('C');

        // Verifica se ele foi removido
        $row = $this->mapper->fetchRow('A');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 ainda existe v2');
        $row = $this->mapper->fetchRow('B');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v2');

        // Remove o registro
        $this->mapper->delete('A');

        // Verifica se ele foi removido
        self::assertNull($this->mapper->fetchRow('A'), 'row1 não existe v3');
        $row = $this->mapper->fetchRow('B');
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v3');
    }

    public function testDeleteInvalidArrayKey()
    {
        $this->mapper->setTableKey([MapperAbstract::KEY_INTEGER => 'id_int', MapperAbstract::KEY_STRING => 'id_char']);

        $this->expectException(\InvalidArgumentException::class);

        $this->mapper->delete('A');
    }

    public function testDeleteInvalidArrayMultiKey(): void
    {
        $this->mapper->setTableKey(
            [
                MapperAbstract::KEY_INTEGER => 'id_int',
                MapperAbstract::KEY_STRING => ['id_char', 'id_char2'],
            ]
        );

        $this->expectException(\InvalidArgumentException::class);

        $this->mapper->delete('A');
    }

    public function testDeleteInvalidArraySingleKey(): void
    {
        $this->mapper->setTableKey([MapperAbstract::KEY_INTEGER => 'id_int', MapperAbstract::KEY_STRING => 'id_char']);

        $this->expectException(LogicException::class);

        $this->mapper->delete(['id_int' => 'A']);
    }

    public function testDeleteArrayKey(): void
    {
        // Cria a tabela com chave string
        $this->mapper->setTableKey([MapperConcrete::KEY_INTEGER => 'id_int', MapperConcrete::KEY_STRING => 'id_char']);
        $this->dropTables()->createTables(['album_array']);
        $this->mapper->setUseAllKeys(false);
        $this->mapper->setOrder(['id_int', 'id_char']);

        // Abaixo é igual ao testDelete trocando 1, 2 por A, B
        $row1 = [
            'id_int' => 1,
            'id_char' => 'A',
            'artist' => 'Rush',
            'title' => 'Presto',
            'deleted' => 0,
        ];
        $row2 = [
            'id_int' => 2,
            'id_char' => 'B',
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0,
        ];

        $this->mapper->insert($row1);
        $this->mapper->insert($row2);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1]);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 existe');
        $row = $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2]);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 existe');

        // Marca para usar o campo deleted
        $this->mapper->setUseDeleted(true)->setShowDeleted(true);

        // Remove o registro
        $this->mapper->delete(['id_char' => 'A']);
        $row1['deleted'] = 1;

        // Verifica se foi removido
        $row = $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1]);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals(1, $row['deleted'], 'row1 marcado como deleted');

        $row = $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2]);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 ainda existe v1');

        // Marca para mostrar os removidos
        $this->mapper->setShowDeleted(true);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1]);
        self::assertInstanceOf(ArrayObject::class, $row, 'row1 ainda existe v1');
        self::assertEquals($row1, $row->toArray());
        $row = $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2]);
        self::assertInstanceOf(ArrayObject::class, $row, 'row2 ainda existe v1');
        self::assertEquals($row2, $row->toArray());

        // Marca para remover o registro da tabela
        $this->mapper->setUseDeleted(false);

        // Remove o registro que não existe
        $this->mapper->delete(['id_char' => 'C']);

        // Verifica se ele foi removido
        self::assertNotEmpty(
            $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1]),
            'row1 ainda existe v3'
        );
        self::assertNotEmpty(
            $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2]),
            'row2 ainda existe v3'
        );

        // Remove o registro
        $this->mapper->delete(['id_char' => 'A']);

        // Verifica se ele foi removido
        self::assertNull(
            $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1]),
            'row1 não existe v4'
        );
        self::assertNotEmpty(
            $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2]),
            'row2 ainda existe v4'
        );
    }

    public function testUpdateArrayKey(): void
    {
        // Cria a tabela com chave string
        $this->mapper->setTableKey(
            [
                MapperConcrete::KEY_INTEGER => 'id_int',
                MapperConcrete::KEY_STRING => ['id_char', 'artist'],
            ]
        );
        $this->dropTables()->createTables(['album_array_two']);
        $this->mapper->setUseAllKeys(true);
        $this->mapper->setOrder(['id_int', 'id_char', 'artist']);

        // Abaixo é igual ao testDelete trocando 1, 2 por A, B
        $row1 = [
            'id_int' => 1,
            'id_char' => 'A',
            'artist' => 'Rush',
            'title' => 'Presto',
            'deleted' => 0,
        ];
        $row2 = [
            'id_int' => 2,
            'id_char' => 'B',
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0,
        ];

        $this->mapper->insert($row1);
        $this->mapper->insert($row2);

        // Verifica se o registro existe
        $row = $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1, 'artist' => 'Rush']);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row1, $row->toArray(), 'row1 existe');
        $row = $this->mapper->fetchRow(['id_char' => 'B', 'id_int' => 2, 'artist' => 'Rush']);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals($row2, $row->toArray(), 'row2 existe');

        // atualizar o registro
        $this->mapper->update(['title' => 'New title'], ['id_char' => 'A', 'id_int' => 1, 'artist' => 'Rush']);

        // Verifica se foi removido
        $row = $this->mapper->fetchRow(['id_char' => 'A', 'id_int' => 1, 'artist' => 'Rush']);
        self::assertInstanceOf(ArrayObject::class, $row);
        self::assertEquals('New title', $row['title'], 'row1 atualizado ');
    }
}
