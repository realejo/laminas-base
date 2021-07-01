<?php

declare(strict_types=1);

/**
 * Model para recuperar as pastas de upload e verificar se elas possuem as permissões necessárias
 */

namespace Realejo\Utils;

class Upload
{
    /**
     * Retorna a pasta de upload para o model baseado no nome da classe
     * Se a pasta não existir ela será criada
     *
     * @param string $path Nome da classe a ser usada
     *
     * @return string
     */
    public static function getUploadPath(string $path = ''): string
    {
        // Define a pasta de upload
        $path = self::getUploadRoot() . '/' . str_replace('_', '/', strtolower($path));

        // Verifica se a pasta do cache existe
        if (!file_exists($path)) {
            $oldumask = umask(0);
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            umask($oldumask);
        }

        // Retorna a pasta de upload
        return $path;
    }

    /**
     * Retorna a pasta de visualizacao para o model baseado no nome da classe
     * Se a pasta não existir ela será criada
     *
     * @param string $path Nome da classe a ser usada
     *
     * @return string
     */
    public static function getAssetsReservedPath(string $path = ''): string
    {
        // Define a pasta de upload
        $path = self::getAssetsReservedRoot() . '/' . str_replace('_', '/', strtolower($path));

        // Verifica se a pasta do cache existe
        if (!file_exists($path)) {
            $oldumask = umask(0);
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            umask($oldumask);
        }

        // Retorna a pasta de upload
        return $path;
    }

    /**
     * Retorna a pasta de visualizacao para o model baseado no nome da classe
     * Se a pasta não existir ela será criada
     *
     * @param string $path Nome da classe a ser usada
     *
     * @return string
     */
    public static function getAssetsPublicPath(string $path = ''): string
    {
        // Define a pasta de upload
        $path = self::getAssetsPublicRoot() . '/' . str_replace('_', '/', strtolower($path));

        // Verifica se a pasta do cache existe
        if (!file_exists($path)) {
            $oldumask = umask(0);
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
            umask($oldumask);
        }

        // Retorna a pasta de upload
        return $path;
    }

    /**
     * Retorna a pasta raiz de todos os uploads
     *
     * @return string
     */
    public static function getUploadRoot(): string
    {
        // Verifica se a pasta de cache existe
        if (!defined('APPLICATION_DATA') || realpath(APPLICATION_DATA) === false) {
            throw new \RuntimeException('A pasta raiz do data não está definido em APPLICATION_DATA');
        }

        $path = APPLICATION_DATA . '/uploads';

        // Verifica se existe e se tem permissão de escrita
        if (!is_dir($path) || !is_writable($path)) {
            throw new \RuntimeException(
                'A pasta raiz de upload data/uploads não existe ou não tem permissão de escrita'
            );
        }

        // retorna a pasta raiz do cache
        return $path;
    }

    /**
     * Retorna a pasta raiz no data para gravar os arquivos enviados
     * @return string
     */
    public static function getAssetsReservedRoot(): string
    {
        // Verifica se a pasta de upload existe
        if (!defined('APPLICATION_DATA') || realpath(APPLICATION_DATA) === false) {
            throw new \RuntimeException('A pasta raiz do data não está definido em APPLICATION_DATA');
        }

        $path = APPLICATION_DATA . '/assets';

        // Verifica se existe e se tem permissão de escrita
        if (!is_dir($path) || !is_writable($path)) {
            throw new \RuntimeException(
                'A pasta raiz de upload data/assets não existe ou não tem permissão de escrita'
            );
        }

        // retorna a pasta raiz do cache
        return $path;
    }

    /**
     * Retorna a pasta raiz no public para gravar os arquivos enviados
     * @return string
     */
    public static function getAssetsPublicRoot(): string
    {
        // Verifica se a pasta de upload existe
        if (!defined('APPLICATION_HTTP') || realpath(APPLICATION_HTTP) === false) {
            throw new \RuntimeException('A pasta raiz do site não está definido em APPLICATION_HTTP');
        }

        $path = APPLICATION_HTTP . '/assets';

        // Verifica se existe e se tem permissão de escrita
        if (!is_dir($path) || !is_writable($path)) {
            throw new \RuntimeException(
                'A pasta raiz de upload site/assets não existe ou não tem permissão de escrita'
            );
        }

        return $path;
    }
}
