[![Latest Version on Packagist](https://img.shields.io/packagist/v/kapersoft/sharefile-api.svg?style=flat-square)](https://packagist.org/packages/kapersoft/sharefile-api)
[![Build Status](https://img.shields.io/travis/kapersoft/sharefile-api/master.svg?style=flat-square)](https://travis-ci.org/kapersoft/sharefile-api)
[![StyleCI](https://styleci.io/repos/101933034/shield?branch=master)](https://styleci.io/repos/101933034)
[![Quality Score](https://img.shields.io/scrutinizer/g/kapersoft/sharefile-api.svg?style=flat-square)](https://scrutinizer-ci.com/g/kapersoft/sharefile-api)
[![Total Downloads](https://img.shields.io/packagist/dt/kapersoft/sharefile-api.svg?style=flat-square)](https://packagist.org/packages/kapersoft/sharefile-api)

# A minimal implementation of the ShareFile Api
This is a minimal PHP implementation of the [ShareFile API](https://api.sharefile.com). It contains only the methods needed for my [flysystem-sharefile adapter](https://github.com/kapersoft/flysystem-sharefile). I am open to PRs that add extra methods to the client. 

Here are a few examples on how you can use the package:
```php
// Connect to ShareFile
$client = new Client('hostname', 'client_id', 'secret', 'username', 'password');

// Create a folder
$newFolder = $client->createFolder($parentId, $name, $description);

// Upload a file in that folder 
$client->uploadFileStandard($filename, $newFolder['Id']);

// Get details for a file using filepath
$picture = $client->getItemByPath('/Personal Folders/Pictures/Picture.jpg');

// Get a thumbnail of a file
$client->getThumbnailUrl($picture['Id']);
```

## Installation
You can install the package via composer:
``` bash
composer require kapersoft/sharefile-api
```

## Usage
The first thing you need to do is get an OAuth2 key. Go to the [Get an API key](https://api.sharefile.com/rest/oauth2-request.aspx) section on the [ShareFile API site](https://api.sharefile.com/) to get this key.

With an OAuth2 key you can instantiate a `Kapersoft\Sharefile\Client`:
```php
$client = new Client('hostname', 'client_id', 'secret', 'username', 'password');
```

Look in [the source code of `Kapersoft\ShareFile\Client`](https://github.com/kapersoft/sharefile-api/blob/master/src/Client.php) to discover the methods you can use. More examples can be found in [the source code of `Kapersoft\ShareFile\Test\TestShareFileApi`](https://github.com/kapersoft/sharefile-api/blob/master/tests/TestShareFileApi.php).

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing
In the `/tests`-folder are two tests defined:
- `TestClient.php` tests the `Kapersoft\Sharefile\Client`-class using mock Guzzle objects;
- `TestShareFileApi.php` tests the `Kapersoft\Sharefile\Client`-class using the live ShareFile API. To use this test fill in your ShareFile credentials under section `<PHP>` of the `phpunit.xml.dist`-file in the project root folder. Some tests need additional parameters to run. These parameters can be found in the first lines of the test.
 
## Security
If you discover any security related issues, please email kapersoft@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](LICENSE.txt) for more information.
