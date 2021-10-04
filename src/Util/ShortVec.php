<?php

namespace Tighten\SolanaPhpSdk\Util;

class ShortVec
{
    /**
     * @param array $bytes
     * @return array list($length, $size)
     */
    public static function decodeLength(array $bytes): array
    {
        $len = 0;
        $size = 0;
        while ($size < sizeof($bytes)) {
            $elem = $bytes[$size];
            $len |= ($elem & 0x7F) << ($size * 7);
            $size++;
            if (($elem & 0x80) == 0) {
                break;
            }
        }
        return [$len, $size];
    }

    public static function encodeLength(int $length): array
    {
        $elems = [];
        $rem_len = $length;

        for (;;) {
            $elem = $rem_len & 0x7f;
            $rem_len >>= 7;
            if (! $rem_len) {
                array_push($elems, $elem);
                break;
            }
            $elem |= 0x80;
            array_push($elems, $elem);
        }

        return $elems;
    }
}
