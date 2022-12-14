## Install

Via Composer

``` bash
$ composer require layershifter/tld-extract
```
## Usage

```php
$extract = new Extract();

# For domain 'gist.github.com'

$result = $extract->parse('shop.github.com');
$result->getFullHost(); // will return (string) 'shop.github.com'
$result->getRegistrableDomain(); // will return (string) 'github.com'
$result->isValidDomain(); // will return (bool) true
$result->isIp(); // will return (bool) false

# For IP '127.0.0.1'

$result = $extract->parse('192.168.0.1');
$result->getFullHost(); // will return (string) '192.168.0.1'
$result->getRegistrableDomain(); // will return null
$result->isValidDomain(); // will return (bool) false
$result->isIp(); // will return (bool) true
```
