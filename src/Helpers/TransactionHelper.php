<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Yii;
use yii\db\Connection;

/**
 * Class TransactionHelper.
 */
class TransactionHelper
{
    /**
     * Return all active db connections.
     * 
     * @staticvar array $connections
     * @return Connection[]
     */
    public static function getConnections()
    {
        static $connections = null;
        
        if ($connections === null) {
            $connections = [];
            
            foreach (Yii::$app->getComponents() as $name => $component) {
                if (!is_array($component) || !isset($component['class'])) {
                    continue;
                }

                if (is_a($component['class'], Connection::className(), /* $allow_string = */ true)) {
                    $connection = Yii::$app->get($name);
                    
                    /* @var $connection Connection */
                    
                    if ($connection !== null && $connection->isActive) {
                        $connections[] = $connection;
                    }
                }
            }
        }
        
        return $connections;
    }
    
    /**
     * Commit all active db transactions.
     */
    public static function commitTransactions()
    {
        foreach (static::getConnections() as $connection) {
            if ($connection->transaction === null || !$connection->transaction->isActive) {
                continue;
            }
            
            $connection->transaction->commit();
        }
    }
    
    /**
     * Rollback all active db transactions.
     */
    public static function rollbackTransactions()
    {
        foreach (static::getConnections() as $connection) {
            if ($connection->transaction === null || !$connection->transaction->isActive) {
                continue;
            }
            
            $connection->transaction->rollBack();
        }
    }
}
