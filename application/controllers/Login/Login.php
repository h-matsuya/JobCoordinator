<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

    const LOGIN_START    = 1;	// ログイン画面出力
    const LOGIN_SUCCESS  = 2;	// ログイン処理成功 → マイページTOPへ
    const LOGIN_ERROR    = 3;	// ログイン処理失敗 → エラーメッセージをセットしてログイン画面出力

    protected $viewType  = 0;
    protected $viewData  = NULL;
    protected $userData  = NULL;
    protected $inputData = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User', 'modelUser', TRUE);
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
        $res = 0;
        if(empty($this->input->post('action'))){
            $res = self::LOGIN_START;
        }else{
            if($this->login_lib->_login_validation()){
                // バリデーション成功 → ユーザーデータを取得
                $this->userData = $this->modelUser->get_once_user($this->input->post("login_id"));
                $res = self::LOGIN_SUCCESS;
            }else{
                // バリデーションエラー
                $res = self::LOGIN_ERROR;
            }
        }
        return $res;
    }

    protected function _mainprocess()
    {
        switch($this->viewType){
            case self::LOGIN_START:
                $this->viewData['result'] = $this->modelUser->get_all_user();
                break;
            case self::LOGIN_SUCCESS:
                // リダイレクト先を決定
                $redirectUrl = 'member/mypage';
                if(!is_null($this->session->userdata('logged_in_back_url')) && !empty($this->session->userdata('logged_in_back_url'))){
                    $redirectUrl = $this->session->userdata('logged_in_back_url');
                    $this->session->unset_userdata('logged_in_back_url');
                }
                // session 操作
                $this->userData['magic_code'] = $this->login_lib->_create_magic_code($this->userData['LOGIN_ID'], $this->userData['MAIL']);
                $this->session->set_userdata($this->config->item('sess_member'), $this->userData);
                redirect($redirectUrl);
                break;
            case self::LOGIN_ERROR:
                break;
            default:
                break;
        }
    }

    protected function _main_view()
    {
        $device = $this->my_device->_get_user_device();
        $this->viewData['title'] = 'JobCoordinator-Login';

        $this->load->view($device. '/common/header', $this->viewData);
        $this->load->view($device. '/login/login',   $this->viewData);
        $this->load->view($device. '/common/footer', $this->viewData);
    }

/********************* ↓ sub function ↓ *********************/
}
