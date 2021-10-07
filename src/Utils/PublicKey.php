<?php

namespace Tighten\SolanaPhpSdk\Utils;

use Exception;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PublicKey
{
    public function findProgramAddressViaJavascript(string $mintKey)
    {
        $node = (new ExecutableFinder())->find('node');

        if (! $node) {
            throw new Exception(sprintf('Sorry, cannot find Node in your PATH:\n%s', exec('echo $PATH')));
        }

        $process = new Process([
            $node,
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
