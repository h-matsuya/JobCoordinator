<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Attendance_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * 出退勤情報取得要求処理
     * 
     * ログインユーザの出退勤情報をDBから取得します。
     * 
     * @param object $modelAttendance  出退勤情報モデルのインスタンスオブジェクト
     * @param array  $userData         ログインユーザセッション保持情報
     * @param string $dateFrom         期間表示指定の期間FROM
     * @param string $dateEnd          期間表示指定の期間TO
     * 
     * @return array $attendance       取得した出退勤情報のレコード配列
     */
    public function _get_attendance_list($modelAttendance, $userData, $dateFrom, $dateEnd)
    {
        // 表示データ収集
        // 操作したプロパティを一度全て破棄
        $modelAttendance->clear_all_propaty();
        // 出退勤情報取得
        $modelAttendance->setParam(array(
            'UNIQUE_KEY'           => $userData['UNIQUE_KEY'],
            'ATTENDANCE_DATE_FROM' => $dateFrom,
            'ATTENDANCE_DATE_TO'   => $dateEnd,
        ));
        $attendance = $modelAttendance->get_once_user_attendances();

        return $attendance;
    }

    /**
     * 出勤時刻登録要求処理
     * 
     * 現在時刻を取得し、ログインユーザの出勤日時情報をDBに登録します。
     * 
     * @param object $modelAttendance  出勤情報モデルのインスタンスオブジェクト
     * @param array  $userData         ログインユーザセッション保持情報
     * 
     * @return object $modelAttendance 出勤情報モデルのインスタンスオブジェクト
     */
    public function _regist_checkin($modelAttendance, $userData)
    {
        // 現在日時を取得する
        $now_date = $this->CI->my_date->_get_now_date();
        $now_time = $this->CI->my_date->_get_now_time();
        // パラメータを設定する
        $modelAttendance->setParam(array(
            'UNIQUE_KEY'      => $userData['UNIQUE_KEY'],
            'ATTENDANCE_DATE' => $now_date,
            'CHECKIN_TIME'    => $now_time
        ));
        // 設定した値をデータ登録する
        $modelAttendance->insert();

        return $modelAttendance;
    }

    /**
     * 退勤時刻登録要求処理
     * 
     * 現在時刻を取得し、ログインユーザの退勤時刻情報をDBに登録します。
     * 
     * @param object $modelAttendance  出勤情報モデルのインスタンスオブジェクト
     * @param array  $userData         ログインユーザセッション保持情報
     * 
     * @return object $modelAttendance 出勤情報モデルのインスタンスオブジェクト
     */
    public function _regist_checkout($modelAttendance, $userData)
    {
        // 現在日時を取得する
        $now_date = $this->CI->my_date->_get_now_date();
        $now_time = $this->CI->my_date->_get_now_time();
        // 出退勤情報取得
        $modelAttendance->setParam(array(
            'UNIQUE_KEY' => $userData['UNIQUE_KEY'],
            'ATTENDANCE_DATE' => $now_date,
        ));
        $updata = $modelAttendance->get_once_user_attendance();
        // 更新情報を追加
        $updata['CHECKOUT_TIME'] = $now_time;
        // パラメータを設定する
        $modelAttendance->setParam($updata);
        // 設定した値をデータ更新する
        $modelAttendance->update();

        return $modelAttendance;
    }

    /**
     * 出退勤詳細情報更新要求処理
     * 
     * 入力された更新情報を、ログインユーザの出退勤情報DBに登録します。
     * 
     * @param object $modelAttendance    出勤情報モデルのインスタンスオブジェクト
     * @param array  $userData           ログインユーザセッション保持情報
     * @param string $manualSelectedDate 更新対象日
     * @param float  $manualBreaktime    更新対象の休憩時間
     * @param string $manualRemarks      更新対象の備考
     * @param string $manualCheckout     更新対象の時刻 H:i:s
     * 
     * @return object $modelAttendance   出勤情報モデルのインスタンスオブジェクト
     */
    public function _update_detail($modelAttendance, $userData, $manualSelectedDate, $manualBreaktime, $manualRemarks, $manualCheckout)
    {
        // 出退勤情報取得
        $modelAttendance->setParam(array(
            'UNIQUE_KEY' => $userData['UNIQUE_KEY'],
            'ATTENDANCE_DATE' => $manualSelectedDate,
        ));
        $updata = $modelAttendance->get_once_user_attendance();
        // 更新情報を追加
        $updata['BREAK_TIME'] = $manualBreaktime;
        $updata['REMARKS'] = $manualRemarks;
        if ($manualCheckout !== null) {
            $updata['CHECKOUT_TIME'] = $manualCheckout;
        }
        // パラメータを設定する
        $modelAttendance->setParam($updata);
        // 設定した値をデータ更新する
        $modelAttendance->update();

        return $modelAttendance;
    }

    /**
     * 出退勤一覧表示用データ整形処理
     * 
     * 取得した情報を基に表示用のデータに整形します。
     * ・合計時間情報を取得する。
     * ・出退勤時間と休憩時間を基に稼働時間を取得する。
     * 
     * @param  array $attendance 取得した出退勤情報のレコード配列
     * 
     * @return array 取得した出退勤情報のレコード配列の整形後の配列、合計時間情報
     */
    public function _prepare_data($attendance)
    {
        // サマリ情報,休憩を差し引いた勤務時間の取得
        $sumWorkTime = 0;
        $sumBreakTime = 0;
        foreach ($attendance as $key => &$val) {
            // 休憩時間がある場合は休憩時間を減算し、合計休憩時間を加算する
            if (!empty($val['BREAK_TIME'])) {
                $val['WORKING_TIME'] -= $val['BREAK_TIME'];
                $sumBreakTime += $val['BREAK_TIME'];
            }
            $sumWorkTime += $val['WORKING_TIME'];
        }

        return array(
            'attendance'   => $attendance,
            'sumBreakTime' => $sumBreakTime,
            'sumWorkTime'  => $sumWorkTime,
        );
    }


    /**
     * 出退勤詳細情報更新要求処理
     * 
     * 入力された更新情報を、ログインユーザの出退勤情報DBに登録します。
     * 
     * @param string $IndateMonth 月表示指定の指定月
     * @param string $IndateStart 期間表示指定の期間FROM
     * @param string $IndateEnd   期間表示指定の期間TO
     * 
     * @return array $period DBにてフィルタする為のFROM TOの年月日と画面のラジオボタンチェック情報
     * ※ 返却値はlist関数にて各値を変数化して使用することを推奨します。
     */
    public function _get_period_status($IndateMonth, $IndateStart, $IndateEnd)
    {
        // 現在日時を取得する
        $now_date = $this->CI->my_date->_get_now_date();
        $now_time = $this->CI->my_date->_get_now_time();
        // 表示期間指定
        $dateFrom = $this->CI->my_date->_get_m_first_day($now_date);
        $dateEnd  = $this->CI->my_date->_get_m_last_day($dateFrom);
        // 表示切り替え状態、ラジオボタン選択状態保持用
        $selectedMonth = true;
        $selectedrange = false;
        // 表示月の期間指定が存在する場合は指定月を表示する
        if ($IndateMonth !== null) {
            $dateFrom = $this->CI->my_date->_convert_date($IndateMonth, 'Y-m-d');
            $dateEnd  = $this->CI->my_date->_get_m_last_day($dateFrom);
        }
        // 期間指定が存在する場合は期間日を表示する
        if ($IndateStart !== null && $IndateEnd !== null) {
            $dateFrom = $this->CI->my_date->_convert_date($IndateStart,' Y-m-d');
            $dateEnd  = $this->CI->my_date->_convert_date($IndateEnd, 'Y-m-d');
            $selectedMonth = false;
            $selectedrange = true;
        }

        return array(
            'dateFrom'      => $dateFrom,
            'dateEnd'       => $dateEnd,
            'selectedMonth' => $selectedMonth,
            'selectedrange' => $selectedrange,
        );
    }
}
