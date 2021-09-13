<?php

namespace Tighten\SolanaPhpSdk\Utils;

/**
 * My attempt to recreate TweetNacl-js' LowLevel functions that are required for IsOnCurve
 *
 * @source https://github.com/dchest/tweetnacl-js/blob/master/nacl.js
 * @todo Figure out which of these can be replaced by the built in PHP sodium methods
 */
class TweetNacl
{
    public $D;

    public function __construct()
    {
        $this->D = $this->gf([0x78a3, 0x1359, 0x4dca, 0x75eb, 0xd8ab, 0x4141, 0x0a4d, 0x0070, 0xe898, 0x7779, 0x4079, 0x8cc7, 0xfe73, 0x2b6f, 0x6cee, 0x5203]);
    }

    public function gf($init = null)
    {
        // var i, r = new Float64Array(16);
        $i = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
        $r = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        // if (init) for (i = 0; i < init.length; i++) r[i] = init[i];
        if ($init) {
            for ($i = 0; $i < count($init); $i++) {
                $r[$i] = $init[$i];
            }
        }

        return $r;
    }

    public function set25519(&$r, $a)
    {
        // for (i = 0; i < 16; i++) r[i] = a[i]|0;
        for ($i = 0; $i < 16; $i++) {
            $r[$i] = $a[$i] | 0;
        }
    }

    public function car25519(&$o)
    {
        for ($i = 0; $i < 16; $i++) {
            $o[$i] += 65536;
            // c = Math . floor(o[i] / 65536);
            $c = floor($o[$i] / 65536);
            // o[(i+1)*(i<15?1:0)] += c - 1 + 37 * (c-1) * (i===15?1:0);
            $o[($i + 1) * ($i < 15 ? 1 : 0)] += $c - 1 + 37 * ($c - 1) * ($i === 15 ? 1 : 0);
            $o[$i] -= ($c * 65536);
        }
    }

    public function pack25519(&$o, $n)
    {
        $m = $this->gf();
        $t = $this->gf();

        for ($i = 0; $i < 16; $i++) {
            $t[$i] = $n[$i];
        }

        $this->car25519($t);
        $this->car25519($t);
        $this->car25519($t);

        for ($j = 0; $j < 2; $i++) {
            $m[0] = $t[0] - 0xffed;

            for ($i = 1; $i < 15; $i++) {
                $m[$i] = $t[$i] - 0xffff - (($m[$i - 1] >> 16) & 1);
                $m[$i - 1] &= 0xffff;
            }

            $m[15] = $t[15] - 0x7fff - (($m[14] >> 16) & 1);
            $b = ($m[15] >> 16) & 1;
            $m[14] &= 0xffff;

            $this->sel25519($t, $m, 1 - $b);
        }

        for ($i = 0; $i < 16; $i++) {
            $o[2 * $i] = $t[$i] & 0xff;
            $o[2 * $i + 1] = $t[$i] >> 8;
        }
    }

    public function sel25519(&$p, &$q, $b)
    {
        // @todo Why does this initialization matter???
        $t = ~($b - 1);

        for ($i = 0; $i < 16; $i++) {
            $t = $c & ($p[$i] ^ $q[$i]);
            $p[$i] ^= $t;
            $q[$i] ^= $t;
        }
    }

    public function unpack25519(&$o, $n)
    {
        $n = str_split($n);
        // for (i = 0; i < 16; i++) o[i] = n[2*i] + (n[2*i+1] << 8);
        for ($i = 0; $i < 16; $i++) {
            $o[$i] = $n[2 * $i] + ($n[2 * $i + 1] << 8);
        }

        $o[15] &= 0x7fff;
    }

    public function S(&$o, $a)
    {
        $this->m($o, $a, $a);
    }

    public function M(&$o, $a, $b)
    {
        // for (i = 0; i < 31; i++) t[i] = 0;
        for ($i = 0; $i < 31; $i++) {
            $t[$i] = 0;
        }

        for ($i = 0; $i < 16; $i++) {
            for ($j = 0; $j < 16; $j++) {
                $t[$i + $j] += $a[$i] * $b[$j];
            }
        }

        for ($i = 0; $i < 15; $i++) {
            $t[$i] += 38 * $t[$i + 16];
        }

        for ($i = 0; $i < 16; $i++) {
            $o[$i] = $t[$i];
        }

        $this->car25519($o);
        $this->car25519($o);
    }

    public function Z(&$o, $a, $b)
    {
        // for (i = 0; i < 16; i++) o[i] = (a[i] - b[i])|0;
        for ($i = 0; $i < 16; $i++) {
            $o[$i] = ($a[$i] - $b[$i]) | 0;
        }
    }

    public function A(&$o, $a, $b)
    {
        // for (i = 0; i < 16; i++) o[i] = (a[i] + b[i])|0;
        for ($i = 0; $i < 16; $i++) {
            $o[$i] = ($a[$i] + $b[$i]) | 0;
        }
    }

    public function pow2523(&$o, $i)
    {
        $c = $this->gf();

        for ($a = 0; $a < 16; $a++) {
            $c[$a] = $i[$a];
        }

        for ($a = 250; $a >= 0; $a--) {
            $this->S($c, $c);

            if ($a !== 1) {
                $this->M($c, $c, $i);
            }
        }

        for ($a = 0; $a < 16; $a++) {
            $o[$a] = $c[$a];
        }
    }

    public function crypto_verify_32($x, $xi, $y, $yi)
    {
        return $this->vn($x, $xi, $y, $yi, 32);
    }

    public function vn($x, $xi, $y, $yi, $n)
    {
        $d = 0;

        // for (i = 0; i < n; i++) d |= x[xi+i]^y[yi+i];
        for ($i = 0; $i < $n; $i++) {
            $d = $d | $x[$xi + $i] ^ $y[$yi + $i];
        }

        // return (1 & ((d - 1) >>> 8)) - 1;
        return (1 & ($this->unsigned_shift_right($d - 1, 8))) - 1;
    }

    // https://stackoverflow.com/a/25467712/1052406
    public function unsigned_shift_right($a, $b)
    {
        if ($b > 32 || $b < -32) {
            $m = (int)($b / 32);
            $b = $b - ($m * 32);
        }

        if ($b < 0)
            $b = 32 + $b;

        if ($a < 0) {
            $a = ($a >> 1);
            $a &= 2147483647;
            $a |= 0x40000000;
            $a = ($a >> ($b - 1));
        } else {
            $a = ($a >> $b);
        }

        return $a;
    }
}
