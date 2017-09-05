<?php

namespace Jaacoder\Yii2Activated\Models;

/**
 * Class TransactionHandlerTrait.
 */
trait AutoStartTransactionTrait
{
    
    public function initAutoStartTransaction()
    {
        $handler = function() {
            $this->requireTransaction();
        };
        
        $this->on(\yii\db\ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        $this->on(\yii\db\ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        $this->on(\yii\db\ActiveRecord::EVENT_BEFORE_DELETE, $handler);
    }
    
    /**
     * Ensure a transaction is active.
     */
    public function requireTransaction()
    {
        if ($this->getDb()->transaction === null || !$this->getDb()->transaction->isActive) {
            $this->getDb()->beginTransaction();
        }
    }
}
