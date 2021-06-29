<?php

declare(strict_types=1);

namespace RealejoTest\Utils;

use Exception;
use Laminas\Mail;
use Laminas\Mime;
use PHPUnit\Framework\TestCase;
use Realejo\Utils\MailSender;

class MailSenderTest extends TestCase
{
    public function testConstructError(): void
    {
        $this->expectException(Exception::class);
        new MailSender();
    }

    private function getMailSenderConfig()
    {
        $configFile = __DIR__ . '/../../configs/mailsender.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }

        return require $configFile . '.dist';
    }

    public function testConstructSuccess(): void
    {
        $config = $this->getMailSenderConfig();
        $oMailer = new MailSender($config['mailsender']);
        self::assertInstanceOf(MailSender::class, $oMailer);
        self::assertInstanceOf(Mail\Transport\Smtp::class, $oMailer->getTransport());
    }

    public function testSetEmailComAnexoStrings(): void
    {
        $config = $this->getMailSenderConfig();
        $oMailer = new MailSender($config['mailsender']);

        $files = [
            TEST_ROOT . '/assets/sql/album.create.sql',
            TEST_ROOT . '/assets/sql/album.drop.sql',
        ];

        $oMailer->setEmailMessage(
            null,
            null,
            $config['test-name'],
            $config['test-email'],
            'Olá',
            'Olá mundo, teste do anexo com array de strings',
            ['anexos' => $files]
        );

        //verifica se os remetentes e destinatarios estao ok
        self::assertEquals($config['mailsender']['name'], $oMailer->getMessage()->getFrom()->current()->getName());
        self::assertEquals($config['mailsender']['email'], $oMailer->getMessage()->getFrom()->current()->getEmail());
        self::assertEquals($config['test-name'], $oMailer->getMessage()->getTo()->current()->getName());
        self::assertEquals($config['test-email'], $oMailer->getMessage()->getTo()->current()->getEmail());

        //define e verifica o reply-to
        $oMailer->getMessage()->setReplyTo($config['mailsender']['email'], $config['mailsender']['name']);
        self::assertEquals($config['mailsender']['name'], $oMailer->getMessage()->getReplyTo()->current()->getName());
        self::assertEquals(
            $config['mailsender']['email'],
            $oMailer->getMessage()->getReplyTo()->current()->getEmail()
        );

        //verifica o assunto
        self::assertEquals('Olá', $oMailer->getMessage()->getSubject());

        //verifica se existe o mime part html
        self::assertNotEmpty($oMailer->getMessage()->getBody());
        self::assertCount(3, $oMailer->getMessage()->getBody()->getParts());

        $parts = $oMailer->getMessage()->getBody()->getParts();
        self::assertInstanceOf(Mime\Part::class, $parts[0]);
        self::assertInstanceOf(Mime\Part::class, $parts[1]);
        self::assertInstanceOf(Mime\Part::class, $parts[2]);

        self::assertEquals('Olá mundo, teste do anexo com array de strings', $parts[0]->getContent());
        self::assertEquals('album.create.sql', $parts[1]->getFileName());
        self::assertEquals('album.drop.sql', $parts[2]->getFileName());
    }

    public function testEnvioEmailComAnexoSource(): void
    {
        $config = $this->getMailSenderConfig();
        $oMailer = new MailSender($config['mailsender']);

        $file1 = fopen(TEST_ROOT . '/assets/sql/album.create.sql', 'rb');
        $file2 = fopen(TEST_ROOT . '/assets/sql/album.drop.sql', 'rb');

        $files = [
            $file1,
            $file2,
        ];

        $oMailer->setEmailMessage(
            null,
            null,
            $config['test-name'],
            $config['test-email'],
            'Olá',
            'Olá mundo, teste do anexo com array de strings',
            ['anexos' => $files]
        );

        //verifica se os remetentes e destinatarios estao ok
        self::assertEquals($config['mailsender']['name'], $oMailer->getMessage()->getFrom()->current()->getName());
        self::assertEquals($config['mailsender']['email'], $oMailer->getMessage()->getFrom()->current()->getEmail());
        self::assertEquals($config['test-name'], $oMailer->getMessage()->getTo()->current()->getName());
        self::assertEquals($config['test-email'], $oMailer->getMessage()->getTo()->current()->getEmail());

        //define e verifica o reply-to
        $oMailer->getMessage()->setReplyTo($config['mailsender']['email'], $config['mailsender']['name']);
        self::assertEquals($config['mailsender']['name'], $oMailer->getMessage()->getReplyTo()->current()->getName());
        self::assertEquals(
            $config['mailsender']['email'],
            $oMailer->getMessage()->getReplyTo()->current()->getEmail()
        );

        //verifica o assunto
        self::assertEquals('Olá', $oMailer->getMessage()->getSubject());

        //verifica se existe o mime part html
        self::assertNotEmpty($oMailer->getMessage()->getBody());
        self::assertCount(3, $oMailer->getMessage()->getBody()->getParts());

        $parts = $oMailer->getMessage()->getBody()->getParts();
        self::assertInstanceOf(Mime\Part::class, $parts[0]);
        self::assertInstanceOf(Mime\Part::class, $parts[1]);
        self::assertInstanceOf(Mime\Part::class, $parts[2]);

        self::assertEquals('Olá mundo, teste do anexo com array de strings', $parts[0]->getContent());
        self::assertEquals('application/octet-stream', $parts[1]->getType());
        self::assertEquals('application/octet-stream', $parts[2]->getType());
    }

    public function testEnvioEmailHtmlSuccess(): void
    {
        $config = $this->getMailSenderConfig();
        $oMailer = new MailSender($config['mailsender']);
        $htmlEmail = '<html><head><title>Olá mundo</title></head>'
            . '<body><h2>Teste do html</h2>Aqui é um post em html<br/></body></html>';

        $oMailer->setEmailMessage(
            null,
            null,
            $config['test-name'],
            $config['test-email'],
            'Olá',
            $htmlEmail
        );

        //verifica se os remetentes e destinatarios estao ok
        self::assertEquals($config['mailsender']['name'], $oMailer->getMessage()->getFrom()->current()->getName());
        self::assertEquals($config['mailsender']['email'], $oMailer->getMessage()->getFrom()->current()->getEmail());
        self::assertEquals($config['test-name'], $oMailer->getMessage()->getTo()->current()->getName());
        self::assertEquals($config['test-email'], $oMailer->getMessage()->getTo()->current()->getEmail());

        //define e verifica o reply-to
        $oMailer->getMessage()->setReplyTo($config['mailsender']['email'], $config['mailsender']['name']);
        self::assertEquals($config['mailsender']['name'], $oMailer->getMessage()->getReplyTo()->current()->getName());
        self::assertEquals(
            $config['mailsender']['email'],
            $oMailer->getMessage()->getReplyTo()->current()->getEmail()
        );

        //verifica o assunto
        self::assertEquals('Olá', $oMailer->getMessage()->getSubject());

        //verifica se existe o mime part html
        self::assertNotEmpty($oMailer->getMessage()->getBody());
        self::assertCount(1, $oMailer->getMessage()->getBody()->getParts());

        $parts = $oMailer->getMessage()->getBody()->getParts();
        self::assertInstanceOf(Mime\Part::class, $parts[0]);

        self::assertEquals(
            '<html><head><title>Olá mundo</title></head>'
            . '<body><h2>Teste do html</h2>Aqui é um post em html<br/></body></html>',
            $parts[0]->getContent()
        );

        if ($config['test-really-send-email'] === true) {
            self::assertNull($oMailer->send());
        }
    }

    public function testMessageDifferentSender(): void
    {
        $config = $this->getMailSenderConfig();
        $oMailer = new MailSender($config['mailsender']);
        $htmlEmail = '<html><head><title>Olá mundo</title></head>'
            . '<body><h2>Teste do html</h2>Aqui é um post em html<br/></body></html>';

        $oMailer->setEmailMessage(
            'Another sender',
            'another-email@somewhere.com',
            $config['test-name'],
            $config['test-email'],
            'Olá',
            $htmlEmail
        );

        //verifica se os remetentes e destinatarios estao ok
        self::assertEquals('Another sender', $oMailer->getMessage()->getFrom()->current()->getName());
        self::assertEquals('another-email@somewhere.com', $oMailer->getMessage()->getFrom()->current()->getEmail());
        self::assertEquals($config['test-name'], $oMailer->getMessage()->getTo()->current()->getName());
        self::assertEquals($config['test-email'], $oMailer->getMessage()->getTo()->current()->getEmail());

        //define e verifica o reply-to
        $oMailer->getMessage()->setReplyTo('another-email2@somewhere.com', 'Another sender 2');
        self::assertEquals('Another sender 2', $oMailer->getMessage()->getReplyTo()->current()->getName());
        self::assertEquals(
            'another-email2@somewhere.com',
            $oMailer->getMessage()->getReplyTo()->current()->getEmail()
        );

        //verifica o assunto
        self::assertEquals('Olá', $oMailer->getMessage()->getSubject());

        //verifica se existe o mime part html
        self::assertNotEmpty($oMailer->getMessage()->getBody());
        self::assertCount(1, $oMailer->getMessage()->getBody()->getParts());

        $parts = $oMailer->getMessage()->getBody()->getParts();
        self::assertInstanceOf(Mime\Part::class, $parts[0]);

        self::assertEquals(
            '<html><head><title>Olá mundo</title></head>'
            . '<body><h2>Teste do html</h2>Aqui é um post em html<br/></body></html>',
            $parts[0]->getContent()
        );

        if ($config['test-really-send-email'] === true) {
            self::assertNull($oMailer->send());
        }
    }

    public function testIsValid(): void
    {
        self::assertFalse(MailSender::isEmail('wrooong'));
        self::assertTrue(MailSender::isEmail('test@email.com'));
    }
}
