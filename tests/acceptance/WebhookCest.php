<?php

use workingconcept\snipcart\services\Webhooks;
use Codeception\Util\HttpCode;
use workingconcept\snipcart\helpers\TestHelper;

class WebhookCest
{
    const WEBHOOK_ENDPOINT = 'actions/snipcart/webhooks/handle';
    const GRAPHQL_ENDPOINT = 'api';

    public function _before(\ApiTester $I)
    {
    }

    public function testInvalidEvent(\ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST(self::WEBHOOK_ENDPOINT, [
            'eventName' => 'foo', // not real
            'mode'      => Webhooks::WEBHOOK_MODE_TEST,
            'createdOn' => date('c'),
            'content'   => TestHelper::generateOrder()
        ]);
        $I->seeResponseIsJson();
        $I->seeResponseCodeIs(HttpCode::BAD_REQUEST);
        $I->seeResponseContains('{"success":false,"errors":{"reason":"Invalid event."}}');
    }
}