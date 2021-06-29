<?php

declare(strict_types=1);

namespace RealejoTest;

use PHPUnit\Framework\TestCase;

class BaseTestCaseTest extends TestCase
{
    private BaseTestCase $baseTestCase;

    protected function setUp(): void
    {
        $this->baseTestCase = new BaseTestCase();
    }

    protected function tearDown(): void
    {
        unset($this->baseTestCase);
    }

    public function testTestSetupMysql():void
    {
        $tables = ['album'];
        $this->baseTestCase->setTables($tables);
        $this->assertEquals($tables, $this->baseTestCase->getTables());

        $dbTest = $this->baseTestCase->createTables();
        $this->assertInstanceOf(BaseTestCase::class, $dbTest);

        $dbTest = $this->baseTestCase->dropTables();
        $this->assertInstanceOf(BaseTestCase::class, $dbTest);

        $dbTest = $this->baseTestCase->createTables()->dropTables();
        $this->assertInstanceOf(BaseTestCase::class, $dbTest);
    }

    public function testClearApplicationData(): void
    {
        // Verifica se está tudo ok
        if (!defined('TEST_DATA')) {
            $this->fail('TEST_DATA não definido');
        }
        if (!is_writable(TEST_DATA)) {
            $this->fail('TEST_DATA não tem permissão de escrita');
        }

        // Grava umas bobeiras la
        $folder = TEST_DATA . '/teste1';
        if (!file_exists($folder)) {
            $oldumask = umask(0);
            mkdir($folder);
            umask($oldumask);
        }
        file_put_contents($folder . '/test1.txt', 'teste');

        $folder = TEST_DATA . '/teste2/teste3';
        if (!file_exists($folder)) {
            $oldumask = umask(0);
            mkdir($folder, 0777, true);
            umask($oldumask);
        }
        file_put_contents($folder . '/sample.txt', 'teste teste');

        // Verifica se a pasta está vazia
        $this->assertFalse($this->baseTestCase->isApplicationDataEmpty());

        $this->baseTestCase->clearApplicationData();

        // Verifica se está vazia
        $files = scandir(TEST_DATA);
        $this->assertCount(3, $files, 'não tem mais nada no APPLICATION_DATA');
        $this->assertEquals(['.', '..', 'cache'], $files, 'não tem mais nada no APPLICATION_DATA');

        // Verifica se a pasta está vazia
        $this->assertTrue($this->baseTestCase->isApplicationDataEmpty());

        // Grava mais coisa no raiz do APPLICATION_DATA
        file_put_contents(TEST_DATA . '/sample.txt', 'outro teste');

        // Verifica se a pasta está vazia depois de apagar
        $this->assertFalse($this->baseTestCase->isApplicationDataEmpty());
        $this->assertTrue($this->baseTestCase->clearApplicationData());
    }
}
