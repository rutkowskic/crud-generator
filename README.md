# CRUD Generator 

Simple CRUD generator for Laravel.

## Getting Started

To get started, you should add the rcoder/crudgenerator Composer dependency to your project:

```
composer require rcoder/crudgenerator --dev
```
Laravel 5.5+ will register the service provider Rcoder\CrudGenerator\CrudGeneratorServiceProvider automatically.

After that You need to publish its assets using the vendor:publish Artisan command:

```
php artisan vendor:publish --provider="Rcoder\CrudGenerator\CrudGeneratorServiceProvider"
```

### Usage

You will find a configuration file located at config/crud.php.

Create in resources/crud folder your model json files, example post.json

```json
{
    "model": "posts",
    "index": {
        "name": "title",
        "where": "langs|code|en"
    },
    "fields": [
        {
            "name": "title",
            "type": "text",
            "active": true,
            "required": true
        },
        {
            "name": "body",
            "type": "textarea",
            "wyswig": false,
            "required": true
        },
        {
            "name": "photo",
            "type": "file",
            "required": false
        },
        {
            "name": "published",
            "type": "radio"
        }
    ],
    "relations":[
        {
            "model": "langs",
            "type": "manytomany",
            "select": {
                "name": "code"
            },
            "fields": [
                {
                    "name": "title",
                    "type": "text"
                }
            ]
        },
        {
            "model": "categories",
            "type": "manytomany",
            "select": {
                "name": "title",
                "where": "langs|code|pl"
            }
        }
    ]
}
```

And generate using Artisan command:

```
php artisan crud:generate
```
Your panel admin is a available on yourapp.com/admin.

## Version

This is development version, in feature will be add relations, more type fields and able to create models and migrations. 

## License

This project is licensed under the MIT License.


