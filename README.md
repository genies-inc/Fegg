Fegg
====

Simple Framework For PHP

## さくらインターネットの共用サーバーに導入する場合の変更点

さくらインターネットのサーバーに導入する場合、.htaccessについて下記を修正する必要がある
- php_flag、php_valueが使えないので全てコメントアウトし、コントロールパネルからphp.iniを書き換える（書き換えなくても動作はする）
- 『RewriteRule ^(.+)$ index.php/$1/ [L]』を『RewriteRule ^(.+)$ index.php?/$1/ [L]』に書き換える
