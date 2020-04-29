<?php

namespace Jaacoder\Yii2Activated\Controllers;

use yii\base\Response;

trait ActivatedControllerTrait
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
    public function activate($config = ['messagesProperty' => null])
    {
        $this->initCommitTransaction();

        if (isset($config['messagesProperty'])) {
            $this->initMessages($config['messagesProperty']);

        } else {
            $this->initMessages();
        }
    }
}
