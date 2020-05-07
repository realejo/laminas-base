<?php

use Laminas\Db\TableGateway\Feature\GlobalAdapterFeature;

error_reporting(E_ALL | E_STRICT);

define('APPLICATION_ENV', 'testing');
define('TEST_ROOT', __DIR__);
define('TEST_DATA', TEST_ROOT . '/assets/data');

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $loader = require __DIR__ . '/../vendor/autoload.php';
}

// Define o banco de dados de testes
// Errado, eu sei. mas só vai ser corrigido no laminas-sdk porque vai quebrar muita coisa
$config = (file_exists(__DIR__ . '/configs/db.php')) ? __DIR__ . '/configs/db.php' : __DIR__ . '/configs/db.php.dist';
GlobalAdapterFeature::setStaticAdapter(new Laminas\Db\Adapter\Adapter(require $config));
