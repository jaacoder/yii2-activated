<?php

namespace Jaacoder\Yii2Activated\Models;

use Jaacoder\Yii2Activated\Helpers\MetaTrait;

trait ActRecordTrait
{
    use MetaTrait;
    use ColumnTrait;
    use MappingTrait;
    use QueryExtraTrait;
    use AutoStartTransactionTrait;
    use UpdateExceptionTrait;

    /**
     * Initialize traits.
     */
    public function activate()
    {
        $this->initAutoStartTransaction();
    }
}