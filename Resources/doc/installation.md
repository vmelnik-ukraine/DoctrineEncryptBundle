#Installation

1. Download TDMDoctrineEncryptBundle using composer
2. Enable the Bundle

### Step 1: Download TDMDoctrineEncryptBundle using composer

Add TDMDoctrineEncryptBundle in your composer.json:

```js
{
    "require": {
        "tdm/doctrine-encrypt-bundle": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update tdm/doctrine-encrypt-bundle
```

Composer will install the bundle to your project's `vendor/tdm` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new TDM\DoctrineEncryptBundle\TDMDoctrineEncryptBundle(),
    );
}
```