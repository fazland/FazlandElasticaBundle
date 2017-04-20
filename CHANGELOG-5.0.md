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
 * Removed `multi_field` type.
 * Removed `Search` annotations.
 * Removed deprecated `servers` configuration key.
 * Removed `_boost`, `_timestamp` and `_ttl` fields, not supported by ES 5.
 * Removed deprecated `mappings` and `is_indexable_callback` configurations.
 * Removed deprecated Doctrine `RepositoryManager`.
 * All the bundle's exception classes are now implementing `Fazland\ElasticaBundle\Exception\ExceptionInterface`: you can use that to catch all the exceptions thrown by this bundle.
 * Indexable callbacks dropped support for array-callable with @-prefixed services. Use `service()` expression function instead.
 * Added `cache` configuration, containing:
   * `indexable_expression`: a service id to a cache object accepted by `ExpressionLanguage` class.
