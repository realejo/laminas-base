<?php

declare(strict_types=1);

namespace RealejoTest\Utils;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Realejo\Utils\DateHelper;

class DateHelperTest extends TestCase
{
    public function testToMysqlFromDateTimeObject(): void
    {
        $data = DateTime::createFromFormat('d/m/Y H:i:s', '12/02/2016 00:00:00');
        $dataTest = DateHelper::toMySQL($data);
        self::assertEquals('2016-02-12 00:00:00', $dataTest);
    }

    public function testToMysqlFromString(): void
    {
        $dataTest = DateHelper::toMySQL('12/02/2016 00:00:00');
        self::assertEquals('2016-02-12 00:00:00', $dataTest);
    }

    public function testStaticDiffFromDateTimeObject(): void
    {
        $data1 = DateTime::createFromFormat(
            'd/m/Y H:i:s',
            '12/02/2016 01:02:03',
            new DateTimeZone('America/Sao_Paulo')
        );
        $data2 = DateTime::createFromFormat(
            'd/m/Y H:i:s',
            '12/05/2018 03:02:01',
            new DateTimeZone('America/Sao_Paulo')
        );

        //diferença de anos entre as datas
        $dataDiffAno = DateHelper::staticDiff($data1, $data2, 'y');
        self::assertEquals(2, $dataDiffAno);

        $dataDiffMes = DateHelper::staticDiff($data1, $data2, 'm');
        self::assertEquals(27, $dataDiffMes);

        $dataDiffSemana = DateHelper::staticDiff($data1, $data2, 'w');
        self::assertEquals(117, $dataDiffSemana);

        $dataDiffDia = DateHelper::staticDiff($data1, $data2, 'd');
        self::assertEquals(820, $dataDiffDia);

        $dataDiffHora = DateHelper::staticDiff($data1, $data2, 'h');
        self::assertEquals(19682, $dataDiffHora);

        $dataDiffMinuto = DateHelper::staticDiff($data1, $data2, 'i');
        self::assertEquals(1180979, $dataDiffMinuto);

        $dataDiffSegundo = DateHelper::staticDiff($data1, $data2, 's');
        self::assertEquals(70858798, $dataDiffSegundo);

        $dataDiffSegundo = DateHelper::staticDiff($data1, $data2);
        self::assertEquals(70858798, $dataDiffSegundo);
    }
}
