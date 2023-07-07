# Effectra\Cache

Effectra\Cache is a package that provides cache implementations based on the PSR-16 (Simple Cache) standard. It includes cache classes that can be used to store and retrieve data in various cache storage systems.

## Features

- PSR-16 compatibility: Implements the `Psr\SimpleCache\CacheInterface`.
- Easy integration: Provides cache implementations for different cache storage systems.
- Flexible configuration: Supports different cache configurations based on the chosen storage system.
- Clean and simple API: Offers a straightforward API for caching data.

## Installation

You can install the package via Composer. Run the following command in your project directory:

```bash
composer require effectra/cache
```

## Usage

The package includes various cache classes, each representing a different cache storage system. You can use the desired cache class based on your requirements. Here's an example of using the `JsonCache` class:

```php
use Effectra\Cache\Psr16\JsonCache;

// Create an instance of the JsonCache class with the cache directory
$cache = new JsonCache('/path/to/cache/directory');

// Store a value in the cache with a key and optional time-to-live (TTL)
$cache->set('key', 'value', 3600); // Cache value with a TTL of 1 hour

// Retrieve a value from the cache
$result = $cache->get('key');

// Delete a value from the cache
$cache->delete('key');

// Clear the entire cache
$cache->clear();
```

## Available Cache Classes

- `FileCache`: Stores cache data in individual files on the file system.
- `JsonCache`: Stores cache data in JSON files on the file system.
- `RedisCache`: Stores cache data in a Redis server.

Please refer to the respective class documentation and the PSR-16 specification for more details on the methods and usage.

## Requirements

- PHP >= 7.2
- Composer (for installation)

## Contributing

Contributions are welcome! If you find any issues or have suggestions for improvements, please open an issue or submit a pull request on the GitHub repository.

## License

This package is open source and available under the [MIT License](https://opensource.org/licenses/MIT).
