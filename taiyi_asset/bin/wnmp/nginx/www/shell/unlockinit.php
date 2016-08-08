<?php
include_once realpath(dirname(__FILE__) . '/../application/library/Tool/Fnc.php'  );
include_once realpath(dirname(__FILE__) . '/../application/library/Orm/Sqlite.php'  );

define('DB_FILE' , realpath(dirname(__FILE__).'../../../../../../user/webdb/taiyi.db'));
$tSqlite = new Orm_Sqlite(DB_FILE);
$tSqlite->query('update mywallet set unlock=0');
