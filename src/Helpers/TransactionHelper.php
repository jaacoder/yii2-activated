<?php

namespace Jaacoder\Yii2Activated\Helpers;

use Yii;
use yii\db\Connection;

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

                $connection = Yii::$app->get($name);
                if (is_object($connection) && $connection instanceof Connection && $connection->isActive) {
                    $connections[] = $connection;
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
