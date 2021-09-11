# Solana PHP SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tightenco/solana-php-sdk.svg?style=flat-square)](https://packagist.org/packages/tightenco/solana-php-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/tighten/solana-php-sdk/run-tests?label=tests)](https://github.com/tighten/solana-php-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)


Simple PHP SDK for Solana.

## Installation

You can install the package via composer:

```bash
composer require tightenco/solana-php-sdk
```

## Usage

### Using the Solana simple client

You can use the `Solana` class for convenient access to API methods. Some are defined in the code:

```php
use Tighten\SolanaPhpSdk\Solana;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

// Using a defined method
$sdk = new Solana(new SolanaRpcClient(SolanaRpcClient::MAINNET_ENDPOINT));
$accountInfo = $sdk->getAccountInfo('4fYNw3dojWmQ4dXtSGE9epjRGy9pFSx62YypT7avPYvA');
var_dump($accountInfo);
```

Anything not defined in the code, you can call yourself, with only a few modifications to your code:

```php
use Tighten\SolanaPhpSdk\Solana;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

// Using a not-defined method using the __call passthrough
$response = $sdk->whateverMethodYouWantHere([$param1, $param2]);
```

For all the possible methods, see the [API documentation](https://docs.solana.com/developing/clients/jsonrpc-api).

### Directly using the RPC client

The `Solana` class is just a light convenience layer on top of the RPC client. You can, if you want, use the client directly, which allows you to work with the full `Response` object:

```php
use Tighten\SolanaPhpSdk\SolanaRpcClient;

$client = new SolanaRpcClient(SolanaRpcClient::MAINNET_ENDPOINT);
$accountInfoResponse = $client->call('getAccountInfo', ['4fYNw3dojWmQ4dXtSGE9epjRGy9pFSx62YypT7avPYvA']);
$accountInfoBody = $accountInfoResponse->json();
$accountInfoStatusCode = $accountInfoResponse->getStatusCode();
``````

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email hello@tighten.co instead of using the issue tracker.

## Credits

- [Matt Stauffer](https://github.com/mattstauffer)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
