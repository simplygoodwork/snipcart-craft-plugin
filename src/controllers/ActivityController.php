<?php
/**
 * Snipcart plugin for Craft CMS 3.x
 *
 * @link      https://workingconcept.com
 * @copyright Copyright (c) 2018 Working Concept Inc.
 */

namespace workingconcept\snipcart\controllers;

use workingconcept\snipcart\Snipcart;
use workingconcept\snipcart\records\WebhookLog;
use Craft;

class ActivityController extends \craft\web\Controller
{

    /**
     * Displays logged webhooks.
     *
     * @return \yii\web\Response
     * @throws
     */
    public function actionIndex(): \yii\web\Response
    {
        $page = Craft::$app->getRequest()->getPageNum();
        $perPage = 20;
        $totalItems = WebhookLog::find()
            ->where(['siteId' => Craft::$app->sites->currentSite->id])
            ->count();

        return $this->renderTemplate(
            'snipcart/cp/activity/index',
            [
                'pageNumber' => $page,
                'totalItems' => $totalItems,
                'totalPages' => ceil($totalItems / $perPage),
                'requests' => Snipcart::$plugin->webhooks->getWebhookRequests(
                    $perPage,
                    $page
                )
            ]
        );
    }

    public function actionDetail(string $id): \yii\web\Response
    {
        return $this->renderTemplate(
            'snipcart/cp/activity/detail',
            [
                'request' => Snipcart::$plugin->webhooks->getWebhookRequestById(
                    $id
                )
            ]
        );
    }
}