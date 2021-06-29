<?php

declare(strict_types=1);

namespace Realejo\Service;

use ArrayIterator;
use InvalidArgumentException;
use Laminas\Cache\Storage as CacheStorage;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\HydratingResultSet;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Predicate;
use Laminas\Db\Sql\Select;
use Laminas\Db\TableGateway\Feature\GlobalAdapterFeature;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Hydrator\ArraySerializable;
use LogicException;
use Psr\Container\ContainerInterface;
use Realejo\Cache\CacheService;
use Realejo\Stdlib\ArrayObject;
use RuntimeException;

abstract class MapperAbstract
{
    public const KEY_STRING = 'STRING';
    public const KEY_INTEGER = 'INTEGER';

    /**
     * @var ArrayObject
     */
    protected $hydratorEntity;

    /**
     * @var ArraySerializable
     */
    protected $hydrator;

    protected bool $useHydrateResultSet = false;

    /**
     * Nome da tabela a ser usada
     */
    protected string $tableName;

    protected ?TableGateway $tableGateway = null;

    /**
     * Define o nome da chave
     * @var string|array
     */
    protected $tableKey;

    /**
     * Join lefts que devem ser usados no mapper
     *
     * @deprecated use $tableJoin
     *
     * @var array
     */
    protected array $tableJoinLeft;

    /**
     * Join lefts que devem ser usados no mapper
     */
    protected array $tableJoin;

    /**
     * Join lefts que devem ser usados no mapper
     */
    protected bool $useJoin = false;

    /**
     * Define se deve usar todas as chaves para os operações de update e delete
     */
    protected bool $useAllKeys = true;

    /**
     * Define a ordem padrão a ser usada na consultas
     *
     * @var string|array|Expression
     */
    protected $order;

    /**
     * Define o adapter a ser usado
     */
    protected ?Adapter $adapter = null;

    /**
     * Define se deve remover os registros ou apenas marcar como removido
     */
    protected bool $useDeleted = false;

    /**
     * Define se deve mostrar os registros marcados como removido
     */
    protected bool $showDeleted = false;

    protected bool $useCache = false;

    protected ?ContainerInterface $serviceLocator = null;

    protected bool $autoCleanCache = true;
    protected $cache;
    protected $lastInsertSet;
    protected $lastInsertKey;
    protected $lastUpdateSet;
    protected $lastUpdateDiff;
    protected $lastUpdateKey;
    protected $lastDeleteKey;

    /**
     *
     * @param string|null $tableName Nome da tabela a ser usada
     * @param string|array $tableKey Nome ou array de chaves a serem usadas
     *
     */
    public function __construct(string $tableName = null, $tableKey = null)
    {
        // Verifica o nome da tabela
        if (!empty($tableName) && is_string($tableName)) {
            $this->tableName = $tableName;
        }

        // Verifica o nome da chave
        if (!empty($tableKey) && (is_string($tableKey) || is_array($tableKey))) {
            $this->tableKey = $tableKey;
        }
    }

    /**
     * Excluí um registro
     *
     * @param int|array $key Código da registro a ser excluído
     *
     * @return int Informa se teve o registro foi removido
     */
    public function delete($key): int
    {
        if (empty($key)) {
            throw new InvalidArgumentException("Chave <b>'$key'</b> inválida");
        }

        if (!is_array($key) && is_array($this->getTableKey()) && count($this->getTableKey()) > 1) {
            throw new InvalidArgumentException('Não é possível apagar um registro usando chaves múltiplas parciais');
        }

        // Grava os dados alterados para referencia
        $this->lastDeleteKey = $key;

        // Verifica se deve marcar como removido ou remover o registro
        if ($this->useDeleted === true) {
            $return = $this->getTableGateway()->update(['deleted' => 1], $this->getKeyWhere($key));
        } else {
            $return = $this->getTableGateway()->delete($this->getKeyWhere($key));
        }

        // Limpa o cache se necessário
        if ($this->getUseCache() && $this->getAutoCleanCache()) {
            $this->getCache()->flush();
        }

        // Retorna se o registro foi excluído
        return $return;
    }

    /**
     * Retorna a chave definida para a tabela
     *
     * @param bool $returnSingle Quando for uma chave multipla, use TRUE para retorna a primeira chave
     * @return array|string
     */
    public function getTableKey(bool $returnSingle = false)
    {
        $key = $this->tableKey;

        // Verifica se é para retorna apenas a primeira da chave multipla
        if (is_array($key) && $returnSingle === true) {
            $key = array_shift($key);
        }

        return $key;
    }

    /**
     * @param string|array
     *
     * @return MapperAbstract
     */
    public function setTableKey($key): self
    {
        if (empty($key) && !is_string($key) && !is_array($key)) {
            throw new InvalidArgumentException('Chave inválida em ' . get_class($this));
        }

        $this->tableKey = $key;

        return $this;
    }

    public function getTableGateway(): TableGateway
    {
        if (null === $this->tableName) {
            throw new InvalidArgumentException('Tabela não definida em ' . get_class($this));
        }

        // Verifica se a tabela já foi previamente carregada
        if (null === $this->tableGateway) {
            $this->tableGateway = new TableGateway($this->tableName, $this->getAdapter());
        }

        // Retorna a tabela
        return $this->tableGateway;
    }

    public function getAdapter(): Adapter
    {
        if (null === $this->adapter) {
            if ($this->hasServiceLocator() && $this->getServiceLocator()->has(Adapter::class)) {
                $this->adapter = $this->getServiceLocator()->get(Adapter::class);

                return $this->adapter;
            }

            $this->adapter = GlobalAdapterFeature::getStaticAdapter();
        }

        return $this->adapter;
    }

    public function setAdapter(Adapter $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function hasServiceLocator(): bool
    {
        return null !== $this->serviceLocator;
    }

    public function getServiceLocator(): ContainerInterface
    {
        return $this->serviceLocator;
    }

    public function setServiceLocator(ContainerInterface $serviceLocator): self
    {
        $this->serviceLocator = $serviceLocator;

        return $this;
    }

    /**
     * Retorna a chave no formato que ela deve ser usada
     *
     * @param Expression|string|array $key
     *
     * @return Expression|string
     */
    protected function getKeyWhere($key)
    {
        if ($key instanceof Expression) {
            return $key;
        }

        if (is_numeric($key) && is_string($this->getTableKey())) {
            return "{$this->getTableKey()} = $key";
        }

        if (is_string($key) && is_string($this->getTableKey())) {
            return "{$this->getTableKey()} = '$key'";
        }

        if (!is_array($this->getTableKey())) {
            throw new LogicException('Chave mal definida em ' . get_class($this));
        }

        $where = [];
        $usedKeys = [];

        // Verifica as chaves definidas
        foreach ($this->getTableKey() as $type => $definedKey) {
            // Verifica se é uma chave única com cast
            if (count($this->getTableKey()) === 1 && !is_array($key)) {
                // Grava a chave como integer
                if (is_numeric($type) || $type === self::KEY_INTEGER) {
                    $where[] = "$definedKey = $key";
                } elseif ($type === self::KEY_STRING) { // Grava a chave como string
                    $where[] = "$definedKey = '$key'";
                }

                $usedKeys[] = $definedKey;
            } elseif (is_array($key) && !is_array($definedKey) && isset($key[$definedKey])) {
                // Grava a chave como integer
                if (is_numeric($type) || $type === self::KEY_INTEGER) {
                    $where[] = "$definedKey = {$key[$definedKey]}";
                } elseif ($type === self::KEY_STRING) {
                    $where[] = "$definedKey = '{$key[$definedKey]}'";
                }

                // Marca a chave com usada
                $usedKeys[] = $definedKey;
            } elseif (is_array($key) && is_array($definedKey)) {
                foreach ($definedKey as $value) {
                    // Grava a chave como integer
                    if (is_numeric($value) || $type === self::KEY_INTEGER) {
                        $where[] = "$value = {$key[$value]}";
                    } elseif ($type === self::KEY_STRING) {
                        $where[] = "$value = '{$key[$value]}'";
                    }
                }

                // Marca a chave com usada
                $usedKeys[] = $definedKey;
            }
        }

        // Verifica se alguma chave foi definida
        if (empty($where)) {
            throw new LogicException('Nenhuma chave definida em ' . get_class($this));
        }

        // Verifica se todas as chaves foram usadas
        if (
            $this->getUseAllKeys()
            && is_array($this->getTableKey())
            && count($usedKeys) !== count($this->getTableKey())
        ) {
            throw new LogicException('Não é permitido usar chaves parciais em ' . get_class($this));
        }

        return '(' . implode(') AND (', $where) . ')';
    }

    public function getUseAllKeys(): bool
    {
        return $this->useAllKeys;
    }

    public function setUseAllKeys(bool $useAllKeys): self
    {
        $this->useAllKeys = $useAllKeys;

        return $this;
    }

    public function getUseCache(): bool
    {
        return $this->useCache;
    }

    /**
     * Define se deve usar o cache
     */
    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    public function getAutoCleanCache(): bool
    {
        return $this->autoCleanCache;
    }

    public function setAutoCleanCache(bool $autoCleanCache): MapperAbstract
    {
        $this->autoCleanCache = $autoCleanCache;

        return $this;
    }

    /**
     * @return CacheStorage\Adapter\Filesystem|CacheStorage\StorageInterface
     */
    public function getCache()
    {
        if (!isset($this->cache)) {
            $this->cache = $this->getServiceLocator()
                ->get(CacheService::class)
                ->getFrontend(str_replace('\\', DIRECTORY_SEPARATOR, get_class($this)));
        }

        return $this->cache;
    }

    public function setCache(CacheStorage\StorageInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param $set
     * @return bool|int
     */
    public function save($set)
    {
        if (!isset($set[$this->getTableKey()])) {
            return $this->insert($set);
        }

        // Caso não seja, envia um Exception
        if (!is_numeric($set[$this->getTableKey()])) {
            throw new RuntimeException("Chave invalida: '{$set[$this->getTableKey()]}'");
        }

        if ($this->fetchRow($set[$this->getTableKey()])) {
            return $this->update($set, $set[$this->getTableKey()]);
        }

        throw new RuntimeException("{$this->getTableKey()} key does not exist in " . get_class($this));
    }

    /**
     * Grava um novo registro
     *
     * @param $set
     * @return int bool
     *
     */
    public function insert($set)
    {
        // Verifica se há algo a ser adicionado
        if (empty($set)) {
            return false;
        }

        // Grava o ultimo set incluído para referencia
        $this->lastInsertSet = $set;
        // Cria um objeto para conseguir usar o hydrator
        if (is_array($set)) {
            $set = new ArrayObject($set);
        }

        $hydrator = $this->getHydrator();
        $set = $hydrator->extract($set);

        // Remove os campos vazios
        foreach ($set as $field => $value) {
            if (is_string($value)) {
                $set[$field] = trim($value);
                if ($set[$field] === '') {
                    $set[$field] = null;
                }
            }
        }

        // Grava o set no BD
        $this->getTableGateway()->insert($set);

        // Recupera a chave gerada do registro
        if (is_array($this->getTableKey())) {
            $rightKeys = $this->getTableKey();
            $key = [];
            foreach ($rightKeys as $type => $k) {
                if (is_array($k)) {
                    foreach ($k as $value) {
                        // Grava a chave como integer
                        if (is_numeric($value) || $type === self::KEY_INTEGER) {
                            $key[$value] = $set[$value];
                            // Grava a chave como string
                        } elseif ($type === self::KEY_STRING) {
                            $key[$value] = $set[$value];
                        }
                    }
                } else {
                    if (isset($set[$k])) {
                        $key[$k] = $set[$k];
                    } else {
                        $key = false;
                        break;
                    }
                }
            }
        } elseif (isset($set[$this->getTableKey()])) {
            $key = $set[$this->getTableKey()];
        }

        if (empty($key)) {
            $key = $this->getTableGateway()->getAdapter()->getDriver()->getLastGeneratedValue();
        }

        // Grava a chave criada para referencia
        $this->lastInsertKey = $key;

        // Limpa o cache se necessário
        if ($this->getUseCache() && $this->getAutoCleanCache()) {
            $this->getCache()->flush();
        }

        // Retorna o código do registro recem criado
        return $key;
    }

    /**
     * @return ArraySerializable
     */
    public function getHydrator()
    {
        return new ArraySerializable();
    }

    public function setHydrator(ArraySerializable $hydrator): self
    {
        if ($hydrator === null) {
            throw new InvalidArgumentException('Invalid hydrator');
        }
        $this->hydrator = $hydrator;

        return $this;
    }

    /**
     * Recupera um registro
     *
     * @param mixed $where condições para localizar o registro
     * @param string|array $order
     *
     * @return null|ArrayObject
     */
    public function fetchRow($where, $order = null)
    {
        // Define se é a chave da tabela
        if (is_numeric($where) || is_string($where)) {
            // Verifica se há chave definida
            if (empty($this->tableKey)) {
                throw new InvalidArgumentException('Chave não definida em ' . get_class($this));
            }

            // Verifica se é uma chave múltipla ou com cast
            if (is_array($this->tableKey)) {
                // Verifica se é uma chave simples com cast
                if (count($this->tableKey) !== 1) {
                    throw new InvalidArgumentException('Não é possível acessar chaves múltiplas informando apenas uma');
                }
                $where = [$this->getTableKey(true) => $where];
            } else {
                $where = [$this->tableKey => $where];
            }
        }

        // Recupera o registro
        $fetchRow = $this->fetchAll($where, $order, 1);

        if ($this->useHydrateResultSet) {
            return !empty($fetchRow) ? $fetchRow->current() : null;
        }

        return !empty($fetchRow) ? $fetchRow[0] : null;
    }

    /**
     * @param array $where condições para localizar o registro
     * @param array|string $order
     * @param int $count
     * @param int $offset
     *
     * @return ArrayObject[]|HydratingResultSet
     */
    public function fetchAll(array $where = null, $order = null, int $count = null, int $offset = null)
    {
        // Cria a assinatura da consulta
        if ($where instanceof Select) {
            $cacheKey = 'fetchAll' . md5($where->getSqlString($this->getAdapter()->getPlatform()));
        } else {
            $cacheKey = 'fetchAll'
                . md5(
                    var_export($this->showDeleted, true)
                    . var_export($this->useJoin, true)
                    . var_export($where, true)
                    . var_export($order, true)
                    . var_export($count, true)
                    . var_export($offset, true)
                );
        }

        // Verifica se tem no cache
        // o Zend_Paginator precisa do Zend_Paginator_Adapter_DbSelect para acessar o cache
        if ($this->getUseCache() && $this->getCache()->hasItem($cacheKey)) {
            return $this->getCache()->getItem($cacheKey);
        }

        // Define a consulta
        if ($where instanceof Select) {
            $select = $where;
        } else {
            $select = $this->getSelect($where, $order, $count, $offset);
        }

        // Recupera os registros do banco de dados
        $fetchAll = $this->getTableGateway()->selectWith($select);

        // Verifica se foi localizado algum registro
        if ($fetchAll !== null && count($fetchAll) > 0) {
            // Passa o $fetch para array para poder incluir campos extras
            $fetchAll = $fetchAll->toArray();
        } else {
            $fetchAll = null;
        }

        if (empty($fetchAll)) {
            // Grava a consulta no cache
            if ($this->getUseCache()) {
                $this->getCache()->setItem($cacheKey, $fetchAll);
            }

            return $fetchAll;
        }

        $hydrator = $this->getHydrator();
        if ($hydrator === null) {
            // Grava a consulta no cache
            if ($this->getUseCache()) {
                $this->getCache()->setItem($cacheKey, $fetchAll);
            }

            return $fetchAll;
        }
        $hydratorEntity = $this->getHydratorEntity();


        if ($this->useHydrateResultSet) {
            $hydrateResultSet = new HydratingResultSet($hydrator, new $hydratorEntity());
            $hydrateResultSet->initialize(new ArrayIterator($fetchAll));

            // Grava a consulta no cache
            if ($this->getUseCache()) {
                $this->getCache()->setItem($cacheKey, $hydrateResultSet);
            }

            return $hydrateResultSet;
        }

        foreach ($fetchAll as $id => $row) {
            $fetchAll[$id] = $hydrator->hydrate($row, new $hydratorEntity());
        }

        // Grava a consulta no cache
        if ($this->getUseCache()) {
            $this->getCache()->setItem($cacheKey, $fetchAll);
        }

        return $fetchAll;
    }

    /**
     * Retorna o select para a consulta
     *
     * @param array $where OPTIONAL An SQL WHERE clause
     * @param string|array|Expression $order OPTIONAL An SQL ORDER clause.
     * @param int|null $count OPTIONAL An SQL LIMIT count.
     * @param int|null $offset OPTIONAL An SQL LIMIT offset.
     *
     * @return Select
     */
    public function getSelect(array $where = null, $order = null, int $count = null, int $offset = null)
    {
        // Retorna o select para a tabela
        $select = $this->getTableSelect();

        // Verifica se existe ordem padrão
        if ($order === false) {
            $select->reset('order');
        } elseif (empty($order) && isset($this->order)) {
            if (is_string($this->order) && strpos($this->order, '=') !== false) {
                $this->order = new Expression($this->order);
            }
            $order = $this->order;
        }

        // Define a ordem
        if (!empty($order)) {
            $select->order($order);
        }

        // Verifica se há paginação, não confundir com o Laminas\Paginator
        if ($count !== null) {
            $select->limit($count);
        }
        if ($offset !== null) {
            $select->offset($offset);
        }

        // Verifica se é um array para fazer o processamento abaixo
        if (!is_array($where)) {
            $where = empty($where) ? [] : [$where];
        }

        // Checks $where is deleted
        if ($this->getUseDeleted() && !$this->getShowDeleted() && !isset($where['deleted'])) {
            $where['deleted'] = 0;
        }

        // Verifica as clausulas especiais se houver
        $where = $this->getWhere($where);

        // processa as clausulas
        foreach ($where as $id => $w) {
            // \Laminas\Db\Sql\Expression
            if (is_numeric($id) && $w instanceof Expression) {
                if (!$w instanceof Predicate\Expression) {
                    $select->where(new Predicate\Expression($w->getExpression()));
                } else {
                    $select->where($w);
                }
                continue;
            }

            // Checks is deleted
            if ($id === 'deleted' && $w === false) {
                $select->where("{$this->getTableGateway()->getTable()}.deleted=0");
                continue;
            }

            if ($id === 'deleted' && $w === true) {
                $select->where("{$this->getTableGateway()->getTable()}.deleted=1");
                continue;
            }

            if ((is_numeric($id) && $w === 'ativo') || ($id === 'ativo' && $w === true)) {
                $select->where("{$this->getTableGateway()->getTable()}.ativo=1");
                continue;
            }

            if ($id === 'ativo' && $w === false) {
                $select->where("{$this->getTableGateway()->getTable()}.ativo=0");
                continue;
            }

            // Valor numerico
            if (!is_numeric($id) && is_numeric($w)) {
                if (strpos($id, '.') === false) {
                    $id = "{$this->tableName}.$id";
                }
                $select->where(new Predicate\Operator($id, '=', $w));
                continue;
            }

            // Texto e Data
            if (!is_numeric($id)) {
                if (strpos($id, '.') === false) {
                    $id = "{$this->tableName}.$id";
                }

                if ($w === null) {
                    $select->where(new Predicate\IsNull($id));
                } else {
                    $select->where(new Predicate\Operator($id, '=', $w));
                }
                continue;
            }

            throw new LogicException("Condição inválida '$w' em " . get_class($this));
        }

        return $select;
    }

    /**
     * Retorna o select a ser usado no fetchAll e fetchRow
     */
    public function getTableSelect(): Select
    {
        $select = $this->getTableGateway()->getSql()->select();

        $tableJoinDefinition = $this->tableJoin ?? $this->tableJoinLeft ?? null;

        if (!empty($tableJoinDefinition) && $this->getUseJoin()) {
            foreach ($tableJoinDefinition as $definition) {
                if (empty($definition['table']) && !is_string($definition['table'])) {
                    throw new InvalidArgumentException('Tabela não definida em ' . get_class($this));
                }

                if (empty($definition['condition']) && !is_string($definition['condition'])) {
                    throw new InvalidArgumentException(
                        "Condição para a tabela {$definition['table']} não definida em " . get_class($this)
                    );
                }

                if (
                    isset($definition['columns']) && !empty($definition['columns'])
                    && !is_array($definition['columns'])
                ) {
                    throw new InvalidArgumentException(
                        "Colunas para a tabela {$definition['table']} devem ser um array em " . get_class($this)
                    );
                }

                if (array_key_exists('schema', $definition)) {
                    trigger_error('Schema não pode ser usado. Use type.', E_USER_DEPRECATED);
                    $definition['type'] = $definition['schema'];
                }

                if (isset($definition['type']) && !empty($definition['type']) && !is_string($definition['type'])) {
                    throw new InvalidArgumentException('Type devem ser uma string em ' . get_class($this));
                }

                if (!isset($definition['type'])) {
                    $definition['type'] = null;
                }

                $select->join(
                    $definition['table'],
                    $definition['condition'],
                    $definition['columns'],
                    $definition['type']
                );
            }
        }

        return $select;
    }

    public function getUseJoin(): bool
    {
        return $this->useJoin;
    }

    public function setUseJoin(bool $useJoin): self
    {
        $this->useJoin = $useJoin;

        return $this;
    }

    /**
     * Retorna se irá usar o campo deleted ou remover o registro quando usar delete()
     */
    public function getUseDeleted(): bool
    {
        return $this->useDeleted;
    }

    /**
     * Define se irá usar o campo deleted ou remover o registro quando usar delete()
     */
    public function setUseDeleted(bool $useDeleted): self
    {
        $this->useDeleted = $useDeleted;

        return $this;
    }

    /**
     * Retorna se deve retornar os registros marcados como removidos
     */
    public function getShowDeleted(): bool
    {
        return $this->showDeleted;
    }

    /**
     * Define se deve retornar os registros marcados como removidos
     */
    public function setShowDeleted(bool $showDeleted): MapperAbstract
    {
        $this->showDeleted = $showDeleted;

        return $this;
    }

    /**
     * Processa as clausulas especiais do where
     */
    public function getWhere(array $where): array
    {
        return $where;
    }

    /**
     * @return ArrayObject|string
     */
    public function getHydratorEntity(bool $asObject = true)
    {
        if ($asObject === false) {
            return $this->hydratorEntity;
        }

        if (isset($this->hydratorEntity)) {
            $hydrator = $this->hydratorEntity;

            return new $hydrator();
        }

        return new ArrayObject();
    }

    public function setHydratorEntity(string $hydratorEntity): MapperAbstract
    {
        $this->hydratorEntity = $hydratorEntity;

        return $this;
    }

    /**
     * Altera um registro
     *
     * @param array|ArrayObject $set Dados a serem atualizados
     * @param int|array $key Chave do registro a ser alterado
     *
     * @return bool
     */
    public function update($set, $key)
    {
        // Verifica se o código é válido
        if (empty($key)) {
            throw new InvalidArgumentException("Chave <b>'$key'</b> inválida");
        }

        // Verifica se há algo para alterar
        if (empty($set)) {
            return false;
        }

        // Cria um objeto para conseguir usar o hydrator
        if (is_array($set)) {
            $set = new ArrayObject($set);
        }

        // Recupera os dados existentes
        $row = $this->fetchRow($key);

        // Verifica se existe o registro
        if ($row === null) {
            return false;
        }

        $hydrator = $this->getHydrator();
        $row = $hydrator->extract($row);
        $set = $hydrator->extract($set);

        //@todo Quem deveria fazer isso é o hydrator!
        if ($row instanceof Metadata\ArrayObject) {
            $row = $row->toArray();
            if (isset($row['metadata'])) {
                $row[$row->getMappedKeyname('metadata', true)] = $row['metadata'];
                unset($row['metadata']);
            }
        }

        // Remove os campos vazios
        foreach ($set as $field => $value) {
            if (is_string($value)) {
                $set[$field] = trim($value);
                if ($set[$field] === '') {
                    $set[$field] = null;
                }
            }
        }

        // Verifica se há o que atualizar
        $diff = self::arrayDiffAssocRecursive($set, $row);

        // Grava os dados alterados para referencia
        $this->lastUpdateSet = $set;
        $this->lastUpdateKey = $key;

        // Grava o que foi alterado
        $this->lastUpdateDiff = [];
        foreach ($diff as $field => $value) {
            $this->lastUpdateDiff[$field] = [$row[$field], $value];
        }

        // Verifica se há algo para atualizar
        if (empty($diff)) {
            return false;
        }

        // Salva os dados alterados
        $return = $this->getTableGateway()->update($diff, $this->getKeyWhere($key));

        // Limpa o cache, se necessário
        if ($this->getUseCache() && $this->getAutoCleanCache()) {
            $this->getCache()->flush();
        }

        // Retorna que o registro foi alterado
        return $return;
    }

    private function arrayDiffAssocRecursive($array1, $array2)
    {
        $difference = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::arrayDiffAssocRecursive($value, $array2[$key]);
                    if (!empty($new_diff)) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }

    /**
     * @return array
     */
    public function getLastInsertSet()
    {
        return $this->lastInsertSet;
    }

    /**
     * @return int
     */
    public function getLastInsertKey()
    {
        return $this->lastInsertKey;
    }

    /**
     * @return array
     */
    public function getLastUpdateSet()
    {
        return $this->lastUpdateSet;
    }

    /**
     * @return array
     */
    public function getLastUpdateDiff()
    {
        return $this->lastUpdateDiff;
    }

    /**
     * @return int
     */
    public function getLastUpdateKey()
    {
        return $this->lastUpdateKey;
    }

    /**
     * @return int
     */
    public function getLastDeleteKey()
    {
        return $this->lastDeleteKey;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return array $tableJoinLeft
     */
    public function getTableJoinLeft()
    {
        return $this->tableJoinLeft;
    }

    /**
     * @param array $tableJoinLeft
     *
     * @return MapperAbstract
     */
    public function setTableJoinLeft($tableJoinLeft)
    {
        $this->tableJoinLeft = $tableJoinLeft;

        return $this;
    }

    /**
     *
     * @return string|array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     *
     * @param string|array|Expression $order
     *
     * @return MapperAbstract
     */
    public function setOrder($order)
    {
        if (empty($order) && !is_string($order) && !is_array($order) && (!$order instanceof Expression)) {
            throw new InvalidArgumentException('Ordem inválida em ' . get_class($this));
        }

        $this->order = $order;

        return $this;
    }

    public function isUseHydrateResultSet(): bool
    {
        return $this->useHydrateResultSet;
    }

    /**
     * @param bool $useHydrateResultSet
     * @return MapperAbstract
     */
    public function setUseHydrateResultSet(bool $useHydrateResultSet)
    {
        $this->useHydrateResultSet = $useHydrateResultSet;

        return $this;
    }
}
