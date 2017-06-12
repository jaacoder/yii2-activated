<?php

namespace Jaacoder\Yii2Activated\Controllers\Rest;

use Jaacoder\Yii2Activated\Helpers\AppException;
use Jaacoder\Yii2Activated\Helpers\TransactionHelper;

/**
 * Description of ErrorHandler
 */
class ErrorHandler extends \yii\web\ErrorHandler
{
    public function handleException($exception)
    {
        // rollback all active db transactions
        TransactionHelper::rollbackTransactions();
        
        if ($exception instanceof AppException) {
            print json_encode(['messages' => $exception->messages]);
            exit();
            //
        } else {
            return parent::handleException($exception);
        }
    }
}
