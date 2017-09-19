<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-6-8
 * Time: 下午10:09
 */

namespace DBOperate;

use PDO;
use PDOStatement;

/**
 * Class ModelBase
 * @package Lib
 */
class Connection implements ConnectionInterface
{
    public static function insert(string $preStr, array $inputParams)
    {
        return self::modifyData($preStr, $inputParams);
    }

    public static function delete(string $preStr, array $inputParams)
    {
        return self::modifyData($preStr, $inputParams);
    }

    public static function update(string $preStr, array $inputParams)
    {
        return self::modifyData($preStr, $inputParams);
    }

    public static function select(string $preStr, array $inputParams)
    {
        $stmt = self::execute($preStr, $inputParams);
        if (self::isPdoStatement($stmt)) {
            $rows = $stmt->fetchAll();
            $stmt->closeCursor();
            return $rows;
        } else {
            return false;
        }
    }

    private static function modifyData(string $preStr, array $inputParams)
    {
        $stmt = self::execute($preStr, $inputParams);
        if (self::isPdoStatement($stmt)) {
            $affectNum = $stmt->rowCount();
            $stmt->closeCursor();
            return (int)$affectNum;
        } else {
            return false;
        }
    }

    /**
     * @param string $preStr
     * @param array|null $inputParams
     * @param int $curExeTime
     * @param int $maxReExeTimes
     *
     * @return bool|PDOStatement
     */
    protected static function execute(
        string $preStr,
        array $inputParams,
        $curExeTime = 0,
        $maxReExeTimes = 2
    )
    {
        $pdo = self::getPdo();
        if (!self::isPDOInstance($pdo)) {
            return false;
        }
        try {
            $stmt = @$pdo->prepare($preStr);
            $exeState = $stmt->execute($inputParams);
            if ($exeState === true) {
                return $stmt;
            } else {
                $err['sql'] = $stmt->queryString;
                $err['input'] = $inputParams;
            }
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013 && ++$curExeTime < $maxReExeTimes) {
                $pdo = self::getPdo(true);
                if (self::isPDOInstance($pdo)) {
                    return self::execute($preStr, $inputParams, $curExeTime);
                }
            }
            $err['statement'] = $preStr;
            $err['input'] = $inputParams;
            $err['exception'] = $e->errorInfo;
        }var_dump($err);
        if (!empty(self::$logger)) {
//            self::$logger->log(json_encode($err), Logger::ERROR);
        }
        return false;
    }

    private static $transactionId;

    /**
     * 开启一个事务，成功返回事务ID；失败返回false
     * 只有凭相应的事务ID才可以关闭这个事务，解决事务嵌套问题
     * @return bool|int
     */
    protected static function beginTransaction()
    {
        $pdo = self::getPdo();
        if (self::isPDOInstance($pdo) && !$pdo->inTransaction()) {
            try {
                $beginStatus = $pdo->beginTransaction();
                if ($beginStatus) {
                    return self::$transactionId = uniqid();
                }
            } catch (\PDOException $e) {
                return false;
            }
        }
        return false;
    }

    protected static function commitTransaction($transactionId)
    {
        $pdo = self::getPdo();
        if (($transactionId == self::$transactionId) && self::isPDOInstance($pdo) && $pdo->inTransaction()) {
            return $pdo->commit();
        }
        return false;
    }

    protected static function rollBackTransaction($transactionId)
    {
        $pdo = self::getPdo();
        if (($transactionId == self::$transactionId) && self::isPDOInstance($pdo) && $pdo->inTransaction()
        ) {
            return $pdo->rollBack();
        }
        return false;
    }

    protected static function getLastInsertId()
    {
        $pdo = self::getPdo();
        if (self::isPDOInstance($pdo)) {
            $lastInsertId = $pdo->lastInsertId();
            return is_numeric($lastInsertId) ? ((int)$lastInsertId) : $lastInsertId;
        }
        return false;
    }

    public static function closeTransactionWhenRequestClose()
    {
        self::rollBackTransaction(self::$transactionId);
    }

    private static $pdo;

    protected static function getPdo($refreshConn = false)
    {
        $pdo = &self::$pdo;
        if (!$refreshConn && self::isPDOInstance($pdo)) {
            return $pdo;
        }
        $pdo = null;
        if (empty(self::$config)) {
            throw new \UnexpectedValueException("self::\$config is null.");
        }
        $config = self::$config;
        $dsn = <<<TAG
{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['db']};charset={$config['charset']}
TAG;
        $charset = $config['charset'];
        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NAMED,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        try {
            $instance = new PDO($dsn, $config['user'], $config['pwd'], $pdoOptions);
            return $pdo = $instance;
        } catch (\PDOException $e) {
            if (!empty(self::$logger)) {
//                self::$logger->log(json_encode($e->getMessage()), Logger::EMERGENCY);
            }
            return false;
        }
    }

    protected static function isPdoStatement($stmt)
    {
        return $stmt instanceof PDOStatement;
    }

    protected static function isPDOInstance($pdo)
    {
        return $pdo instanceof PDO;
    }

    private static $logger = null;

    public static function setLogger($logger)
    {
        self::$logger = $logger;
    }


    private static $config;

    /**
     * @param mixed $config
     */
    public static function setConfig($config)
    {
        self::$config = $config;
    }

    public static function getSchemaName()
    {
        return self::$config['db'];
    }
}
