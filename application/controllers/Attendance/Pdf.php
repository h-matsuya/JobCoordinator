<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'../vendor/autoload.php');

class Pdf extends CI_Controller {

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
        $this->load->library('controllers/Attendance/pdf_lib');
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
        return $res;
    }

    protected function _mainprocess()
    {
        // // 表示データ収集

        // 期間指定の選択状態および期間指定状態の入力パラメータ
        $IndateMonth = $this->input->post('date_month');
        $IndateStart = $this->input->post('date_start');
        $IndateEnd   = $this->input->post('date_end');
        // 検索期間、期間指定状態の取得
        $period = $this->attendance_lib->_get_period_status($IndateMonth, $IndateStart, $IndateEnd);

        // 出勤情報一覧を取得
        $attendance = $this->attendance_lib->_get_attendance_list($this->modelAttendance, $this->userData, $period['dateFrom'], $period['dateEnd']);

        // 画面表示の為のデータ整形
        $prepareData = $this->attendance_lib->_prepare_data($attendance);
        $attendance = $prepareData['attendance'];

        // ユーザ詳細取得
        $user = $this->modelUser->get_once_user($this->userData['LOGIN_ID']);

        // View表示用変数へ表示用データを設定する
        $this->_set_view_data(array(
            'user'         => $user,
            'attendance'   => array_column($attendance, null, 'ATTENDANCE_DATE'),
            'sumWorkTime'  => $prepareData['sumWorkTime'],
            'sumBreakTime' => $prepareData['sumBreakTime'],
            'datePropaty'  => array(
                'range'    => date_range($period['dateFrom'], $period['dateEnd']),
            ),
        ));
    }

    protected function _main_view()
    {
        // PDF出力要求
        $this->print_pdf();
    }

    /********************* ↓ sub function ↓ *********************/
    private function print_pdf()
    {
        // 一覧部分表示用
        $device = $this->my_device->_get_user_device();
        //CodeIgniterのviewの第三引数をtrueにしてhtml文字列として扱う
        $html = $this->load->view($device . '/attendance/pdf/layout', $this->viewData, true);
        // PDF描画要求
        $this->pdf_lib->_init_pdf();
        $this->pdf_lib->WriteHTML($html);
        $this->pdf_lib->Output();
    }

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
    }

}
