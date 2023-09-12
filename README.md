# users


`composer require controleonline/users:dev-master`



Create a new fila on controllers:
config\routes\controllers\users.yaml

```
controllers:
    resource: ../../vendor/controleonline/users/src/Controller/
    type: annotation      
```

Add to entities:
nelsys-api\config\packages\doctrine.yaml
```
doctrine:
    dbal:
        # configure these for your database server
        driver: "pdo_mysql"
        server_version: "5.7"
        charset: utf8mb4
        url: "%env(resolve:DATABASE_URL)%"
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci
        mapping_types:
            enum: string
        options:
            1002: 'SET sql_mode=(SELECT REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", ""));SET TRANSACTION ISOLATION LEVEL READ COMMITTED;'
    orm:
        dql:
            numeric_functions:
                acos: DoctrineExtensions\Query\Mysql\Acos
                cos: DoctrineExtensions\Query\Mysql\Cos
                sin: DoctrineExtensions\Query\Mysql\Sin
                pi: DoctrineExtensions\Query\Mysql\Pi
                rand: DoctrineExtensions\Query\Mysql\Rand
        auto_generate_proxy_classes: true
        naming_strategy: doctrine.orm.naming_strategy.underscore
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                type: annotation
                dir: "%kernel.project_dir%/src/Entity"
                prefix: 'App\Entity'
                alias: App
            Users:
                is_bundle: false
                type: annotation
                dir: "%kernel.project_dir%/vendor/controleonline/users/src/Entity"
                prefix: 'ControleOnline\Entity'
                alias: App                             
```          


Add this line on your routes:
config\packages\api_platform.yaml
```          
mapping   :
    paths: ['%kernel.project_dir%/src/Entity','%kernel.project_dir%/src/Resource',"%kernel.project_dir%/vendor/controleonline/users/src/Entity"]        
```          