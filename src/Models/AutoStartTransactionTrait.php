<?php

namespace Jaacoder\Yii2Activated\Models;

use yii\base\ModelEvent;
use yii\db\ActiveRecord;

trait AutoStartTransactionTrait
{
    /**
     * Config model to start transaction automatically
     */
    public function initAutoStartTransaction()
    {
        $handler = function(ModelEvent $modelEvent) {
            if ($modelEvent->isValid) {
                $this->requireTransaction();
            }
        };

        $this->on(ActiveRecord::EVENT_BEFORE_INSERT, $handler);
        $this->on(ActiveRecord::EVENT_BEFORE_UPDATE, $handler);
        $this->on(ActiveRecord::EVENT_BEFORE_DELETE, $handler);
    }

    /**
     * Ensure a transaction is active.
     */
    public function requireTransaction()
    {
        if ($this->getDb()->transaction === null || !$this->getDb()->transaction->isActive) {
            \Yii::info('Begin Transaction');
            $this->getDb()->beginTransaction();
        }
    }

}
