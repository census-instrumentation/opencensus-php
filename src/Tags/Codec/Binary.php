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

namespace OpenCensus\Tags\Codec;

use OpenCensus\Tags\Tag;
use OpenCensus\Tags\TagKey;
use OpenCensus\Tags\TagValue;
use OpenCensus\Tags\TagContext;

class Binary {
    use \OpenCensus\Utils\VarintTrait;

    /** Binary Encoding Version number */
    private const TAGS_VERSION_ID = "\x00";

    /** KeyTypes (currently only string is supported) */
    private const KEYTYPE_STRING = "\x00";
    private const KEYTYPE_INT64  = "\x01";
    private const KEYTYPE_TRUE   = "\x02";
    private const KEYTYPE_FALSE  = "\x03";

    /**
     * Encode the provided TagContext to our binary wire format.
     *
     * @param TagContext $tagContext the object holding the Tag's to encode.
     * @return string the bytestring holding our binary encoded Tag's.
     */
    public static function encode(TagContext $tagContext): string
    {
        $str = self::TAGS_VERSION_ID;
        /** @var Tag $tag */
        foreach ($tagContext->tags() as $tag) {
            $str .= self::KEYTYPE_STRING;
            $key = $tag->getKey()->getName();
            $buf = '';
            self::encodeUnsigned($buf, strlen($key));
            $str .= $buf . $key;
            $value = $tag->getValue()->getValue();
            $buf = '';
            self::encodeUnsigned($buf, strlen($value));
            $str .= $buf . $value;
        }
        return $str;
    }

    public static function decode(string $str, \Exception &$err = null): TagContext
    {
        $tagContext = new TagContext();
        $strLen = strlen($str);
        if ($strLen == 0) {
            return $tagContext;
        }
        if ($str[0] > self::TAGS_VERSION_ID) {
            $err = new \Exception("cannot decode: unsupported version: " . ord($str[0]) .
                "; supports only up to: " . ord(self::TAGS_VERSION_ID));
            return $tagContext;
        };

        $idx = 1;

        $tagsLen = 0;
        while ($idx < $strLen) {
            // read keytype
            if ($str[$idx] != self::KEYTYPE_STRING) {
                $err = new \Exception("cannot decode: invalid key type: " . ord($str[$idx]));
                return new TagContext();
            }
            $idx++;

            if ($idx >= $strLen) {
                $err = new \Exception("unexpected end: index: " . $idx . " length: " . $strLen);
            }

            // read varint (key length)
            $keyLen = 0;
            $cnt = self::decodeUnsigned(substr($str, $idx), $keyLen);
            if ($cnt <= 0 || $keyLen == 0) {
                $err = new \Exception("cannot decode: key length varint");
                return new TagContext();
            }
            $idx += $cnt;

            // read key payload of size $keylen
            $valueEnd = $idx + $keyLen;
            if ($valueEnd > $strLen) {
                $err = new \Exception("malformed encoding: length: $keyLen, upper: $valueEnd, maxLength: $strLen");
                return new TagContext();
            }
            try {
                $key = TagKey::create(substr($str, $idx, $keyLen));
            } catch (\Exception $e) {
                $err = $e;
                return TagContext();
            }
            $idx = $valueEnd;

            // read varint (value length)
            $valLen = 0;
            $cnt = self::decodeUnsigned(substr($str, $idx), $valLen);
            if ($cnt <= 0 || $keyLen == 0) {
                $err = new \Exception("cannot decode: value length varint");
                return new TagContext();
            }

            // Total size of all Tag key + value strings excluding serialization
            // details should not be greater than TagContext::MAX_LENGTH
            // Since TagContext extraction is an all or nothing operation, we
            // fail the decode step if overstepping TagContext::MAX_LENGTH.
            $tagsLen += $keyLen + $valLen;
            if ($tagsLen > TagContext::MAX_LENGTH) {
                $err = new \Exception(TagContext::EX_INVALID_CONTEXT);
                return new TagContext();
            }

            $idx += $cnt;

            // read value payload of size $valLen
            $valueEnd = $idx + $valLen;
            if ($valueEnd > $strLen) {
                $err = new \Exception("malformed encoding: length: $valLen, upper: $valueEnd, maxLength: $strLen");
                return new TagContext();
            }
            try {
                $value = TagValue::create(substr($str, $idx, $valLen));
            } catch (\Exception $e) {
                $err = $e;
                return new TagContext();
            }
            $idx = $valueEnd;

            $tagContext->upsert($key, $value);
        }


        return $tagContext;
    }
}
