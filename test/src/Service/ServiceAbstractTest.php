<?php

declare(strict_types=1);

namespace RealejoTest\Service;

use DateTime;
use Laminas\Db\Adapter\Adapter;
use Laminas\Dom\Document;
use Laminas\Dom\Document\Query as DomQuery;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerInterface;
use Realejo\Cache\CacheService;
use Realejo\Service\Metadata\MetadataService;
use Realejo\Service\ServiceAbstract;
use Realejo\Stdlib\ArrayObject;
use RealejoTest\BaseTestCase;

class ServiceAbstractTest extends BaseTestCase
{
    protected string $tableName = 'album';
    protected string $tableKeyName = 'id';

    protected array $tables = ['album'];

    private ServiceConcrete $service;

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

        $this->dropTables()->createTables()->insertDefaultRows();

        $this->service = new ServiceConcrete();

        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');
        $this->service->setCache($cacheService->getFrontend());

        // Remove as pastas criadas
        $this->clearApplicationData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropTables();

        unset($this->service);

        $this->clearApplicationData();
    }

    public function testFindAll(): void
    {
        // O padrão é não usar o campo deleted
        $this->service->getMapper()->setOrder('id');
        $albuns = $this->service->findAll();
        self::assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->service->getMapper()->setShowDeleted(true)->setUseDeleted(false);
        self::assertCount(4, $this->service->findAll(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostrar os removidos e usar o campo deleted
        $this->service->getMapper()->setShowDeleted(false)->setUseDeleted(true);
        self::assertCount(3, $this->service->findAll(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->service->getMapper()->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->service->findAll();
        self::assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');

        // Marca não mostrar os removidos
        $this->service->getMapper()->setUseDeleted(true)->setShowDeleted(false);

        $albuns = $this->defaultValues;
        unset($albuns[3]); // remove o deleted=1

        $findAll = $this->service->findAll();
        foreach ($findAll as $id => $row) {
            $findAll[$id] = $row->toArray();
        }
        self::assertEquals($albuns, $findAll);

        // Marca mostrar os removidos
        $this->service->getMapper()->setShowDeleted(true);

        $findAll = $this->service->findAll();
        foreach ($findAll as $id => $row) {
            $findAll[$id] = $row->toArray();
        }
        self::assertEquals($this->defaultValues, $findAll);
        self::assertCount(4, $this->service->findAll());
        $this->service->getMapper()->setShowDeleted(false);
        self::assertCount(3, $this->service->findAll());

        // Verifica o where
        self::assertCount(2, $this->service->findAll(['artist' => $albuns[0]['artist']]));
        self::assertNull($this->service->findAll(['artist' => $this->defaultValues[3]['artist']]));

        // Verifica o paginator com o padrão
        $paginator = $this->service->findPaginated();

        $temp = [];
        foreach ($paginator->getIterator() as $p) {
            $temp[] = $p->getArrayCopy();
        }

        $findAll = $this->service->findAll();
        foreach ($findAll as $id => $row) {
            $findAll[$id] = $row->toArray();
        }
        $paginator = json_encode($temp);
        self::assertNotEquals(json_encode($this->defaultValues), $paginator);
        self::assertEquals(json_encode($findAll), $paginator, 'retorno do paginator é igual');

        // Verifica o paginator alterando o paginator
        $this->service->getPaginatorOptions()
            ->setPageRange(2)
            ->setCurrentPageNumber(1)
            ->setItemCountPerPage(2);
        $paginator = $this->service->findPaginated();

        $temp = [];
        foreach ($paginator->getCurrentItems() as $p) {
            $temp[] = $p->getArrayCopy();
        }
        $paginator = json_encode($temp);

        self::assertNotEquals(json_encode($this->defaultValues), $paginator);
        $fetchAll = $this->service->findPaginated([], null, 2);
        $temp = [];
        foreach ($fetchAll as $p) {
            $temp[] = $p->toArray();
        }
        $fetchAll = $temp;
        self::assertEquals(json_encode($fetchAll), $paginator);

        // Apaga qualquer cache
        self::assertTrue($this->service->getCache()->flush(), 'apaga o cache');

        // Define exibir os deletados
        $this->service->getMapper()->setShowDeleted(true);

        // Liga o cache
        $this->service->setUseCache(true);
        $findAll = $this->service->findAll();
        $temp = [];
        foreach ($findAll as $p) {
            $temp[] = $p->toArray();
        }
        $findAll = $temp;
        self::assertEquals($this->defaultValues, $findAll, 'fetchAll está igual ao defaultValues');
        self::assertCount(4, $findAll, 'Deve conter 4 registros');

        // Grava um registro "sem o cache saber"
        $this->service->getMapper()->getTableGateway()->insert(
            [
                'id' => 10,
                'artist' => 'nao existo por enquanto',
                'title' => 'bla bla',
                'deleted' => 0,
            ]
        );

        self::assertCount(
            4,
            $this->service->findAll(),
            'Deve conter 4 registros depois do insert "sem o cache saber"'
        );
        self::assertTrue($this->service->getCache()->flush(), 'limpa o cache');
        self::assertCount(5, $this->service->findAll(), 'Deve conter 5 registros');

        // Define não exibir os deletados
        $this->service->getMapper()->setShowDeleted(false);
        self::assertCount(4, $this->service->findAll(), 'Deve conter 4 registros showDeleted=false');

        // Apaga um registro "sem o cache saber"
        $this->service->getMapper()->getTableGateway()->delete("id=10");
        $this->service->getMapper()->setShowDeleted(true);
        self::assertCount(5, $this->service->findAll(), 'Deve conter 5 registros');
        self::assertTrue($this->service->getCache()->flush(), 'apaga o cache');
        self::assertCount(4, $this->service->findAll(), 'Deve conter 4 registros 4');
    }

    public function testFindOne(): void
    {
        // Marca pra usar o campo deleted
        $this->service->getMapper()->setUseDeleted(true);

        $this->service->getMapper()->setOrder('id');

        // Verifica os itens que existem
        self::assertInstanceOf(ArrayObject::class, $this->service->findOne(1));
        self::assertEquals($this->defaultValues[0], $this->service->findOne(1)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->service->findOne(2));
        self::assertEquals($this->defaultValues[1], $this->service->findOne(2)->toArray());
        self::assertInstanceOf(ArrayObject::class, $this->service->findOne(3));
        self::assertEquals($this->defaultValues[2], $this->service->findOne(3)->toArray());
        self::assertEmpty($this->service->findOne(4));

        // Verifica o item removido
        $this->service->getMapper()->setShowDeleted(true);
        $findOne = $this->service->findOne(4);
        self::assertInstanceOf(ArrayObject::class, $findOne);
        self::assertEquals($this->defaultValues[3], $findOne->toArray());
        $this->service->getMapper()->setShowDeleted(false);
    }

    public function testFindAssoc(): void
    {
        $this->service->getMapper()->setOrder('id');

        // O padrão é não usar o campo deleted
        $albuns = $this->service->findAssoc();
        self::assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');
        self::assertInstanceOf(ArrayObject::class, $albuns[1]);
        self::assertEquals($this->defaultValues[0], $albuns[1]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[2]);
        self::assertEquals($this->defaultValues[1], $albuns[2]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[3]);
        self::assertEquals($this->defaultValues[2], $albuns[3]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[4]);
        self::assertEquals($this->defaultValues[3], $albuns[4]->toArray());

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->service->getMapper()->setShowDeleted(true)->setUseDeleted(false);
        self::assertCount(4, $this->service->findAssoc(), 'showDeleted=true, useDeleted=false');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->service->getMapper()->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->service->findAssoc();
        self::assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');
        self::assertInstanceOf(ArrayObject::class, $albuns[1]);
        self::assertEquals($this->defaultValues[0], $albuns[1]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[2]);
        self::assertEquals($this->defaultValues[1], $albuns[2]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[3]);
        self::assertEquals($this->defaultValues[2], $albuns[3]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[4]);
        self::assertEquals($this->defaultValues[3], $albuns[4]->toArray());
    }

    public function testFindAssocWithMultipleKeys(): void
    {
        $this->service->getMapper()->setTableKey([$this->tableKeyName, 'naoexisto']);

        $this->service->getMapper()->setOrder('id');

        // O padrão é não usar o campo deleted
        $albuns = $this->service->findAssoc();
        self::assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');
        self::assertInstanceOf(ArrayObject::class, $albuns[1]);
        self::assertEquals($this->defaultValues[0], $albuns[1]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[2]);
        self::assertEquals($this->defaultValues[1], $albuns[2]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[3]);
        self::assertEquals($this->defaultValues[2], $albuns[3]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[4]);
        self::assertEquals($this->defaultValues[3], $albuns[4]->toArray());

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->service->getMapper()->setShowDeleted(true)->setUseDeleted(false);
        self::assertCount(4, $this->service->findAssoc(), 'showDeleted=true, useDeleted=false');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->service->getMapper()->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->service->findAssoc();
        self::assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');
        self::assertInstanceOf(ArrayObject::class, $albuns[1]);
        self::assertEquals($this->defaultValues[0], $albuns[1]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[2]);
        self::assertEquals($this->defaultValues[1], $albuns[2]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[3]);
        self::assertEquals($this->defaultValues[2], $albuns[3]->toArray());
        self::assertInstanceOf(ArrayObject::class, $albuns[4]);
        self::assertEquals($this->defaultValues[3], $albuns[4]->toArray());
    }

    public function testHtmlSelectGettersSetters(): void
    {
        self::assertEquals('{nome}', $this->service->getHtmlSelectOption(), 'padrão {nome}');
        self::assertInstanceOf(
            ServiceAbstract::class,
            $this->service->setHtmlSelectOption('{title}'),
            'setHtmlSelectOption() retorna RW_App_Model_Base'
        );
        self::assertEquals('{title}', $this->service->getHtmlSelectOption(), 'troquei por {title}');
    }

    public function testHtmlSelectWhere(): void
    {
        $id = 'teste';
        $this->service->setHtmlSelectOption('{title}');

        $this->service->getMapper()->setOrder('id');

        $select = $this->service->getHtmlSelect($id, null, ['where' => ['artist' => 'Rush']]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(3, $options, " 3 opções encontradas");

        self::assertEmpty($options->current()->nodeValue, "primeiro é vazio 1");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do primeiro é vazio 1");

        $options->next();
        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, "nome do segundo ok 1");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 1"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, "nome do terceiro ok 1");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 1"
        );


        $select = $this->service->getHtmlSelect($id, 1, ['where' => ['artist' => 'Rush']]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(2, $options, " 2 opções encontradas");

        self::assertNotEmpty($options->current()->nodeValue, "primeiro não é vazio 2");
        self::assertNotEmpty($options->current()->getAttribute('value'), "o valor do primeiro não é vazio 2");

        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, "nome do segundo ok 2");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 2"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, "nome do terceiro ok 2");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 2"
        );
    }

    public function testHtmlSelectSemOptionValido(): void
    {
        $id = 'teste';
        $this->service->getMapper()->setOrder('id');

        $select = $this->service->getHtmlSelect($id);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(5, $options, " 5 opções encontradas");

        self::assertEmpty($options->current()->nodeValue, "primeiro é vazio 1");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do primeiro é vazio 1");

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do segundo ok 1");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 1"
        );

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do terceiro ok 1");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 1"
        );

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do quarto ok 1");
        self::assertEquals(
            $this->defaultValues[2]['id'],
            $options->current()->getAttribute('value'),
            "valor do quarto ok 1"
        );

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do quinto ok 1");
        self::assertEquals(
            $this->defaultValues[3]['id'],
            $options->current()->getAttribute('value'),
            "valor do quinto ok 1"
        );

        $select = $this->service->setHtmlSelectOption('{nao_existo}')->getHtmlSelect($id);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(5, $options, " 5 opções encontradas");

        self::assertEmpty($options->current()->nodeValue, "primeiro é vazio 2");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do primeiro é vazio 2");

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do segundo ok 2");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 2"
        );

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do terceiro ok 2");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 2"
        );

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do quarto ok 2");
        self::assertEquals(
            $this->defaultValues[2]['id'],
            $options->current()->getAttribute('value'),
            "valor do quarto ok 2"
        );

        $options->next();
        self::assertEmpty($options->current()->nodeValue, "nome do quinto ok 2");
        self::assertEquals(
            $this->defaultValues[3]['id'],
            $options->current()->getAttribute('value'),
            "valor do quinto ok 2"
        );
    }

    public function testHtmlSelectOption(): void
    {
        $id = 'teste';
        $this->service->getMapper()->setOrder('id');

        $select = $this->service->setHtmlSelectOption('{artist}')->getHtmlSelect($id);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        self::assertCount(1, DomQuery::execute("#$id", $domDocument, DomQuery::TYPE_CSS), "id #$id existe");
        self::assertCount(
            1,
            DomQuery::execute("select[name=\"$id\"]", $domDocument, DomQuery::TYPE_CSS),
            "placeholder select[name=\"$id\"] encontrado"
        );
        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(5, $options, " 5 opções encontradas");

        self::assertEmpty($options->current()->nodeValue, "primeiro é vazio");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do primeiro é vazio");

        $options->next();
        self::assertEquals($this->defaultValues[0]['artist'], $options->current()->nodeValue, "nome do segundo ok");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['artist'], $options->current()->nodeValue, "nome do terceiro ok");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok"
        );

        $options->next();
        self::assertEquals($this->defaultValues[2]['artist'], $options->current()->nodeValue, "nome do quarto ok");
        self::assertEquals(
            $this->defaultValues[2]['id'],
            $options->current()->getAttribute('value'),
            "valor do quarto ok"
        );

        $options->next();
        self::assertEquals($this->defaultValues[3]['artist'], $options->current()->nodeValue, "nome do quinto ok");
        self::assertEquals(
            $this->defaultValues[3]['id'],
            $options->current()->getAttribute('value'),
            "valor do quinto ok"
        );
    }

    public function testHtmlSelectPlaceholder(): void
    {
        $ph = 'myplaceholder';
        $this->service->getMapper()->setOrder('id');
        $select = $this->service->getHtmlSelect('nome_usado', null, ['placeholder' => $ph]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(
            1,
            DomQuery::execute('#nome_usado', $domDocument, DomQuery::TYPE_CSS),
            'id #nome_usado existe'
        );
        self::assertCount(
            1,
            DomQuery::execute("select[placeholder=\"$ph\"]", $domDocument, DomQuery::TYPE_CSS),
            "placeholder select[placeholder=\"$ph\"] encontrado"
        );
        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(5, $options, " 5 opções encontradas");
        self::assertEquals($ph, $options->current()->nodeValue, "placeholder é a primeira");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do placeholder é vazio");
    }

    public function testHtmlSelectShowEmpty(): void
    {
        $this->service->getMapper()->setOrder('id');
        $select = $this->service->getHtmlSelect('nome_usado');
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(
            1,
            DomQuery::execute('#nome_usado', $domDocument, DomQuery::TYPE_CSS),
            'id #nome_usado existe'
        );
        self::assertCount(5, DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS), '5 opções existem');
        self::assertEmpty(
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS)->current()->nodeValue,
            "a primeira é vazia"
        );
        self::assertEmpty(
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS)->current()->getAttribute('value'),
            "o valor da primeira é vazio"
        );

        $select = $this->service->getHtmlSelect('nome_usado', 1);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(
            1,
            DomQuery::execute('#nome_usado', $domDocument, DomQuery::TYPE_CSS),
            'id #nome_usado existe COM valor padrão'
        );
        self::assertCount(
            4,
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS),
            '4 opções existem COM valor padrão'
        );

        $select = $this->service->getHtmlSelect('nome_usado', null, ['show-empty' => false]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(
            1,
            DomQuery::execute('#nome_usado', $domDocument, DomQuery::TYPE_CSS),
            'id #nome_usado existe SEM valor padrão e show-empty=false'
        );
        self::assertCount(
            4,
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS),
            '4 opções existem SEM valor padrão e show-empty=false'
        );

        // sem mostrar o empty
        $select = $this->service->getHtmlSelect('nome_usado', 1, ['show-empty' => false]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(
            1,
            DomQuery::execute('#nome_usado', $domDocument, DomQuery::TYPE_CSS),
            'id #nome_usado existe com valor padrão e show-empty=false'
        );
        self::assertCount(
            4,
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS),
            '4 opções existem com valor padrão e show-empty=false'
        );

        // sem mostrar o empty
        $select = $this->service->getHtmlSelect('nome_usado', 1, ['show-empty' => true]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(
            1,
            DomQuery::execute('#nome_usado', $domDocument, DomQuery::TYPE_CSS),
            'id #nome_usado existe com valor padrão e show-empty=true'
        );
        self::assertCount(
            5,
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS),
            '5 opções existem com valor padrão e show-empty=true'
        );
        self::assertEmpty(
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS)->current()->nodeValue,
            "a primeira é vazia com valor padrão e show-empty=true"
        );
        self::assertEmpty(
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS)->current()->getAttribute('value'),
            "o valor da primeira é vazio com valor padrão e show-empty=true"
        );
    }

    public function testHtmlSelectGrouped(): void
    {
        $id = 'teste';
        $this->service->getMapper()->setOrder('id');

        $select = $this->service->setHtmlSelectOption('{title}')->getHtmlSelect($id, 1, ['grouped' => 'artist']);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(1, DomQuery::execute("#$id", $domDocument, DomQuery::TYPE_CSS), "id #$id existe");

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(4, $options, " 4 opções encontradas");

        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, "nome do primeiro ok 1");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do primeiro ok 1"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, "nome do segundo ok 1");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 1"
        );

        $options->next();
        self::assertEquals($this->defaultValues[2]['title'], $options->current()->nodeValue, "nome do terceiro ok 1");
        self::assertEquals(
            $this->defaultValues[2]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 1"
        );

        $options->next();
        self::assertEquals($this->defaultValues[3]['title'], $options->current()->nodeValue, "nome do quarto ok 1");
        self::assertEquals(
            $this->defaultValues[3]['id'],
            $options->current()->getAttribute('value'),
            "valor do quarto ok 1"
        );

        $optgroups = DomQuery::execute("optgroup", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(3, $optgroups, " 3 grupo de opções encontrados");

        self::assertEquals(
            $this->defaultValues[0]['artist'],
            $optgroups->current()->getAttribute('label'),
            "nome do primeiro grupo ok"
        );
        self::assertEquals(2, $optgroups->current()->childNodes->length, " 2 opções encontrados no priemiro optgroup");
        self::assertEquals(
            $this->defaultValues[0]['title'],
            $optgroups->current()->firstChild->nodeValue,
            "nome do primeiro ok 2"
        );
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $optgroups->current()->firstChild->getAttribute('value'),
            "valor do primeiro ok 2"
        );
        self::assertEquals(
            $this->defaultValues[1]['title'],
            $optgroups->current()->firstChild->nextSibling->nodeValue,
            "nome do segundo ok 2"
        );
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $optgroups->current()->firstChild->nextSibling->getAttribute('value'),
            "valor do segundo ok 2"
        );

        $optgroups->next();
        self::assertEquals(
            $this->defaultValues[2]['artist'],
            $optgroups->current()->getAttribute('label'),
            "nome do segundo grupo ok"
        );
        self::assertEquals(1, $optgroups->current()->childNodes->length, " 2 opções encontrados");
        self::assertEquals(
            $this->defaultValues[2]['title'],
            $optgroups->current()->firstChild->nodeValue,
            "nome do terceiro ok 2"
        );
        self::assertEquals(
            $this->defaultValues[2]['id'],
            $optgroups->current()->firstChild->getAttribute('value'),
            "valor do terceiro ok 2"
        );

        $optgroups->next();
        self::assertEquals(
            $this->defaultValues[3]['artist'],
            $optgroups->current()->getAttribute('label'),
            "nome do terceiro grupo ok"
        );
        self::assertEquals(1, $optgroups->current()->childNodes->length, " 2 opções encontrados");
        self::assertEquals(
            $this->defaultValues[3]['title'],
            $optgroups->current()->firstChild->nodeValue,
            "nome do terceiro ok 2"
        );
        self::assertEquals(
            $this->defaultValues[3]['id'],
            $optgroups->current()->firstChild->getAttribute('value'),
            "valor do terceiro ok 2"
        );

        // SELECT VAZIO!

        $select = $this->service->setHtmlSelectOption('{title}')->getHtmlSelect(
            $id,
            1,
            ['grouped' => 'artist', 'where' => ['id' => 100]]
        );
        self::assertNotEmpty($select);
        $domDocument = new Document($select);
        self::assertCount(1, DomQuery::execute("#$id", $domDocument, DomQuery::TYPE_CSS), "id #$id existe");

        self::assertCount(
            1,
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS),
            " nenhuma option com where id = 100"
        );
        self::assertCount(
            0,
            DomQuery::execute("optgroup", $domDocument, DomQuery::TYPE_CSS),
            " nenhuma optgroup com where id = 100"
        );

        self::assertEmpty(
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS)->current()->nodeValue,
            "primeiro é vazio"
        );
        self::assertEmpty(
            DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS)->current()->getAttribute('value'),
            "o valor do primeiro é vazio"
        );
    }

    public function testHtmlSelectMultipleKey(): void
    {
        // Define a chave multipla
        // como ele deve considerar apenas o primeiro o teste abaixo é o mesmo de testHtmlSelectWhere
        $this->service->getMapper()->setTableKey(['id', 'nao-existo']);
        $this->service->getMapper()->setOrder('id');

        $id = 'teste';
        $this->service->setHtmlSelectOption('{title}');

        $select = $this->service->getHtmlSelect($id, null, ['where' => ['artist' => 'Rush']]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(3, $options, " 3 opções encontradas");

        self::assertEmpty($options->current()->nodeValue, "primeiro é vazio 1");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do primeiro é vazio 1");

        $options->next();
        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, "nome do segundo ok 1");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 1"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, "nome do terceiro ok 1");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 1"
        );


        $select = $this->service->getHtmlSelect($id, 1, ['where' => ['artist' => 'Rush']]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(2, $options, " 2 opções encontradas");

        self::assertNotEmpty($options->current()->nodeValue, "primeiro não é vazio 2");
        self::assertNotEmpty($options->current()->getAttribute('value'), "o valor do primeiro não é vazio 2");

        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, "nome do segundo ok 2");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 2"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, "nome do terceiro ok 2");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 2"
        );
    }

    public function testHtmlSelectMultipleKeyWithCast(): void
    {
        // Define a chave multipla
        // como ele deve considerar apenas o primeiro o teste abaixo é o mesmo de testHtmlSelectWhere
        $this->service->getMapper()->setTableKey(['CAST' => 'id', 'nao-existo']);
        $this->service->getMapper()->setOrder('id');

        $id = 'teste';
        $this->service->setHtmlSelectOption('{title}');

        $select = $this->service->getHtmlSelect($id, null, ['where' => ['artist' => 'Rush']]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(3, $options, " 3 opções encontradas");

        self::assertEmpty($options->current()->nodeValue, "primeiro é vazio 1");
        self::assertEmpty($options->current()->getAttribute('value'), "o valor do primeiro é vazio 1");

        $options->next();
        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, "nome do segundo ok 1");
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 1"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, "nome do terceiro ok 1");
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            "valor do terceiro ok 1"
        );


        $select = $this->service->getHtmlSelect($id, 1, ['where' => ['artist' => 'Rush']]);
        self::assertNotEmpty($select);
        $domDocument = new Document($select);

        $options = DomQuery::execute("option", $domDocument, DomQuery::TYPE_CSS);
        self::assertCount(2, $options, ' 2 opções encontradas');

        self::assertNotEmpty($options->current()->nodeValue, 'primeiro não é vazio 2');
        self::assertNotEmpty($options->current()->getAttribute('value'), 'o valor do primeiro não é vazio 2');

        self::assertEquals($this->defaultValues[0]['title'], $options->current()->nodeValue, 'nome do segundo ok 2');
        self::assertEquals(
            $this->defaultValues[0]['id'],
            $options->current()->getAttribute('value'),
            "valor do segundo ok 2"
        );

        $options->next();
        self::assertEquals($this->defaultValues[1]['title'], $options->current()->nodeValue, 'nome do terceiro ok 2');
        self::assertEquals(
            $this->defaultValues[1]['id'],
            $options->current()->getAttribute('value'),
            'valor do terceiro ok 2'
        );
    }

    public function testServiceLocator(): void
    {
        $fakeServiceLocator = new FakeServiceLocator();
        $service = new ServiceConcrete();
        $service->setServiceLocator($fakeServiceLocator);
        self::assertInstanceOf(FakeServiceLocator::class, $service->getServiceLocator());
        self::assertInstanceOf(ContainerInterface::class, $service->getServiceLocator());

        $cacheService = new CacheService();
        $cacheService->setCacheDir($this->getDataDir() . '/cache');
        $service->setCache($cacheService->getFrontend());

        $mapper = $service->getMapper();
        self::assertInstanceOf(FakeServiceLocator::class, $mapper->getServiceLocator());
        self::assertInstanceOf(ContainerInterface::class, $mapper->getServiceLocator());

        self::assertNull($service->getFromServiceLocator(DateTime::class));

        $realServiceLocator = new ServiceManager();
        $service->setServiceLocator($realServiceLocator);
        self::assertInstanceOf(DateTime::class, $service->getFromServiceLocator(DateTime::class));

        $service->getFromServiceLocator(MetadataService::class);
        self::assertTrue($service->getServiceLocator()->has(MetadataService::class));
        self::assertInstanceOf(MetadataService::class, $service->getServiceLocator()->get(MetadataService::class));
        self::assertTrue($service->getServiceLocator()->get(MetadataService::class)->hasServiceLocator());

        $fakeObject = (object)['id' => 1];
        $service->getServiceLocator()->setService('fake', $fakeObject);
        self::assertTrue($service->getServiceLocator()->has('fake'));
        self::assertEquals($fakeObject, $service->getServiceLocator()->get('fake'));
    }

    public function testFindPaginated(): void
    {
        $this->service->getMapper()->setOrder('id');
        $albuns = $this->service->findPaginated();
        self::assertInstanceOf(\Realejo\Paginator\Paginator::class, $albuns);
        self::assertCount(4, $albuns->getCurrentItems());

        self::assertFalse($this->service->getUseCache());

        // Liga o cache
        $this->service->setUseCache(true);
        self::assertTrue($this->service->getUseCache());

        // Verifica o paginator com o padrão
        $paginator = $this->service->findPaginated();

        // verifica se vai utilizar o mesmo cache id quando realizar a mesma consulta,
        // pois estava criando novo id e nunca
        // utilizando o cache no paginator
        $oldId = $this->service->getCache()->getIterator()->key();
        $fetchAll = $this->service->setUseCache(true)->findPaginated();
        self::assertEquals($oldId, $this->service->getCache()->getIterator()->key());
        // Apaga qualquer cache
        self::assertTrue($this->service->getCache()->flush(), 'apaga o cache');

        $temp = [];
        foreach ($paginator->getIterator() as $p) {
            $temp[] = $p->getArrayCopy();
        }

        $findAll = $this->service->findAll();
        foreach ($findAll as $id => $row) {
            $findAll[$id] = $row->toArray();
        }
        $paginator = json_encode($temp);
        self::assertEquals(json_encode($findAll), $paginator, 'retorno do paginator é igual');

        // Verifica o paginator alterando o paginator
        $this->service->getPaginatorOptions()
            ->setPageRange(2)
            ->setCurrentPageNumber(1)
            ->setItemCountPerPage(2);
        $paginator = $this->service->findPaginated();

        $temp = [];
        foreach ($paginator->getCurrentItems() as $p) {
            $temp[] = $p->getArrayCopy();
        }
        $paginator = json_encode($temp);

        self::assertNotEquals(json_encode($this->defaultValues), $paginator);
        $fetchAll = $this->service->findPaginated([], null, 2);
        $temp = [];
        foreach ($fetchAll as $p) {
            $temp[] = $p->toArray();
        }
        $fetchAll = $temp;
        self::assertEquals(json_encode($fetchAll), $paginator);

        // verifica se vai utilizar o mesmo cache id quando realizar a mesma consulta, pois estava criando nova e nunca
        // utilizando o cache no paginator
        $oldId = $this->service->getCache()->getIterator()->key();
        $fetchAll = $this->service->setUseCache(true)->findPaginated([], null, 2);
        self::assertEquals($oldId, $this->service->getCache()->getIterator()->key());
    }
}
