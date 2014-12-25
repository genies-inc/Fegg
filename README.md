Fegg
====

Simple Framework For PHP

さくらインターネットへの導入について

さくらインターネットのサーバーに導入する場合、.htaccessについて下記を修正する必要がある
1. php_flag、php_valueが使えないので全てコメントアウトし、コントロールパネルからphp.iniを書き換える（書き換えなくても動作はする）
2. 『RewriteRule ^(.+)$ index.php/$1/ [L]』を『RewriteRule ^(.+)$ index.php?/$1/ [L]』に書き換える
