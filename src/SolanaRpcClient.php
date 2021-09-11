<?php

namespace Tighten\SolanaPhpSdk;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Tighten\SolanaPhpSdk\Exceptions\GenericException;
use Tighten\SolanaPhpSdk\Exceptions\InvalidIdResponseException;
use Tighten\SolanaPhpSdk\Exceptions\MethodNotFoundException;

/**
 * @see https://docs.solana.com/developing/clients/jsonrpc-api
 */
class SolanaRpcClient
{
    public const DEVNET_ENDPOINT = 'https://api.devnet.solana.com';
    public const TESTNET_ENDPOINT = 'https://api.testnet.solana.com';
    public const MAINNET_ENDPOINT = 'https://api.mainnet-beta.solana.com';

    protected $endpoint;
    protected $randomKey;

    public function __construct(string $endpoint)
    {
        $this->endpoint = $endpoint;
        $this->randomKey = random_int(5, 25000);
    }

    public function call(string $method, array $params = []): Response
    {
        $response = Http::acceptJson()->post(
            $this->endpoint,
            $this->buildRpc($method, $params)
        );

        $this->validateResponse($response, $method, $params);

        return $response;
    }

    protected function buildRpc(string $method, array $params): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $this->randomKey,
            'method' => $method,
            'params' => $params,
        ];
    }

    protected function validateResponse(Response $response, string $method, array $params): void
    {
        if ($response['id'] !== $this->randomKey) {
            throw new InvalidIdResponseException();
        }

        if (isset($response['error'])) {
            if ($response['error']['code'] === -32601) {
                throw new MethodNotFoundException("API Error: Method {$method} not found.");
            } else {
                throw new GenericException($response['error']['message']);
            }
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new GenericException('API Error: status code ' . $response->getStatusCode());
        }
    }
}
