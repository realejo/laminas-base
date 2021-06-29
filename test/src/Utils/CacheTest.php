<?php

declare(strict_types=1);

namespace RealejoTest\Utils;

use Realejo\Cache\CacheService;
use RealejoTest\BaseTestCase;
use Laminas\Cache\Storage\Adapter\Filesystem;

class CacheTest extends BaseTestCase
{
    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new CacheService();
        $this->cacheService->setCacheDir($this->getDataDir() . '/cache');

        $this->clearApplicationData();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearApplicationData();
    }

    public function testGetCacheRoot(): void
    {
        $path = $this->cacheService->getCacheRoot();

        self::assertNotNull($path);
        self::assertEquals(realpath(TEST_DATA . '/cache'), $path);
        self::assertFileExists($path);
        self::assertDirectoryExists($path);
        self::assertTrue(is_writable($path));
    }

    public function testGetCachePath(): void
    {
        // Verifica se todas as opções são iguais
        self::assertEquals($this->cacheService->getCacheRoot(), $this->cacheService->getCachePath(null));
        self::assertEquals($this->cacheService->getCacheRoot(), $this->cacheService->getCachePath(''));
        self::assertEquals($this->cacheService->getCacheRoot(), $this->cacheService->getCachePath());

        // Cria ou recupera a pasta album
        $path = $this->cacheService->getCachePath('Album');

        // Verifica se foi criada corretamente a pasta
        self::assertNotNull($path);
        self::assertEquals(realpath(TEST_DATA . '/cache/album'), $path);
        self::assertNotEquals(realpath(TEST_DATA . '/cache/Album'), $path);
        self::assertFileExists($path);
        self::assertDirectoryExists($path);
        self::assertTrue(is_writable($path));

        // Apaga a pasta
        $this->rrmdir($path);

        // Verifica se a pasta foi apagada
        self::assertFileNotExists($path);

        // Cria ou recupera a pasta album
        $path = $this->cacheService->getCachePath('album');

        // Verifica se foi criada corretamente a pasta
        self::assertNotNull($path);
        self::assertEquals(realpath(TEST_DATA . '/cache/album'), $path);
        self::assertNotEquals(realpath(TEST_DATA . '/cache/Album'), $path);
        self::assertFileExists($path, 'Verifica se a pasta album existe');
        self::assertDirectoryExists($path, 'Verifica se a pasta album é uma pasta');
        self::assertTrue(is_writable($path), 'Verifica se a pasta album tem permissão de escrita');

        // Apaga a pasta
        $this->rrmdir($path);

        // Verifica se a pasta foi apagada
        self::assertFileNotExists($path);

        // Cria ou recupera a pasta
        $path = $this->cacheService->getCachePath('album_Teste');

        // Verifica se foi criada corretamente a pasta
        self::assertNotNull($path);
        self::assertEquals(realpath(TEST_DATA . '/cache/album/teste'), $path);
        self::assertNotEquals(realpath(TEST_DATA . '/cache/Album/Teste'), $path);
        self::assertFileExists($path, 'Verifica se a pasta album_Teste existe');
        self::assertDirectoryExists($path, 'Verifica se a pasta album_Teste é uma pasta');
        self::assertTrue(is_writable($path), 'Verifica se a pasta album_Teste tem permissão de escrita');

        // Apaga a pasta
        $this->rrmdir($path);

        // Verifica se a pasta foi apagada
        self::assertFileNotExists($path, 'Verifica se a pasta album_Teste foi apagada');

        // Cria ou recupera a pasta
        $path = $this->cacheService->getCachePath('album/Teste');

        // Verifica se foi criada corretamente a pasta
        self::assertNotNull($path, 'Teste se o album/Teste foi criado');
        self::assertEquals(realpath(TEST_DATA . '/cache/album/teste'), $path);
        self::assertNotEquals(realpath(TEST_DATA . '/cache/Album/Teste'), $path);
        self::assertFileExists($path, 'Verifica se a pasta album/Teste existe');
        self::assertDirectoryExists($path, 'Verifica se a pasta album/Teste é uma pasta');
        self::assertTrue(is_writable($path), 'Verifica se a pasta album/Teste tem permissão de escrita');
    }

    public function testGetFrontendComClass(): void
    {
        $cache = $this->cacheService->getFrontend('Album');
        self::assertInstanceOf(Filesystem::class, $cache);
    }

    public function testGetFrontendSemClass(): void
    {
        $cache = $this->cacheService->getFrontend(null);
        self::assertInstanceOf(Filesystem::class, $cache);
    }
}
