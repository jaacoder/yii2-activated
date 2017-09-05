<?php

namespace Jaacoder\Yii2Activated\Controllers;

use Jaacoder\Yii2Activated\Helpers\TransactionHelper;

/**
 * Trait CommitTransactionTrait.
 *
 * @author jaacoder
 */
trait CommitTransactionTrait
{
    /**
     * Init auto response trait.
     */
    public function initCommitTransaction()
    {
        $this->on(static::EVENT_AFTER_ACTION, function() {
            TransactionHelper::commitTransactions();
        });
    }
    
}
