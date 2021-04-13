<?php

namespace PhpArsenal\SalesforceMapperBundle;

/**
 * The events that are thrown by the mapper bundle
 */
class Events
{
    const beforeSave = 'salesforce.mapper.before_save';
    const afterSave = 'salesforce.mapper_after_save';
}