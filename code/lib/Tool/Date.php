<?php
/**
 * Tool_Dateクラス
 * 
 * 日付の操作に必要な処理を提供するクラス。
 * 
 * @access public
 * @author Genies, Inc.
 * @version 1.0.4
 */
class Tool_Date
{
    function __construct()
    {
    }


    /**
     * 日数指定による日付計算
     * @param string $dateTime
     * @param int $interval
     * @return string 「%Y-%m-%d」形式
     */
    function addDay($dateTime, $interval, $format = 'Y-m-d H:i:s')
    {
        // 日付要素取得
        $dateTime = $this->makeupDateFormat($dateTime);
        $date = strptime(date($dateTime), "%Y-%m-%d %H:%M:%S");

        $year = $date['tm_year'] + 1900;
        $month = $date['tm_mon'] + 1;
        $day = $date['tm_mday'];
        $hour = $date['tm_hour'] ? $date['tm_hour'] : '00';
        $min = $date['tm_min'] ? $date['tm_min'] : '00';
        $sec = $date['tm_sec'] ? $date['tm_sec'] : '00';

        // 指定された日数分移動
        $timeStamp = mktime($hour, $min, $sec, $month, $day, $year);
        $timeStamp = $timeStamp + $interval * 86400;
    
        return date($format, $timeStamp);
    }
    

    /**
     * 分数指定による日付計算
     * @param string $dateTime
     * @param int $interval
     * @return string 「%Y-%m-%d」形式
     */
    function addMinute($dateTime, $interval, $format = 'Y-m-d H:i:s')
    {
        // 日付要素取得
        $dateTime = $this->makeupDateFormat($dateTime);
        $date = strptime(date($dateTime), "%Y-%m-%d %H:%M:%S");
        
        $year = $date['tm_year'] + 1900;
        $month = $date['tm_mon'] + 1;
        $day = $date['tm_mday'];
        $hour = $date['tm_hour'];
        $min = $date['tm_min'];

        // 指定された日数分移動
        $timeStamp = mktime($hour, $min, 0, $month, $day, $year);
        $timeStamp = $timeStamp + $interval * 60;
    
        return date($format, $timeStamp);
    }
    
    
    /**
     * 月数指定による日付計算
     * @param string $dateTime
     * @param int $interval
     * @return string 「%Y-%m-%d H:i:s」形式
     */
    function addMonth($dateTime, $interval, $format = 'Y-m-d H:i:s')
    {
        // 日付要素取得
        $dateTime = $this->makeupDateFormat($dateTime);
        $date = strptime(date($dateTime), "%Y-%m-%d %H:%M:%S");
        
        $year = $date['tm_year'] + 1900;
        $month = $date['tm_mon'] + 1;
        $day = $date['tm_mday'];

        // 指定された月数分移動
        $month = $month + $interval;

        // 最終日取得
        $lastDay = $this->getTheLastday(sprintf("%04d%02d", $year, $month));
        if ($day > $lastDay) { $day = $lastDay; }
        
        // タイムスタンプに変換
        $timeStamp = mktime(0, 0, 0, $month, $day, $year);
        
        return date($format, $timeStamp);
    }
    

    /**
     * ２つの日時の日数差取得
     * @param string $fromDateTime
     * @param string $toDateTime
     * @param boolean $month true:月数で算出 false:日数で算出
     * @return int 
     */
    function getDiff($fromDateTime, $toDateTime, $month = false)
    {
        $interval = 0;
        
        if ($month) {

            // 月数差
            $fromDateTime = $this->makeupDateFormat($fromDateTime);
            $toDateTime = $this->makeupDateFormat($toDateTime);

            $fromTime = strtotime($fromDateTime);
            $toTime = strtotime($toDateTime);
            $fromMonth = date("Y", $fromTime) * 12 + date("m", $fromTime);
            $toMonth = date("Y", $toTime) * 12 + date("m", $toTime);
            
            $interval = $toMonth - $fromMonth;
            
        } else {
            
            // 日数差
            $fromDateTime = $this->makeupDateFormat($fromDateTime);
            $date = strptime(date($fromDateTime), "%Y-%m-%d %H:%M:%S");
            $year = $date['tm_year'] + 1900;
            $month = $date['tm_mon'] + 1;
            $day = $date['tm_mday'];
            $hour = $date['tm_hour'];
            $min = $date['tm_min'];
            $sec = $date['tm_sec'];
            $fromTimeStamp = mktime($hour, $min, $sec, $month, $day, $year);

            $toDateTime = $this->makeupDateFormat($toDateTime);
            $date = strptime(date($toDateTime), "%Y-%m-%d %H:%M:%S");
            $year = $date['tm_year'] + 1900;
            $month = $date['tm_mon'] + 1;
            $day = $date['tm_mday'];
            $hour = $date['tm_hour'];
            $min = $date['tm_min'];
            $sec = $date['tm_sec'];
            $toTimeStamp = mktime($hour, $min, $sec, $month, $day, $year);

            $intervalTime = $toTimeStamp - $fromTimeStamp;
            $interval = $intervalTime / 3600 / 24;
        }
        
        return $interval;
    }
    
    
    /**
     * 西暦取得
     * @param string $date H010101形式の和暦
     * @return string %Y-%m-%d形式
     */
    function getWesternCalendar($date)
    {
        $era = strtoupper(mb_substr($date, 0, 1));
        $year = mb_substr($date, 1, 2);

        // 和暦に応じて年度加算
        switch($era){
            case "T":
                $year = 1911 + $year;
                break;
            case "S":
                $year = 1925 + $year;
                break;
            case "H":
                $year = 1988 + $year;
                break;
        }
     
        return $year . '-' . mb_substr($date, 3, 2) . '-' . mb_substr($date, 5, 2);
    }
    
    
    /**
     * GMTオフセット取得
     * @param string $timeZone
     * @return string 「+09:00」形式 
     */
    function getGmtOffset($timeZone = 'Ajia/Tokyo')
    {
        $dateTimeZone = new DateTimeZone($timeZone);
        $dateTime = new DateTime("now", $dateTimeZone);
        $offset = $dateTimeZone->getOffset($dateTime);

        if ($offset >= 0) {
            $code = '+';
        } else {
            $code = '-';
        }
        $offset = abs($offset);
        $hour = floor($offset / 3600);
        $min = floor(($offset - $hour * 3600) / 60);
        
        return sprintf("%s%02d:%02d", $code, $hour, $min);
    }
    
    
    /**
     * ソーシャルメディア風時刻取得
     * @param string $date 変換元日付
     * @param string $timezone 変換元タイムゾーン（Asia/Tokyo形式）
     * @return string 「10分前」形式 
     */
    function getSocialTime($date, $timezone = '') {
        
        // 変換元タイムゾーンで日付をtimeに変換
        if ($timezone) {
            date_default_timezone_set($timezone);
        }

        $date = $this->makeupDateFormat($date);
        $time = strtotime($date);
        $currentTime = strtotime('now');
        $socialTime = abs($time - $currentTime);

        $day = floor($socialTime / (24*60*60));
        $hour = floor($socialTime / (60*60));
        $minits = floor($socialTime / 60)-($hour*60);
        $second = $socialTime % 60;

        if($day > 0){
            $socialTime = $day . "日前";
        } else if($hour > 0){
            $socialTime = $hour . "時間前";
        } else if($minits > 0){
            $socialTime = $minits . "分前";
        } else if($second > 0){
            $socialTime = $second . "秒前";
        } else {
            $socialTime = "";
        }

        return $socialTime;
    }
    
    
    /**
     * 月末の日付取得
     * @param string $dateTime
     * @return string 
     */
    function getTheLastday($dateTime)
    {
        // 日付要素取得
        $dateTime = $this->makeupDateFormat($dateTime);
        $date = strptime(date($dateTime), "%Y-%m-%d %H:%M:%S");
        
        $year = $date['tm_year'] + 1900;
        $month = $date['tm_mon'] + 1;
        
        // mktime関数で日付を0にして前月の末日を取得
        $date = mktime(0, 0, 0, $month + 1, 0, $year);
        
        return date("d", $date);
    }


    /**
     * 日付フォーマットの成形
     * @param string $date
     * @return string 変換可：変換後日付　変換不可：空白
     */
    function makeupDateFormat($date)
    {
        // 年月日などの各数字は前ゼロであること
        // 年月日などの区切り記号[-,/]、日付の区切り記号[:]は任意
        // 日付と時間の間の空白は任意
        $pattern = array(
            '/^([0-9]{4})$/' => '$1-01-01 00:00:00',
            '/^([0-9]{4})[\-\/]*([0-9]{2})[\-\/]*$/' => '$1-$2-01 00:00:00',
            '/^([0-9]{4})[\-\/]*([0-9]{2})[\-\/]*([0-9]{2})\s*$/' => '$1-$2-$3 00:00:00',
            '/^([0-9]{4})[\-\/]*([0-9]{2})[\-\/]*([0-9]{2})\s*([0-9]{2})[\:]*$/' => '$1-$2-$3 $4:00:00',
            '/^([0-9]{4})[\-\/]*([0-9]{2})[\-\/]*([0-9]{2})\s*([0-9]{2})[\:]*([0-9]{2})[\:]*$/' => '$1-$2-$3 $4:$5:00',
            '/^([0-9]{4})[\-\/]*([0-9]{2})[\-\/]*([0-9]{2})\s*([0-9]{2})[\:]*([0-9]{2})[\:]*([0-9]{2})$/' => '$1-$2-$3 $4:$5:$6',
        );
        $fixedDate = preg_replace(array_keys($pattern), array_values($pattern), $date);
        
        return $fixedDate;
    }
}

/* End of file Tool_Date.php */
