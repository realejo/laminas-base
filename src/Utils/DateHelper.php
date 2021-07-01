<?php

declare(strict_types=1);

namespace Realejo\Utils;

use DateTime;
use DateTimeInterface;

class DateHelper
{
    /**
     * Transforma data no formato d/m/a para o formato a-m-d
     *
     * @param string|DateTimeInterface $d data a se transformada para o formato do MYSQL
     * @return string
     */
    public static function toMySQL($d): ?string
    {
        if (empty($d)) {
            return null;
        }

        if ($d instanceof DateTime) {
            $sql = $d->format('Y-m-d H:i:s');
        } else {
            $datetime = explode(' ', $d);
            $date = explode('/', $datetime[0]);
            $sql = sprintf("%04d-%02d-%02d", $date[2], $date[1], $date[0]);

            if (isset($datetime[1])) {
                $sql .= ' ' . $datetime[1];
            }
        }

        return $sql;
    }

    /**
     * Retorna a diferença entre duas datas ($d1-$d2)
     * Sempre calculado a partir da diferença de segundos entre as datas
     *
     * Opções para $part
     *         y - anos
     *         m - meses
     *         w - semanas
     *         d - dias
     *         h - horas
     *         i - minutos
     *         s - segundos (padrão)
     *
     * @param DateTimeInterface $d1
     * @param DateTimeInterface $d2
     * @param string|null $part
     * @return int
     */
    public static function staticDiff(DateTimeInterface $d1, DateTimeInterface $d2, string $part = null): int
    {
        $diff = abs($d1->getTimestamp() - $d2->getTimestamp());

        switch ($part) {
            case 'y':
                return (int)floor($diff / 31536000); # 60*60*24*365
            case 'm':
                return (int)floor($diff / 2592000); # 60*60*24*30
            case 'w':
                return (int)floor($diff / 604800); # 60*60*24*7
            case 'd':
                return (int)floor($diff / 86400); # 60*60*24
            case 'h':
                return (int)floor($diff / 3600);  # 60*60
            case 'i':
                return (int)floor($diff / 60);
            case 's':
            default:
                return $diff;
        }
    }

    /**
     * Validate if a date is valid and is in the given format
     */
    public static function isDate(string $date, string $format = 'm/d/Y'): bool
    {
        $DateTimeInterface = DateTime::createFromFormat($format, $date);

        // Verifica se apareceu algum erro
        $errors = DateTime::getLastErrors();
        if (!empty($errors['warning_count'])) {
            return false;
        }

        return $DateTimeInterface !== false;
    }

    public static function isFormat(string $format, string $date): bool
    {
        DateTime::createFromFormat($format, $date);
        $date_errors = DateTime::getLastErrors();

        return (($date_errors['warning_count'] + $date_errors['error_count']) === 0);
    }
}
