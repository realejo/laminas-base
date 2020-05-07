<?php

/**
 * @see https://github.com/cakephp/cakephp/blob/ec23730aff39a2ca1230c2f34cbff92a96a44a75/src/Utility/Text.php
 * @link http://book.cakephp.org/3.0/en/views/helpers/text.html#truncating-text
 */

namespace Realejo\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class Text extends AbstractHelper
{
    /**
     * Truncates text.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as ending and appended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     * - `trimWidth` If true, $text will be truncated with the width
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of HTML attributes and options.
     * @return string Trimmed string.
     * @link http://book.cakephp.org/3.0/en/core-libraries/string.html#truncating-text
     */
    public static function truncate($text, $length = 100, array $options = [])
    {
        $default = [
            'ellipsis' => '...',
            'exact' => true,
            'html' => true,
            'trimWidth' => false,
        ];

        if (!empty($options['html']) && strtolower(mb_internal_encoding()) === 'utf-8') {
            $default['ellipsis'] = "\xe2\x80\xa6";
        }
        $options += $default;
        $prefix = '';
        $suffix = $options['ellipsis'];
        if ($options['html']) {
            $ellipsisLength = self::strlen(strip_tags($options['ellipsis']), $options);
            $truncateLength = 0;
            $totalLength = 0;
            $openTags = [];
            $truncate = '';
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                $contentLength = self::strlen($tag[3], $options);
                if ($truncate === '') {
                    if (
                        !preg_match(
                            '/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/i',
                            $tag[2]
                        )
                    ) {
                        if (preg_match('/<[\w]+[^>]*>/', $tag[0])) {
                            array_unshift($openTags, $tag[2]);
                        } elseif (preg_match('/<\/([\w]+)[^>]*>/', $tag[0], $closeTag)) {
                            $pos = array_search($closeTag[1], $openTags);
                            if ($pos !== false) {
                                array_splice($openTags, $pos, 1);
                            }
                        }
                    }
                    $prefix .= $tag[1];
                    if ($totalLength + $contentLength + $ellipsisLength > $length) {
                        $truncate = $tag[3];
                        $truncateLength = $length - $totalLength;
                    } else {
                        $prefix .= $tag[3];
                    }
                }
                $totalLength += $contentLength;
                if ($totalLength > $length) {
                    break;
                }
            }
            if ($totalLength <= $length) {
                return $text;
            }
            $text = $truncate;
            $length = $truncateLength;
            foreach ($openTags as $tag) {
                $suffix .= '</' . $tag . '>';
            }
        } else {
            if (self::strlen($text, $options) <= $length) {
                return $text;
            }
            $ellipsisLength = self::strlen($options['ellipsis'], $options);
        }
        $result = self::substr($text, 0, $length - $ellipsisLength, $options);
        if (!$options['exact']) {
            if (self::substr($text, $length - $ellipsisLength, 1, $options) !== ' ') {
                $result = self::removeLastWord($result);
            }
            // If result is empty, then we don't need to count ellipsis in the cut.
            if (!strlen($result)) {
                $result = self::substr($text, 0, $length, $options);
            }
        }
        return $prefix . $result . $suffix;
    }

    /**
     * Get string length.
     *
     * ### Options:
     *
     * - `html` If true, HTML entities will be handled as decoded characters.
     * - `trimWidth` If true, the width will return.
     *
     * @param string $text The string being checked for length
     * @param array $options An array of options.
     * @return int
     */
    private static function strlen($text, array $options)
    {
        if (empty($options['trimWidth'])) {
            $strlen = 'mb_strlen';
        } else {
            $strlen = 'mb_strwidth';
        }
        if (empty($options['html'])) {
            return $strlen($text);
        }
        $pattern = '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i';
        $replace = preg_replace_callback(
            $pattern,
            static function ($match) use ($strlen) {
                $utf8 = html_entity_decode($match[0], ENT_HTML5 | ENT_QUOTES, 'UTF-8');
                return str_repeat(' ', $strlen($utf8, 'UTF-8'));
            },
            $text
        );
        return $strlen($replace);
    }

    /**
     * Return part of a string.
     *
     * ### Options:
     *
     * - `html` If true, HTML entities will be handled as decoded characters.
     * - `trimWidth` If true, will be truncated with specified width.
     *
     * @param string $text The input string.
     * @param int $start The position to begin extracting.
     * @param int $length The desired length.
     * @param array $options An array of options.
     * @return string
     */
    private static function substr($text, $start, $length, array $options)
    {
        if (empty($options['trimWidth'])) {
            $substr = 'mb_substr';
        } else {
            $substr = 'mb_strimwidth';
        }
        $maxPosition = self::strlen($text, ['trimWidth' => false] + $options);
        if ($start < 0) {
            $start += $maxPosition;
            if ($start < 0) {
                $start = 0;
            }
        }
        if ($start >= $maxPosition) {
            return '';
        }
        if ($length === null) {
            $length = self::strlen($text, $options);
        }
        if ($length < 0) {
            $text = self::substr($text, $start, null, $options);
            $start = 0;
            $length += self::strlen($text, $options);
        }
        if ($length <= 0) {
            return '';
        }
        if (empty($options['html'])) {
            return (string)$substr($text, $start, $length);
        }
        $totalOffset = 0;
        $totalLength = 0;
        $result = '';
        $pattern = '/(&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};)/i';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            $offset = 0;
            if ($totalOffset < $start) {
                $len = self::strlen($part, ['trimWidth' => false] + $options);
                if ($totalOffset + $len <= $start) {
                    $totalOffset += $len;
                    continue;
                }
                $offset = $start - $totalOffset;
                $totalOffset = $start;
            }
            $len = self::strlen($part, $options);
            if ($offset !== 0 || $totalLength + $len > $length) {
                if (
                    strpos($part, '&') === 0 && preg_match($pattern, $part)
                    && $part !== html_entity_decode($part, ENT_HTML5 | ENT_QUOTES, 'UTF-8')
                ) {
                    // Entities cannot be passed substr.
                    continue;
                }
                $part = $substr($part, $offset, $length - $totalLength);
                $len = self::strlen($part, $options);
            }
            $result .= $part;
            $totalLength += $len;
            if ($totalLength >= $length) {
                break;
            }
        }
        return $result;
    }

    /**
     * Removes the last word from the input text.
     *
     * @param string $text The input text
     * @return string
     */
    private static function removeLastWord(string $text): string
    {
        $spacepos = mb_strrpos($text, ' ');
        if ($spacepos !== false) {
            $lastWord = mb_strrpos($text, $spacepos);
            // Some languages are written without word separation.
            // We recognize a string as a word if it doesn't contain any full-width characters.
            if (mb_strwidth($lastWord) === mb_strlen($lastWord)) {
                $text = mb_substr($text, 0, $spacepos);
            }
            return $text;
        }
        return '';
    }
}
