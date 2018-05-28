<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Create extends CI_Controller {

    const CREATE_SUCCESS = 1;	// 入力チェック成功 → 会員登録完了画面へ
    const CREATE_ERROR   = 2;	// 入力チェック失敗 → エラーメッセージをセットして会員登録エラー画面出力

    protected $viewType = 0;
    protected $viewData = NULL;
    protected $uKey     = '';
    protected $userData = NULL;
    protected $checkRes = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User', 'modelUser', TRUE);
        $this->load->library('controllers/Entry/entry_lib');
    }

/********************* ↓ routes function ↓ *********************/
    public function index($param)
    {
        $this->uKey = $param;
        $this->viewType = $this->_preprocess();
        $this->_mainprocess();
        $this->_main_view();
    }

/********************* ↓ main function ↓ *********************/
    protected function _preprocess()
    {
        $res = 0;
        $this->userData = $this->entry_lib->_check_unique_key($this->uKey);
        if(!empty($this->userData)){
            $res = self::CREATE_SUCCESS;
        }else{
            $res = self::CREATE_ERROR;
        }
        return $res;
    }

    protected function _mainprocess()
    {
        switch($this->viewType){
            case self::CREATE_SUCCESS:
                // view 用フラグセット
                $this->checkRes = true;
                // DB に本登録(フラグ更新)
                $res = $this->modelUser->update_user_data($this->userData['LOGIN_ID']);
                // サンクスメール送信
                $resMail = $this->entry_lib->_user_sendMail_create($this->userData);
                // 管理者通知メール送信
                $resMail = $this->entry_lib->_admin_sendMail_create($this->userData);
                // ログインセッション発行
                $this->userData['magic_code'] = $this->login_lib->_create_magic_code($this->userData['LOGIN_ID'], $this->userData['MAIL']);
                $this->session->set_userdata($this->config->item('sess_member'), $this->userData);
                break;
            case self::CREATE_ERROR:
                // バリデートエラー
                break;
            default:
                break;
        }
    }

    protected function _main_view()
    {
        $device = $this->my_device->_get_user_device();
        $this->viewData['title']      = 'JobCoordinator-Entry';
        $this->viewData['checkRes']   = $this->checkRes;
        $this->viewData['url_mypage'] = base_url() .'member/mypage';
        $this->viewData['url_top']    = base_url();

        $this->load->view($device. '/common/header', $this->viewData);
        $this->load->view($device. '/entry/create',  $this->viewData);
        $this->load->view($device. '/common/footer', $this->viewData);
    }

/********************* ↓ sub function ↓ *********************/
}
