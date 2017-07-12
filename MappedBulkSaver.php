<?php

namespace LogicItLab\Salesforce\MapperBundle;

use Phpforce\SoapClient\Result\SaveResult;
use Phpforce\SoapClient\BulkSaverInterface;
use LogicItLab\Salesforce\MapperBundle\Annotation\AnnotationReader;

/**
 * Provides bulk creates, deletes, updates and upserts for mapped (annotated)
 * objects
 *
 * @author Logic It Lab <team@logicitlab.com>
 */
class MappedBulkSaver implements MappedBulkSaverInterface
{
    /**
     * @var BulkSaver
     */
    private $bulkSaver;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * @var array
     */
    private $bulkModels = [];

    public function __construct(BulkSaverInterface $bulkSaver, Mapper $mapper, AnnotationReader $annotationReader)
    {
        $this->bulkSaver = $bulkSaver;
        $this->mapper = $mapper;
        $this->annotationReader = $annotationReader;
    }

    private function storeModel($model, $matchField)
    {
        $section = 'created';

        if ($matchField) {
            $section = 'upserted';
        } else if (!empty($model->getId())) {
            $section = 'updated';
        }

        $this->bulkModels[$section][] = $model;
    }

    private function clearModels()
    {
        $this->bulkModels = [];
    }

    /**
     * @param array $results
     * @param string $section upserted|created
     */
    private function populatModelIds($results, $section)
    {
        /* @var SaveResult $relatedResult */
        if (isset($this->bulkModels[$section])) {
            foreach ($this->bulkModels[$section] as $key => $storedModel) {
                $relatedResult = $results[$section][$key];

                if (!$storedModel->getId() && $relatedResult->isSuccess()) {
                    $storedModel->setId($relatedResult->getId());
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save($model, $matchField = null)
    {
        $this->storeModel($model, $matchField);

        $record = $this->mapper->mapToSalesforceObject($model, null !== $matchField);
        $objectMapping = $this->annotationReader->getSalesforceObject($model);

        $matchFieldName = null;
        if ($matchField) {
            $field = $this->annotationReader->getSalesforceField($model, $matchField);
            if (!$field) {
                throw new \InvalidArgumentException(sprintf(
                        'Invalid match field %s. Make sure to specify a mapped '
                        . 'property’s name', $matchField)
                );
            }
            $matchFieldName = $field->name;
        }

        $this->bulkSaver->save($record, $objectMapping->name, $matchFieldName);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($model)
    {
        $record = $this->mapper->mapToSalesforceObject($model);

        $this->bulkSaver->delete($record);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $results = $this->bulkSaver->flush();

        $this->populatModelIds($results, 'upserted');
        $this->populatModelIds($results, 'created');
        $this->clearModels();

        return $results;
    }

    /**
     * Get bulk delete limit
     *
     * @return int
     */
    public function getBulkDeleteLimit()
    {
        return $this->bulkSaver->getBulkDeleteLimit();
    }

    /**
     * Set bulk delete limit
     *
     * @param int $bulkDeleteLimit
     * @return MappedBulkSaver
     */
    public function setBulkDeleteLimit($bulkDeleteLimit)
    {
        $this->bulkSaver->setBulkDeleteLimit($bulkDeleteLimit);
        return $this;
    }

    /**
     * Get bulk save limit
     *
     * @return int
     */
    public function getBulkSaveLimit()
    {
        return $this->bulkSaver->getBulkSaveLimit();
    }

    /**
     * Set bulk save limit
     *
     * @param int $bulkSaveLimit
     * @return MappedBulkSaver
     */
    public function setBulkSaveLimit($bulkSaveLimit)
    {
        $this->bulkSaver->setBulkSaveLimit($bulkSaveLimit);
        return $this;
    }
}
