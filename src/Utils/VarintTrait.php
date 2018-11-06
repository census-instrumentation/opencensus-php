<?php
/**
 * Copyright 2018 OpenCensus Authors
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenCensus\Utils;

/**
 * Varint encoding and decoding methods inspired by Go encoding/binary package.
 * @see <a href="https://golang.org/src/encoding/binary/varint.go">encoding/binary/varint</a>
 */
trait VarintTrait
{
    /**
     * Varint encode unsigned integer.
     *
     * @param string $buf bytestring to hold varint encoding of $x.
     * @param int $x the unsigned integer to varint encode.
     * @return int number of bytes written to buf.
     */
    public static function encodeUnsigned(string &$buf, int $x): int
    {
        $offset = $i = strlen($buf);
        while ($x >= 0x80) {
            $buf[$i++] = chr($x | 0x80);
            $x >>= 7;
        }
        $buf[$i++] = chr($x);
        return $i - $offset;
    }

    /**
     * Varint encode signed integer.
     *
     * @param string $buf bytestring to hold varint encoding of $x.
     * @param int $x the signed integer to varint encode.
     * @return int number of bytes written to buf.
     */
    public static function encodeSigned(string &$buf, int $x): int
    {
        $ux = $x << 1;
        if ($x < 0) {
            $ux = ~$ux;
        }
        return self::encodeUnsigned($buf, $ux);
    }

    /**
     * Varint decode to unsigned integer.
     *
     * @param string $buf bytestring holding varint encoding.
     * @param int $x integer to receive the decoded value.
     * @return int number of bytes read from buf.
     */
    public static function decodeUnsigned(string $buf, int &$x): int
    {
        $x = 0;
        $s = 0;
        for ($i = 0; $i < strlen($buf); $i++) {
            $b = ord($buf[$i]);
            if ($b < 0x80) {
                if ($i > 9 || $i == 9 && $b > 1) {
                    // overflow
                    $x = 0;
                    return -($i + 1);
                }
                $x = $x | $b << $s;
                return $i + 1;
            }
            $x |= ($b & 0x7f) << $s;
            $s += 7;
        }
        $x = 0;
        return 0;
    }

    /**
     * Varint decode to signed integer.
     *
     * @param string $buf bytestring holding varint encoding.
     * @param int $x integer to receive the decoded value.
     * @return int number of bytes read from buf.
     */
    public static function decodeSigned(string $buf, int &$x): int
    {
        $ux = 0;
        $cnt = self::decodeUnsigned($buf, $ux);
        $x = $ux >> 1;
        if ($ux&1 != 0) {
            $x = ~$x;
        }
        return $cnt;
    }
}
