Fegg
====

Simple Framework For PHP

## さくらインターネットの共用サーバーへの導入

さくらインターネットのサーバーに導入する場合、.htaccessを修正する必要がある
- php_flag、php_valueが使えないので全てコメントアウトし、コントロールパネルからphp.iniを書き換える（書き換えなくても動作はする）
- 『RewriteRule ^(.+)$ index.php/$1/ [L]』を『RewriteRule ^(.+)$ index.php?/$1/ [L,QSA]』に書き換える
