<?php
/**
 * Snipcart plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2020 Working Concept Inc.
 */

namespace workingconcept\snipcart\helpers;

use craft\helpers\StringHelper;
use workingconcept\snipcart\models\snipcart\Customer;
use workingconcept\snipcart\models\snipcart\Order;
use workingconcept\snipcart\models\snipcart\Item;

class TestHelper
{
    public static function generateOrder($customer = null, $items = null, $returnArray = true)
    {
        $customer = self::generateCustomer($customer);
        $items = self::generateOrderItems($items);

        $totalWeight = 0;
        $grandTotal = 0;
        $itemsTotal = 0;

        foreach ($items as $item)
        {
            $totalWeight += $item->totalWeight;
            $grandTotal += $item->price;
            $itemsTotal += $item->quantity;
        }

        $order = new Order([
            'invoiceNumber' => self::generateInvoiceNumber(),
            'token' => StringHelper::UUID(),
            'creationDate' => date('c'),
            'modificationDate' => date('c'),
            'status' => Order::STATUS_IN_PROGRESS,
            'email' => $customer->email,
            'billingAddressName'       => $customer->billingAddressName,
            'billingAddressAddress1'   => $customer->billingAddressAddress1,
            'billingAddressAddress2'   => $customer->billingAddressAddress2,
            'billingAddressCity'       => $customer->billingAddressCity,
            'billingAddressProvince'   => $customer->billingAddressProvince,
            'billingAddressPostalCode' => $customer->billingAddressPostalCode,
            'billingAddressCountry'    => $customer->billingAddressCountry,
            'billingAddressPhone'      => $customer->billingAddressPhone,
            'billingAddress'           => [
                'fullName'   => $customer->billingAddressName,
                'name'       => $customer->billingAddressName,
                'address1'   => $customer->billingAddressAddress1,
                'address2'   => $customer->billingAddressAddress2,
                'city'       => $customer->billingAddressCity,
                'province'   => $customer->billingAddressProvince,
                'postalCode' => $customer->billingAddressPostalCode,
                'country'    => $customer->billingAddressCountry,
                'phone'      => $customer->billingAddressPhone,
            ],
            'shippingAddressName'       => $customer->shippingAddressName,
            'shippingAddressAddress1'   => $customer->shippingAddressAddress1,
            'shippingAddressAddress2'   => $customer->shippingAddressAddress2,
            'shippingAddressCity'       => $customer->shippingAddressCity,
            'shippingAddressProvince'   => $customer->shippingAddressProvince,
            'shippingAddressPostalCode' => $customer->shippingAddressPostalCode,
            'shippingAddressCountry'    => $customer->shippingAddressCountry,
            'shippingAddressPhone'      => $customer->shippingAddressPhone,
            'shippingAddress'           => [
                'fullName'   => $customer->shippingAddressName,
                'name'       => $customer->shippingAddressName,
                'address1'   => $customer->shippingAddressAddress1,
                'address2'   => $customer->shippingAddressAddress2,
                'city'       => $customer->shippingAddressCity,
                'province'   => $customer->shippingAddressProvince,
                'postalCode' => $customer->shippingAddressPostalCode,
                'country'    => $customer->shippingAddressCountry,
                'phone'      => $customer->shippingAddressPhone,
            ],
            'shippingAddressSameAsBilling' => true,
            'creditCardLast4Digits' => null,
            'shippingMethod' => 'UPS Ground',
            'shippingFees' => 5,
            'taxableTotal' => 0,
            'taxesTotal' => 0,
            'itemsTotal' => $itemsTotal,
            'totalWeight' => $totalWeight,
            'grandTotal' => $grandTotal,
            'finalGrandTotal' => $grandTotal,
            'ipAddress' => '0.0.0.0',
            'userAgent' => 'test',
            'items' => $items,
        ]);

        if ($returnArray === false) {
            return $order;
        }

        $orderArray = $order->toArray([], $order->extraFields(), true);

        if (isset($orderArray['cpUrl']))
        {
            /**
             * Remove read-only property that would throw an exception
             * if we tried to set it.
             */
            unset($orderArray['cpUrl']);
        }

        return $orderArray;
    }

    public static function generateCustomer($data = null): Customer
    {
        return new Customer($data ?? [
            'email'                     => 'tobias@actorpull.biz',
            'billingAddressName'        => 'Tobias Fünke',
            'billingAddressFirstName'   => 'Tobias',
            'billingAddressAddress1'    => '1234 Balboa Towers Circle',
            'billingAddressAddress2'    => 'Apt 1234',
            'billingAddressCity'        => 'Los Angeles',
            'billingAddressProvince'    => 'CA',
            'billingAddressPostalCode'  => '92706',
            'billingAddressPhone'       => '(555) 555-5555',
            'billingAddressCountry'     => 'US',
            'shippingAddressName'       => 'Tobias Fünke',
            'shippingAddressFirstName'  => 'Tobias',
            'shippingAddressAddress1'   => '1234 Balboa Towers Circle',
            'shippingAddressAddress2'   => 'Apt 1234',
            'shippingAddressCity'       => 'Los Angeles',
            'shippingAddressProvince'   => 'CA',
            'shippingAddressPostalCode' => '92706',
            'shippingAddressPhone'      => '(555) 555-5555',
            'shippingAddressCountry'    => 'US',
        ]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public static function generateSubscription(): array
    {
        $customer = self::generateCustomer();

        return [
            'user' => [
                'id' => StringHelper::UUID(),
                'email' => $customer->email,
                'creationDate' => date('c'),
                'mode' => 'Test',
                'gravatarUrl' => 'https://www.gravatar.com/avatar/b2b4677d71645916cbce0a893f7f6076?s=70&d=https%3a%2f%2fcdn.snipcart.com%2fassets%2fimages%2favatar.jpg',
                'billingAddress' => [
                    'fullName'   => $customer->billingAddressName,
                    'firstName'  => $customer->billingAddressName,
                    'name'       => $customer->billingAddressName,
                    'company'    => 'Company Name',
                    'address1'   => $customer->billingAddressAddress1,
                    'address2'   => $customer->billingAddressAddress2,
                    'city'       => $customer->billingAddressCity,
                    'country'    => $customer->billingAddressCountry,
                    'postalCode' => $customer->billingAddressPostalCode,
                    'province'   => $customer->billingAddressProvince,
                    'phone'      => $customer->billingAddressPhone,
                    'vatNumber'  => null
                ],
                'shippingAddress' => [
                    'fullName'   => $customer->billingAddressName,
                    'firstName'  => $customer->billingAddressName,
                    'name'       => $customer->billingAddressName,
                    'company'    => 'Company Name',
                    'address1'   => $customer->billingAddressAddress1,
                    'address2'   => $customer->billingAddressAddress2,
                    'city'       => $customer->billingAddressCity,
                    'country'    => $customer->billingAddressCountry,
                    'postalCode' => $customer->billingAddressPostalCode,
                    'province'   => $customer->billingAddressProvince,
                    'phone'      => $customer->billingAddressPhone,
                    'vatNumber'  => null
                ],
                'initialOrderToken' => StringHelper::UUID(),
                'schedule' => [
                    'interval' => 'Day',
                    'intervalCount' => 1,
                    'trialPeriodInDays' => null,
                    'startsOn' => date('c') // 2017-10-04T00:00:00Z
                ],
                'itemId' => StringHelper::UUID(),
                'name' => 'Plan with new syntax',
                'modificationDate' => date('c'),
                'cancelledOn' => null,
                'amount' => 30,
                'quantity' => 1,
                'userDefinedId' => 'PLAN_NEW_SYNTAX',
                'totalSpent' => 30,
                'status' => 'Paid',
                'gatewayId' => null,
                'metadata' => null,
                'cartId' => null,
            ]
        ];
    }

    public static function generateOrderItems($data = null): array
    {
        $items = [];

        if ($data && is_array($data)) {
            foreach ($data as $itemData) {
                $items[] = new Item($itemData);
            }

            return $items;
        }

        $items[] = new Item([
            'token'        => StringHelper::UUID(),
            'name'         => 'Test Item A',
            'price'        => 5,
            'quantity'     => 1,
            'url'          => 'https://snipcart.test/item-a',
            'id'           => 'item-a-sku',
            'shippable'    => true,
            'taxable'      => true,
            'weight'       => 10,
            'totalWeight'  => 10,
            'uniqueId'     => 'item-a-sku',
            'customFields' => [
                [
                    'name'         => 'custom-field-a',
                    'displayValue' => 'Custom Field A',
                    'type'         => 'dropdown',
                    'value'        => 'Custom Field Value',
                ],
            ],
            'unitPrice' => 5,
            'totalPrice' => 5,
        ]);

        $items[] = new Item([
            'token'        => StringHelper::UUID(),
            'name'         => 'Test Item B',
            'price'        => 10,
            'quantity'     => 1,
            'url'          => 'https://snipcart.test/item-b',
            'id'           => 'item-b-sku',
            'shippable'    => false,
            'taxable'      => false,
            'weight'       => 0,
            'totalWeight'  => 0,
            'uniqueId'     => 'item-b-sku',
            'customFields' => [
                [
                    'name'         => 'custom-field-a',
                    'displayValue' => 'Custom Field A',
                    'type'         => 'dropdown',
                    'value'        => 'Custom Field Value',
                ],
            ],
            'unitPrice' => 10,
            'totalPrice' => 10,
        ]);

        $items[] = new Item([
            'token'        => StringHelper::UUID(),
            'name'         => 'Test Item C',
            'price'        => 15,
            'quantity'     => 1,
            'url'          => 'https://snipcart.test/item-c',
            'id'           => 'item-c-sku',
            'shippable'    => false,
            'taxable'      => false,
            'weight'       => 0,
            'totalWeight'  => 0,
            'uniqueId'     => 'item-c-sku',
            'customFields' => [
                [
                    'name'         => 'custom-field-a',
                    'displayValue' => 'Custom Field A',
                    'type'         => 'dropdown',
                    'value'        => 'Custom Field Value',
                ],
            ],
            'unitPrice' => 15,
            'totalPrice' => 15,
        ]);

        return $items;
    }

    public static function generateInvoiceNumber(): string
    {
        return 'SNIP-' . self::generateRandomString(6);
    }

    public static function generateRandomString($length = 10): string
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';

        for ($i = 0; $i < $length; $i++)
        {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}
