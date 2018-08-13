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
    private $conn;

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
            $this->conn = DriverManager::getConnection($connectionParams, $config);
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
        try {
            return $this->conn->executeUpdate($operate->prepareStr(), $operate->prepareValues());
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
        /** @var ResultStatement $stmt */
        try {
            $stmt = $this->conn->executeQuery($select->prepareStr(), $select->prepareValues());
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
        $this->conn->beginTransaction();
    }


    /**
     * @throws DBOperateException
     */
    public function rollback()
    {
        try {
            $this->conn->rollBack();
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
            $this->conn->commit();
        } catch (ConnectionException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    public function isTransactionActive()
    {
        return $this->conn->isTransactionActive();
    }

    /**
     * @throws DBOperateException
     */
    public function isRollbackOnly()
    {
        try {
            return $this->conn->isRollbackOnly();
        } catch (ConnectionException $e) {
            throw new DBOperateException($e->getMessage());
        }
    }

    public function setAutoCommit(bool $autoCommit)
    {
        $this->conn->setAutoCommit($autoCommit);
    }

    /**
     * @return string
     */
    public function getLastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}
