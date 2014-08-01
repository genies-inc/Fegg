<?php
/**
 * Tool_Fileクラス
 * 
 * Fileの操作に必要な処理を提供するクラス。
 * 
 * @access public
 * @author Genies, Inc.
 * @version 1.0.9
 */
class Tool_File
{
    function __construct()
    {
        
    }


    /**
     * ディレクトリ間の全ファイルコピー
     * @param string $sourceDirectory
     * @param string $toDirectory
     */
    function copyAll($fromDirectory, $toDirectory)
    {
        if (is_dir($fromDirectory) && is_dir($toDirectory)) {
            if ($handle = opendir($fromDirectory)) {
                while (($file = readdir($handle)) !== false) {
                    
                    if ($file == "." || $file == "..") {
                        continue;
                    }
                    copy($fromDirectory . "/" . $file, $toDirectory . "/" . $file);
                    chmod($toDirectory . "/" . $file, 0666);
                }
                closedir($handle);
            }
        }
    }
    

    /**
     * ディレクトリコピー
     * @param string $fromDirectory コピー元ディレクトリ
     * @param string $toDirectory コピー先のディレクトリ（要作成） 
     * @param boolean $recursiveCallFlag True: サブディレクトりも処理
     */
    function copyDirectory($fromDirectory, $toDirectory, $recursiveCallFlag = false)
    {
        if (is_dir($fromDirectory)) {
            
            if ($handle = opendir($fromDirectory)) {
                while (($item = readdir($handle)) !== false) {
                    
                    if ($item == "." || $item == "..") {
                        continue;
                    }

                    if (is_dir($fromDirectory . "/" . $item)) {
                        if ($recursiveCallFlag) {
                            // コピー先のディレクトリ作成（存在していれば処理されない）と再帰呼出
                            $this->createDirectory($toDirectory . "/" . $item);
                            $this->copyDirectory($fromDirectory . "/" . $item, $toDirectory . "/" . $item, true);
                        }
                    } else {
                        copy($fromDirectory . "/" . $item, $toDirectory . "/" . $item);
                    }
                }
                closedir($handle);
            }
        }
    }
    
    
    /**
     * ファイルコピー
     * @param string $from
     * @param string $to
     */
    function copyFile($source, $dest)
    {
        if (file_exists($source)) {
            if (copy($source, $dest)) {
                chmod($dest, 0666);
            }
        }        
    }


    /**
     * ディレクトリ作成
     * @param string $directory 
     * @param string $permission パーミッション（chmodのパラメーター）
     */
    function createDirectory($directory, $permission = '0777')
    {
        if (!file_exists($directory)) {
            if (mkdir($directory)) {
                $permission = sprintf("%04d", "777");
                chmod($directory, octdec($permission));
            }
        }
    }
    
    
    /**
     * ファイルの移動
     * @param string $from 移動元ファイル
     * @param string $to 移動先ファイル
     */
    function moveFile($from, $to)
    {
        if (file_exists($from)) {
            rename($from, $to);
        }
    }
    
    
    /**
     * ファイル読込み
     * @param string $fileName 
     */
    function readFile($fileName)
    {
        $buffer = "";
        if (file_exists($fileName)) {
            $filePointer = @fopen($fileName, "r");
            while (!feof($filePointer)) {
                $buffer .= fgets($filePointer);
            }
            fclose($filePointer);
        }
        
        return $buffer;
    }
    
    
    /**
     * ディレクトリ削除
     * @param string $directory
     */
    function removeDirectory($directory)
    {
        if (is_dir($directory)) {
            if ($handle = opendir($directory)) {
                while (($item = readdir($handle)) !== false) {

                    if ($item == "." || $item == "..") {
                        continue;
                    }

                    if (is_dir($directory . "/" . $item)) {
                        // ディレクトリであれば自身を再帰呼出する
                        $this->removeDirectory($directory . "/" . $item);
                    } else {
                        unlink($directory . "/" . $item);
                    }
                }
                closedir($handle);
            }
            rmdir($directory);
        }
    }
    
    
    /**
     * 指定時間以上経過したファイルの削除
     *
     * @param String $path 削除ファイルパス
     * @param Int $min 削除対象とする経過時間（分）
     * @param Boolean
     */
    function removeExpiredFile($path, $min)
    {
        $now = time();

        if (!($dir = @opendir($path))) {
            return false;
        }
        while ($item = readdir($dir)) {

            if ($item == "." || $item == "..") {
                continue;
            }
            
            if (!is_dir($path . $item)) {

                $name = $path . $item;
                $mtime = filemtime($name);

                // 指定分以上経過したファイルを削除
                if (($now - $mtime) >= $min * 60) {

                    if (file_exists($name)) {

                        unlink($name);

                    }
                }
            }
        }
        closedir($dir);

        return false;
    }    
    

    /**
     * ファイル削除
     * @param string $file
     */
    function removeFile($file)
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    
    /**
     * ディレクトリ中のファイル更新日時変更
     * @param string $file
     * @param string $datetime
     */
    function touchDirectory($directory, $datetime)
    {
        if (is_dir($directory)) {
            
            $date = strptime(date($datetime), "%Y-%m-%d %H:%M:%S");
            $year = $date['tm_year'] + 1900;
            $month = $date['tm_mon'] + 1;
            $day = $date['tm_mday'];
            $hour = $date['tm_hour'];
            $min = $date['tm_min'];
            $sec = $date['tm_sec'];
            $timestamp = mktime($hour, $min, $sec, $month, $day, $year);

            if ($handle = opendir($directory)) {
                while (($item = readdir($handle)) !== false) {
                    
                    if ($item == "." || $item == "..") {
                        continue;
                    }

                    if (is_dir($directory . "/" . $item)) {
                        // ディレクトリであれば自身を再帰呼出する
                        touch($directory . "/" . $item, $timestamp);
                        $this->touchDirectory($directory . "/" . $item, $datetime);
                    } else {
                        touch($directory . "/" . $item, $timestamp);
                    }
                }
                closedir($handle);
            }
        }
    }
    
    
    /**
     * ファイル出力
     * @param string $file
     * @param string $data
     * @param string $writeOption 
     */
    function writeFile($file, $data, $writeOption = 'a')
    {
        $filePointer = fopen($file, $writeOption);
        fwrite($filePointer, $data);
        fclose($filePointer);
        
        if (file_exists($file)) {
            chmod($file, 0666);
        }
    }
}

/* End of file File.php */
