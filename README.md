Laminas Base
============

[![Build Status](https://travis-ci.org/realejo/laminas-base.svg?branch=master)](https://travis-ci.org/realejo/laminas-base)
[![Coverage Status](https://coveralls.io/repos/github/realejo/laminas-base/badge.svg?branch=master)](https://coveralls.io/github/realejo/laminas-base?branch=master)

Biblioteca com classes comuns utilizados nos projetos utilizando Laminas desenvolvidos pela Realejo.

Service
-------

Model para utilizar o TableGateway com funções mais comuns.

Permite criar o campo `deleted` onde o registro é marcado como removido 
e não definitavemente removido da tabela no banco de dados.


Service MPTT
------------
Implementação da árvore pre-ordernada modificada. Ideal para lidar com dados hierarquicos. 

http://www.sitepoint.com/print/hierarchical-data-database

Utils\MailSender
----------------

Classe utilizada para enviar emails via smtp.

É necessário ter as configurações definidas na pasta /config/autoload/config_email.php ou enviá-las no momento de construção do objeto.

Exemplo do arquivo:
```
<?php
return [
    'name'       => 'Nome do remetente',
    'email'      => 'email@do.remetente',
    'returnPath' => 'email@do.remetente',
    'host'       => 'smtp.dominio.com',
    'username'   => '',
    'password'   => '',
    'port'       => '2525',
];
```

