<?php

/**
 * Test case para as funcionalidades padrões
 *
 * Apesar da classe se chamar abstract ela não é exatamente um.
 * É de propósito pois dentro do outros testes ela pode ser iniciada para criar as tabelas
 *
 * @todo colocar dentro da biblioteca padrão!
 */

namespace RealejoTest;

use PHPUnit\Framework\TestCase;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\Feature\GlobalAdapterFeature;
use Laminas\Db\TableGateway\TableGateway;

class BaseTestCase extends TestCase
{
    protected const SQL_CREATE = 'create';
    protected const SQL_DROP = 'drop';

    /** @var Adapter */
    protected $adapter = null;

    /**
     * Lista de tabelas que serão criadas e dropadas
     *
     * @var array
     */
    protected $tables = [];

    protected $dataDir;

    private $tableGateways = [];

    public static function setUpBeforeClass(): void
    {
        // Apaga todo o conteúdo do APPLICATION_DATA
        $oTemp = new self();
        $oTemp->clearApplicationData();
    }

    public static function tearDownAfterClass(): void
    {
        // Apaga todo o conteúdo do APPLICATION_DATA
        $oTemp = new self();
        $oTemp->clearApplicationData();
    }

    public function getAdapter(): Adapter
    {
        if (!isset($this->adapter)) {
            $this->adapter = GlobalAdapterFeature::getStaticAdapter();
        }
        return $this->adapter;
    }

    public function getTableGateway(string $table): TableGateway
    {
        if (!isset($this->tableGateways[$table])) {
            $this->tableGateways[$table] = new TableGateway(
                $table,
                $this->getAdapter()
            );
        }
        return $this->tableGateways[$table];
    }

    public function setAdapter(Adapter $adapter): BaseTestCase
    {
        $this->adapter = $adapter;
        return $this;
    }

    public function createTables($tables = null): BaseTestCase
    {
        // Não deixa executar em produção
        if (APPLICATION_ENV !== 'testing') {
            $this->fail('Só é possível executar createTables() em testing');
        }

        if (empty($tables)) {
            $tables = $this->tables;
        }

        if (empty($tables)) {
            return $this;
        }

        // Recupera o script para criar as tabelas
        foreach ($tables as $tbl) {
            // Cria a tabela de usuários
            $this->getAdapter()->query(
                file_get_contents($this->getSqlFile($tbl, self::SQL_CREATE)),
                Adapter::QUERY_MODE_EXECUTE
            );
        }

        return $this;
    }

    /**
     * @param string $file
     * @param string $sqlAction
     * @return string|false
     */
    private function getSqlFile(string $file, string $sqlAction)
    {
        if ($sqlAction === self::SQL_CREATE) {
            // Procura primeiro na pasta do modulo caso queria substituir a do geral
            if (strpos(TEST_ROOT, '/modules') !== false) {
                $modulePath = substr(TEST_ROOT, 0, strpos(TEST_ROOT, '/modules'));
                $paths[] = "$modulePath/tests/assets/sql/$file.sql";
                $paths[] = "$modulePath/tests/assets/sql/$file.create.sql";
            }

            // Procura na pasta geral de teste do aplicativo
            $paths[] = TEST_ROOT . "/assets/sql/$file.sql";
            $paths[] = TEST_ROOT . "/assets/sql/$file.create.sql";

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            $this->fail("Arquivo sql não encontrado $file");
        }

        if ($sqlAction === self::SQL_DROP) {
            // Procura primeiro na pasta do modulo caso queria substituir a do geral
            if (strpos(TEST_ROOT, '/modules') !== false) {
                $modulePath = substr(TEST_ROOT, 0, strpos(TEST_ROOT, '/modules'));
                $paths[] = "$modulePath/tests/assets/sql/$file.drop.sql";
            }

            // Procura na pasta geral de teste do aplicativo
            $paths = [
                TEST_ROOT . "/assets/sql/$file.drop.sql"
            ];

            foreach ($paths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            return false;
        }

        $this->fail("Action não definido $sqlAction");

        return null;
    }

    public function dropTables(array $tables = []): BaseTestCase
    {
        // Não deixa executar em produção
        if (APPLICATION_ENV !== 'testing') {
            $this->fail('Só é possível executar dropTables() em testing');
        }

        if (empty($tables)) {
            $tables = array_reverse($this->tables);
        }

        if (!empty($tables)) {
            // Recupera o script para remover as tabelas
            foreach ($tables as $tbl) {
                $tblFile = $this->getSqlFile($tbl, self::SQL_DROP);
                if ($tblFile === false) {
                    $dropCommand = "DROP TABLE IF EXISTS $tbl;";
                } else {
                    $dropCommand = file_get_contents($tblFile);
                }

                $this->getAdapter()->query($dropCommand, Adapter::QUERY_MODE_EXECUTE);
            }
        }

        return $this;
    }

    public function insertRows(string $table, array $rows): BaseTestCase
    {
        // Não deixa executar em produção
        if (APPLICATION_ENV !== 'testing') {
            $this->fail('Só é possível executar insertRows() em testing');
        }

        if (is_string($table)) {
            $table = new TableGateway($table, $this->getAdapter());
        } elseif (!$table instanceof TableGateway) {
            throw new \RuntimeException("$table deve ser um string ou TableGateway");
        }

        foreach ($rows as $r) {
            $table->insert($r);
        }

        return $this;
    }

    public function getDataDir(): string
    {
        if (empty($this->dataDir)) {
            // Verifica se há APPLICATION_DATA
            if (!defined('TEST_DATA')) {
                $this->fail('TEST_DATA not defined.');
            }
            $this->dataDir = TEST_DATA;
        }

        // Verifica se a pasta existe e tem permissão de escrita
        if (!is_dir($this->dataDir) || !is_writable($this->dataDir)) {
            $this->fail("{$this->dataDir} not writeable.");
        }

        return $this->dataDir;
    }

    public function setDataDir(string $dataDir): void
    {
        $this->dataDir = $dataDir;
    }

    /**
     * Apaga todas pastas do APPLICATION_DATA
     */
    public function clearApplicationData(): bool
    {
        // Apaga todo o conteudo dele
        $this->rrmdir($this->getDataDir(), $this->getDataDir());

        return $this->isApplicationDataEmpty();
    }

    /**
     * Retorna se a pasta APPLICATION_DATA está vazia
     */
    public function isApplicationDataEmpty(): bool
    {
        return (count(scandir($this->getDataDir())) === 3);
    }

    /**
     * Apaga recursivamente o conteúdo de um pasta
     *
     * @param string $dir
     * @param string $root OPCIONAL pasta raiz para evitar que seja apagada
     */
    public function rrmdir(string $dir, string $root = null): void
    {
        // Não deixa executar em produção
        if (APPLICATION_ENV !== 'testing') {
            $this->fail('Só é possível executar rrmdir() em testing');
        }

        // Não deixa apagar fora do APPLICATION_DATA
        if (empty($this->getDataDir()) || strpos($dir, $this->getDataDir()) === false) {
            $this->fail('Não é possível apagar fora do APPLICATION_DATA');
        }

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== "." && $object !== ".." && $object !== ".gitignore") {
                    if (filetype($dir . "/" . $object) === "dir") {
                        $this->rrmdir($dir . "/" . $object, $root);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }

            // Não apaga a raiz
            if ($dir !== $root && count(scandir($dir)) === 2) {
                rmdir($dir);
            }
        }
    }

    protected function getAssetsPath(string $path = ''): string
    {
        // Path do asset a ser usado
        $path = realpath($this->getDataDir() . '/../' . $path);

        // Verifica se a pasta existe e tem permissão de escrita
        if (empty($path) || !is_dir($path)) {
            $this->fail($this->getDataDir() . "/../$path não definido");
        }

        return $path;
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokePrivateMethod(&$object, string $methodName, array $parameters = [])
    {
        try {
            $reflection = new \ReflectionClass(get_class($object));
        } catch (\ReflectionException $e) {
            throw new \RuntimeException('Cannot reflect class ' . get_class($object) . ':' . $e->getMessage());
        }
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables(array $tables): BaseTestCase
    {
        $this->tables = $tables;

        return $this;
    }
}
