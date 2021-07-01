<?php

declare(strict_types=1);

/**
 * @see https://gist.github.com/mcaskill/02636e5970be1bb22270#file-function-date-format-conversion-php
 */

namespace Realejo\View\Helper;

use DateTime;
use Laminas\View\Helper\AbstractHelper;

class FormatDate extends AbstractHelper
{
    /**
     * @param DateTime| string $date
     * @param string $format
     * @return string
     */
    public function __invoke($date, string $format)
    {
        if (!$date instanceof DateTime) {
            $date = new DateTime($date);
        }

        $locale = setlocale(LC_TIME, 0);
        setlocale(LC_TIME, ['pt_BR.utf8', 'pt_BR']);
        $dateFormatted = strftime($this->dateFormatToStrftimeFormat($format), $date->getTimestamp());
        setlocale(LC_ALL, $locale);
        return $dateFormatted;
    }

    /**
     * Convert date/time format between `date()` and `strftime()`
     *
     * Timezone conversion is done for Unix. Windows users must exchange %z and %Z.
     *
     * Unsupported date formats : S, n, t, L, B, G, u, e, I, P, Z, c, r
     * Unsupported strftime formats : %U, %W, %C, %g, %r, %R, %T, %X, %c, %D, %F, %x
     *
     * @param string $format The format to parse.
     * @param string $syntax The format's syntax. Either 'strf' for `strtime()` or 'date' for `date()`.
     * @return bool|string Returns a string formatted according $syntax using the given $format or `false`.
     * @link http://php.net/manual/en/function.strftime.php#96424
     *
     * @example Convert `%A, %B %e, %Y, %l:%M %P` to `l, F j, Y, g:i a`,
     *              and vice versa for "Saturday, March 10, 2001, 5:16 pm"
     */
    private function dateFormatTo(string $format, string $syntax)
    {
        // http://php.net/manual/en/function.strftime.php
        $strf_syntax = [
            // Day - no strf eq : S (created one called %O)
            '%O',
            '%d',
            '%a',
            '%e',
            '%A',
            '%u',
            '%w',
            '%j',
            // Week - no date eq : %U, %W
            '%V',
            // Month - no strf eq : n, t
            '%B',
            '%m',
            '%b',
            '%-m',
            // Year - no strf eq : L; no date eq : %C, %g
            '%G',
            '%Y',
            '%y',
            // Time - no strf eq : B, G, u; no date eq : %r, %R, %T, %X
            '%P',
            '%p',
            '%l',
            '%I',
            '%H',
            '%M',
            '%S',
            // Timezone - no strf eq : e, I, P, Z
            '%z',
            '%Z',
            // Full Date / Time - no strf eq : c, r; no date eq : %c, %D, %F, %x
            '%s'
        ];

        // http://php.net/manual/en/function.date.php
        $date_syntax = [
            'S',
            'd',
            'D',
            'j',
            'l',
            'N',
            'w',
            'z',
            'W',
            'F',
            'm',
            'M',
            'n',
            'o',
            'Y',
            'y',
            'a',
            'A',
            'g',
            'h',
            'H',
            'i',
            's',
            'O',
            'T',
            'U'
        ];

        switch ($syntax) {
            case 'date':
                $from = $strf_syntax;
                $to = $date_syntax;
                break;

            case 'strf':
                $from = $date_syntax;
                $to = $strf_syntax;
                break;

            default:
                return false;
        }

        $pattern = array_map(
            static function ($s) {
                return '/(?<!\\\\|\%)' . $s . '/';
            },
            $from
        );

        return preg_replace($pattern, $to, $format);
    }

    /**
     * Equivalent to `date_format_to( $format, 'date' )`
     *
     * @param string $strf_format A `strftime()` date/time format
     * @return string
     */
    private function strftimeFormatToDateFormat(string $strf_format)
    {
        return $this->dateFormatTo($strf_format, 'date');
    }

    /**
     * Equivalent to `convert_datetime_format_to( $format, 'strf' )`
     *
     * @param string $date_format A `date()` date/time format
     * @return string
     */
    private function dateFormatToStrftimeFormat(string $date_format)
    {
        return $this->dateFormatTo($date_format, 'strf');
    }
}
