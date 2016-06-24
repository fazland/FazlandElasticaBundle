HTTP compression
==========================================

By default, FazlandElasticaBundle and Elastica do not compress the HTTP request but you can do it with a simple configuration:

```yaml
# app/config/config.yml
fazland_elastica:
    clients:
        default:
            host: example.com
            compression: true
```
