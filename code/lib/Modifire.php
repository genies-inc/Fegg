<?php

/*
 * Modifireクラス
 * 
 * テンプレート向けモディファイア拡張クラス
 * $this->getClass( 'Modifire' ); を呼ぶことで利用できるようになります
 * 
 * @access public
 * @author LionHeart Co., Ltd.
 * @version 1.0.0
 */

class Modifire {

    // 無いとエラーが発生しちゃうので
    // 今後設定ファイルとか用いるのであれば $app = FEGG_getInstance(); $app->Modifire->xxx; とかで利用しても良いかも
    public function __construct()
    {
    }

}

/**
 * Fegg向け日時フォーマット
 * 標準date関数のラッパー（引数の順番が合わないため）
 *
 * @param  mixed  $time   元の日時（文字列が来た場合はstrtotimeでタイムスタンプに変換する）
 * @param  string $format 日付フォーマット（ルールはdate関数と同じ）
 * @return string         フォーマット変換後の日時
 */
function feggDate( $time, $format )
{
    // $timeが文字列ならタイムスタンプに変換
    if(! is_numeric( $time ) ) {
        $time = strtotime( $time );
    }

    return date( $format, $time );
}

/**
 * Fegg向けImplode
 * 標準implode関数のFegg用関数（引数の順番が合わないため）
 *
 * @param  array  $pieces 結合する配列
 * @param  string $glue   配列間に挿入する文字列
 * @return string         結合した文字列
 */
function feggImplode( $pieces, $glue = '' )
{
    // 配列じゃなければそのまま表示
    if(! is_array( $pieces ) ) {
        return $pieces;
    }

    return implode( $glue, $pieces );
}

/**
 * 代替文字表示
 *
 * @param  mixed  $param   検証変数
 * @param  string $replace 代替文字列
 * @return mixed           検証変数または代替文字列
 */
function feggDefault( $param, $replace )
{
    if( is_array( $param ) ) {
        // 配列の場合は要素数で確認
        if(! count( $param ) ) {
            return $replace;
        }
    } else if( is_string( $param ) || is_numeric( $param ) ) {
        // 文字列・数値の場合は文字数で確認
        if(! strlen( $param ) ) {
            return $replace;
        }
    } else {
        // その他はempty関数で確認
        if( empty( $param ) ) {
            return $replace;
        }
    }

    return $param;
}

/**
 * 第二引数と同じ値だった場合に第三引数
 * 異なった値の場合は第四引数を表示する
 *
 * @param  mixed $param    検証変数A
 * @param  mixed $check    検証変数B
 * @param  mixed $is_true  A=Bの時に表示
 * @param  mixed $is_false A!=Bの時に表示
 * @return mixed           $is_true または $is_false
 */
function feggCheck( $param, $check, $is_true=NULL, $is_false=NULL )
{
    if( $param === $check ) {
        return $is_true;
    } else {
        return $is_false;
    }
}

/**
 * 置換して出力
 * 標準str_replace関数のFegg用関数（引数の順番が合わないため）
 *
 * @param  mixed  $subject 置換対象の値
 * @param  mixed  $search  検索する値
 * @param  mixed  $replace 置換する値
 * @return mixed           置換後の値
 */
function feggReplace( $subject, $search, $replace )
{
    return str_replace( $search, $replace, $subject );
}

/**
 * 正規表現で置換して出力
 * 標準preg_replace関数のFegg用関数（引数の順番が合わないため）
 *
 * @param  mixed   $subject 置換対象の値
 * @param  mixed   $pattern 検索する正規表現パターン
 * @param  mixed   $replace 置換する値
 * @param  integer $limit   置換する回数
 * @return mixed            置換後の値
 */
function feggPregReplace( $subject, $pattern, $replace, $limit=-1 )
{
    return preg_replace( $pattern, $replace, $subject, $limit );
}
