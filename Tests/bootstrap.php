<?php

$loader = require_once __DIR__ . '/../vendor/autoload.php';

class PHPUnit_Framework_TestCase extends PHPUnit\Framework\TestCase
{
}

Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(function ($class) use ($loader) {
    spl_autoload_call($class);
    return class_exists($class, false);
});