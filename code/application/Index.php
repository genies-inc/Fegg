<?php
/**
 * Index クラス
 * URLにルートが指定されたときに呼び出されるデフォルトのクラス。
 * 必須ではないが、システムの初期ページは Index.php に記載することを推奨。
 */
class Index extends Application
{
    /**
     * コンストラクタ
     */
    function __construct() {
        parent::__construct();
    }
    

    /**
     * 初期処理
     */
    function __init()
    {
        /**
         * 自身のインスタンスが生成された直後に実行される処理で、
         * Applicationクラスのメソッドを $this->in() のように利用できる点がコンストラクタとの違い。
         * このメソッドが無い場合は処理が省略される。
         */
    }


    /**
     * 共通処理
     */
    function _common()
    {
        /**
         * メソッド名の先頭をアンダーバーにすることで内部メソッドとなり、
         * ディスパッチャーでは呼び出せなくなる。
         */
    }
    

    /**
     * 初期ページ表示
     */
    function index()
    {
        /**
         * URLにクラス名のみを指定した場合に呼び出されるメソッド。
         * 必須ではないが、各クラスの初期ページは index メソッドに記載することを推奨。
         */
        
        // 画面表示
        $this->displayPage('index');
    }
}
/* End Of File: Index.php */