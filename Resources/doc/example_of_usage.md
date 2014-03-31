#Example Of Usage

Lets imagine that we are storing some private data in our database and we don't want 
to somebody can see it even if he will get raw database on his hands in some dirty way. 
With this bundle this task can be easily made and we even don't see these processes 
because bundle uses some doctrine life cycle events. In database information will 
be encoded. In the same time entities in program will be clear as always and all 
these things will be happen automatically.

## Simple example

For example, we have some user entity with two fields which we want to encode in database.
We must import annotation `@Encrypted` first and then mark fields with it.

###Doctrine Entity

```php
namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

// importing @Encrypted annotation
use VMelnik\DoctrineEncryptBundle\Configuration\Encrypted;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_v")
 */
class UserV {
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;
    
    /**
     * @ORM\Column(type="text", name="total_money")
     * @Encrypted
     * @var int
     */
    private $totalMoney;
    
    /**
     * @ORM\Column(type="string", length=100, name="first_name")
     * @var string
     */
    private $firstName;
    
    /**
     * @ORM\Column(type="string", length=100, name="last_name")
     * @var string
     */
    private $lastName;
    
    /**
     * @ORM\Column(type="text", name="credit_card_number")
     * @Encrypted
     * @var string
     */
    private $creditCardNumber;
    
    //common getters/setters here...

}
```

###Fixtures

```php

namespace Acme\DemoBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Acme\DemoBundle\Entity\UserV;

class LoadUserData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $user = new UserV();
        $user->setFirstName('Victor');
        $user->setLastName('Melnik');
        $user->setTotalMoney(20);
        $user->setCreditCardNumber('1234567890');

        $manager->persist($user);
        $manager->flush();
    }
}
```

###Controller

```php

namespace Acme\DemoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

// these import the "@Route" and "@Template" annotations
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

// our entity
use Acme\DemoBundle\Entity\UserV;

class DemoController extends Controller
{
    /**
     * @Route("/show-user/{id}", name="_vmelnik_decrypt_test", requirements={"id" = "\d+"})
     * @Template
     */
    public function getUserAction(UserV $user) {}
}
```

###Template

```twig
<div>Common info: {{ user.lastName ~  ' ' ~ user.firstName }}</div>
<div>
    Decoded info:
    <dl>
        <dt>Total money<dt>
        <dd>{{ user.totalMoney }}</dd>
        <dt>Credit card<dt>
        <dd>{{ user.creditCardNumber }}</dd>
    </dl>
</div> 
```

When we follow link /show-user/{x}, where x - id of our user in DB, we will see that 
user's information is decoded and in the same time information in database will 
be encoded. In database we'll have something like this:

```
id                  | 1
total_money         | dx+taMIxyUdI3OTlqkjDBKRWP9Qr28PCaCCYxwbjEQU=
first_name          | Victor
last_name           | Melnik
credit_card_number  | 1Y+Yzq6/dDXvtnYHhTyadWfIm6xhGLxuKL2oSuxuzL4=
```

So our information is encoded and all okay.

###Requirements

You need `DoctrineFixturesBundle` and `php-mcrypt` extension for this example
