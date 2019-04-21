<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class My_date {

    public function __construct()
    {
    }

    /**
     * 現在年月日を取得します。
     * 
     * @param  string $date Y-m-d を含む日時
     * @return string Y-m-dに変換した現在の年月日文字列
     */
    public function _get_now_date() {
        $date = new DateTime('now');
        return $date->format('Y-m-d');
    }

    /**
     * 現在時刻を取得します。
     * 
     * @param  string $date Y-m-d を含む日時
     * @return string H:i:sに変換した現在の時分秒文字列
     */
    public function _get_now_time() {
        $date = new DateTime('now');
        return $date->format('H:i:s');
    }

    /**
     * 指定したフォーマットにおいて日時などを取得します。
     * 「年」「月」「日」「時」「分」「秒」が含まれる場合は、「Y-m-d H:i:s」
     * に変換し、変換処理を行います。
     * 
     * @param  string $date Y-m-d を含む日時
     * @return string $val1 指定フォーマットに変換した年月日文字列
     */
    public function _convert_date($date, $format) {
        $date = rtrim(str_replace(array('年', '月',), '-' , str_replace(array('時', '分'), ':', str_replace(array('日', '秒'), ' ', $date))));
        $dateInstance = new DateTime($date);
        $val1 = $dateInstance->format($format);
        return $val1;
    }

    /**
     * 指定した日の曜日を取得します。
     *
     * @param  string $datetime Y-m-d を含む日時文字列
     * @return string 対象の曜日
     */
    public function _get_day_of_week($datetime) {
        $week = array( "日", "月", "火", "水", "木", "金", "土" );
        $dateObj = new DateTime($datetime);
        return $week[$dateObj->format("w")];
    }

    /**
     * 時刻を指定した分単位で切り上げします。
     *
     * @param  string  $time hh:mm を含む日時
     * @param  integer $per  mm 切り上げる対象とする分
     * @return string  切り上げた結果の時分 $time が存在しない場合は空文字("")を返却する
     */
    public function _ceil_per_time($time, $per = 15) {
        if (!isset($time) || empty($time)) return '';
        $deteObj = new DateTime($time);
        // 切り上げ処理
        $ceil_num = ceil(sprintf('%d', $deteObj->format('i')) / $per) * $per;

        // 切り上げた結果、時(h)が上がる場合は時を1hあげ分を0に変更する
        $hour = $deteObj->format('H');
        if($ceil_num == 60 ) {
            $hour = $deteObj->modify('+1 hour')->format('H');
            $ceil_num = '00';
        }
        $have = $hour . sprintf('%02d', $ceil_num);
        $ceilTimeObj = new DateTime($have);

        return $ceilTimeObj->format('H:i');
    }

    /**
     * 時刻を指定した分単位で切り捨てします。
     *
     * @param  string  $time hh:mm を含む日時
     * @param  integer $per  mm 切り捨てる対象とする分
     * @return string  hh:mm 切り捨てた結果の時分 $time が存在しない場合は空文字("")を返却する
     */
    public function _floor_per_time($time, $per = 15) {
        if (!isset($time) || empty($time)) return '';
        $deteObj = new DateTime($time);
        // 切り上げ処理
        $floor_num = floor(sprintf('%d', $deteObj->format('i')) / $per) * $per;

        $hour = $deteObj->format('H');
        $have = $hour . sprintf('%02d', $floor_num);
        $floorTimeObj = new DateTime($have);

        return $floorTimeObj->format('H:i');
    }

    /**
     * 指定した年月の初日を返却します。
     *
     * @param  string $date y/m を含む日時
     * @return string 月の初日 y-m-d
     */
    public function _get_m_first_day($date) {
        if (!isset($date) || empty($date)) return '';
        $deteObj = new DateTime($date);

        $lastday = new DateTime(date('Y-m-d',
                                    mktime(0,    //時
                                        0,       //分
                                        0,       //秒
                                        $deteObj->format('m'),  //月
                                        1,       //日(0を指定すると前月の末日)
                                        $deteObj->format('Y')   //年
                                        )));

        return $lastday->format('Y-m-d');
    }

    /**
     * 指定した年月の最終日を返却します。
     *
     * @param  string $date y/m を含む日時
     * @return string 月の最終日 y-m-d
     */
    public function _get_m_last_day($date) {
        if (!isset($date) || empty($date)) return '';
        $deteObj = new DateTime($date);

        $lastday = new DateTime(date('Y-m-d',
                                    mktime(0,    //時
                                        0,       //分
                                        0,       //秒
                                        $deteObj->format('m') + 1,  //月
                                        0,       //日(0を指定すると前月の末日)
                                        $deteObj->format('Y')   //年
                                        )));

        return $lastday->format('Y-m-d');
    }

    /**
     * 指定した時刻の差分を小数点第2位まで含めて時間(h)で返却します。
     * 指定された時刻は一度切り捨て処理を行う。
     *
     * @param  string $datetimeFrom y-m-d hh:mm
     * @param  string $datetimeTo   y-m-d hh:mm
     * @return float  差分時間(h)
     */
    public function _get_diff_time($datetimeFrom, $datetimeTo) {
        // TODO 固有の処理はサービスクラスに移行する(切り捨てと小数点制御)
        $datetimeFrom = $this->_ceil_per_time($datetimeFrom);
        $datetimeTo   = $this->_floor_per_time($datetimeTo);
        $diff_hour = (strtotime($datetimeTo) - strtotime($datetimeFrom)) / 3600;
        return number_format($diff_hour,2);
    }

}
