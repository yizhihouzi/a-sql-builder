<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2018/8/13
 * Time: 15:51
 */

namespace DBOperate\Tests;

use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\Connection;
use DBOperate\Exception\DBOperateException;
use DBOperate\Operate\Delete;
use DBOperate\Operate\Insert;
use DBOperate\Operate\Select;
use DBOperate\Operate\Update;
use DBOperate\Table;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{

    /**
     * @throws DBOperateException
     */
    public function testTransactionNest()
    {
        $conn = new Connection(DB_URI);
        self::assertFalse($conn->isTransactionActive());
        $conn->beginTransaction();
        $conn->beginTransaction();
        self::assertTrue($conn->isTransactionActive());
        self::assertFalse($conn->isRollbackOnly());
        $conn->rollback();
        self::assertTrue($conn->isRollbackOnly());
        $this->expectException(DBOperateException::class);
        $conn->commit();
    }

    /**
     * @throws DBOperateException
     */
    public function testInsert()
    {
        $conn   = new Connection(DB_URI);
        $insert = new Insert(new Table('t'));
        $insert->setInsertValues(['content'], [1], [2], [3]);
        $affNum = $conn->insert($insert);
        self::assertEquals(3, $affNum);
        return $conn;
    }

    /**
     * @depends testInsert
     *
     * @param Connection $conn
     *
     * @return int
     * @throws DBOperateException
     */
    public function testSelect(Connection $conn)
    {
        $select = new Select(new Table('t'));
        $select->where(new Condition(new Column('id'), 1, '>'));
        $select->orderBy(new Column('id'), false);
        $select->fetchCols(new Column('content'), new Column('id'));
        $row = $conn->select($select, true);
        self::assertArrayHasKey('content', $row);
        self::assertArrayHasKey('id', $row);
        return $row['id'];
    }

    /**
     * @depends testSelect
     *
     * @param $id
     *
     * @return int
     * @throws DBOperateException
     */
    public function testUpdate(int $id)
    {
        $conn     = new Connection(DB_URI);
        $update   = new Update(new Table('t'));
        $content2 = 'hhh';
        $update->setColumn(new Column('content'), $content2);
        $update->where(new Condition(new Column('id'), $id));
        $affNum = $conn->update($update);
        self::assertEquals(1, $affNum);
        return $id;
    }

    /**
     * @param int $id
     *
     * @depends testUpdate
     * @throws DBOperateException
     */
    public function testDelete(int $id)
    {
        $conn   = new Connection(DB_URI);
        $delete = new Delete(new Table('t'));
        $delete->where(new Condition(new Column('id'), $id));
        $affNum = $conn->delete($delete);
        self::assertEquals(1, $affNum);
    }
}
