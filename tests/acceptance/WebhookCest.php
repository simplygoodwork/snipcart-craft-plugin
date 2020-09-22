<?php

use workingconcept\snipcart\controllers\WebhooksController;
use workingconcept\snipcart\Snipcart;
use workingconcept\snipcart\services\Webhooks;
use workingconcept\snipcart\helpers\TestHelper;
use Codeception\Util\HttpCode;
use craft\web\Request;
use yii\web\JsonResponseFormatter;
use yii\base\InvalidConfigException;
use yii\web\Response;
use yii\web\ResponseFormatterInterface;
use Yii;

class WebhookCest
{
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    /**
     * @var WebhooksController
     */
    protected $webhooksController;

    /**
     * @var Request
     */
    protected $request;

    public function _before(\ApiTester $I)
    {
        /**
         * Use devMode to skip webhook validation and fake ShipStation
         * order forwarding.
         */
        Craft::$app->config->getGeneral()->devMode = true;

        // get a controller instance
        $this->webhooksController = new WebhooksController(
            'webhooks',
            Snipcart::getInstance()
        );

        // prep a request object
        $this->request = Craft::$app->getRequest();
    }

    /**
     * API should only accept `eventName` types we know about.
     *
     * @param  \ApiTester  $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testInvalidEvent(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => 'foo', // not real
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals($response->content, '{"success":false,"errors":{"reason":"Invalid event."}}');
    }

    /**
     * Webhooks can must be posted as `'Live'` or `'Test'`.
     *
     * @param  \ApiTester  $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testInvalidMode(\ApiTester $I)
    {
        // test mode
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::OK);

        // live mode
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_LIVE,
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::OK);

        // invalid mode
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => 'Special', // not 'Live' or 'Test'
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals(
            '{"success":false,"errors":{"reason":"Invalid mode."}}',
            $response->content
        );
    }

    /**
     * Webhooks must be posted with a `mode` parameter.
     *
     * @param  \ApiTester  $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testMissingMode(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals(
            '{"success":false,"errors":{"reason":"Request missing mode."}}',
            $response->content
        );
    }

    /**
     * Webhooks must have a `content` parameter.
     *
     * @param  \ApiTester  $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testEmptyContent(\ApiTester $I)
    {
        // `null` content
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => null
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals(
            '{"success":false,"errors":{"reason":"Request missing content."}}',
            $response->content
        );

        // missing content
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals(
            '{"success":false,"errors":{"reason":"Request missing content."}}',
            $response->content
        );

        // empty content
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => ''
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals(
            '{"success":false,"errors":{"reason":"Request missing content."}}',
            $response->content
        );

        // empty content
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => []
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->getStatusCode(), HttpCode::BAD_REQUEST);
        $I->assertEquals(
            '{"success":false,"errors":{"reason":"Request missing content."}}',
            $response->content
        );
    }

    /**
     * TODO: figure out how to mock Snipcart API response since we don’t have credentials in test
     */
//    public function testBadToken()
//    {
//    }

    /**
     * TODO: figure out how to mock Snipcart API response since we don’t have credentials in test
     */
//    public function testNonStringToken()
//    {
//    }

    /**
     * Extraneous/unexpected payload properties should be stripped away and
     * not throw exceptions when they don’t map to a plugin model.
     *
     * Each unexpected property should be discarded when the model is
     * populated instead of throwing an exception.
     *
     * @param  ApiTester  $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testUnknownPayloadProperties(\ApiTester $I)
    {
        $order = TestHelper::generateOrder();

        $order['nonExistentOrderProperty'] = 'foo!';
        $order['nonExistentOrderPropertyTwo'] = 'foo!';
        $order['items'][0]['nonExistentItemProperty'] = 'foo!';

        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_ORDER_COMPLETED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => $order
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * The `shippingrates.fetch` webhook should respond with `rates` and
     * `package` keys, even if they’re both empty.
     *
     * @param  \ApiTester  $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testFetchShippingRates(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_SHIPPINGRATES_FETCH,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);

        $I->assertStringContainsString(
            '"rates":',
            $response->content
        );

        $I->assertStringContainsString(
            '"package":',
            $response->content
        );
    }

//    public function testUnshippableOrder()
//    {
//    }

//    public function testInvalidCountryRates()
//    {
//    }

//    public function testOrderCompletion()
//    {
//    }

//    public function testMatrixProductOrderCompletion()
//    {
//    }

    /**
     * Test `order.status.changed` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testOrderStatusChanged(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'from'      => 'Disputed',
            'to'        => 'Shipped',
            'eventName' => WebhooksController::WEBHOOK_ORDER_STATUS_CHANGED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `order.paymentStatus.changed` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testOrderPaymentStatusChanged(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'from'      => 'Authorized',
            'to'        => 'Paid',
            'eventName' => WebhooksController::WEBHOOK_ORDER_PAYMENT_STATUS_CHANGED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `order.trackingNumber.changed` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testOrderTrackingNumberChanged(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'trackingNumber' => '12345',
            'trackingUrl'    => 'https://fedex.com/12345',
            'eventName'      => WebhooksController::WEBHOOK_ORDER_TRACKING_NUMBER_CHANGED,
            'mode'           => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn'      => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'        => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `subscription.created` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testSubscriptionCreated(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_SUBSCRIPTION_CREATED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateSubscription()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `subscription.cancelled` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testSubscriptionCancelled(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_SUBSCRIPTION_CANCELLED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateSubscription()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `subscription.cancelled` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testSubscriptionPaused(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_SUBSCRIPTION_CANCELLED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateSubscription()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `subscription.resumed` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testSubscriptionResumed(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_SUBSCRIPTION_RESUMED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateSubscription()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `subscription.invoice.created` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testSubscriptionInvoiceCreated(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_SUBSCRIPTION_INVOICE_CREATED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateSubscription()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `taxes.calculate` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testTaxesCalculate(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_TAXES_CALCULATE,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateOrder()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `customauth:customer_updated` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testCustomerUpdated(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_CUSTOMER_UPDATED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => TestHelper::generateCustomer()
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `order.refund.created` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testRefundCreated(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_REFUND_CREATED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'        => [
                'orderToken' => '62b31459-919e-4e6a-9c5d-764f5739cb9f',
                'amount' => 29.75,
                'comment' => 'Requested by customer.',
                'notifiedCustomerByEmail' => true,
                'currency' => 'USD'
            ]
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * Test `order.notification.created` webhook.
     *
     * @param ApiTester $I
     * @throws \yii\base\InvalidRouteException
     */
    public function testNotificationCreated(\ApiTester $I)
    {
        $response = $this->postDataAsJson([
            'eventName' => WebhooksController::WEBHOOK_REFUND_CREATED,
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'), // "2018-12-05T18:43:22.2419667Z"
            'content'   => [
                'notificationType' => 'Comment',
                'sentByEmailOn' => null,
                'sentByEmail' => false,
                'orderToken' => '2e8fbc93-6a20-48f1-ad39-6797a61730b5',
                'body' => 'notification body',
                'message' => '<p>Backorder is expected on September 1st.</p>',
                'resourceUrl' => 'https://foo.bar/',
                'subject' => 'notification subject'
            ]
        ]);

        $I->assertSame($response->format, Response::FORMAT_JSON);
        $I->assertSame($response->statusCode, HttpCode::OK);
    }

    /**
     * @param $data
     *
     * @return mixed|\yii\web\Response|null
     * @throws \yii\base\InvalidRouteException
     */
    private function postDataAsJson($data)
    {
        $this->request->getHeaders()->set('Accept', 'application/json');
        $this->request->getHeaders()->set('X-Http-Method-Override', 'POST');
        $this->request->setRawBody(json_encode($data));

        $response = $this->webhooksController->runAction('handle');

        // cheap version of Response::prepare()
        if (
            $response->content === null &&
            ! empty($response->data) &&
            $response->format === Response::FORMAT_JSON
        )
        {
            $formatter = new JsonResponseFormatter();
            $formatter->format($response);
        }

        return $response;
    }
}
