<?php
/**
 * CSVクラス
 * 
 * 一般的な操作に関するクラス。
 * 
 * @access public
 * @author Genies, Inc.
 * @version 1.0.0
 */
class Tool_CSV
{
    function __construct() 
    {
    }
    

    /**
     * CSVファイルを１行読み込む
     *
     * @param resource handle ファイルハンドル
     * @param int length 読み込むデータ長
     * @param string delimiter 区切り文字
     * @param string enclosure 囲み文字
     * @return ファイル末尾到達、エラー時: FALSE
     */
    function _fgetcsv (&$handle, $length = null, $delimiter = ',', $enclosure = '"')
    {
        $delimiter = preg_quote($delimiter);
        $enclosure = preg_quote($enclosure);

        // 改行に対応するため enclosure が偶数になるまで読み込み１行として扱う
        $line = "";
        $eof = false;
        while ($eof != true) {

            if (empty($length)) {
                $line .= fgets($handle);
            } else {
                $line .= fgets($handle, $length);
            }

            $itemcnt = preg_match_all('/'. $enclosure . '/', $line, $dummy);
            if ($itemcnt % 2 == 0){
                $eof = true;
            }

        }

        if (trim($line) != "") {

            // 末尾の改行をカンマに置換（この後の処理のため）
            $line = preg_replace('/(?:\r\n|[\r\n])?$/', '', trim($line));
            $csv = $line . $delimiter;

            // 各項目を取得
            $pattern = '/(' . $enclosure . '[^' . $enclosure . ']*(?:' . $enclosure . $enclosure . '[^' . $enclosure . ']*)*' . $enclosure . '|[^' . $delimiter . ']*)' . $delimiter .'/';
            preg_match_all($pattern, $csv, $matches);

            $items = $matches[1];
            for($i = 0; $i < count($items); $i++){

                // 囲み文字を削除、囲み文字内の同一文字の置換
                $items[$i] = preg_replace('/^'.$enclosure.'(.*)'.$enclosure.'$/s', '$1', $items[$i]);
                $items[$i] = str_replace($enclosure.$enclosure, $enclosure, $items[$i]);

            }

            return $items;

        } else {
            return false;
        }
    }


    /**
     * CSVファイルを読み込みタイトル行（１行目）連想配列として返す
     *
     * @param <String> $csvPath CSVファイルのパス及びファイル名
     * @return <Array> 「0 => 項目名」形式
     */
    function getItemName($csvPath)
    {
        $arrItemName = array();

        // CSVファイルの存在確認
        if (file_exists($csvPath)) {

            // CSVファイルのオープン
            $filePointer = fopen($csvPath, 'r');

            // 1行取得
            $arrItemName = $this->_fgetcsv($filePointer);

        }

        return $arrItemName;
    }


    /**
     * CSVファイルを読み込み連想配列として返す
     *
     * @param string $csvPath CSVファイルのパス及びファイル名
     * @param boolean $itemNameFlag true: 1行目を項目名、2行目以降を値として連想配列にする false: 項目名は0からの連番とする
     * @param string $toEncoding エンコード文字コードを指定　デフォルト:連想配列のキー、内容をutf-8に変換
     * @param string $fromEncoding エンコード文字コードを指定　デフォルト:連想配列のキー、内容をutf-8に変換
     * @return array
     */
    function load($csvPath, $itemNameFlag = true, $toEncoding = 'utf-8', $fromEncoding = 'auto')
    {
        // CSVデータ格納用連想配列
        $arrCsv     = array();
        $arrKeyName = array();

        // CSVファイルの存在確認
        if (file_exists($csvPath)) {

            // CSVファイルのオープン
            $filePointer = fopen($csvPath, 'r');

            // 1行取得
            $arrItem = $this->_fgetcsv($filePointer);

            $lineCounter = 0;

            do {

                if (feof($filePointer) && $arrItem == false) { break; }

                if ($lineCounter == 0 && $itemNameFlag) {

                    // 1行目を連想配列の項目名として保持
                    for ($i = 0; $i < count($arrItem); $i++) {

                        if ($toEncoding) {
                            $arrKeyName[$i] = mb_convert_encoding($arrItem[$i], $toEncoding, $fromEncoding);
                        } else {
                            $arrKeyName[$i] = $arrItem[$i];
                        }

                    }

                } else {

                    // 各行の値を連想配列に格納
                    if ($itemNameFlag) {

                        // 項目名を配列Keyとする場合
                        for ($i = 0; $i < count($arrItem); $i++) {

                            if (!isset($arrKeyName[$i])) {
                                echo "Undefined Offset: $i at Line " . $lineCounter;
                            }

                            if ($toEncoding) {
                                $arrCsv[$lineCounter - 1][$arrKeyName[$i]] = mb_convert_encoding($arrItem[$i], $toEncoding, $fromEncoding);
                            } else {
                                $arrCsv[$lineCounter - 1][$arrKeyName[$i]] = $arrItem[$i];
                            }

                        }

                    } else {

                        // 項目名配列Keyとしない場合
                        for ($i = 0; $i < count($arrItem); $i++) {
                            if ($toEncoding) {
                                $arrCsv[$lineCounter][$i] = mb_convert_encoding($arrItem[$i], $toEncoding, $fromEncoding);
                            } else {
                                $arrCsv[$lineCounter][$i] = $arrItem[$i];
                            }
                        }
                    }
                }

                $lineCounter = $lineCounter + 1;

                // 1行取得
                $arrItem = $this->_fgetcsv($filePointer);

            } while (!(feof($filePointer) && $arrItem == false));

            return $arrCsv;

        }

    }
}
/* End of file Tool_CSV.php */