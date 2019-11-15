<?php
/**
 * Snipcart plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\snipcart\fields;

use workingconcept\snipcart\helpers\VersionHelper;
use workingconcept\snipcart\Snipcart;
use workingconcept\snipcart\models\ProductDetails as ProductDetailsModel;
use workingconcept\snipcart\assetbundles\ProductDetailsFieldAsset;
use Craft;
use craft\base\ElementInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\TypeLoader;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

/**
 * ProductDetails
 *
 * @property ProductDetails $value
 */
class ProductDetails extends \craft\base\Field
{

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('snipcart', 'Snipcart Product Details');
    }

    /**
     * @return bool
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }


    // Public Properties
    // =========================================================================

    /**
     * @var bool Whether to display "shippable" option for this field instance
     *           and allow it to be set per entry.
     */
    public $displayShippableSwitch = false;

    /**
     * @var bool Whether to display "taxable" option for this field instance
     *           and allow it to be set per entry.
     *
     */
    public $displayTaxableSwitch = false;

    /**
     * @var bool Whether to display "inventory" option for this field instance.
     */
    public $displayInventory = false;

    /**
     * @var bool Default "shippable" value.
     */
    public $defaultShippable = false;

    /**
     * @var bool Default "taxable" value.
     */
    public $defaultTaxable = false;

    /**
     * @var
     */
    public $defaultWeight;

    /**
     * @var
     */
    public $defaultWeightUnit;

    /**
     * @var
     */
    public $defaultLength;

    /**
     * @var
     */
    public $defaultWidth;

    /**
     * @var
     */
    public $defaultHeight;

    /**
     * @var
     */
    public $defaultDimensionsUnit;

    /**
     * @var string
     */
    public $skuDefault = '';


    // Public Methods
    // =========================================================================

    /**
     * After the Element is saved, save the Product Details to their table.
     *
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        // If we're using Craft 3.4+, first see if it's worth saving.
        $shouldSave = VersionHelper::isCraft34() ?
            $element->isFieldDirty($this->handle) :
            true;

        if ($shouldSave)
        {
            Snipcart::$plugin->fields->saveProductDetailsField(
                $this,
                $element
            );
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * Pull details out of the database for use like any other field.
     *
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        return Snipcart::$plugin->fields->getProductDetailsField(
            $this, $element,
            $value
        );
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public function getContentGqlType()
    {
        $typeName = $this->handle.'_SnipcartField';

        $productDetailsType = GqlEntityRegistry::getEntity($typeName)
            ?: GqlEntityRegistry::createEntity($typeName, new ObjectType([
                'name'   => $typeName,
                'fields' => [
                    'sku'            => Type::string(),
                    'price'          => Type::float(),
                    'shippable'      => Type::boolean(),
                    'taxable'        => Type::boolean(),
                    'weight'         => Type::float(),
                    'weightUnit'     => Type::string(),
                    'length'         => Type::float(),
                    'width'          => Type::float(),
                    'height'         => Type::float(),
                    'inventory'      => Type::int(),
                    'dimensionsUnit' => Type::string(),
                ],
            ]));

        TypeLoader::registerType($typeName, static function () use ($productDetailsType) { return $productDetailsType ;});

        return $productDetailsType;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml(
        $value,
        ElementInterface $element = null
    ): string {
        Craft::$app->getView()->registerAssetBundle(
            ProductDetailsFieldAsset::class
        );

        return Craft::$app->getView()->renderTemplate(
            'snipcart/fields/product-details/field',
            [
                'name'                  => $this->handle,
                'field'                 => $this,
                'element'               => $element,
                'value'                 => $value,
                'settings'              => $this->getSettings(),
                'weightUnitOptions'     => ProductDetailsModel::getWeightUnitOptions(),
                'dimensionsUnitOptions' => ProductDetailsModel::getDimensionsUnitOptions(),
                'isCraft34'             => VersionHelper::isCraft34()
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): string
    {
        Craft::$app->getView()->registerAssetBundle(
            ProductDetailsFieldAsset::class
        );

        return Craft::$app->getView()->renderTemplate(
            'snipcart/fields/product-details/settings',
            [
                'field'                 => $this,
                'weightUnitOptions'     => ProductDetailsModel::getWeightUnitOptions(),
                'dimensionsUnitOptions' => ProductDetailsModel::getDimensionsUnitOptions(),
            ]
        );
    }

    /**
     * Add one custom validation rule that the Element will call. This will make
     * it possible to validate each of the "sub-fields" we're working with.
     *
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            'validateProductDetails',
        ];
    }

    /**
     * Validate the ProductDetails model, adding any errors to the Element.
     *
     * @param  ElementInterface  $element
     */
    public function validateProductDetails(ElementInterface $element)
    {
        $productDetails = $element->getFieldValue($this->handle);
        $productDetails->validate();

        $errors = $productDetails->getErrors();

        if (count($errors) > 0) {
            foreach ($errors as $subfield => $errors) {
                foreach ($errors as $message) {
                    $element->addError($this->handle.'['.$subfield.']',
                        $message);
                }
            }
        }
    }

}
