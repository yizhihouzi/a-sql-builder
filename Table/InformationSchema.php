<?php
/**
 * Created by PhpStorm.
 * User: arvin
 * Date: 17-9-19
 * Time: 下午5:23
 */

namespace DBOperate\Table;

use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\Connection;
use DBOperate\Operate\Select;
use DBOperate\Table;

class InformationSchema
{
    public static function getTables($schemaName = null)
    {
        if (empty($schemaName)) {
            $schemaName = Connection::getSchemaName();
        }
        $table           = new Table('Information_schema`.`columns');
        $select          = new Select($table);
        $tableNameColumn = new Column('TABLE_NAME', $table);
        $select->fetchCols($tableNameColumn);
        $select->groupBy($tableNameColumn);
        $select->where(new Condition(new Column('TABLE_SCHEMA', $table), $schemaName));
        $rows = Connection::select($select);
        return array_column($rows, 'TABLE_NAME');
    }

    public static function getTableCols($tableName, $schemaName = null)
    {
        if (empty($schemaName)) {
            $schemaName = Connection::getSchemaName();
        }
        $table  = new Table('Information_schema`.`columns');
        $select = new Select($table);
        $select->fetchCols(new Column('COLUMN_NAME', $table));
        $condition1 = new Condition(new Column('table_schema', $table), $schemaName);
        $condition2 = new Condition(new Column('table_name', $table), $tableName);
        $select->where($condition1, $condition2);
        $rows = Connection::select($select);
        if (!is_array($rows)) {
            return false;
        }
        return array_column($rows, 'COLUMN_NAME');
    }
}
