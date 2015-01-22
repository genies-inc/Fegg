<?php
/*
 * Validationクラス
 * 
 * 値をチェックするためのメソッドを提供
 * 関連ファイル： $validation_error_message.php
 * 
 * @access public
 * @author Genies, Inc.
 * @version 1.0.8
 */
class Validation {

    private $_errorFlag;
    private $_errorMessage;
    private $_validationErrorMessage;
    
    
    /**
     * Constructor
     */
    function __construct($languageCode)
    {
        // 初期化
        $this->_errorFlag = false;
        
        // if文でのエラー判定簡素化のために初期値をfalseに
        $this->_errorMessage = false;
        
        // エラーメッセージ取得
        if ($languageCode) {
            $languageCode = '/' . $languageCode;
        }
        require(FEGG_CODE_DIR . "/config" . $languageCode . "/validation_error_message.php");
        
        $this->_validationErrorMessage = $validation_error_message;
    }


    /**
     * エラー設定（コード指定）
     * @param string $name 項目名
     * @param string $code エラーコード
     */
    public function _setError($name, $code = '')
    {
        $this->_errorFlag = true;
        if (is_array($code)) {
            // $codeがarray型の場合は、array[0]:基本文章コード、array[1]:パラメーター１コード、array[2]..と続く
            $statement = array_shift($code);
            $word = array();
            foreach ($code as $key => $value) {
                $word[] = isset($this->_validationErrorMessage[$value]) ? $this->_validationErrorMessage[$value] : $value;
            }
            $this->_errorMessage[$name] = vsprintf($this->_validationErrorMessage[$statement], $word);
        } else {
            $this->_errorMessage[$name] = isset($this->_validationErrorMessage[$code]) ? $this->_validationErrorMessage[$code] : $code; 
        }
    }
    
    
    /**
     * 英数字（半角）検証
     * @param string $name 項目名
     * @param string $value 検証値
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function alphameric($name, $value, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (preg_match('/^[0-9a-zA-Z]+$/', $value)) {

            $flag = true;
            
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    
    
    /**
     * 日付検証
     * @param string $name 項目名
     * @param string $value 日付（のみや月までの指定も可）
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function date($name, $value, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }

        // 日付に含まれる/,-を除去
        $value = preg_replace('/[\/\-]/', '', $value);
                    
        // 検証
        $flag = false;
        if (preg_match('/^[0-9]+$/', $value)) {

            // 文字数毎に処理
            switch (strlen($value)) {
                case 4:
                    $flag = true;
                    break;
                
                case 6:
                    $month = substr($value, 4, 2);
                    if ($month >= 1 && $month <= 12) {
                        $flag = true;
                    }
                    break;
                    
                case 8:
                    $year = substr($value, 0, 4);
                    $month = substr($value, 4, 2);
                    $day = substr($value, 6, 2);
                    $flag = checkdate($month + 0, $day + 0, $year + 0);
                    break;
            }
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * EMail検証
     * @param string $name 項目名
     * @param string $value Emailアドレス
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function email($name, $value, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (preg_match('/^[-\+\w](\\.?[-\+\w])*@[-\+\w]+(\.[-\+\w]+)*(\.[a-zA-Z]{2,4})$/', $value)) {

            $flag = true;
            
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * エラーメッセージ取得
     * @return array エラー時：メッセージ配列　正常時:false
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    
    /**
     * 半角検証
     * @param string $name 項目名
     * @param string $value 検証対象値
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function hankaku($name, $value, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (strlen($value) == mb_strlen($value)) {

            $flag = true;
            
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * 実行時点でのエラー有無判定
     * @return boolean エラー時： true / 正常時： false
     */
    public function isError()
    {
        return $this->_errorFlag;
    }
    

    /**
     * カタカナ検証
     * @param string $name 項目名
     * @param string $value 検証対象値
     * @param string $code エラーメッセージコード
     * @param boolean $zenkakuFlag 全角のみ指定（通常半角のみ）
     * @return boolean 正常： true / 異常： false
     */
    public function katakana($name, $value, $code = '', $zenkakuFlag = false)
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;

        if ($zenkakuFlag) {
            if (preg_match("/^[ァ-ヶー　]+$/u", $value)) {

                $flag = true;

            }
        } else {
            mb_regex_encoding("UTF-8");
            if (preg_match("/^^[｡-ﾟ ()\.]+$/u", $value)) {

                $flag = true;

            }
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    
    
    /**
     * 文字数
     * @param string $name 項目名
     * @param string $value 検証値
     * @param numeric $length 文字数
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function length($name, $value, $length, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        
        $encode = mb_internal_encoding();   
        mb_internal_encoding('UTF-8');
        
        if (mb_strlen($value) == $length) {
            
            $flag = true;
            
        }
        
        mb_internal_encoding($encode);       

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;        
    }
    
    
    /**
     * 最大バイト数
     * @param string $name 項目名
     * @param string $value 検証値
     * @param numeric $byte 最大バイト数
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function maxbyte($name, $value, $byte, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;

        if (strlen($value) <= $byte) {

            $flag = true;

        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    
    
    /**
     * 最大文字数
     * @param string $name 項目名
     * @param string $value 検証値
     * @param numeric $length 最大文字数
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function maxlength($name, $value, $length, $code = '') 
    {

        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        
        $encode = mb_internal_encoding();   
        mb_internal_encoding('UTF-8');
        
        if (mb_strlen($value) <= $length) {
            
            $flag = true;
            
        }
        
        mb_internal_encoding($encode);       

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * 最大文字数
     * @param string $name 項目名
     * @param string $value 検証値
     * @param numeric $length 最少文字数
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function minlength($name, $value, $length, $code = '') 
    {

        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        
        $encode = mb_internal_encoding();   
        mb_internal_encoding('UTF-8');
        
        if (mb_strlen($value) >= $length) {
            
            $flag = true;
            
        }
        
        mb_internal_encoding($encode);       

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    
    
    /**
     * 数値
     * @param string $name 項目名
     * @param string $value 検証値
     * @param string $code エラーメッセージコード
     * @param boolean $decimalPointFlag 小数点以下を許可
     * @param boolean $minusFlag マイナス値を許可
     * @return boolean 正常： true / 異常： false
     */
    public function numeric($name, $value, $code = '', $decimalPointFlag = false, $minusFlag = false) 
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }

        // 検証パターンを編集
        $pattern = '/^';
        $pattern .= $minusFlag ? '[\-]*' : '';
        $pattern .= '[0-9]+';
        $pattern .= $decimalPointFlag ? '(\.[0-9]+){0,1}' : '';
        $pattern .= '$/';
            
        // 検証
        $flag = false;
        if (preg_match($pattern, $value)) {

            $flag = true;
            
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * パスワード
     * @param string $name 項目名
     * @param string $value パスワード（半角英数と-_@!#）
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function password($name, $value, $code = '', $mixedLettersFlag = false)
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (!$mixedLettersFlag) {
            // 半角英数
            if (preg_match('/^[0-9a-zA-Z\-\_\@\!\#]+$/', $value)) {
                $flag = true;
            }
        } else {
            // 英数字混合
            if (preg_match('/^[0-9a-zA-Z\-\_\@\!\#]+$/', $value) && preg_match('/([0-9].*[a-zA-Z]|[a-zA-Z].*[0-9])/', $value)) {
                $flag = true;
            }
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
        
    }
    
    
    /**
     * 必須入力
     * @param string $name 項目名
     * @param mixed $value 検証値（1次元配列のみ対応）
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function required($name, $value, $code = '') {

        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (!is_array($value)) {
            if (trim($value) != '') {
                $flag = true;
            }
        } else {
            $result = array_filter($value);
            if (!empty($result)) {
                $flag = true;
            }
        }
        
        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * エラー設定（コード指定）
     * @param string $name 項目名
     * @param string $code エラーコード
     */
    public function setError($name, $code = '')
    {
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        $this->_setError($name, $code); 
    }
    

    /**
     * エラー設定（メッセージ指定）
     * @param string $name 項目名
     * @param string $message エラーメッセージ
     */
    public function setErrorMessage($name, $message)
    {
        $this->_errorFlag = true;
        $this->_errorMessage[$name] = $message; 
    }

    
    /**
     * 電話番号
     * @param string $name 項目名
     * @param string $value 電話番号（先頭国番号対応）
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function tel($name, $value, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (preg_match('/^\+*[0-9\(\)\-]+[0-9]+$/', $value)) {

            $flag = true;
            
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * 時間
     * @param string $name 項目名
     * @param string $value 時間（24時間形式）
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function time($name, $value, $code = '') 
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }

        // 時間に含まれる:を除去
        $value = preg_replace('/[\:]/', '', $value);
                    
        // 検証
        $flag = false;
        if (preg_match('/^[0-9]{4,4}$/', $value)) {

            $hour = substr(trim($value), 0 ,2);
            $minute = substr(trim($value), 2 ,2);
            if (($hour >= 0 && $hour <= 23) && ($minute >= 0 && $minute <= 59)) {
                $flag = true;
            }

        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    
    
    /**
     * URL
     * @param string $name 項目名
     * @param string $value url
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function url($name, $value, $code = '') 
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        
        // XSSを防ぐための確認
        if (!preg_match('|[^-/?:#@&=+$,\w.!~*;\'()%]|', $value)) {
            
            // URL形式を確認
            if (preg_match('/^(https?|ftp)(:\/\/[0-9a-zA-Z\.\-\_\/\~\?=%#:]+)$/', $value)) {

                $flag = true;
                
            }
        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    

    /**
     * ユーザーID
     * @param string $name 項目名
     * @param string $value パスワード（半角英数と-_.）
     * @param string $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function userId($name, $value, $code = '')
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (preg_match('/^[0-9a-zA-Z\-\_\.]+$/', $value)) {

            $flag = true;

        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
    
    
    /**
     * 郵便番号（日本形式のみ対応）
     * @param type $name 項目名
     * @param type $value 郵便番号
     * @param type $code エラーメッセージコード
     * @return boolean 正常： true / 異常： false
     */
    public function zipcode($name, $value, $code = '') 
    {
        // 空白時は処理しない
        if (trim($value) == '') { return true; }
        
        // 既に同じ名称のエラーが設定されている場合処理しない
        if (isset($this->_errorMessage[$name])) { return false; }
            
        // 検証
        $flag = false;
        if (preg_match('/^\d{3}[\-]*\d{4}$/', $value)) {

            $flag = true;

        }

        // エラーメッセージ設定
        if (!$flag) { 
            $this->_setError($name, $code); 
        }

        return $flag;
    }
}
/* End of file Validation.php */