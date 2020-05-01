# Borsch - Application

Borsch Framework application wrapper.

This package is part of the Borsch Framework.

## Installation

Via [composer](https://getcomposer.org/) :

`composer require borsch/application`

## Usage

```php
require_once __DIR__.'/vendor/autoload.php';

use Borsch\Application\App;
use Laminas\Diactoros\ServerRequestFactory;

$app = new App();

$pipeline = (require_once __DIR__.'/config/pipeline.php');
$routes = (require_once __DIR__.'/config/routes.php');

$pipeline($app);
$routes($app);

$app->run(ServerRequestFactory::fromGlobals());
```

## License

The package is licensed under the MIT license. See [License File](https://github.com/debuss/borsch-application/blob/master/LICENSE.md) for more information.