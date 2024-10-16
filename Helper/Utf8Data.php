<?php
/**
 * Sofinco Epayment module for Magento
 *
 * Feel free to contact Sofinco at support@paybox.com for any
 * question.
 *
 * LICENSE: This source file is subject to the version 3.0 of the Open
 * Software License (OSL-3.0) that is available through the world-wide-web
 * at the following URI: http://opensource.org/licenses/OSL-3.0. If
 * you did not receive a copy of the OSL-3.0 license and are unable
 * to obtain it through the web, please send a note to
 * support@paybox.com so we can mail you a copy immediately.
 *
 * @version   1.0.7-psr
 * @author    BM Services <contact@bm-services.com>
 * @copyright 2012-2017 Sofinco
 * @license   http://opensource.org/licenses/OSL-3.0
 * @link      http://www.paybox.com/
 */

namespace Sofinco\Epayment\Helper;

/**
 * From symfony/polyfill-php72
 *
 * @author Nicolas Grekas <p@tchwork.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class Utf8Data
{
    public static function encode($s)
    {
        $s .= $s;
        $len = \strlen($s);

        for ($i = $len >> 1, $j = 0; $i < $len; ++$i, ++$j) {
            switch (true) {
                case $s[$i] < "\x80":
                    $s[$j] = $s[$i];
                    break;
                case $s[$i] < "\xC0":
                    $s[$j] = "\xC2";
                    $s[++$j] = $s[$i];
                    break;
                default:
                    $s[$j] = "\xC3";
                    $s[++$j] = \chr(\ord($s[$i]) - 64);
                    break;
            }
        }

        return \substr($s, 0, $j);
    }

    public static function decode($s)
    {
        $s = (string) $s;
        $len = \strlen($s);

        for ($i = 0, $j = 0; $i < $len; ++$i, ++$j) {
            switch ($s[$i] & "\xF0") {
                case "\xC0":
                case "\xD0":
                    $c = (\ord($s[$i] & "\x1F") << 6) | \ord($s[++$i] & "\x3F");
                    $s[$j] = $c < 256 ? \chr($c) : '?';
                    break;

                case "\xF0":
                    ++$i;
                    // no break

                case "\xE0":
                    $s[$j] = '?';
                    $i += 2;
                    break;

                default:
                    $s[$j] = $s[$i];
            }
        }

        return \substr($s, 0, $j);
    }
}
