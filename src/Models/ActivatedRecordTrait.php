<?php

namespace Jaacoder\Yii2Activated\Models;

trait ActivatedRecordTrait
{
    use AutoStartTransactionTrait;
    use MappingTrait;
    use QueryExtraTrait;
    use ColumnTrait;

    /**
     * Initialize traits.
     */
    public function activate()
    {
        $this->initAutoStartTransaction();
    }
}