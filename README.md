# Salesforce Mapper Bundle

[![Release](https://img.shields.io/github/v/release/php-arsenal/salesforce-mapper-bundle)](https://github.com/php-arsenal/salesforce-mapper-bundle/releases)
[![Travis](https://img.shields.io/travis/php-arsenal/salesforce-mapper-bundle)](https://travis-ci.com/php-arsenal/salesforce-mapper-bundle)
[![Test Coverage](https://img.shields.io/codeclimate/coverage/php-arsenal/salesforce-mapper-bundle)](https://codeclimate.com/github/php-arsenal/salesforce-mapper-bundle)
[![Packagist](https://img.shields.io/packagist/dt/php-arsenal/salesforce-mapper-bundle)](https://packagist.org/packages/php-arsenal/salesforce-mapper-bundle)

## Introduction

The Symfony bundle helps you fetch & map Salesforce objects effectivelly to your own modals to use later with Doctrine or stand-alone.

## Installation

`composer require php-arsenal/salesforce-mapper-bundle`

## Features

* Easily fetch records from Salesforce, and save these same records back to
  Salesforce: read-only fields are automatically ignored when saving.
* Find by criteria just like in Doctrine.
* Fetch related records in one go, so you save on
[API calls](http://www.salesforce.com/us/developer/docs/api/Content/implementation_considerations.htm#topic-title_request_metering).
* Adjust the mappings to retrieve and save records exactly like you want to.
* The MappedBulkSaver helps you stay within your Salesforce API limits by using 
  bulk creates, deletes, updates and upserts for mapped objects.
* Completely unit tested (still working on that one).

## Usage

Once installed, the bundle offers several services that are autowired for dependancy injection:

* a mapper: `PhpArsenal\SalesforceMapperBundle\Mapper`
* a bulk saver: `PhpArsenal\SalesforceMapperBundle\MappedBulkSaver`

### Fetch filtered records

Use the mapper to fetch records from Salesforce. An example:

```php
<?php

use PhpArsenal\SalesforceMapperBundle\Mapper;
use PhpArsenal\SalesforceMapperBundle\Model\Opportunity;

class MyService {
    private $mapper;
    
    public function __construct(Mapper $mapper) {
      $fetchedObjects = $mapper->findBy(new Opportunity(), [
          'Name'  => 'Just an opportunity',
      ]);
    }
}
```

You can even fetch related records just by going through related objects:

```php
...
$opportunity = $fetchedObjects[0];
echo 'The opportunity belongs to: ' . $opportunity->getAccount()->getName();
...
```

### Fetch all records

```php
...
$fetchedObjects = $mapper->findAll(Opportunity::class);
...
```

### Saving records

If you create a new record and save it, the ID assigned to it by Salesforce is
accessible with `getId()`.

```php
...
$opportunity = new Opportunity();
$opportunity->setName('Some name');
echo $opportunity->getId(); // Returns null

$mapper->save($account);
echo $account->getId(); // Returns the new ID, e.g. `001D000000h0Jod`
...
```

### Custom objects and properties

In the `Model` folder you will find several standard Salesforce objects. As this
is a generic client bundle, this directory does not contain custom objects, nor
do the objects in it have custom properties. 

If you would like to add custom objects or properties, please extend from `AbstractModel` or the models provided.

The mapper knows how to map fields reading the annotations above the property:

```php
...
use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;
...
    /**
     * @var string
     * @Salesforce\Field(name="AccountId")
     */
    protected $accountId;
...
```
