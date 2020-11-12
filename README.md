# ACSEO SELECT AUTOCOMPLETE BUNDLE

**Caution : This bundle is currently in development**

This bundle provides a simple form type to create autocompleted select of doctrine entities/documents. 

## Limitations (Will be resolved soon)

- The searchable property is the property displayed
- Search on nested properties is not possible
- Search on multiple properties is not possible
- Doctrine ODM is supported but provider class is missing

## Installation

**Using composer (Not available yet)**

```shell script
$ composer require acseo/select-autocomplete-bundle
```

**With VCS repository**

In your composer.json

```json
{
    "require": {
        "acseo/select-autocomplete-bundle": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/acseo/select-autocomplete-bundle.git"
        }
    ]
}
```

```shell script
$ composer update acseo/select-autocomplete-bundle
```

## Usage

Enable the bundle :

```php
// config/bundles/php

return [
    Acseo\SelectAutocomplete\SelectAutocompleteBundle::class => ['all' => true]
];
```

Import the autocomplete route :

````yaml
# config/routes.yaml

acseo_select_autocomplete:
    resource: '@SelectAutocompleteBundle/Resources/config/routes.yaml'
````

Use the autocomplete form theme :

```yaml
# config/packages/twig.yaml

twig:
    form_themes: 
        - '@SelectAutocomplete/form_theme.html.twig'
```

Use the autocomplete form type :

```php
// Somewhere in your code

use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;

$formBuilder
    ->add('nestedProperty', AutocompleteType::class, [
        'class' => \App\Entity\TargetClass::class,    // Class supposed to be autocompleted
        'property' => 'name',                         // Searchable property
        // 'strategy' => 'starts_with'                // Filter strategy (starts_with|ends_with|contains|equal)
        // 'multiple' => true                         // Optional multiple option
    ])
;
```

Allow class and property to be searchable :

```yaml
# config/packages/select_autocomplete.yaml

select_autocomplete:
    classes:                        # Define allowed classes
        App\Entity\TargetClass:
            properties: [name]      # Define allowed properties
```

Init your favorite js autocomplete (example with select2)

```js
$('.select-autocomplete').each((i, el) => {
    const $el = $(el);

    $el.select2({
        minimumInputLength: 1,
        ajax: {
            cache: false,
            url: $el.data('url'),               // Element is rendered with entrypoint url without "terms" param
            delay: 250,
            dataType: 'json',
            data: params => ({
                terms: params.term || '',
                // format: 'json'               // (optional, json by default) allowed : json|xml|csv
            }),
            processResults: data => ({          // Options are returned by api like { label: '...', value: '...' }
                results: data.map(item => ({      
                    id: item.value,
                    text: item.label
                }))
            })
        }
    });
});
```
