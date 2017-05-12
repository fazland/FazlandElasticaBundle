CHANGELOG for 5.x
=================

This changelog references the relevant changes (bug and security fixes) done
in 5.0 versions.

* 5.0.0 (xxxx-xx-xx)

 * Add `ruflin/Elastica` 5.x support.
 * Dropped support to PHP 5.
 * [BC BREAK] removed `default_index` configuration.
 * [BC BREAK] removed `default_manager` configuration.
 * [BC BREAK] removed support for external configuration source (`fazland_elastica.config_source`-tagged services are now ignored).
 * [BC BREAK] Removed `hits`, `hydrate` and `query_builder` options from `elastica_to_model_transformer` configuration. You need to implement a custom `fetcher` instead.
 * [BC BREAK] Removed `multi_field` type.
 * [BC BREAK] Removed `Search` annotations.
 * [BC BREAK] Removed deprecated `servers` configuration key.
 * [BC BREAK] Removed `_boost`, `_timestamp` and `_ttl` fields, not supported by ES 5.
 * [BC BREAK] Removed deprecated `mappings` and `is_indexable_callback` configurations.
 * [BC BREAK] Removed deprecated Doctrine `RepositoryManager`.
 * All the bundle's exception classes are now implementing `Fazland\ElasticaBundle\Exception\ExceptionInterface`: you can use that to catch all the exceptions thrown by this bundle.
 * Identifier fields for entities are automatically retrieved from metadata (if possibile).
 * Add experimental support to composite key for transformed entities.
 * Properties of a type can reference another mapping via `@index/type` syntax.
 * Indexable callbacks dropped support for array-callable with @-prefixed services. Use `service()` expression function instead.
 * Added `cache` configuration, containing:
   * `indexable_expression`: a service id to a cache object accepted by `ExpressionLanguage` class.
 * Add custom ResultSet class with injected transformer and `getTransformed` method.
 * `search` operation in a type will return a `Fazland\ElasticaBundle\Elastica\ResultSet` instance.
