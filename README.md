Fegg
====
Simple Framework For PHP

３ファイルで構成されたPHPフレームワークです。Smartyに近いテンプレートエンジン、URLディスパッチャ、URLマッピング、多言語対応などの豊富な機能を持ちながら、軽量かつ高速に動作することが特徴です。フレームワーク全体のソースコードが1500行程度と、把握が容易でメンテナンス性にも優れています。

ファイル／ディレクトリ構成
----
Feggは、下記の３ファイルで構成されています。

|ファイル|概要|
|---|---|
|/fegg/fegg.php|Fegg本体|
|/fegg/settings.php|Fegg設定ファイル|
|/htdocs/index.php|ディスパッチャ|

下記は、Feggによるウェブアプリケーションの最少構成です。（テンプレートエンジン及びデータベース未使用の場合）

|ディレクトリ／ファイル|概要|
|---|---|
|/code/application/sample.php|ウェブアプリケーション|
|/fegg/fegg.php|Fegg本体|
|/fegg/settings.php|Fegg設定ファイル|
|/htdocs/.htaccess|Apache設定ファイル|
|/htdocs/index.php|ディスパッチャ|

テンプレートエンジンを使用した場合、構成はこのようになります。

|ディレクトリ／ファイル|概要|
|---|---|
|/code/application/sample.php|ウェブアプリケーション|
|/code/template/_cache|テンプレートキャシュディレクトリ（書込権限必要）|
|/code/template/sample.tpl|テンプレートファイル|
|/fegg/fegg.php|Fegg本体|
|/fegg/settings.php|Fegg設定ファイル|
|/htdocs/.htaccess|Apache設定ファイル|
|/htdocs/index.php|ディスパッチャ|

その他
----
さくらインターネットのサーバーに導入する場合、.htaccessを修正する必要があります。
- php_flag、php_valueが使えないので全てコメントアウトし、コントロールパネルからphp.iniを書き換える（書き換えなくても動作はする）
- .htaccessの以下の部分を書き換える

変更前
```
RewriteRule ^(.+)$ index.php/$1/ [L]
```
変更後
```
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?/$1/ [L,QSA]
```

Apacheでエイリアスを設定する場合、次の変更が必要です。
- .htaccessのRewriteBaseを / から /alias_name と書き換える
- index.phpの23行目移行を次のように書き換える

変更前
```
$tempPath = '';
for ($i = 0; $i < substr_count(FEGG_REWRITEBASE, '/') + 1; $i++) {
    $tempPath .= '/..';
}
```
変更後
$tempPath = '/..';
// for ($i = 0; $i < substr_count(FEGG_REWRITEBASE, '/') + 1; $i++) {
//     $tempPath .= '/..';
// }
