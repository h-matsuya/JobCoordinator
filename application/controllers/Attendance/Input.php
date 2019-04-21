<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Input extends CI_Controller {

    const ATTENDANCE_START    = 1;	// 勤怠打刻画面出力
    const ATTENDANCE_CHECKIN  = 2;	// 出勤時刻登録処理
    const ATTENDANCE_CHECKOUT = 3;	// 退勤時刻登録処理
    const ATTENDANCE_UPDATE   = 4;	// 詳細情報更新処理

    const REQUEST_CHECKIN     = 1;	// 出勤時刻登録要求
    const REQUEST_CHECKOUT    = 2;	// 退勤時刻登録要求
    const REQUEST_UPDATE      = 3;	// 詳細情報更新要求

    protected $viewType = 0;
    protected $viewData = NULL;

    // ログイン情報
    protected $userData = '';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User', 'modelUser', TRUE);
        $this->load->model('Attendance', 'modelAttendance', TRUE);
        $this->load->library('controllers/Attendance/attendance_lib');
        $this->load->helper('date');
        $this->userData = $this->session->userdata($this->config->item('sess_member'));
    }

/********************* ↓ routes function ↓ *********************/
    public function index()
    {
        $this->viewType = $this->_preprocess();
        $this->_mainprocess();
        $this->_main_view();
    }

/********************* ↓ main function ↓ *********************/
    protected function _preprocess()
    {
        // ログインチェック
        $this->login_lib->_is_logged_in('login');

        $res = 0;
        switch($this->input->post('check')){
            case self::REQUEST_CHECKIN:
                // 各種対応制御indexをセット
                $res = self::ATTENDANCE_CHECKIN;
                break;
            case self::REQUEST_CHECKOUT:
                // 各種対応制御indexをセット
                $res = self::ATTENDANCE_CHECKOUT;
                break;
            case self::REQUEST_UPDATE:
                // 各種対応制御indexをセット
                $res = self::ATTENDANCE_UPDATE;
                break;
            default:
                // 各種対応制御indexをセット
                $res = self::ATTENDANCE_START;
                break;
        }
        return $res;
    }

    protected function _mainprocess()
    {
        switch($this->viewType){
            // 出勤打刻
            case self::ATTENDANCE_CHECKIN:
                // 出勤時刻登録処理
                $this->modelAttendance = $this->attendance_lib->_regist_checkin($this->modelAttendance, $this->userData);
                break;
            // 退勤打刻
            case self::ATTENDANCE_CHECKOUT:
                // 退勤時刻登録処理
                $this->modelAttendance = $this->attendance_lib->_regist_checkout($this->modelAttendance, $this->userData);
                break;
            // 詳細情報各種更新
            case self::ATTENDANCE_UPDATE:
                // 更新するための入力パラメータ
                $manualSelectedDate = $this->input->post('manual_selected_date');
                $manualBreaktime    = $this->input->post('manual_breaktime');
                $manualRemarks      = $this->input->post('manual_remarks');
                $manualCheckout     = $this->input->post('manual_checkout');
                // 出退勤情報更新処理
                $this->modelAttendance = $this->attendance_lib->_update_detail(
                    $this->modelAttendance,
                    $this->userData,
                    $manualSelectedDate,
                    $manualBreaktime,
                    $manualRemarks,
                    $manualCheckout
                );
                break;
            // 初期表示,表示期間指定時 or その他
            case self::ATTENDANCE_START:
            default:
                break;
        }

        // 期間指定の選択状態および期間指定状態の入力パラメータ
        $IndateMonth = $this->input->get('date_month');
        $IndateStart = $this->input->get('date_start');
        $IndateEnd   = $this->input->get('date_end');
        // 検索期間、期間指定状態の取得
        $period = $this->attendance_lib->_get_period_status($IndateMonth, $IndateStart, $IndateEnd);

        // 出勤情報一覧を取得
        $attendance = $this->attendance_lib->_get_attendance_list($this->modelAttendance, $this->userData, $period['dateFrom'], $period['dateEnd']);

        // 画面表示の為のデータ整形(画面部品選択情報、サマリ情報)
        $prepareData = $this->attendance_lib->_prepare_data($attendance);
        $attendance = $prepareData['attendance'];

        // ユーザ詳細取得
        $user = $this->modelUser->get_once_user($this->userData['LOGIN_ID']);

        // 出勤ボタンの状態制御のため、現在日の登録済み出勤情報を取得
        $todayAttend = $this->attendance_lib->_get_attendance_list($this->modelAttendance, $this->userData, $this->my_date->_get_now_date(), $this->my_date->_get_now_date());

        // View表示用変数へ表示用データを設定する
        $this->_set_view_data(array(
            'user'        => $user,
            'attendance'  => array_column($attendance, null, 'ATTENDANCE_DATE'),
            'todayAttend' => array_column($todayAttend, null, 'ATTENDANCE_DATE'),
            'attendanceMonth' => $this->my_date->_convert_date($this->my_date->_get_m_first_day($period['dateFrom']), 'Y年m月d日'), 
            'attendanceFrom'  => $this->my_date->_convert_date($period['dateFrom'], 'Y年m月d日'), 
            'attendanceTo'    => $this->my_date->_convert_date($period['dateEnd'], 'Y年m月d日'), 
            'selectedMonth'   => $period['selectedMonth'], 
            'selectedrange'   => $period['selectedrange'],
            'sumWorkTime'     => $prepareData['sumWorkTime'],
            'sumBreakTime'    => $prepareData['sumBreakTime'],
            'datePropaty' => array(
                'range'   => date_range($period['dateFrom'], $period['dateEnd']),
            ),
        ));
    }

    protected function _main_view()
    {
        $device = $this->my_device->_get_user_device();
        $this->viewData['title'] = 'JobCoordinator-AttendanceTop';
        $this->load->view($device. '/common/header', $this->viewData);
        $this->load->view($device. '/attendance/index', $this->viewData);
        $this->load->view($device. '/common/footer', $this->viewData);
    }

    /********************* ↓ sub function ↓ *********************/

    /**
     * View表示用の変数へ値を格納します。
     * 入力データの連想配列のキーをView表示用変数の連想配列キー名として
     * 格納します。
     *
     * @param array $setData
     */
    private function _set_view_data($setData=array()) {
        foreach($setData as $key => $val) {
            $this->viewData['result'][$key] = $val;
        }
        $this->_set_view_csrf();
    }

    /**
     * CSRF対策用のトークンデータをView変数に設定します。
     */
    private function _set_view_csrf() {
        // フレームワーク特有のCSRF対策
        $this->viewData['csrf'] = array(
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash()
        );
    }

}
