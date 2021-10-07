<?php

namespace Tighten\SolanaPhpSdk\Utils;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PublicKey
{
    public function findProgramAddressViaJavascript(string $mintKey)
    {
        $process = new Process([
            (new ExecutableFinder())->find('node'),
            'findProgramAddress.js',
            $mintKey,
        ], realpath(__DIR__ . '/../../js-src'), null, null, null);

        $process->run();

        if (! $process->isSuccessful()) {
            dd($process->getErrorOutput());
        }

        return trim($process->getOutput());
    }
}
