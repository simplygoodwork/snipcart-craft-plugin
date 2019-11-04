<?php
/**
 * Snipcart plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */
namespace workingconcept\snipcart\console\controllers;

use workingconcept\snipcart\providers\ShipStation;

use Craft;
use craft\helpers\DateTimeHelper;
use workingconcept\snipcart\Snipcart;
use workingconcept\snipcart\models\Order;
use yii\console\Controller;
use yii\console\ExitCode;

class VerifyController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Checks that most recent orders exist in Snipcart and ShipStation.
     * Sends notifications if any are missing.
     *
     * @param bool $forceFeed  forcefully re-send missing orders
     *
     * @return int
     * @throws
     */
    public function actionCheckOrders($forceFeed = false): int
    {
        $startTime    = microtime(true);
        $limit        = 3;
        $failedOrders = [];
        
        $this->stdout('-------------------------------------' . PHP_EOL);
        $this->stdout("Checking last $limit orders..." . PHP_EOL);
        $this->stdout('-------------------------------------' . PHP_EOL);

        // TODO: only get *shippable* orders!
        $orders = Snipcart::$plugin->orders->getOrders([
            'limit' => $limit, 
            'cache' => false 
        ]);

        foreach ($orders as $order)
        {
            $this->stdout("Snipcart $order->invoiceNumber … ");
            $shipStationStatusString = '✓';

            if ( ! Snipcart::$plugin->shipments->
                        shipStation->
                        getOrderBySnipcartInvoice($order->invoiceNumber)
            )
            {
                $shipStationStatusString = '✗';
                $failedOrders[] = $order;
            }

            $this->stdout("ShipStation [$shipStationStatusString]" . PHP_EOL);
        }

        if (count($failedOrders) > 0)
        {
            $reFeedResults = $this->_reFeedToShipStation(
                $failedOrders,
                $forceFeed
            );

            $this->_sendAdminNotification($failedOrders, $reFeedResults);
        }

        $this->stdout('-------------------------------------' . PHP_EOL);

        $endTime       = microtime(true);
        $executionTime = ($endTime - $startTime);

        $this->stdout("Finished in ${executionTime} seconds." . PHP_EOL . PHP_EOL);

        return ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    /**
     * Try re-feeding missing orders into ShipStation.
     *
     * @param Order[] $orders
     * @param bool    $force
     *
     * @return array  If attempts were made to re-send the orders to
     *                ShipStation, they'll be in this array where the key is the
     *                invoice number and the value is true if successful.
     * @throws
     */
    private function _reFeedToShipStation($orders, $force): array
    {
        $reFeedResult = [];
        $minuteLimit = Snipcart::$plugin->getSettings()->reFeedAttemptWindow;

        foreach ($orders as $order)
        {
            // try again, but only briefly or if forced
            if ($force || DateTimeHelper::isWithinLast(
                $order->creationDate,
                $minuteLimit . ' minutes')
            )
            {
                $this->stdout('-------------------------------------' . PHP_EOL);
                $this->stdout(sprintf(
                    'Re-sending order %s to ShipStation … ',
                    $order->invoiceNumber
                ));

                $result = Snipcart::$plugin->shipments->shipStation->createOrder($order);
                $succeeded = isset($result->orderId) && empty($result->getErrors());
                $wasTest = $succeeded && $result->orderId === ShipStation::TEST_ORDER_ID;

                // log failure for troubleshooting
                Craft::error(sprintf(
                    'ShipStation re-feed failed: %s',
                    implode(', ', $result->getErrors())
                ), 'snipcart');

                $statusString = $succeeded ? '✓' : '✗';

                if ($wasTest)
                {
                    $statusString .= ' (test)';
                }

                $this->stdout($statusString . PHP_EOL);

                $reFeedResult[$order->invoiceNumber] = $succeeded;
            }
        }

        // TODO: re-verify and report result

        return $reFeedResult;
    }

    /**
     * Let somebody know that one or more orders didn't make it to ShipStation.
     *
     * @param Order[] $snipcartOrders
     * @param array   $reFeedResults
     *
     * @return int
     * @throws
     */
    private function _sendAdminNotification($snipcartOrders, $reFeedResults): int
    {
        Snipcart::$plugin->notifications->setEmailTemplate(
            'snipcart/email/recovery'
        );

        Snipcart::$plugin->notifications->setNotificationVars([
            'orders'    => $snipcartOrders,
            'reattempt' => $reFeedResults,
        ]);

        $toEmails = Snipcart::$plugin->getSettings()->notificationEmails;
        $subject  = 'Recovered Snipcart Orders';

        if ( ! Snipcart::$plugin->notifications->sendEmail($toEmails, $subject))
        {
            $this->stderr('Notifications failed.'. PHP_EOL);
        }

        return ExitCode::OK;
    }

}