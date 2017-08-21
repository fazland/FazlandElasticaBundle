Configuration
=============

Client connections
------------------

You can define your client connections under the `clients` configuration key.
Here's the simplest client configuration:

```yaml
fazland_elastica:
    clients:
        default: { host: localhost, port: 9200 }
```

You can also multiple connections per client:

```yaml
fazland_elastica:
    clients:
        default:
        	- { host: es1.internal, port: 9200 }
        	- { host: es2.internal, port: 9200 }
        	- { host: es3.internal, port: 9200 }
```

Full client configuration reference:

```yaml
fazland_elastica:
    default_client: ~		# Defaults to the first client defined
    clients:
        client_id:
            connectionStrategy: Simple 			# (or RoundRobin)
            connections:
                -
                    host: hostname
                    port: 9200
                    url: http://hostname:9200/base_path		# To specify a base path with host and port
                    proxy: ~		# Null for environmental proxy, empty string to disable proxy or a proxy url usable from curl
                    transport: ~	# Http or https
                    timeout: 60		# In seconds
                    connectTimeout: 5
                    retryOnConflict: 1 		# How may retries should be performed after a "Conflict" response.
                    
                    # Usage with amazon elasticsearch service
                    aws_access_key_id: ~
                    aws_secret_access_key: ~
                    aws_region: ~
                    aws_session_token: ~
                    
                    logger: logger_service			# Defaults to fazland_elastica.logger if debug is enabled
                                                    # and disabled if not in debug.
                    compression: false
                    headers:
                        Content-Type: application/json
                        X-Custom-Header: foobar
```

Indexes
-------

You must define your indexes in order to use it with the bundle.
You can define them under the `indexes` configuration key.

```yaml
fazland_elastica:
    indexes:
        foo_index:
            index_name: foo_index_%kernel.environment%		# To specify a custom index name
            use_alias: true				# true, simple or a service id implementing AliasStrategyInterface
            settings:					# ES index settings
                index:
                    refresh_interval: -1
                max_result_window: 20000
            types: []
```

You can also define an `_all` index key to set some settings once for all indexes.

```yaml
fazland_elastica:
    indexes:
        _all:
            settings:
                index.number_of_shards: 5
                index.number_of_replicas: 1
        index1:
            ...
        index2:
            ...
```

Types
-----

See [types.md](types.md) for more information.
