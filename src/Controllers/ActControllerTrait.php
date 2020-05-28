<?php

namespace Jaacoder\Yii2Activated\Controllers;

use yii\base\Response;

trait ActControllerTrait
{
    use CommitTransactionTrait;
    use MessagesTrait;
    use RequestTrait;
    use RouteTrait;

    /**
     * Initialize traits.
     * 
     * @param array $config
     */
    public function activate()
    {
        $this->initCommitTransaction();
        $this->initMessages();
    }
}
