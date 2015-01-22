<?php
/**
 * 共通クエリー
 *
 * MySQLクラスが自動的に付加するクエリーを定義
 * 関連クラス：MySQL.php
 */ 
$mysql_regular_query = array(
    // テーブルに関わらず付加されるクエリー
    'regular_use' => array(
        'count' => array(
            'where' => 'delete_flag = 0',
            'order' => '',
        ),
        'select' => array(
            'where' => 'delete_flag = 0',
            'order' => '',
        ),
        'insert' => array(
            'item' => 'update_datetime = now(), create_datetime = now()',
        ),
        'update' => array(
            'item' => 'update_datetime = now()',
            'where' => '',
        ),
    ),
    
    // テーブルに応じて付加されるクエリー
    'table' => array(
        'table_name' => array(
            'select' => array(
                'where' => 'show_flag = 0',
                'order' => '',
            ),
            'insert' => array(
                'item' => 'update_time = now(), create_datetime = now()',
            ),
            'update' => array(
                'item' => 'update_time = now()',
                'where' => '',
            ),
        ),
    ),
);
/* End of file mysql_regular_query.php */