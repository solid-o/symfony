Solido Symfony Integration
==========================

Provides Symfony Bundle and Symfony integrations for the solido suite.  
Read more in the [dedicated documentation section](https://solid-o.github.io/docs/#/symfony-integration)

Smithy model generation
-----------------------

When `solido/smithy` is installed, the bundle can expose a `solido:smithy:dump` command:

```yaml
solido:
    dto:
        namespaces:
            - App\DTO
    smithy:
        enabled: true
        namespace: com.example.api
        service_name: ExampleService
        output: '%kernel.project_dir%/build/smithy/model.smithy'
        type_overrides:
            App\ValueObject\UserId: String
            App\DTO\v1\v1_0\User::id: smithy.api#String
```

```shell
bin/console solido:smithy:dump
```

Resources
---------

- [Documentation](https://solid-o.github.io/docs/#/symfony-integration)
- [Contributing](https://solid-o.github.io/docs/#/CONTRIBUTING)
- [Report issues](https://github.com/solid-o/symfony/issues/new) and [send Pull Request](https://github.com/solid-o/symfony/pulls)
