<?php

namespace Jaacoder\Yii2Activated\Controllers;

use Jaacoder\Yii2Activated\Helpers\TransactionHelper;
use yii\base\Event;
use yii\web\Application;
use yii\web\Controller;
use yii\web\Response;

trait CommitTransactionTrait
{
    /**
     * Init auto response trait.
     */
    public function initCommitTransaction()
    {
        // add event handler to commit transactions
        $this->on(Controller::EVENT_AFTER_ACTION, function() {
            TransactionHelper::commitTransactions();
        });

        // add event handler to rollback transactions
        \Yii::$app->response->on(Response::EVENT_BEFORE_SEND, function(Event $event) {
            if (\Yii::$app->errorHandler->exception !== null)
                TransactionHelper::rollbackTransactions();
        });
    }
    
}
