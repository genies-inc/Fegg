<?php
/**
 * MySQL接続先情報
 *
 * 関連クラス：MySQL.php
 */ 
// 本番環境
if (false) {
    
    // Database(Master)設定（１台を想定）
    $mysql_config['master'] = array(
        'server'   => '127.0.0.1',
        'port'     => '',
        'database' => 'db_name',
        'username' => 'db_user',
        'password' => 'db_password'
    );

    // Database(Slave)設定（複数台を想定）
    $mysql_config['slave'][] = array(
        'server'   => '127.0.0.1',
        'port'     => '',
        'database' => 'db_name',
        'username' => 'db_user',
        'password' => 'db_password'
    );
    
// 開発環境
} else {
    
    // Database(Master)設定（１台を想定）
    $mysql_config['master'] = array(
        'server'   => '127.0.0.1',
        'port'     => '',
        'database' => 'db_name',
        'username' => 'db_user',
        'password' => 'db_password'
    );

    // Database(Slave)設定（複数台を想定）
    $mysql_config['slave'][] = array(
        'server'   => '127.0.0.1',
        'port'     => '',
        'database' => 'db_name',
        'username' => 'db_user',
        'password' => 'db_password'
    );
}
/* End of file mysql_config.php */
