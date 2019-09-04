<?php declare(strict_types=1);

namespace OpenCensus\Utils;

class IdGenerator
{
    const CHARSET = '0123456789abcdef';

    /**
     * Generates a random hex string
     *
     * In case where there is not enough entropy for random_bytes(), the generation will use "dumber" method.
     *
     * @param int $length of bytes
     * @return string
     */
    public static function hex(int $length) : string
    {
        if ($length <= 0) {
            return '';
        }

        try {
            return bin2hex(random_bytes($length));
        } catch (\Throwable $ex) {
            return substr(str_shuffle(str_repeat(self::CHARSET, $length)), 1, $length);
        }
    }
}
