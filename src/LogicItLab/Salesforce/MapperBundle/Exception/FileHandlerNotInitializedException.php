<?php

namespace LogicItLab\Salesforce\MapperBundle\Exception;

class FileHandlerNotInitializedException extends \Exception
{
    public function __construct()
    {
        parent::__construct("Unable to initialize file handler during wsdl validation");
    }
}