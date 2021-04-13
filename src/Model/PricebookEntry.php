<?php

namespace PhpArsenal\SalesforceMapperBundle\Model;

use PhpArsenal\SalesforceMapperBundle\Annotation as Salesforce;

/**
 * Represents a product entry (an association between a Pricebook2 and Product2)
 * in a price book
 *
 * @Salesforce\SObject(name="PricebookEntry")
 * @link http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_objects_pricebookentry.htm
 */
class PricebookEntry extends AbstractModel
{
    /**
     * @var string
     * @Salesforce\Field(name="Name")
     */
    protected $name;

    /**
     * @var boolean
     * @Salesforce\Field(name="IsActive")
     */
    protected $isActive;

    /**
     * @var Product
     * @Salesforce\Relation(field="Product2Id", name="Product2",
     *                      class="PhpArsenal\Salesforce\SalesforceMapperBundle\Model\Product")
     */
    protected $product;

    /**
     * @var string
     * @Salesforce\Field(name="Product2Id")
     */
    protected $productId;

    protected $pricebook;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function setProduct($product)
    {
        $this->product = $product;
    }
}