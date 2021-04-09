# Salesforce Mapper Bundle

[![Release](https://img.shields.io/github/v/release/php-arsenal/salesforce-mapper-bundle)](https://github.com/php-arsenal/salesforce-mapper-bundle/releases)
[![Travis](https://img.shields.io/travis/php-arsenal/salesforce-mapper-bundle)](https://travis-ci.org/php-arsenal/salesforce-mapper-bundle)
[![Test Coverage](https://img.shields.io/codeclimate/coverage/php-arsenal/salesforce-mapper-bundle)](https://codeclimate.com/github/php-arsenal/salesforce-mapper-bundle)
[![Packagist](https://img.shields.io/packagist/dt/php-arsenal/salesforce-mapper-bundle)](https://packagist.org/packages/php-arsenal/salesforce-mapper-bundle)

## Introduction

This bundle provides transparent, object-oriented access to your Salesforce
data. 

## Features

* Easily fetch records from Salesforce, and save these same records back to
  Salesforce: read-only fields are automatically ignored when saving.
* Find by criteria, so you don’t have to roll your own SOQL queries.
* Fetch related records in one go, so you save on
[API calls](http://www.salesforce.com/us/developer/docs/api/Content/implementation_considerations.htm#topic-title_request_metering).
* Adjust the mappings to retrieve and save records exactly like you want to.
* The MappedBulkSaver helps you stay within your Salesforce API limits by using 
  bulk creates, deletes, updates and upserts for mapped objects.
* Completely unit tested (still working on that one).

## Documentation

Documentation is included in the [Resources/doc directory](http://github.com/LogicItLab/LogicItLabSalesforceMapperBundle/tree/master/Resources/doc/index.md).
 