#Installation

1. Download VMelnikDoctrineEncryptBundle using composer
2. Enable the Bundle

### Step 1: Download VMelnikDoctrineEncryptBundle using composer

Add VMelnikDoctrineEncryptBundle in your composer.json:

```js
{
    "require": {
        "vmelnik/doctrine-encrypt-bundle": "dev-master"
    }
}
```

Now tell composer to download the bundle by running the command:

``` bash
$ php composer.phar update vmelnik/doctrine-encrypt-bundle
```

Composer will install the bundle to your project's `vendor/vmelnik` directory.

### Step 2: Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new VMelnik\DoctrineEncryptBundle\VMelnikDoctrineEncryptBundle(),
    );
}
```