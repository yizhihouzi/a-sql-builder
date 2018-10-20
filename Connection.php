<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-6-8
 * Time: 下午10:09
 */

namespace DBOperate;

use DBOperate\Exception\DBOperateException;
use DBOperate\Operate\Delete;
use DBOperate\Operate\Insert;
use DBOperate\Operate\Select;
use DBOperate\Operate\Update;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;

class Connection
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $_conn;
    /**
     * @var bool 处于事务中的查询是否默认使用悲观锁
     */
    private $selectForUpdateInTransaction;

    /**
     * Connection constructor.
     *
     * @param array              $connectionParams
     * @param Configuration|null $config
     *
     * @param bool               $selectForUpdateInTransaction
     *
     * @throws DBOperateException
     */
    public function __construct(
        array $connectionParams,
        ?Configuration $config = null,
        bool $selectForUpdateInTransaction = true
    ) {
        try {
            $this->_conn = DriverManager::getConnection($connectionParams, $config);
        } catch (DBALException $e) {
            throw new DBOperateException($e->getMessage());
        }
        $this->selectForUpdateInTransaction = $selectForUpdateInTransaction;
    }

    /**
     * 增加操作
     *
     * @param Insert $insert
     *
     * @return int
     * @throws Exception\DBOperateException
     */
    public function insert(Insert $insert)
    {
        return $this->modifyData($insert);
    }

    /**
     * 更新操作
     *
     * @param Update $update
     *
     * @return int
     * @throws Exception\DBOperateException
     */
    public function update(Update $update)
    {
        return $this->modifyData($update);
    }

    /**
     * 删除操作
     *
     * @param Delete $delete
     *
     * @return int
     * @throws Exception\DBOperateException
     */
    public function delete(Delete $delete)
    {
        return $this->modifyData($delete);
    }

    /**
     * 数据增、删、改
     *
     * @param Operate $operate
     *
     * @return  int
     * @throws Exception\DBOperateException
     */
    private function modifyData(Operate $operate)
    {
        try {
            return $this->_conn->executeUpdate($operate->prepareStr(), $operate->prepareValues());
        } catch (DBALException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    /**
     * 数据查询
     *
     * @param Select $select
     *
     *
     * @param bool   $selectForUpdateInTransaction
     *
     * @return array
     * @throws DBOperateException
     */
    public function select(Select $select, bool $selectForUpdateInTransaction = null)
    {
        $stmt   = $this->innerSelect($select, $selectForUpdateInTransaction);
        $result = $stmt->fetchAll();
        return $result;
    }

    /**
     * 数据查询
     *
     * @param Select    $select
     *
     * @param bool|null $selectForUpdateInTransaction
     *
     * @return array|null
     * @throws DBOperateException
     */
    public function selectSingle(Select $select, bool $selectForUpdateInTransaction = null)
    {
        $stmt   = $this->innerSelect($select, $selectForUpdateInTransaction);
        $result = $stmt->fetch(FetchMode::ASSOCIATIVE);
        return is_array($result) ? $result : null;
    }

    /**
     * @param Select    $select
     * @param bool|null $selectForUpdateInTransaction
     *
     * @return ResultStatement
     * @throws DBOperateException
     */
    private function innerSelect(Select $select, ?bool $selectForUpdateInTransaction = null)
    {
        if ($this->isTransactionActive()) {
            $select->forUpdate($selectForUpdateInTransaction ?? $this->selectForUpdateInTransaction);
        } else {
            $select->forUpdate(false);
        }
        try {
            /** @var ResultStatement $stmt */
            $stmt = $this->_conn->executeQuery($select->prepareStr(), $select->prepareValues());
        } catch (DBALException $e) {
            throw new DBOperateException($e->getMessage());
        }
        return $stmt;
    }

    /**
     * 开启事务
     */
    public function beginTransaction()
    {
        $this->_conn->beginTransaction();
    }


    /**
     * 回滚事务
     * @throws DBOperateException
     */
    public function rollback()
    {
        try {
            $this->_conn->rollBack();
        } catch (ConnectionException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    /**
     * 提交事务
     * @throws DBOperateException
     */
    public function commit()
    {
        try {
            $this->_conn->commit();
        } catch (ConnectionException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    /**
     * 当前连接是否存在事务
     * @return bool
     */
    public function isTransactionActive()
    {
        return $this->_conn->isTransactionActive();
    }

    /**
     * 是否必须回滚
     * @throws DBOperateException
     */
    public function isRollbackOnly()
    {
        try {
            return $this->_conn->isRollbackOnly();
        } catch (ConnectionException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    /**
     * 设置是否自动提交
     *
     * @param bool $autoCommit
     */
    public function setAutoCommit(bool $autoCommit)
    {
        $this->_conn->setAutoCommit($autoCommit);
    }

    /**
     * 打开数据库连接
     */
    public function connect()
    {
        $this->_conn->connect();
    }

    /**
     * 关闭数据库连接
     */
    public function close()
    {
        $this->_conn->close();
    }

    /**
     * @return string
     */
    public function getLastInsertId()
    {
        return $this->_conn->lastInsertId();
    }
}
