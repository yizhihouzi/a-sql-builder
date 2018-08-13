<?php
/**
 * Created by PhpStorm.
 * User: yizhihouzi
 * Date: 2018/8/13
 * Time: 14:38
 */

namespace Test;

use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\Exception\DBOperateException;
use PHPUnit\Framework\TestCase;

class ConditionTest extends TestCase
{

    public function testEqual()
    {
        $c1 = new \DBOperate\Condition(
            new Column('t1'),
            '4565',
            '='
        );
        self::assertEquals('t1 = ?', (string)$c1);
    }

    public function testIn()
    {
        try {
            new Condition(new Column('col1'), 'jfjd', 'in');
        } catch (\Exception $e) {
            self::assertTrue($e instanceof DBOperateException);
        }
        $c1 = new Condition(new Column('col1'), [1, 2, 3], 'in');
        self::assertEquals('col1 in (?,?,?)', (string)$c1);
        $c2 = new Condition(new Column('col1'), ['kk', 'fd', 'kk'], 'in');
        self::assertEquals('col1 in (?,?,?)', (string)$c2);
    }

    public function testNull()
    {
        $c1 = new Condition(new Column('col1'), null, '=');
        self::assertEquals('col1 is null ', (string)$c1);
        $c2 = new Condition(new Column('col1'), null, '><');
        self::assertEquals('col1 is not null ', (string)$c2);
    }

    public function testGreater()
    {
        $c1 = new Condition(new Column('col1'), 3, '>');
        self::assertEquals('col1 > ?', (string)$c1);
        $c1 = new Condition(new Column('col1'), new Column('col2'), '>');
        self::assertEquals('col1 > col2', (string)$c1);
    }

    public function testLess()
    {
        $c1 = new Condition(new Column('col1'), 3, '<');
        self::assertEquals('col1 < ?', (string)$c1);
        $c1 = new Condition(new Column('col1'), new Column('col2'), '<');
        self::assertEquals('col1 < col2', (string)$c1);
    }
}
