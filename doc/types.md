Type configuration
==================

Custom Property Paths
---------------------

Since FazlandElasticaBundle 3.1.0, it is now possible to define custom property paths
to be used for data retrieval from the underlying model.

```yaml
    user:
        properties:
            username:
                property_path: indexableUsername
            firstName:
                property_path: names[first]
```

This feature uses the Symfony PropertyAccessor component and supports all features
that the component supports.

The above example would retrieve an indexed field `username` from the property
`User->indexableUsername`, and the indexed field `firstName` would be populated from a
key `first` from an array on `User->names`.

Setting the property path to `false` will disable transformation of that value. In this
case the mapping will be created but no value will be populated while indexing. You can
populate this value by listening to the `POST_TRANSFORM` event emitted by this bundle.
See [cookbook/custom-properties.md](cookbook/custom-properties.md) for more information
about this event.

Handling missing results with FazlandElasticaBundle
-----------------------------------------------

By default, FazlandElasticaBundle will throw an exception if the results returned from
Elasticsearch are different from the results it finds from the chosen persistence
provider. This may pose problems for a large index where updates do not occur instantly
or another process has removed the results from your persistence provider without
updating Elasticsearch.

The error you're likely to see is something like:
'Cannot find corresponding Doctrine objects for all Elastica results.'

To solve this issue, each type can be configured to ignore the missing results:

```yaml
    user:
        persistence:
            elastica_to_model_transformer:
                ignore_missing: true
```

Dynamic templates
-----------------

Dynamic templates allow to define mapping templates that will be
applied when dynamic introduction of fields / objects happens.

[Documentation](https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic-templates.html)

```yaml
fazland_elastica:
    indexes:
        app:
            types:
                user:
                    dynamic_templates:
                        my_template_1:
                            match: apples_*
                            mapping:
                                type: float
                        my_template_2:
                            match: *
                            match_mapping_type: string
                            mapping:
                                type: string
                                index: not_analyzed
                    properties:
                        username: { type: string }
```

Nested objects in FazlandElasticaBundle
-----------------------------------

Note that object can autodetect properties

```yaml
fazland_elastica:
    indexes:
        app:
            types:
                post:
                    properties:
                        date: { boost: 5 }
                        title: { boost: 3 }
                        content: ~
                        comments:
                            type: "nested"
                            properties:
                                date: { boost: 5 }
                                content: ~
                        user:
                            type: "object"
                        approver:
                            type: "object"
                            properties:
                                date: { boost: 5 }
```

Parent fields
-------------

```yaml
fazland_elastica:
    indexes:
        app:
            types:
                comment:
                    properties:
                        date: { boost: 5 }
                        content: ~
                    _parent:
                        type: "post"
                        property: "post"
                        identifier: "id"
```

The parent field declaration has the following values:

 * `type`: The parent type.
 * `property`: The property in the child entity where to look for the parent entity. It may be ignored if is equal to
  the parent type.
 * `identifier`: The property in the parent entity which has the parent identifier. Defaults to `id`.

Note that to create a document with a parent, you need to call `setParent` on the document rather than setting a
_parent field. If you do this wrong, you will see a `RoutingMissingException` as Elasticsearch does not know where
to store a document that should have a parent but does not specify it.

Date format example
-------------------

If you want to specify a [date format](https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-date-format.html):

```yaml
    user:
        properties:
            username: { type: string }
            lastlogin: { type: date, format: basic_date_time }
            birthday: { type: date, format: "yyyy-MM-dd" }
```


Disable dynamic mapping example
-------------------

If you want to specify manually the dynamic capabilities of Elasticsearch mapping, you can use 
the [dynamic](https://www.elastic.co/guide/en/elasticsearch/reference/current/dynamic.html) option:

```yaml
    user:
        dynamic: strict
        properties:
            username: { type: string }
            addresses: { type: object, dynamic: true }
```

With this example, Elasticsearch is going to throw exceptions if you try to index a not mapped field, except in `addresses`.

Custom settings
---------------

Any setting can be specified when declaring a type. For example, to enable a custom
analyzer, you could write:

```yaml
    indexes:
        search:
            settings:
                index:
                    analysis:
                        analyzer:
                            my_analyzer:
                                type: custom
                                tokenizer: lowercase
                                filter   : [my_ngram]
                        filter:
                            my_ngram:
                                type: "nGram"
                                min_gram: 3
                                max_gram: 5
            types:
                blog:
                    properties:
                        title: { boost: 8, analyzer: my_analyzer }
```

Testing if an object should be indexed
--------------------------------------

FazlandElasticaBundle can be configured to automatically index changes made for
different kinds of objects if your persistence backend supports these methods,
but in some cases you might want to run an external service or call a property
on the object to see if it should be indexed.

A property, `indexable_callback` is provided under the type configuration that
lets you configure this behaviour which will apply for any automated watching
for changes and for a repopulation of an index.

In the example below, we're checking the enabled property on the user to only
index enabled users.

```yaml
    types:
        users:
            indexable_callback: 'enabled'
```

The callback option supports multiple approaches:

* A method on the object itself provided as a string. `enabled` will call
  `Object->enabled()`. Note that this does not support chaining methods with dot notation
  like property paths. To achieve something similar use the ExpressionLanguage option
  below.
* An array of a class and a static method to call on that class which will be called with
  the object as the only argument. `[ 'Acme\DemoBundle\IndexableChecker', 'isIndexable' ]`
  will call Acme\DemoBundle\IndexableChecker::isIndexable($object)
* If you have the ExpressionLanguage component installed, A valid ExpressionLanguage
  expression provided as a string. The object being indexed will be supplied as `object`
  in the expression. `object.isEnabled() or object.shouldBeIndexedAnyway()`.
  Services can be inkoved through the `service` expression function (ex: 
  `service('app.checker').isIndexable(object)` will call `isIndexable` method on `app.checker` service).
  For more information on the ExpressionLanguage component and its capabilities see its
  [documentation](http://symfony.com/doc/current/components/expression_language/index.html)

In all cases, the callback should return a true or false, with true indicating it will be
indexed, and a false indicating the object should not be indexed, or should be removed
from the index if we are running an update.

Provider Configuration
----------------------

### Specifying a custom query builder for populating indexes

When populating an index, it may be required to use a different query builder method
to define which entities should be queried.

```yaml
    user:
        persistence:
            provider:
                query_builder_method: createIsActiveQueryBuilder
```

### Changing the document identifier

By default, ElasticaBundle will automatically retrieve the identifier 
fields of your entities and use those as the Elasticsearch document 
identifier. Composite keys will be separated by a space. You can change
this value in the persistence configuration.

```yaml
    user:
        persistence:
            identifier: searchId
```

Listener Configuration
----------------------

### Realtime, selective index update

If you use the Doctrine integration, you can let ElasticaBundle update the indexes automatically
when an object is added, updated or removed. It uses Doctrine lifecycle events.
Declare that you want to update the index in real time:

```yaml
    user:
        persistence:
            driver: orm #the driver can be orm, mongodb, phpcr or propel
            model: Application\UserBundle\Entity\User
            listener: ~ # by default, listens to "insert", "update" and "delete"
```

Now the index is automatically updated each time the state of the bound Doctrine repository changes.
No need to repopulate the whole "user" index when a new `User` is created.

You can also choose to only listen for some of the events:

```yaml
    persistence:
        listener:
            insert: true
            update: false
            delete: true
```

> **Propel** doesn't support this feature yet.
