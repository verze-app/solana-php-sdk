<?php

namespace Tighten\SolanaPhpSdk\Util;

class ShortVec
{
    public static function decodeLength(array $bytes): int
    {
        $len = 0;
        $size = 0;
        for (;;) {
            $elem = array_shift($bytes);
            $len |= ($elem & 0x7f) << ($size * 7);
            $size++;
            if (($elem & 0x80) === 0) {
                break;
            }
        }
        return $len;
    }

    public static function encodeLength(array $bytes, int $len)
    {
        $rem_len = $len;
        for (;;) {
            $elem = $rem_len & 0x7f;
            $rem_len >>= 7;
            if ($rem_len == 0) {
                array_push($bytes, $elem);
                break;
            } else {
                $elem |= 0x80;
                array_push($bytes, $elem);
            }
        }
    }
}
