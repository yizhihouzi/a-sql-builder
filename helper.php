<?php
use DBOperate\Column;
use DBOperate\Condition;
use DBOperate\Operate\Select;
use DBOperate\Table;
use DBOperate\Table\InformationSchema;
use DBOperate\Connection;

require __DIR__ . '/autoload.php';
$config = [
    'charset' => 'utf8'
];
Connection::setConfig($config);
$options   = (getopt('d:n:'));
$tablesDir = !empty($options['d']) ? $options['d'] : (__DIR__ . '/Tables');
if (is_dir($tablesDir)) {
    delDir($tablesDir);
}
mkdir($tablesDir);

$tableTemplate = file_get_contents(__DIR__ . '/TableTemplate.tpl');
$tables        = InformationSchema::getTables();
foreach ($tables as $table) {
    $tableCols     = getTableCols($table, null, true);
    $colsProperty  = genTableColProperty($tableCols);
    $tableFileName = ucStr($table) . 'Table';
    $newTable      = str_replace('{{TABLE_COLUMNS_PROPERTY}}', $colsProperty, $tableTemplate);
    if (!empty($options['n'])) {
        $newTable = str_replace('{{NAMESPACE}}', PHP_EOL . "namespace {$options['n']};" . PHP_EOL, $newTable);
    } else {
        $newTable = str_replace('{{NAMESPACE}}', '', $newTable);
    }
    $newTable = str_replace('{{TABLE_CLASS_NAME}}', $tableFileName, $newTable);
    $newTable = str_replace('{{TABLE_NAME}}', $table, $newTable);
    file_put_contents("$tablesDir/$tableFileName.php", $newTable);
}
function genTableColProperty($cols)
{
    $colArr = [];
    foreach ($cols as $col) {
        $colName      = lcfirst(ucStr($col['COLUMN_NAME']));
        $colArr[] = " * @property Column $colName {$col['COLUMN_COMMENT']}";
    }
    return '/**' . PHP_EOL . implode(PHP_EOL, $colArr) . PHP_EOL . ' */';
}

function delDir($dir)
{
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delDir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function ucStr($tableName)
{
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));
}

function getTableCols($tableName, $schemaName = null, $withComment = false)
{
    if (empty($schemaName)) {
        $schemaName = Connection::getSchemaName();
    }
    $table  = new Table('Information_schema`.`columns');
    $select = new Select($table);
    $select->fetchCols(new Column('COLUMN_NAME', $table));
    if ($withComment) {
        $select->fetchCols(new Column('COLUMN_COMMENT', $table));
    }
    $condition1 = new Condition(new Column('table_schema', $table), $schemaName);
    $condition2 = new Condition(new Column('table_name', $table), $tableName);
    $select->where($condition1, $condition2);
    $rows = Connection::select($select);
    return $rows;
}