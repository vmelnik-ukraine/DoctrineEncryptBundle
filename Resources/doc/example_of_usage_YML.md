#Example Of Usage with YML

Instead of using annotations you can do the following in your orm.yml mapping file

## Simple example
```php
//AppBundle/Resources/config/doctrine/YourEntity.orm.yml

AppBundle\Entity\YourEntity:
    type: entity
    id:
        id:
            type: integer
            generator:
                strategy: AUTO
    fields:
        yourField:
                type: encrypted
                
```

Now make sure to setup the encrypted type in Doctrine

```php
//app/config/config.yml

doctrine:
    dbal:
        driver:   "%database_driver%"
        host:     "%database_host%"
        port:     "%database_port%"
        dbname:   "%database_name%"
        user:     "%database_user%"
        password: "%database_password%"
        charset:  UTF8
        types:
            encrypted: VMelnik\DoctrineEncryptBundle\Types\EncryptedType
