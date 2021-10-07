<?php

namespace Tighten\SolanaPhpSdk\Utils;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PublicKey
{
    public function findProgramAddressViaJavascript(string $mintKey)
    {
        $process = new Process([
            // (new ExecutableFinder())->find('node'), // @todo solve why not working!
            '/Users/mattstauffer/.nvm/versions/node/v15.4.0/bin/node',
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
