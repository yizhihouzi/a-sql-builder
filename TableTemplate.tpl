<?php
{{NAMESPACE}}
use DBOperate\Table;
use DBOperate\Column;

{{TABLE_COLUMNS_PROPERTY}}
class {{TABLE_CLASS_NAME}} extends Table
{
    public function __construct()
    {
        parent::__construct("{{TABLE_NAME}}");
    }
}
