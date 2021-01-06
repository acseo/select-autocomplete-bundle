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
        // The class supposed to be autocompleted (required)
        'class' => \App\Entity\TargetClass::class,
           
        // The searchable property
        // This property is display by default if "display" option is not defined (default: identifier)
        'property' => 'name',
        
        // Displayable label
        // Can be a method name or a property or a function to make custom choice label (default: identifier)
        'display' => 'customMethod',
        'display' => function($object) {
            return $object->getName();
        },
        
        // The filter strategy
        // Default available options are starts_with|ends_with|contains|equal (optional, default: contains)
        // Ignored if "provider" option is defined
        'strategy' => 'starts_with',
        
        // Multiple option (optional, default false)               
        'multiple' => true,
        
        // Custom collection provider (Usage of partial query is allowed) (optional, default null)
        'provider' => function(ObjectRepository $repository, string $terms) {
            return $repository->createQueryBuilder('o')
                ->where('o.name = :name')
                ->setParameter('name', $terms.'%')
                ->getQuery()
                ->getResult()
            ;
        },
        
        // By default autocompletion is on same route the form is rendered
        // If you want to use a specific controller, you can defined an accessible route
        // This route will be set in attribute of input
        // 'autocomplete_url' => '/custom_entry_point',
    ])
;
```

Init your favorite js autocomplete (example with select2)

```js
$('.select-autocomplete').each((i, el) => {
    const $el = $(el);

    $el.select2({
        minimumInputLength: 1,
        ajax: {
            cache: false,
            url: $el.data('autocomplete-url'),
            delay: 250,
            dataType: 'json',
            data: params => ({
                terms: params.term || '',
                // autocomplete_format: 'json'  // (optional, json by default) allowed : json|xml|csv
            }),
            processResults: data => ({          // Options are returned by api like { [value]: label }
                results: Object.keys(data).map(value => ({      
                    id: value,
                    text: data[value]
                }))
            })
        }
    });
});
```
