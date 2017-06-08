FazlandElasticaBundle Usage
=======================

Basic Searching
---------------

> This example assumes you have defined an index `app` and a type `user` in your `config.yml`.

```php
$type = $this->container->get('fazland_elastica.index.app.user');

// Returns all users who have example.net in any of their mapped fields
$resultSet = $type->search('example.net');

// Get transformed results (aka doctrine entities)
$results = $resultSet->getTransformed();

// Get total hits
$count = $resultSet->getTotalHits();
```

Aggregations
------------

When searching with aggregations, they can be retrieved when using the paginated
methods on the finder.

```php
$query = new \Elastica\Query();
$agg = new \Elastica\Aggregation\Terms('tags');
$agg->setField('companyGroup');
$query->addAggregation($agg);

$resultSet = $type->search($query);
$aggs = $resultSet->getAggregations();
```

Searching the entire index
--------------------------

You can also define a finder that will work on the entire index. Adjust your index
configuration as per below:

```yaml
fazland_elastica:
    indexes:
        app:
            finder: ~
```

You can now use the index wide finder service `fazland_elastica.finder.app`:

```php
/** var Fazland\ElasticaBundle\Finder\MappedFinder */
$finder = $this->container->get('fazland_elastica.finder.app');

// Returns a mixed array of any objects mapped
$results = $finder->find('bob');
```

Type Repositories
-----------------

In the case where you need many different methods for different searching terms, it
may be better to separate methods for each type into their own dedicated repository
classes, just like Doctrine ORM's EntityRepository classes.

An example for using a repository:

```php
/** var Fazland\ElasticaBundle\Manager\RepositoryManager */
$repositoryManager = $this->container->get('fazland_elastica.manager');

/** var Fazland\ElasticaBundle\Repository */
$repository = $repositoryManager->getRepository('index_one/user_type');

/** var array of Acme\UserBundle\Entity\User */
$users = $repository->find('bob');
```

For more information about customising repositories, see the cookbook entry
[Custom Repositories](cookbook/custom-repositories.md).

Using a custom fetcher for transforming results
------------------------------------------------------------

When returning results from ElasticSearch to be transformed by the bundle, the default
`ObjectFetcher` object will be called. This object will subsequently hydrate the object using
the `ObjectManager::find` method, passing the identifier to it. In many
circumstances this is not ideal and you'd prefer to use a different method to join in
any entity relations that are required on the page that will be displaying the results.

```yaml
            user:
                persistence:
                    fetcher: app.my_fetcher
```

An example for using a custom query builder method:

```php
class MyFetcher implements ObjectFetcherInterface
{
    /**
     * Returns a SORTED list of object given the identifiers.
     * The keys MUST be the object identifier as stored in Elastic document.
     *
     * @param array ...$identifiers
     *
     * @return iterable|object[]
     */
    public function find(...$identifiers) {
        $res = $this->createCustomQueryBuilder()
            ->getQuery()->getResults();
 
        foreach ($res as $object) {
            yield $object->getId() => $object;
        }
    }
}
```

Advanced Searching Example
--------------------------

If you would like to perform more advanced queries, here is one example using
the snowball stemming algorithm.

It searches for Article entities using `title`, `tags`, and `categoryIds`.
Results must match at least one specified `categoryIds`, and should match the
`title` or `tags` criteria. Additionally, we define a snowball analyzer to
apply to queries against the `title` field.

Assuming a type is configured as follows:

```yaml
fazland_elastica:
    indexes:
        app:
            settings:
                index:
                    analysis:
                        analyzer:
                            my_analyzer:
                                type: snowball
                                language: English
            types:
                article:
                    properties:
                        title: { boost: 10, analyzer: my_analyzer }
                        tags:
                        categoryIds:
                    persistence:
                        driver: orm
                        model: Acme\DemoBundle\Entity\Article
                        provider: ~
                        finder: ~
```

The following code will execute a search against the Elasticsearch server:

```php
$finder = $this->container->get('fazland_elastica.finder.app.article');
$boolQuery = new \Elastica\Query\BoolQuery();

$fieldQuery = new \Elastica\Query\Match();
$fieldQuery->setFieldQuery('title', 'I am a title string');
$fieldQuery->setFieldParam('title', 'analyzer', 'my_analyzer');
$boolQuery->addShould($fieldQuery);

$tagsQuery = new \Elastica\Query\Terms();
$tagsQuery->setTerms('tags', array('tag1', 'tag2'));
$boolQuery->addShould($tagsQuery);

$categoryQuery = new \Elastica\Query\Terms();
$categoryQuery->setTerms('categoryIds', array('1', '2', '3'));
$boolQuery->addMust($categoryQuery);

$resultSet = $type->search($boolQuery);
```
