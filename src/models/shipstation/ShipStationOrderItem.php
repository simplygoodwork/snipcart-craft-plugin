<?php
/**
 * Snipcart plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\snipcart\models;

/**
 * ShipStation Order Item Model
 * https://www.shipstation.com/developer-api/#/reference/model-orderitem
 *
 * @package workingconcept\snipcart\models
 *
 * @property ShipStationWeight|null $weight
 * @property ShipStationItemOption[]|null $options
 */
class ShipStationOrderItem extends \craft\base\Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null The system generated identifier for the OrderItem. This is a read-only field.
     */
    public $orderItemId;

    /**
     * @var string|null An identifier for the OrderItem in the originating system.
     */
    public $lineItemKey;

    /**
     * @var string|null The SKU (stock keeping unit) identifier for the product associated with this line item.
     */
    public $sku;

    /**
     * @var string The name of the product associated with this line item. Cannot be `null`
     */
    public $name;

    /**
     * @var string|null The public URL to the product image.
     */
    public $imageUrl;

    /**
     * @var ShipStationWeight|null
     */
    private $_weight;

    /**
     * @var int|null The quantity of product ordered.
     */
    public $quantity;

    /**
     * @var float|null The sell price of a single item specified by the order source.
     */
    public $unitPrice;

    /**
     * @var float|null The tax price of a single item specified by the order source.
     */
    public $taxAmount;

    /**
     * @var float|null The shipping amount or price of a single item specified by the order source.
     */
    public $shippingAmount;

    /**
     * @var string|null The location of the product within the seller's warehouse (e.g. Aisle 3, Shelf A, Bin 5)
     */
    public $warehouseLocation;

    /**
     * @var ShipStationItemOption[]|null
     */
    private $_options;

    /**
     * @var int|null The identifier for the Product Resource associated with this OrderItem.
     */
    public $productId;

    /**
     * @var string|null The fulfillment SKU associated with this OrderItem if the fulfillment provider requires an
     * identifier other then the SKU.
     */
    public $fulfillmentSku;

    /**
     * @var bool|null Indicates that the OrderItem is a non-physical adjustment to the order
     * (e.g. a discount or promotional code)
     */
    public $adjustment;

    /**
     * @var string|null The Universal Product Code associated with this OrderItem.
     */
    public $upc;

    /**
     * @var \DateTime|null The timestamp the orderItem was created in ShipStation's database. Read-Only.
     */
    public $createDate;

    /**
     * @var \DateTime|null The timestamp the orderItem was modified in ShipStation.
     * `modifyDate` will equal `createDate` until a modification is made. Read-Only.
     */
    public $modifyDate;


    // Public Methods
    // =========================================================================

    /**
     * Returns the item’s weight.
     *
     * @return ShipStationWeight|null The item’s weight.
     */
    public function getWeight()
    {
        return $this->_weight;
    }

    /**
     * Sets the item’s weight.
     *
     * @param array|ShipStationWeight $weight The item’s weight.
     * @return ShipStationWeight;
     */
    public function setWeight($weight)
    {
        if (is_array($weight))
        {
            $weight = new ShipStationWeight($weight);
        }

        return $this->_weight = $weight;
    }

    /**
     * Returns the item’s options.
     *
     * @return ShipStationItemOption[] The item’s options.
     */
    public function getOptions(): array
    {
        if ($this->_options !== null)
        {
            return $this->_options;
        }

        $this->_options = [];

        return $this->_options;
    }

    /**
     * Sets the item’s options.
     *
     * @param ShipStationItemOption[] $options The item’s options.
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
    }

    /**
     * Populate model from SnipcartItem.
     *
     * @param SnipcartItem $snipcartItem
     *
     * @return ShipStationOrderItem
     */
    public function populateFromSnipcartItem($snipcartItem): ShipStationOrderItem
    {
        $this->lineItemKey = $snipcartItem->id;
        $this->name        = $snipcartItem->name;
        $this->quantity    = $snipcartItem->quantity;
        $this->unitPrice   = $snipcartItem->price;

        $itemWeight = new ShipStationWeight();
        $itemWeight->setAttributes([
            'value' => $snipcartItem->weight,
            'units' => ShipStationWeight::UNIT_GRAMS,
        ]);
        $itemWeight->validate();

        $this->weight = $itemWeight;

        if ( ! empty($snipcartItem->customFields))
        {
            $itemOptions = [];

            foreach ($snipcartItem->customFields as $customField)
            {
                $itemOption = new ShipStationItemOption();

                $itemOption->name  = $customField->name;
                $itemOption->value = $customField->value;
                $itemOption->validate();

                $itemOptions[] = $itemOption;
            }

            $this->setOptions($itemOptions);
        }

        $this->validate();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function datetimeAttributes(): array
    {
        return ['createDate', 'modifyDate'];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = array_keys(\Yii::getObjectVars($this));
        $fields = array_merge($fields, ['weight', 'options']);
        return array_combine($fields, $fields);
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['orderItemId', 'quantity', 'productId'], 'number', 'integerOnly' => true],
            [['unitPrice', 'taxAmount', 'shippingAmount'], 'number', 'integerOnly' => false],
            [['lineItemKey', 'sku', 'name', 'warehouseLocation', 'fulfillmentSku', 'upc', 'createDate', 'modifyDate'], 'string', 'max' => 255],
            [['name'], 'required'],
            [['imageUrl'], 'url'],
            [['adjustment'], 'boolean'],
        ];
    }

}
