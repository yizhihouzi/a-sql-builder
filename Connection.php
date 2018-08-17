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
     * Connection constructor.
     *
     * @param string             $mysqlUri
     *
     * @param Configuration|null $config
     *
     * @throws DBOperateException
     */
    public function __construct(string $mysqlUri, ?Configuration $config = null)
    {
        $connectionParams = ['url' => $mysqlUri];
        try {
            $this->_conn = DriverManager::getConnection($connectionParams, $config);
        } catch (DBALException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    /**
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
     * @param Operate $operate
     *
     * @return  int
     * @throws Exception\DBOperateException
     */
    private function modifyData(Operate $operate)
    {
        if (!$this->_conn->ping()) {
            $this->_conn->close();
        }
        try {
            return $this->_conn->executeUpdate($operate->prepareStr(), $operate->prepareValues());
        } catch (DBALException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    /**
     * @param Select $select
     * @param bool   $singleRow
     *
     * @return array|bool
     * @throws DBOperateException
     */
    public function select(Select $select, bool $singleRow = false)
    {
        if (!$this->_conn->ping()) {
            $this->_conn->close();
        }
        try {
            /** @var ResultStatement $stmt */
            $stmt = $this->_conn->executeQuery($select->prepareStr(), $select->prepareValues());
        } catch (DBALException $e) {
            throw new DBOperateException($e->getMessage());
        }
        if (!$singleRow) {
            $result = $stmt->fetchAll();
        } else {
            $result = $stmt->fetch(FetchMode::ASSOCIATIVE);
        }
        return $result;
    }

    public function beginTransaction()
    {
        $this->_conn->beginTransaction();
    }


    /**
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

    public function isTransactionActive()
    {
        return $this->_conn->isTransactionActive();
    }

    /**
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

    public function setAutoCommit(bool $autoCommit)
    {
        $this->_conn->setAutoCommit($autoCommit);
    }

    /**
     * @return string
     */
    public function getLastInsertId()
    {
        return $this->_conn->lastInsertId();
    }
}
