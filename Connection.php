<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-6-8
 * Time: 下午10:09
 */

namespace DBOperate;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use UnexpectedValueException;

/**
 * Class ModelBase
 * @package Lib
 */
class Connection implements ConnectionInterface
{
    public static function insert(Operate $insert): int
    {
        return self::modifyData($insert);
    }

    public static function update(Operate $update): int
    {
        return self::modifyData($update);
    }

    public static function delete(Operate $delete): int
    {
        return self::modifyData($delete);
    }

    public static function select(Operate $select, bool $singleRow = false):?array
    {
        $stmt   = self::execute($select->prepareStr(), $select->prepareValues());
        $result = $stmt->fetchAll();
        $stmt->closeCursor();
        return $singleRow ? ($result[0] ?? null) : $result;
    }

    private static function modifyData(Operate $operate): int
    {
        $stmt      = self::execute($operate->prepareStr(), $operate->prepareValues());
        $affectNum = $stmt->rowCount();
        $stmt->closeCursor();
        return $affectNum;
    }

    /**
     * @param string     $preStr
     * @param array|null $inputParams
     * @param int        $curExeTime
     * @param int        $maxReExeTimes
     *
     * @return PDOStatement
     */
    protected static function execute(
        string $preStr,
        array $inputParams,
        $curExeTime = 0,
        $maxReExeTimes = 2
    ): PDOStatement {
        $pdo = self::getPdo();
        try {
            $stmt     = $pdo->prepare($preStr);
            $exeState = $stmt->execute($inputParams);
            if ($exeState === true) {
                return $stmt;
            } else {
                $err['sql']   = $stmt->queryString;
                $err['input'] = $inputParams;
                if (!empty(self::$logger) && is_callable([self::$logger, 'error'])) {
                    self::$logger->error(json_encode($err));
                }
                throw new RuntimeException(json_encode($err));
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013 && ++$curExeTime < $maxReExeTimes) {
                self::getPdo(true);
                return self::execute($preStr, $inputParams, $curExeTime);
            } else {
                $err['statement'] = $preStr;
                $err['input']     = $inputParams;
                $err['exception'] = $e->errorInfo;
                if (!empty(self::$logger) && is_callable([self::$logger, 'error'])) {
                    self::$logger->error(json_encode($err));
                }
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw new RuntimeException(json_encode($err));
            }
        }
    }

    private static $transactionId;

    /**
     * 开启一个事务，成功返回事务ID；失败返回false
     * 只有凭相应的事务ID才可以关闭这个事务，解决事务嵌套问题
     * @return bool|int
     */
    public static function beginTransaction()
    {
        $pdo = self::getPdo();
        if (!$pdo->inTransaction()) {
            try {
                $beginStatus = $pdo->beginTransaction();
                if ($beginStatus) {
                    return self::$transactionId = uniqid();
                }
            } catch (PDOException $e) {
                return false;
            }
        }
        return false;
    }

    public static function commitTransaction($transactionId)
    {
        $pdo = self::getPdo();
        if (($transactionId !== false) && ($transactionId == self::$transactionId) && $pdo->inTransaction()) {
            return $pdo->commit();
        }
        return false;
    }

    public static function rollBackTransaction($transactionId)
    {
        $pdo = self::getPdo();
        if (($transactionId !== false) && ($transactionId == self::$transactionId) && $pdo->inTransaction()) {
            return $pdo->rollBack();
        }
        return false;
    }

    public static function getLastInsertId()
    {
        $pdo          = self::getPdo();
        $lastInsertId = $pdo->lastInsertId();
        return is_numeric($lastInsertId) ? ((int)$lastInsertId) : $lastInsertId;
    }

    public static function closeTransactionWhenRequestClose()
    {
        self::rollBackTransaction(self::$transactionId);
    }

    /**
     * @var PDO
     */
    private static $pdo;

    /**
     * @param bool $refreshConn
     *
     * @return PDO
     * @throws UnexpectedValueException|PDOException
     */
    public static function getPdo($refreshConn = false): PDO
    {
        $pdo = &self::$pdo;
        if (!$refreshConn && self::isPDOInstance($pdo)) {
            return $pdo;
        }
        $pdo = null;
        if (empty(self::$config)) {
            throw new UnexpectedValueException("self::\$config is null.");
        }
        $config     = self::$config;
        $dsn        = <<<TAG
{$config['driver']}:host={$config['host']};port={$config['port']};dbname={$config['db']};charset={$config['charset']}
TAG;
        $charset    = $config['charset'];
        $pdoOptions = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NAMED,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset",
            PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        try {
            $instance = new PDO($dsn, $config['user'], $config['pwd'], $pdoOptions);
            return $pdo = $instance;
        } catch (PDOException $e) {
            if (!empty(self::$logger) && is_callable([self::$logger, 'emergency'])) {
                self::$logger->emergency(json_encode($e->getMessage()));
            }
            throw $e;
        }
    }

    public static function setPdo(PDO $pdo)
    {
        self::$pdo = $pdo;
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
