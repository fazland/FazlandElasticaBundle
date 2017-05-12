##### Custom Properties

Since FazlandElasticaBundle 3.1.0, we now dispatch an event for each transformation of an
object into an Elastica document which allows you to set custom properties on the Elastica
document for indexing.

Set up an event listener or subscriber for 
`Fazland\ElasticaBundle\Event\TransformEvent::POST_TRANSFORM` to be able to inject your own
parameters.

```php

namespace AcmeBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Fazland\ElasticaBundle\Event\TransformEvent;

class CustomPropertyListener implements EventSubscriberInterface
{
    private $anotherService;
    
    // ...
    
    public function addCustomProperty(TransformEvent $event)
    {
        $document = $event->getDocument();
        $custom = $this->anotherService->calculateCustom($event->getObject());
                    
        $document->set('custom', $custom);
    }
    
    public static function getSubscribedEvents()
    {
        return array(
            TransformEvent::POST_TRANSFORM => 'addCustomProperty',
        );
    }
}
```

Service definition:
```yml
acme.listener.custom_property:
    class: AcmeBundle\EventListener\CustomPropertyListener
    tags:
        - { name: kernel.event_subscriber }
```
