# A minimal implementation of ShareFile Api
This is a minimal PHP implementation of the [ShareFile API](https://api.sharefile.com). I am open to PRs that add extra methods to the client. 

Here are a few examples on how you can use the package:
```php
// Connect to ShareFile
$client = new Client('hostname', 'client_id', 'secret', 'username', 'password');

// Create a folder
$newFolder = $client->createFolder($parentId, $name, $description);

// Upload a file in that folder 
$client->uploadFileStandard($filename, $newFolder['Id']);

// Get a thumbnail of a file
$client->getThumbnailUrl($itemId);
```

## Installation
You can install the package via composer:
``` bash
composer require kapersoft/sharefile-api
```

## Usage
The first thing you need to do is get an OAuth2 key. Go to the [Get an API key](https://api.sharefile.com/rest/oauth2-request.aspx) section on the [ShareFile API site](https://api.sharefile.com/).

With an OAuth2 key you can instantiate a `Kapersoft\Sharefile\Client`:
```php
$client = new Client('hostname', 'client_id', 'secret', 'username', 'password');
```

Look in [the source code of `Kapersoft\ShareFile\Client`](https://github.com/kapersoft/sharefile-api/blob/master/src/Client.php) to discover the methods you can use. Examples can be found in  [the source code of `Kapersoft\ShareFile\Test\TestShareFileApi`](https://github.com/kapersoft/sharefile-api/blob/master/tests/TestShareFileApi.php).

## Security
If you discover any security related issues, please email kapersoft@gmail.com instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
