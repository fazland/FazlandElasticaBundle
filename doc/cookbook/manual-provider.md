Manual provider
===============

Create a service with the tag "fazland_elastica.provider" and attributes for the
index and type for which the service will provide.

```yaml
# app/config/config.yml
services:
    acme.search_provider.user:
        class: Acme\UserBundle\Provider\UserProvider
        arguments:
            - '@fazland_elastica.index.app.user'
        tags:
            - { name: fazland_elastica.provider, index: app, type: user }
```

Its class must implement `Fazland\ElasticaBundle\Provider\ProviderInterface`.

```php

namespace Acme\UserBundle\Provider;

use Fazland\ElasticaBundle\Provider\ProviderInterface;

class UserProvider implements ProviderInterface
{
    /**
     * Provides objects for index/type population.
     *
     * @param int $offset
     * @param int $size
     *
     * @return iterable
     */
    public function provide(int $offset = null, int $size = null);
    {
        yield [
            'username' => 'Bob',
        ];
    }
    
    public function clear()
    {
        // Do cleanup tasks
    }
}
```

You will find a more complete implementation example in `src/Fazland/ElasticaBundle/Doctrine/AbstractProvider.php`.
