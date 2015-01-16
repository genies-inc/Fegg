<?php
/**
 * Fegg 基本設定
 * 
 * サーバー環境、実行状況に合わせて変更する必要のある設定値を定義。
 * ほぼ変更のない値はdefineで定数定義し、アプリケーション毎に見直す必要がある値は$settingsとして変数定義している。
 
 * @author Genies, Inc.
 * @version 1.0.2
 */
// 定数定義（基本的に変更の必要がない値を定義）
// 制御
define('FEGG_DEFAULT_CHARACTER_CODE', 'UTF-8');
define('FEGG_DEFAULT_TIMEZONE', 'Asia/Tokyo');

if (defined('FEGG_REWRITEBASE')) {
    // ドメイン
    define('FEGG_CURRENT_PROTOCOL', isset($_SERVER['X-SSL-REQUEST']) && $_SERVER['X-SSL-REQUEST'] == 'on' ? 'https' : 'http');
    define('FEGG_CURRENT_PORT', in_array( $_SERVER['SERVER_PORT'], array( '80', '443' ) ) ? '' : $_SERVER['SERVER_PORT']);
    define('FEGG_UNIQUE_SCHEME', (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'dummy.com').(FEGG_CURRENT_PORT ? ':'.FEGG_CURRENT_PORT : ''));
    define('FEGG_CURRENT_DOMAIN', FEGG_CURRENT_PROTOCOL . '://' . FEGG_UNIQUE_SCHEME);
    define('FEGG_HTTP_DOMAIN', 'http://' . FEGG_UNIQUE_SCHEME);
    define('FEGG_HTTPS_DOMAIN', 'https://' . FEGG_UNIQUE_SCHEME);

    // 現在のURL
    define('FEGG_CURRENT_URL', FEGG_CURRENT_DOMAIN . FEGG_REWRITEBASE);
}

// 変数定義（環境に合わせて変更が必要な値を定義）
// 実行モード 1:実行中　空白:メンテナンス中
$settings['run_mode'] = '1';

// メンテナンス用ページURL
$settings['maintenance_page_url'] = '';

// 開発者IPアドレス（複数指定可）
$settings['developer_ip'] = array('210.138.248.229');
    
// デフォルト言語コード（１コードのみ指定。多言語処理しない場合は省略可）
#$settings['default_language'] = 'ja';

// サポート言語コード（カンマ区切りで複数指定可。多言語処理しない場合は省略可）
#$settings['support_language'] = array('ja');

// テンプレートディレクトリ
$settings['template_dir'] = FEGG_CODE_DIR . '/template';
$settings['template_ext'] = 'tpl';
$settings['template_cache_dir'] = FEGG_CODE_DIR . '/data/cache/template';

// グローバルコンフィグディレクトリ（省略可）
$settings['global_config_dir'] = '';

/* End of file settings.php */