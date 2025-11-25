<?php

namespace PhpArsenal\SalesforceMapperBundle\Request\ParamConverter;

use PhpArsenal\SalesforceMapperBundle\Mapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * resolves salesforce id route parameters into salesforce objects using the mapper
 */
class SalesforceParamConverter implements ValueResolverInterface
{
    protected Mapper $mapper;

    /** @var array<string, string> property name => salesforce model class */
    protected array $mappings;

    public function __construct(Mapper $mapper, array $mappings)
    {
        $this->mapper = $mapper;
        $this->mappings = $mappings;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $class = $argument->getType();
        if (!$class || !in_array($class, $this->mappings, true)) {
            return [];
        }

        $propertyName = array_search($class, $this->mappings, true);
        if (!$propertyName || !$request->attributes->has($propertyName)) {
            return [];
        }

        $id = $request->attributes->get($propertyName);
        $model = $this->mapper->find($class, $id);
        if (!$model) {
            throw new NotFoundHttpException('Model with id ' . $id . ' not found');
        }

        return [$model];
    }
}
