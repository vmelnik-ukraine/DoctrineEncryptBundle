#Configuration Reference

All available configuration options are listed below with their default values.

``` yaml
vmelnik_doctrine_encrypt:  
# Secret key for encrypt algorithm. All secret key checks are encryptor tasks only.
    secret_key:           ~ # Required
#  Default and only one encryptor is aes256. If you want to provide your own - set encryptor_class
    encryptor:            aes256 
#  If you want, you can use your own Encryptor. Encryptor must implements EncryptorInterface interface
#  Default: VMelnik\DoctrineEncryptBundle\Encryptors\AES256Encryptor
    encryptor_class:      ~ 
#  ORM and MongoDB are supported using "orm" or "odm" respectively.
    db_driver:            orm 
```