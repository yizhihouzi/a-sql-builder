<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-6-8
 * Time: 下午10:09
 */

namespace DBOperate;

use DBOperate\Exception\DBOperateException;
use PDO;
use PDOException;
use PDOStatement;
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
                throw new DBOperateException(json_encode($err));
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
                throw new DBOperateException(json_encode($err));
            }
        }
    }

    /**
     * @var string|null
     */
    private static $transactionId;

    /**
     * 开启一个事务，成功返回事务ID；失败返回false
     * 只有凭相应的事务ID才可以关闭这个事务，解决事务嵌套问题
     * @return null|string
     */
    public static function beginTransaction():?string
    {
        $pdo = self::getPdo();
        if (!$pdo->inTransaction()) {
            $beginStatus = $pdo->beginTransaction();
            if ($beginStatus) {
                return self::$transactionId = uniqid();
            }
        }
        return null;
    }

    public static function commitTransaction($transactionId): bool
    {
        if (($transactionId !== null) && ($transactionId === self::$transactionId)) {
            $pdo = self::getPdo();
            if ($pdo->inTransaction()) {
                $status = $pdo->commit();
                if ($status) {
                    self::$transactionId = null;
                }
                return $status;
            }
        }
        return false;
    }

    public static function rollBackTransaction($transactionId): bool
    {
        if (($transactionId !== null) && ($transactionId === self::$transactionId)) {
            $pdo = self::getPdo();
            if ($pdo->inTransaction()) {
                $status = $pdo->rollBack();
                if ($status) {
                    self::$transactionId = null;
                }
                return $status;
            }
        }
        return false;
    }

    public static function getLastInsertId()
    {
        $pdo          = self::getPdo();
        $lastInsertId = $pdo->lastInsertId();
        return is_numeric($lastInsertId) ? ((int)$lastInsertId) : $lastInsertId;
    }

    public static function forceCloseTransaction(): bool
    {
        $pdo = self::getPdo();
        if ($pdo->inTransaction()) {
            return $pdo->rollBack();
        }
        return false;
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

    public static function setPdo(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    protected static function isPdoStatement($stmt): bool
    {
        return $stmt instanceof PDOStatement;
    }

    protected static function isPDOInstance($pdo): bool
    {
        return $pdo instanceof PDO;
    }

    private static $logger = null;

    public static function setLogger($logger): void
    {
        self::$logger = $logger;
    }


    private static $config;

    /**
     * @param mixed $config
     */
    public static function setConfig(array $config): void
    {
        if (!(is_string($config['driver'] ?? false) && is_string($config['host'] ?? false) && is_string($config['port'] ?? false) && is_string($config['db'] ?? false) && is_string($config['charset'] ?? false) && is_string($config['user'] ?? false) && is_string($config['pwd'] ?? false))) {
            self::$config = $config;
        }
    }

    public static function getSchemaName(): ?string
    {
        return self::$config['db'] ?? null;
    }
}
