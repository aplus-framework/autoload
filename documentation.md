# Autoload Library *documentation*

The Autoloader class loads classes through its absolute path or the namespaces.

```php
use Framework\Autoload\Autoloader;

$autoloader = new Autoloader();
$autoloader->setClass('App', 'path/to/App.php'); // PSR-0
$autoloader->setNamespace('App\Controllers', __DIR__ . '/app/Controllers'); // PSR-4
```

## Locator

```php
use Framework\Autoload\Locator;

$locator = new Locator($autoloader);
$locator->getClassName('path/to/App.php'); // App
```

Search for all files named `config.php` in all namespaces:

```php
$locator->findFiles('config', '.php');
```

Get all files within subdirectory in all namespaces:

```php
$locator->getFiles('Controllers');
```

Get file path through namespaces:

```php
$locator->getNamespacedFilepath('App/Foo/Bar');
```

List all files within a directory:

```php
$locator->listFiles(__DIR__);
```
