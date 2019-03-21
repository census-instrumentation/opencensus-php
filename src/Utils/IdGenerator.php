<?php declare(strict_types=1);

namespace OpenCensus\Utils;

class IdGenerator
{
    /**
     * Generates a random hex string
     *
     * In case where there is not enough entropy for random_bytes(), the generation will use "dumber" method.
     *
     * @param int $length of bytes
     * @return string
     */
    public static function hex(int $length): string
    {
        try {
            return bin2hex(random_bytes($length));
        }catch (\Throwable $ex) {
            return substr(str_shuffle(str_repeat('0123456789abcdef', mt_rand(1,10))),1,$length);
        }
    }
}
