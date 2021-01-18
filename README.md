# ACSEO SELECT AUTOCOMPLETE BUNDLE

[![Build Status](https://travis-ci.com/acseo/select-autocomplete-bundle.svg?branch=master)](https://travis-ci.com/acseo/select-autocomplete-bundle)

- ## Table of content
    * [Introduction](#introduction)
    * [Installation](#installation)
    * [Usage](#usage)
    * [Form options](#form-options)
        + [class](#class)
        + [properties](#properties)
        + [display](#display)
        + [strategy](#strategy)
        + [multiple](#multiple)
        + [format](#format)
        + [identifier](#identifier)
        + [autocomplete_url](#autocomplete-url)
        + [provider](#provider)
    * [Providers](#providers)
    
## Introduction

This bundle helps you to build autocomplete fields in your symfony forms without declaring any controller or action.

Fully configurable, you can override any part of the autocompletion process very easily.

Doctrine **ORM** & **ODM** are supported by default, but you can create your own providers for other extensions !

## Installation

Install source code with composer :

```shell script
$ composer require acseo/select-autocomplete-bundle
```

Enable the bundle :

```php
// config/bundles/php

return [
    Acseo\SelectAutocomplete\SelectAutocompleteBundle::class => ['all' => true]
];
```

Import the autocomplete form theme :

```yaml
# config/packages/twig.yaml

twig:
    form_themes: 
        - '@SelectAutocomplete/form_theme.html.twig'
```

## Usage:

Let's start by a simple example :

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        'properties' => 'name',           // The searchable properties used for query
        'display' => 'fullname',          // Displayable label in select options
        'strategy' => 'starts_with',      // The filter strategy of search action
    ])
;
```

With your favorite js library, transform the rendered input to autocomplete input (example with select2) :

```js
$('.acseo-select-autocomplete').each((i, el) => {
    const $el = $(el);

    $el.select2({
        minimumInputLength: 1,
        ajax: {
            cache: false,
            url: $el.data('autocomplete-url'),        // Get autocomplete url to retrieve search responses
            delay: 250,
            dataType: 'json',
            data: params => ({
                q: params.term || '',                  // Append the search terms to url
                // response_format: 'json'             // (optional, json by default, see form type options for allowed values) 
            }),
            processResults: data => ({                 // Transform entrypoint resuls
                results: Object.keys(data).map(value => ({
                    id: value,
                    text: data[value]
                }))
            })
        }
    });
});
```

Please note 3 important things in this js example : 

- The rendered input has a `data-autocomplete-url` attribute and the value inside can be used to retrieve search results.
- The query param `q`, which represents the search terms, has to be added to data-autocomplete-url value.
- By default search results are returned by entrypoint like `[{ "value": "label" }]`.

You're autocomplete is now functional !

## Form options

|  Name  |  Type  |  Required  |  Default  |  Description  |
|--------|--------|------------|-----------|-----------|
| [class](#class)  | string |    yes     |   null    | The model class supposed to be autocompleted |
| [properties](#properties)  | string array |    no     |   id    | The properties used in database query to filter search results of autocomplete action. This properties can be nested with path like "nestedProperty.property". |
| [display](#display)  | string callable array |    no     |   [properties](#properties)    | The displayable properties used to build label of selectable choices. This properties can be nested with path like "nestedProperty.property". |
| [strategy](#strategy)  | string |    no     |   contains    | The strategy used to filter search results (allowed : starts_with / ends_with / contains / equals). |
| [multiple](#multiple)  | bool |    no     |   false    | Is collection field. |
| [format](#format)  | string |    no     |   json    | Default format used to encode choices of autocomplete response. Values allowed are provided by your own serializer (basically json / xml / csv / yaml in symfony serializer). |
| [identifier](#identifier)  | string |    no     |   id    | Name of your model identifier property (will be used as value of each choice option). |
| [autocomplete_url](#autocomplete-url)  | string |    no     |   request.pathInfo    | The entrypoint where autocomplete results can be retrieved. By default we use the route where the form has been built. This value will be set in attribute "data-autocomplete-url" of field input. |
| [provider](#provider)  | string callable array |    no     |   null    |  Create your own custom queries or specify a provider to use. |


**Tips** : You can also override any part of the process more globally by creating a class which extends AutocompleteType.

### class

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class
    ])
;
```

### properties

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        'properties' => 'targetProperty',
        // OR
        'properties' => ['name', 'profile.email'],
    ])
;
```

### display

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        
        'display' => 'targetPropertyOrMethod',
        // OR
        'display' => 'nestedProperty.targetProperty',
        // OR
        'display' => ['user.firstName', 'user.lastName'],
        // OR 
        'display' => function(TargetClass $object): string {
            return $object->getTargetProperty();
        },
    ])
;
```

### strategy

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        
        'strategy' => 'starts_with', // LIKE ...%
        // OR
        'strategy' => 'ends_with',   // LIKE %...
        // OR
        'strategy' => 'contains',    // LIKE %...%
        // OR
        'strategy' => 'equals',      // = ...
    ])
;
```

### multiple

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        'multiple' => true,
    ])
;
```

### format

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        
        // Options values are provided by your serializer (these are default format supported by symfony serializer)
        // Format can be override from js by add response_format param in data-autocomplete-url
        'format' => 'json',
        // OR
        'format' => 'xml',
        // OR
        'format' => 'csv',
        // OR
        'format' => 'yaml',
    ])
;
```

### identifier

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        
        // Identifier is used as choice value 
        'identifier' => 'targetPropertyOrMethod',
    ])
;
```

### autocomplete_url

Sometimes you will need this option to retrieve search results from specific entrypoint.

```php
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,
        
        // This option value will be set in data-autocomplete-url of select input attributes
        'autocomplete_url' => '/my-custom-entrypoint?param1=kevin',
    ])
;
```

### provider

```php
use Acseo\SelectAutocomplete\DataProvider\Doctrine\AbstractDoctrineDataProvider;
use Acseo\SelectAutocomplete\Form\Type\AutocompleteType;
use App\Entity\TargetClass;

$formBuilder
    ->add('example', AutocompleteType::class, [
        'class' => TargetClass::class,

        // Override provider on search action to retrieve custom collection (Usage of partial query is allowed)
        // The second argument is the default provider which supports the model class
        'provider' => function(string $terms, AbstractDoctrineDataProvider $provider) {
            return $provider->getRepository(TargetClass::class)
                ->createQueryBuilder('o')
                ->where('o.name LIKE :name')
                ->setParameter('name', $terms.'%')
                ->getQuery()
                ->getResult()
            ;
        },
        
        // OR
            
        // Use your own provider object
        'provider' => $myProvider,

        // OR

        // You can specify provider to use (the service has to be tagged as acseo_select_autocomplete.data_provider).
        // 2 providers are included by default : ORMDataProvider and ODMDataProvider.
        // You can add many providers, for specific model class or other kind of databases !
        'provider' => MyCustomProvider::class,
                
        // OR
        
        // Create custom provider
        // To know more about providers, please see Providers section.
        'provider' => [
            'find_by_ids' => function(array $ids, AbstractDoctrineDataProvider $provider) {
                return $provider->getRepository(TargetClass::class)->findBy(['id' => $ids]);
            },
            'find_by_terms' => function(string $terms, AbstractDoctrineDataProvider $provider) {
                return $provider->getRepository(TargetClass::class)
                    ->createQueryBuilder('o')
                    ->where('o.name LIKE :name')
                    ->setParameter('name', $terms.'%')
                    ->getQuery()
                    ->getResult()
                ;
            }
        ],
        
        // If provider option is not set, the provider used is the first which supports model class
    ])
;
```

## Providers

Providers classes are used to **retrieve search results** form database and **transform form view data** to model object.

2 Doctrine providers are included by default : ORMDataProvider and ODMDataProvider which supports multiple db connexions.

You can create your own provider for specific model class or specific database. This is an arbitrary example : 

```php
<?php

namespace App\Form\Autocomplete\DataProvider;

use Acseo\SelectAutocomplete\DataProvider\DataProviderInterface;

class CustomDataProvider implements DataProviderInterface
{
    private $manager;

    public function __construct(\SomeWhere\CustomManager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Does provider supports the model class.
     */
    public function supports(string $class): bool
    {
        return $this->manager->supports($class);
        
        // To make specific provider for specific model class
        // return $class === \App\Entity\Foo::class
    }
    
    /**
     * Used to retrieve object with form view data (reverseTransform).
     */
    public function findByIds(string $class, string $identifier, array $ids): array
    {
        return $this->manager->findOneBy([ $identifier => $ids ]);
    }
    
    /**
     * Find collection results of autocomplete action.
     */
    public function findByTerms(string $class, array $properties, string $terms, string $strategy): array
    {
        $qb = $this->manager->createQuery($class);
        
        switch ($strategy) {
            case 'contains':
                $qb->contains($properties, $terms);
            break;
            
            // ... Rest of strategies code
        }
        
        return $qb->limit(20)->exec();
    }
}
```

Finally, tag this service with `acseo_select_autocomplete.data_provider`.

```yaml
services:
    App\Form\Autocomplete\DataProvider\CustomDataProvider:
        autowire: true
        tags: ['acseo_select_autocomplete.data_provider']
```

Now, this provider will be invoked by default if it supports the given model class.
