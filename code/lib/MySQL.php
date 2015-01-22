<?php
/**
 * MySQLクラス
 * 
 * MySQLの操作に必要な処理を提供するクラス。
 * 関連ファイル：mysql_config.php
 *               mysql_regular_query.php
 * 
 * @access public
 * @author Genies, Inc.
 * @version 0.9.0
 */

class DB
{
    private $_app;
    
    private $_connect;
    private $_connectFlag;
    private $_query;
    private $_parameter;
    private $_record;
    private $_returnCode;
    private $_affectedRows;
    
    private $_items;
    private $_table;
    private $_where;
    private $_whereValues;
    private $_group;
    private $_order;
    private $_limit;
    private $_regularUseQueryFlag;
    private $_regularUseQueryFlagForTable;
    
    
    /**
     *  constructor
     */
    function __construct()
    {
        // アプリケーションオブジェクト
        $this->_app = &FEGG_getInstance();

        // コンフィグ取得
        $this->_app->loadConfig('mysql_config');
        $this->_app->loadConfig('mysql_regular_query');

        // 初期化
        $this->_initQuery();
    }
 

    /**
     * クエリー構築
     * @param string $queryType 
     */
    function _buildQuery($queryType) {
        
        $this->_query = '';
        $this->_parameter = array();
        
        // 常用クエリーの設定
        if ($this->_regularUseQueryFlag) {
            $this->_setRegularUseQuery($queryType);
        }
        
        $queryType = strtoupper($queryType);
        $query = '';
        switch ($queryType) {
            case 'COUNT';
                $query .= 'Select Count(*) as number_of_records ';
                $query .= ' From `' . $this->_table . '` ';
                $query .= isset($this->_where) ? 'Where ' . $this->_where : '';
                $query .= isset($this->_group) ? ' Group By ' . $this->_group : '';
                $this->_query = $query;
                $this->_parameter = $this->_whereValues;
                break;
                
            case 'SELECT':
                $query .= 'Select ';
                $tempQuery = '';
                if (is_array($this->_items)) {
                    foreach($this->_items as $key => $value) {
                        if ($tempQuery) { $tempQuery .= ", "; }
                        $tempQuery .= $key;
                    }
                    $query .= $tempQuery;
                } else {
                    $query .= '*';
                }
                
                $query .= ' From `' . $this->_table . '` ';
                $query .= isset($this->_where) ? 'Where ' . $this->_where : '';
                $query .= isset($this->_group) ? ' Group By ' . $this->_group : '';
                $query .= isset($this->_order) ? ' Order By ' . $this->_order : '';
                $query .= isset($this->_limit) ? ' Limit ' . $this->_limit : '';
                
                $this->_query = $query;
                $this->_parameter = $this->_whereValues;
                break;
            
            case 'INSERT':
                $query .= 'Insert Into `' . $this->_table . '` ';
                $tempQuery1 = '';
                $tempQuery2 = '';
                foreach($this->_items as $key => $value) {
                    if (preg_match('/([^=]+)\s*=\s*([\w\(\)\s\+]+)/i', $key, $match)) {
                        
                        // 代入形式
                        switch (true) {
                            case (preg_match('/^now/i', $match[2])):
                                if ($tempQuery1) { $tempQuery1 .= ", "; }
                                $tempQuery1 .= $match[1];
                                if ($tempQuery2) { $tempQuery2 .= ", "; }
                                $tempQuery2 .= '?';
                                $this->_parameter[] = $this->_app->getDatetime();
                                break;
                            
                            default:
                                if ($tempQuery1) { $tempQuery1 .= ", "; }
                                $tempQuery1 .= $match[1];
                                if ($tempQuery2) { $tempQuery2 .= ", "; }
                                $tempQuery2 .= '?';
                                $this->_parameter[] = $match[2];
                                break;
                        }
                        
                    } else {
                        
                        // 項目名のみ
                        if ($tempQuery1) { $tempQuery1 .= ", "; }
                        $tempQuery1 .= $key;

                        if ($tempQuery2) { $tempQuery2 .= ", "; }
                        $tempQuery2 .= '?';
                    
                        $this->_parameter[] = $value;
                    }
                }
                
                $query .= '(' . $tempQuery1 . ') Values (' . $tempQuery2 . ')';

                $this->_query = $query;
                break;
            
            case 'UPDATE':
                $query .= 'Update `' . $this->_table . '` Set ';
                $tempQuery1 = '';
                foreach($this->_items as $key => $value) {
                    if (preg_match('/([^=]+)\s*=\s*([\w\(\)]+)/i', $key, $match)) {
                        
                        // 代入形式
                        if ($tempQuery1) { $tempQuery1 .= ", "; }
                        $tempQuery1 .= $match[1] . '= ?';

                        switch ($match[2]) {
                            case 'now()':
                                $this->_parameter[] = $this->_app->getDatetime();
                                break;
                            
                            default:
                                $this->_parameter[] = $match[2];
                                break;
                        }
                        
                    } else {
                        
                        // 項目名のみ
                        if ($tempQuery1) { $tempQuery1 .= ", "; }
                        $tempQuery1 .= $key . '= ?';

                        $this->_parameter[] = $value;
                    }
                }
                $query .= $tempQuery1 . ' ';

                $query .= $this->_where ? 'Where ' . $this->_where : '';
                if ($this->_whereValues) {
                    foreach ($this->_whereValues as $key => $value) {
                        $this->_parameter[] = $value;
                    }
                }

                $this->_query = $query;
                break;
            
            case 'DELETE':
                
                $query = 'Delete ';
                $query .= 'From ' . $this->_table . ' ';
                $query .= $this->_where ? 'Where ' . $this->_where : '';
                foreach ($this->_whereValues as $key => $value) {
                    $this->_parameter[] = $value;
                }
          
                $this->_query = $query;
                break;
                
            case 'TRUNCATE':
                
                $query = 'Truncate ' . $this->_table . ' ';
          
                $this->_query = $query;
                break;
        }
        
        $returnArray = array();
        $returnArray[0] = $this->_query;
        $returnArray[1] = $this->_parameter;

        return $returnArray;
    }


    /**
     * MySQLサーバーとの接続切断
     */
    function _close()
    {
        if ($this->_connect) {
            mysql_close($this->_connect);
            $this->_connect = null;
        }
    }
    
    
    /**
     * MySQLサーバーへの接続確立
     * @param string $server MySQLサーバー（ポート指定例：localhost:3306）
     * @param string $user ユーザー
     * @param string $password パスワード
     * @param string $database データベース 
     * @param string $port ポート番号
     */
    function _connect($server, $user, $password, $database, $port = '')
    {
        // ポート編集
        if ($port) { $server = "$server:$port"; }

        // 接続
        $this->_connect = mysql_connect($server, $user, $password);
        if (!$this->_connect) {
            echo "No MySQL Server: $server";
            exit;
        }

        if (!mysql_select_db($database, $this->_connect)) {
            echo "No Database: $database";
            exit;
        }
        
        mysql_set_charset("UTF8", $this->_connect);
    }
    

    /**
     * エスケープ
     * @param array $data エスケープ対象値
     */
    private function _escape($data)
    {
        // 格納用に文字をエスケープ（標準関数での非対応文字は別途処理）
        foreach ($data as $key => $value) {
            $data[$key] = str_replace(array('\\', '%', '_'), array('\\\\', '\%', '\_'), $data[$key]);
            $data[$key] = mysql_real_escape_string($value);
            $data[$key] = "'" . $data[$key] . "'";
        }
        return $data;
    }
    

    /**
     * エラー処理
     * @param string $query 実行したクエリー
     */
    private function _error($query)
    {
        if (FEGG_DEVELOPER) {
            echo "[Error] " . mysql_error($this->_connect) . '<br/>';
            echo "[Query] " . $query . '<br/>';
        }
        exit;
    }
    
    
    /**
     * クエリ実行
     * @param string $query SQL文（パラメーター部分は?で表記）
     * @param array $parameter パラメーター配列（SQL中の?の順序に合わせる）
     * @return boolean 正常時: True 異常時: False
     */
    function _executeQuery($query, $parameter)
    {
        // パラメーターのエスケープとバインド
        if (is_array($parameter)) {
            $parameter = $this->_escape($parameter);
            $query = str_replace('?', '%s', $query);
            $query = vsprintf($query, $parameter);
        }

        // 結果格納変数の初期化
        $this->_affectedRows = 0;

        // クエリー実行
        if (!$result = mysql_query($query, $this->_connect)) {
            $this->_error($query);
        }

        if ($result) {

            // 結果行数の格納
            $this->_affectedRows = mysql_affected_rows();

        } else { $this->_error($query); }

        return $this->_affectedRows;
    }   
    
    
    /**
     * データ取得
     * @param string $query SQL文（パラメーター部分は?で表記）
     * @param array $parameter パラメーター配列（SQL中の?の順序に合わせる）
     * @return array 結果を配列で返す。項目名による連想配列。
     */
    function _fetchAll($query, $parameter)
    {
        if (is_array($parameter)) {
            // パラメーターをエスケープ
            $parameter = $this->_escape($parameter);

            // クエリーにパラメーターを編集
            $query = str_replace('?', '%s', $query);
            $query = vsprintf($query, $parameter);
        }
        
        // 結果格納変数の初期化
        $this->_affectedRows = 0;
        $record = array();

        // クエリー実行
        if (!$result = mysql_query($query, $this->_connect)) {
            $this->_error($query);
        }
        
        if ($result) {

            // 結果行数の格納
            $this->_affectedRows = mysql_num_rows($result);

            // 取得行数文繰り返し$recordに格納
            while ($data = mysql_fetch_array($result, MYSQL_ASSOC)) {
                $record[] = $data;
            }
            
        } else { $this->_error($query); }

        // メモリを解放
        mysql_free_result($result);
        
        return $record;
    }
    

    /**
     * 初期化
     */
    function _initQuery()
    {
        // クエリー用変数
        $this->_items = null;
        $this->_table = null;
        $this->_where = null;
        $this->_whereValues = null;
        $this->_group = null;
        $this->_order = null;
        $this->_limit = null;

        // 接続フラグ
        $this->_connectFlag = false;
        
        // 常用クエリーフラグ
        $this->_regularUseQueryFlag = true;
        $this->_regularUseQueryFlagForTable = true;
    }
    
    
    /**
     * 連想配列判定
     * @param array 判定対象の配列
     * @return boolean true: 連想配列 false: 配列
     */
    function _isHash($array)
    {
        // 連想配列の先頭キーに0は使えず、配列の先頭は0という前提
        reset($array);
        list($key) = each($array);
        
        return $key !== 0;
    }
    

    /**
     * 常用クエリーの設定
     * @param string $queryType 
     */
    function _setRegularUseQuery($queryType)
    {
        // テーブルに応じて付加するクエリー
        if ($this->_regularUseQueryFlagForTable) {
            // 項目
            if (isset($this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['item']) && $this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['item']) {
                $this->setItem($this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['item']);
            }

            // 条件
            if (isset($this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['where']) && $this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['where']) {
                $conjunction = '';
                if ($this->_where) {
                    $conjunction = ' And ';
                }
                $this->setWhere($conjunction . $this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['where']);
            }

            // 並び順
            if (isset($this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['order']) && $this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['order']) {
                $conjunction = '';
                if ($this->_order) {
                    $conjunction = ' ,';
                }
                $this->setOrder($conjunction . $this->_app->config['mysql_regular_query']['table'][$this->_table][$queryType]['order']);
            }
        }

        // テーブルに関わらず付加するクエリー
        if ($this->_regularUseQueryFlag) {
            
            // 項目
            if (isset($this->_app->config['mysql_regular_query']['regular_use'][$queryType]['item']) && $this->_app->config['mysql_regular_query']['regular_use'][$queryType]['item']) {
                $this->setItem($this->_app->config['mysql_regular_query']['regular_use'][$queryType]['item']);
            }

            // 条件
            if (isset($this->_app->config['mysql_regular_query']['regular_use'][$queryType]['where']) && $this->_app->config['mysql_regular_query']['regular_use'][$queryType]['where']) {
                $conjunction = '';
                if ($this->_where) {
                    $conjunction = ' And ';
                }
                $this->setWhere($conjunction . $this->_app->config['mysql_regular_query']['regular_use'][$queryType]['where']);
            }

            // 並び順
            if (isset($this->_app->config['mysql_regular_query']['regular_use'][$queryType]['order']) && $this->_app->config['mysql_regular_query']['regular_use'][$queryType]['order']) {
                $conjunction = '';
                if ($this->_order) {
                    $conjunction = ' ,';
                }
                $this->setOrder($conjunction . $this->_app->config['mysql_regular_query']['regular_use'][$queryType]['order']);
            }
        }
    }
    
    
    /**
     * 取得したレコードを返す
     * @param string $index 配列のキーにする項目ID
     * @return array
     */
    function all($index = '')
    {
        if ($index) {
            $tempRecord = $this->_record;
            $record = array();
            foreach ($tempRecord as $key => $value) {
                $record[$value[$index]] = $value;
            }
        } else {
            $record = $this->_record;
        }
        
        return $record;
    }
    

    /**
     * データ件数カウント
     * @param string $table 指定時：各メソッドで指定された値でquery構築、省略時：setQueryメソッドによるquery設定
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function count($table)
    {
        // データベースが明示的に指定されていなければ Slave へ接続
        if (!$this->_connectFlag) {
            $this->slaveServer();
        }

        // テーブル名が指定されているときはメソッドで指定された値でquery構築
        $this->_table = $table;
        $this->_buildQuery('count');

        // クエリーを実行して、論理的に非接続状態にする
        $this->_record = $this->_fetchAll($this->_query, $this->_parameter);
        
        return $this;
    }
    
    
    /**
     * データ削除
     * @param string $table
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function delete($table = '')
    {
        // データベースが明示的に指定されていなければ Slave へ接続
        if (!$this->_connectFlag) {
            $this->masterServer();
        }
        
        // query構築
        $this->_table = $table;
        $this->_buildQuery('delete');
        
        // クエリーを実行して、論理的に非接続状態にする
        $this->_returnCode = $this->_executeQuery($this->_query, $this->_parameter);
        $this->_initQuery();

        return $this;
    }
    

    /**
     * クエリー実行
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function execute()
    {
        // クエリ種類の判定
        if (preg_match('/^\s*select.+/i', $this->_query)) {
            
            // データベースが明示的に指定されていなければ Slave へ接続
            if (!$this->_connectFlag) {
                $this->slaveServer();
            }

            // クエリーを実行して、論理的に非接続状態にする
            $this->_record = $this->_fetchAll($this->_query, $this->_parameter);
            
        } else {
            
            // データベースが明示的に指定されていなければ Master へ接続
            if (!$this->_connectFlag) {
                $this->masterServer();
            }

            // クエリーを実行して、論理的に非接続状態にする
            $this->_returnCode = $this->_executeQuery($this->_query, $this->_parameter);
            
        }
        $this->_initQuery();

        return $this;
    }
    
    
    /**
     * 取得行数、結果行数の取得
     * @return integer 結果行数
     */
    function getAffectedRow()
    {
        $this->_affectedRows;
    }
    
    
    /**
     * 直近で登録されたオートナンバーの取得
     * @return Integer 取得できなかったときは0を返す
     */
    function getLastIndexId()
    {
        $this->masterServer();
        $record = $this->_fetchAll('SELECT LAST_INSERT_ID()', array());
        
        if (isset($record[0]["LAST_INSERT_ID()"])) {
            return $record[0]["LAST_INSERT_ID()"];
        } else {
            return 0;
        }
    }
    

    /**
     * 最後に実行したクエリーの取得
     */
    function getLastQuery()
    {
        $query = str_replace('?', '%s', $this->_query);
        $query = vsprintf($query, $this->_parameter);
        
        return $query;
    }
    

    /**
     * リターンコード取得
     * @return int 
     */
    function getReturnCode()
    {
        return $this->_returnCode;
    }
    
    
    /**
     * 指定した項目だけの配列を取得
     * @param string $index
     * @return array 
     */
    function id($index)
    {
        $tempRecord = $this->_record;
        $ids = array();
        foreach ($tempRecord as $key => $value) {
            $ids[] = $value[$index];
        }
        
        return $ids;
    }
    
    
    /**
     * データ追加
     * @param string $table 
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function insert($table)
    {
        // データベースが明示的に指定されていなければ Master へ接続
        if (!$this->_connectFlag) {
            $this->masterServer();
        }

        // query構築
        $this->_table = $table;
        $this->_buildQuery('insert');

        // クエリーを実行して、論理的に非接続状態にする
        $this->_returnCode = $this->_executeQuery($this->_query, $this->_parameter);
        $this->_initQuery();

        return $this;
    }
    
    
    /**
     * マスターデータベースへの接続
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function masterServer()
    {
        // 接続
        $this->_connect($this->_app->config['mysql_config']['master']['server'],
                        $this->_app->config['mysql_config']['master']['username'],
                        $this->_app->config['mysql_config']['master']['password'],
                        $this->_app->config['mysql_config']['master']['database'],
                        $this->_app->config['mysql_config']['master']['port']
               );
        $this->_connectFlag = true;
        
        return $this;
    }


    /**
     * 取得したレコードの１件目を返す
     * @return array 
     */
    function one()
    {
        if (is_array($this->_record)) {
            $record = $this->_record;
        } else {
            $record = array();
        }
        return array_shift($record);
    }
    
    
    /**
     * データ取得
     * @param string $table 指定時：各メソッドで指定された値でquery構築、省略時：setQueryメソッドによるquery設定
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す

     */
    function select($table)
    {
        // データベースが明示的に指定されていなければ Slave へ接続
        if (!$this->_connectFlag) {
            $this->slaveServer();
        }

        // テーブル名が指定されているときはメソッドで指定された値でquery構築
        $this->_table = $table;
        $this->_buildQuery('select');

        // クエリーを実行して、論理的に非接続状態にする
        $this->_record = $this->_fetchAll($this->_query, $this->_parameter);
        $this->_initQuery();
        
        return $this;
    }
    
    
    /**
     * グループ設定
     * @param string $query 
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function setGroup($query)
    {
        $this->_group .= $query;
        
        return $this;
    }
    
    
    /**
     * 操作項目設定
     * @param string $query 複数の場合カンマ区切り
     * @param mixed $parameter 連想配列の場合は$queryで指定した項目名と一致するもの、配列の場合は左から順に値を使用
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
    */
    function setItem($query, $parameter = '')
    {
        if ($parameter) {
            if ($this->_isHash($parameter)) {

                // パラメーターが連想配列の場合は要素名で一致させる
                $items = explode(',', $query);
                foreach ($items as $value) {
                    $value = preg_replace('/^\s*(\w+)\s*/', '$1', $value);
                    if (isset($parameter[$value])) {
                        $this->_items['`' . $value . '`'] = $parameter[$value];
                    } else {
                        $this->_items['`' . $value . '`'] = '';
                    }
                }

            } else {

                // パラメーターが配列の場合は順番に一致させる
                $items = explode(',', $query);
                foreach ($items as $key => $value) {
                    if (isset($parameter[$key])) {
                        $value = preg_replace('/^\s*(\w+)\s*/', '$1', $value);
                        $this->_items['`' . $value . '`'] = $parameter[$key];
                    } else {
                        $this->_items['`' . $value . '`'] = '';
                    }
                }

            }
        } else {            
            // パラメーター省略時は項目名のみ処理
            $items = explode(',', $query);
            foreach ($items as $value) {
                $this->_items[$value] = '';
            }
        }

        return $this;
    }
    

    /**
     * 取得件数設定
     * @param int $limit
     * @param int $offset 
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function setLimit($limit, $offset = 0)
    {
        $this->_limit = $offset . ',' . $limit;
        
        return $this;
    }
    
    
    /**
     * ソート順設定
     * @param string $query 
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function setOrder($query)
    {
        $this->_order .= $query;
        
        return $this;
    }
    

    /**
     * クエリー設定
     * @param string $query
     * @param array $parameter 
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function setQuery($query, $parameter = array())
    {
        $this->_query = $query;
        $this->_parameter = $parameter;
        
        return $this;
    }
    
    
    /**
     * 条件式設定
     */
    function setWhere()
    {
        // 引数取得
        $numberOfArgs = func_num_args();
        $parameters = func_get_args();
        
        // クエリ取得
        $query = array_shift($parameters);
        
        // パラメータ処理
        if ($numberOfArgs == 1) {
            
            // クエリのみ
            $this->_where .= ' ' . $query;
            
        } else {
            
            // パラメーターあり
            $index = 0;
            foreach ($parameters as $parameter) {
                if (!is_array($parameter)) {
                    
                    $this->_whereValues[] = $parameter;
                    $index = $index + 1;
                    
                } else {
                    
                    // パラメーターが配列の場合以下の変換を行う
                    // = --> in, 
                    // in --> カンマ区切り
                    // <> --> not in
                    // like --> or 区切り
                    
                    // 変換位置の確定
                    preg_match_all('/(\w+\s*(=|<|>|<>|like|in)\s*\(?\s*\?\s*\)?)/i', $query, $matches, PREG_OFFSET_CAPTURE);
                    $position = $matches[0][$index][1];

                    // 演算子の確定
                    preg_match_all('/(\w+\s*(=|<|>|<>|like|in)\s*\(?\s*\?\s*\)?)/i', $query, $matches, PREG_PATTERN_ORDER);
                    $operator = $matches[2][$index];
                    
                    // 対象箇所までのクエリー取得
                    $convertedQueryFrontPart = substr($query, 0, $position);
                    if ($position > 0) {
                        $convertedQuery = substr($query, $position);
                    } else {
                        $convertedQuery = $query;
                    }
                    
                    // 項目名取得
                    $pattern = '/^\s*\w+/i';
                    preg_match($pattern, $convertedQuery, $matches);
                    $itemName = $matches[0];
                    $itemName = '`' . $itemName . '`';
                    
                    // 対象箇所からのクエリー取得
                    $convertedQuery = preg_replace('/^\s*\w+\s*' . $operator . '\s*\(?\s*\?\s*\)?(.*)/', '$1', $convertedQuery);
                    
                    $tempQuery = '';
                    $operator = strtolower($operator);
                    switch ($operator) {
                        case '=':
                        case 'in':
                            foreach ($parameter as $key => $value) {
                                if ($tempQuery) {
                                    $tempQuery .= ',';
                                }
                                $tempQuery .= '?';
                                $this->_whereValues[] = $value;
                                $index = $index + 1;
                            }
                            $convertedQuery = $convertedQueryFrontPart . $itemName . ' in (' . $tempQuery . ') ' . $convertedQuery;
                            break;
                        
                        case '<>':
                            foreach ($parameter as $key => $value) {
                                if ($tempQuery) {
                                    $tempQuery .= ',';
                                }
                                $tempQuery .= '?';
                                $this->_whereValues[] = $value;
                                $index = $index + 1;
                            }
                            $convertedQuery = $convertedQueryFrontPart . $itemName . ' not in (' . $tempQuery . ') ' . $convertedQuery;
                            break;
                        
                        case 'like':
                            $tempQuery = '';
                            foreach ($parameter as $key => $value) {
                                if ($tempQuery) {
                                    $tempQuery .= 'or ';
                                }
                                $tempQuery .= $itemName . ' Like ? ';
                                $this->_whereValues[] = $value;
                                $index = $index + 1;
                            }
                            $convertedQuery = $convertedQueryFrontPart . '(' . $tempQuery . ') ' . $convertedQuery;
                            break;
                        
                    }
                    $query = $convertedQuery;
                }
            }
            $this->_where .= ' ' . $query;
        }
        
        return $this;
    }
    
    
    /**
     * 1次元配列での取得
     * @return array 
     */
    function simpleArray($keyName, $valueName)
    {
        $tempRecord = $this->_record;
        $record = array();
        foreach ($tempRecord as $key => $value) {
            $record[$value[$keyName]] = $value[$valueName];
        }
        
        return $record;
    }
    
    
    /**
     * スレーブサーバーへの接続
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function slaveServer()
    {
        // 接続先のサーバーを決定（ランダム）
        $maxServer = count($this->_app->config['mysql_config']['slave']) - 1;

        $serverNo = 0;
        if ($maxServer > 0) {
            mt_srand();
            $serverNo = mt_rand(0, $maxServer);
        }
        
        // 接続
        $this->_connect($this->_app->config['mysql_config']['slave'][$serverNo]['server'],
                        $this->_app->config['mysql_config']['slave'][$serverNo]['username'],
                        $this->_app->config['mysql_config']['slave'][$serverNo]['password'],
                        $this->_app->config['mysql_config']['slave'][$serverNo]['database'],
                        $this->_app->config['mysql_config']['slave'][$serverNo]['port']
               );
        $this->_connectFlag = true;
        
        return $this;
    }
    
    
    function truncate($table)
    {
        // データベースが明示的に指定されていなければ Slave へ接続
        if (!$this->_connectFlag) {
            $this->slaveServer();
        }

        // テーブル名が指定されているときはメソッドで指定された値でquery構築
        $this->_table = $table;
        $this->_buildQuery('truncate');

        // クエリーを実行して、論理的に非接続状態にする
        $this->_returnCode = $this->_executeQuery($this->_query, $this->_parameter);
        $this->_initQuery();

        return $this;
    }
    
    
    /**
     * 常用クエリーを設定しない
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function unsetRegularUseQuery()
    {
        $this->_regularUseQueryFlag = false;
        
        return $this;
    }
    
    
    /**
     * 各テーブルの常用クエリーを設定しない
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function unsetRegularUseQueryForTable()
    {
        $this->_regularUseQueryFlagForTable = false;
        
        return $this;
    }
    
    
    /**
     * データ取得
     * @param string $table
     * @return MySQL メソッドチェーンに対応するため自身のオブジェクト($this)を返す
     */
    function update($table)
    {
        // データベースが明示的に指定されていなければ Slave へ接続
        if (!$this->_connectFlag) {
            $this->masterServer();
        }
        
        // query構築
        $this->_table = $table;
        $this->_buildQuery('update');
        
        // クエリーを実行して、論理的に非接続状態にする
        $this->_returnCode = $this->_executeQuery($this->_query, $this->_parameter);
        $this->_initQuery();

        return $this;
    }    
}
/* End of file: MySQL.php */