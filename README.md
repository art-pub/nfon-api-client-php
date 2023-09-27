# NFON administration portal REST API PHP-client

This is a small PHP package to send requests against the NFON administration portal REST API.

### Requirements


You need to have
1. the main URL to the API (i.e. `https://api09.nfon.com`)
2. your API public and (i.e. `NFON-ABCDEF-123456`)
3. your API secret and (i.e. `gAp3bTxxUev5JkxOcBdeC5Absm7J84jp6mEhJZd3XiLdjzoGSF`)
4. your account name (i.e. `K1234`)

> [!NOTE]
> If you do not have this information, please contact your NFON partner for assistance.

> [!WARNING]
> **Never share your `API secret`!**

## Usage

### Install the package
The easiest way for installation is to use [composer](https://getcomposer.org/) to use this package:
```bash
bash$ composer.phar require art-pub/nfon-api-client-php
```

### Create a request
```php
    ...
    use NFONAPIClient\client;
    ...
    $apiBasePath = "https://api09.nfon.com"; // <-- insert the API base path provided by NFON
    $apiKey      = "NFON-KEY"; // <-- insert the API key provided by NFON
    $apiSecret   = "NFON-SECRET"; // <-- insert the API secret here
    $account     = "NFON-ACCOUNT"; // <-- insert the account name here
    $path = "/api/customers/" . $account . "/phone-books";
    $method = "GET";
    $body = "";
    $contentType = "application/json";

    // calculate the signature
    [$requestDate, $requestContentType, $requestContentMD5, $signature] = client::getAuthentication($path, $apiSecret, $method, $body, $contentType);

    // make the request
    [$response, $success] = client::request(
            apiBasePath: $apiBasePath,
            apiKey: $apiKey,
            signature: $signature,
            method: $method,
            path: $path,
            apiDate: $requestDate,
            contentMD5: $requestContentMD5,
            body: $body,
            contentLength: strlen($body),
            contentType: $requestContentType,
            apiHeaders: [], // <-- you can add additional request headers here
        );
    if ($success) {
        print_r($response);
        print PHP_EOL;
    } else {
        print "ERROR: $response".PHP_EOL;
    }
    ...
```

The result is JSON-like, but difficult to read or process. The `$response` will therefore have a key `dataMap` having the result in a more useful structure, holding the original data still in `$response['data']` (for single results) or `$response['items']` (for multiple results).

Original data:
```
...
[items] => Array
        (
            [0] => Array
                (
                    [href] => /api/customers/K1234/phone-books/1234
                    [links] => Array
                        (
                        )

                    [data] => Array
                        (
                            [0] => Array
                                (
                                    [name] => phonebookEntryId
                                    [value] => 1234
                                )

                            [1] => Array
                                (
                                    [name] => displayName
                                    [value] => Some User
                                )

                            [2] => Array
                                (
                                    [name] => displayNumber
                                    [value] => +49 (4711) 8015
                                )

                            [3] => Array
                                (
                                    [name] => restricted
                                    [value] => 
                                )

                        )

                )
...
```

DataMap:
```
...
    [dataMap] => Array
        (
            [0] => Array
                (
                    [phonebookEntryId] => 1234
                    [displayName] => Some User
                    [displayNumber] => +49 (4711) 0815
                    [restricted] => 
                )
...
```


### Good to know

#### Datasets and Pagination

Endpoints that return more than one record will return a maximum of 100 records on the first request. The result contains the following information:

Href: Path of the current request

Total: Amount of all datasets (not pages!)

Offset: Offset starting with 0

Size: Amount of maximum results in the response. You can set the amount in the request with the parameter `pageSize=XXX` with `XXX` being max. 100.

Links: Array of links including the first, the next and the last URL to retrieve all data. See `LinksMap["first"]`, `LinksMap["last"]` and `LinksMap["next"]` in the example above.

> [!IMPORTANT]
> **Please note:** You have to iterate through all those links to retrieve all data. Just repeat with the `next` given `Href` until your current `Href` (path of the current request) matches the `last` entry.

> [!IMPORTANT]
> If the `last` entry is empty, you already have all data in the current response.

## Links

* [Latest NFON API Documentation (zip)](https://cdn.nfon.com/API_Documentation.zip)
* [go client for NFON API](https://github.com/art-pub/nfon-api-client)