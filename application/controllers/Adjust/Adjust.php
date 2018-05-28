<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjust extends CI_Controller {

    protected $viewData = NULL;
    protected $resData  = NULL;
    protected $mailData = NULL;
    protected $mailBaseInfo = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User', 'modelUser', TRUE);
        $this->load->model('Shedule_setting', 'modelShedule', TRUE);
        $this->load->model('Mail_history', 'modelMail', TRUE);
        $this->load->model('Proposed', 'modelProposed', TRUE);
        $this->load->model('Hope_place', 'modelHope_place', TRUE);
        $this->load->library('developer/google/calender_lib');
        $this->load->library('controllers/Adjust/adjust_lib');
    }

/********************* ↓ routes function ↓ *********************/
    public function index()
    {
        $processType = $this->_preprocess();
        if($processType){
            $this->_mainprocess();
        }else{
            $this->_errorprocess();
        }
    }

/********************* ↓ main function ↓ *********************/
    protected function _preprocess()
    {
        $res = true;
        $this->mailBaseInfo = json_decode($this->input->post('mail_info'));

        var_dump($this->mailBaseInfo);
        exit;

        if(empty($this->$mailBaseInfo)) $res = false;
        return $res;
    }

    protected function _mainprocess()
    {
        // 受信BOXから取得したメールの件数分処理
        foreach($this->mailBaseInfo as $t_key => $thread){   // スレッドループ
            $msg_cnt = 0;
            foreach($thread->msgdata as $m_key => $msg){  // メッセージループ
                // 最新のメッセージを処理対象とする
                if($msg_cnt != intval($thread->msgcount) - 1){
                    $msg_cnt++;
                    continue;
                }

                // 該当メールの返信先(In-Reply-To)を取得
                list($msg_id, $in_reply_to) = $this->adjust_lib->get_msg_id($msg->all_data);
                $plain_to = $this->adjust_lib->fix_mail_format($msg->to, "<", ">");
                $plain_from = $this->adjust_lib->fix_mail_format($msg->from, "<", ">");

                // 処理モード取得
                list($mode, $owner_mail, $schedule_data) = $this->adjust_lib->get_mail_mode($plain_to, $plain_from, $in_reply_to);

                // 処理モードによって会員データ取得
                if($mode >= 1 && $mode <= 4){
                    list($res, $emp_data) = $this->modelUser->get_once_user_by_mail($owner_mail);
                    if(!$res){
                        var_dump("オーナー情報取得失敗:". $owner_mail. "\n");
                        break;
                    }
                }

                switch($mode){
                    case 1:     // 初期処理
                    case 2:     // 準備処理
                        // スケジュールDBより対象のスケジュール調整データを取得
                        list($check_schedule_res, $res_schedule_data) = $this->modelShedule->get_schedule_setting_by_msgid($in_reply_to, 0);
                        if($mode == 2 && $check_schedule_res){
                            $schedule_id = $res_schedule_data['ID'];
                            $guest_mail = $res_schedule_data["GUEST_MAIL"];
                        }else{
                            // 受信メッセージ情報をDBに登録(from, to, etc)
                            $schedule_data['OWNER_MAIL'] = $plain_from;
                            $schedule_data['GUEST_MAIL'] = $plain_to;
                            $guest_mail = $plain_to;
                            list($res_insert, $schedule_id) = $this->modelShedule->set_new_schedule($schedule_data);
                        }

                        // 受信メッセージ情報をチェック
                        list($check_res, $res_data, $err_msg) = $this->adjust_lib->check_owner_mail_msg($msg->plainbody, $guest_mail, $emp_data, $res_schedule_data);
                        // 取得可能だった情報をDBに保持
                        $this->modelShedule->update_schedule($schedule_id, $res_data);

                        // 受信メール情報をDBに登録
                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                            'schedule_id' => $schedule_id,
                            'msg_id'      => "",
                            'from_name'   => $emp_data['NAME0'],
                            'from_mail'   => $plain_from,
                            'to_name'     => $res_data["guest_name"],
                            'to_mail'     => $plain_to,
                            'cc_name'     => "",
                            'cc_mail'     => $msg->cc,
                            'subject'     => $msg->subject,
                            'mail_text'   => $msg->plainbody,
                        ));

                        if($check_res){
                            // MTG候補日を取得
                            list($res_body, $example, $free_date) = $this->adjust_lib->get_choice_item($emp_data, $msg->to, $res_data, array());
                            if(count($free_date) > 0){
                                // 候補日をDBに追加
                                $this->modelProposed->insert_choice_item($schedule_id, $free_date);
                                // 希望場所をDBに登録
                                $hope_item = array();
                                $hope_item["owner"] = $res_data["mtg_place"];
                                $this->modelHope_place->insert_hope_place($schedule_id, $hope_item);
                                // メッセージID生成
                                $msg_id = $this->adjust_lib->get_msg_id('g');
                                // 送信メッセージIDをDBに登録(guest_send_msg_idへ)
                                $upd_data = array();
                                $upd_data['guest_send_msg_id'] = $msg_id;
                                $upd_data['required'] = $res_data['required'];
                                $upd_data['status'] = 1;	// ステータスを調整中に変更
                                if(!empty($res_data['start_datetime'])) $upd_data['start_datetime'] = $res_data['start_datetime'];
                                if(!empty($res_data['end_datetime'])) $upd_data['end_datetime'] = $res_data['end_datetime'];
                                if(!empty($res_data['guest_name'])) $upd_data['guest_name'] = $res_data['guest_name'];
                                if(!empty($res_data['guest_mail'])) $upd_data['guest_mail'] = $res_data['guest_mail'];
                                if(!empty($res_data['mtg_type'])) $upd_data['mtg_type'] = $res_data['mtg_type'];
                                if(!empty($res_data['mtg_place'])) $upd_data['mtg_place'] = $res_data['mtg_place'];
                                if(!empty($res_data['mtg_tel_no'])) $upd_data['mtg_tel_no'] = $res_data['mtg_tel_no'];
                                if(!empty($res_data['mtg_skype_id'])) $upd_data['mtg_skype_id'] = $res_data['mtg_skype_id'];
                                $this->modelShedule->update_schedule($schedule_id, $upd_data);

                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $res_data["guest_mail"],
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/send00',
                                    'msg_id'        => $msg_id,
                                    'body_data'     => array(
                                        'owner_name'   => $emp_data['NAME1'],
                                        'owner_mail'   => $emp_data['MAIL'],
                                        'bot_name'     => $this->config->item('adjust_from_admin_name'),
                                        'example01'    => $example,
                                        'example02'    => date("Y/m/d", strtotime("+4 day")),
                                        'out_mtg_info' => $res_data["out_mtg_info"],
                                        'reply_info'   => $this->adjust_lib->get_reply_info($msg),
                                        'candidate_schedule' => $res_body,
                                    )
                                );

                                // ゲストにメール送信
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                if($res_mail){
                                    // メール送信成功時にメール内容をDBに登録する
                                    list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                        'schedule_id' => $schedule_id,
                                        'msg_id'      => $msg_id,
                                        'from_name'   => $mail_info['from_name'],
                                        'from_mail'   => $mail_info['from_mail'],
                                        'to_name'     => $res_data["guest_name"],
                                        'to_mail'     => $mail_info['to'],
                                        'subject'     => $mail_info['subject'],
                                        'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                    ));
                                }else{
                                    var_dump("スケジュール調整開始メール送信失敗:". $mail_info['to']. "\n");
                                }
                            }else{
                                // 候補日が指定範囲内で取得不可
                                // オーナー側へ必要情報入力依頼メール送信
                                // メッセージID生成
                                $msg_id = $this->adjust_lib->get_msg_id('o');
                                // 送信メッセージIDをDBに登録(owner_send_msg_idへ)
                                $upd_data = array();
                                $upd_data['owner_send_msg_id'] = $msg_id;
                                $upd_data['status'] = 0;	// ステータスを準備中に変更
                                $this->modelShedule->update_schedule($schedule_id, $upd_data);
                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $emp_data['MAIL'],
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/error00',
                                    'msg_id'        => $msg_id,
                                    'body_data'     => array(
                                        'owner_name'     => $emp_data['NAME1'],
                                        'bot_name'       => $this->config->item('adjust_from_admin_name'),
                                        'start_datetime' => "提案可能な日程が見つかりませんでした。申し訳ありませんが、別の日程を入力してください。",
                                        'end_datetime'   => "提案可能な日程が見つかりませんでした。申し訳ありませんが、別の日程を入力してください。",
                                        'required'       => $res_data["required"],
                                        'guest_name'     => $res_data["guest_name"],
                                        'mtg_type'       => $res_data["mtg_type"],
                                        'mtg_place'      => $res_data["mtg_place"],
                                        'mtg_tel_no'     => $res_data["mtg_tel_no"],
                                        'mtg_skype_id'   => $res_data["mtg_skype_id"],
                                        'reply_info'     => $this->adjust_lib->get_reply_info($msg),
                                    )
                                );
                                // オーナーにメール送信
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                if($res_mail){
                                    // メール送信成功時にメール内容をDBに登録する
                                    list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                        'schedule_id' => $schedule_id,
                                        'msg_id'      => $msg_id,
                                        'from_name'   => $mail_info['from_name'],
                                        'from_mail'   => $mail_info['from_mail'],
                                        'to_name'     => $emp_data['NAME0'],
                                        'to_mail'     => $mail_info['to'],
                                        'subject'     => $mail_info['subject'],
                                        'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                    ));
                                }else{
                                    var_dump("オーナーへのスケジュール調整時の必要情報準備依頼メール送信失敗:". $mail_info['to']. "\n");
                                }

                                if($mode == 1){
                                    // ゲストにメールを送信している場合は「しばらくおまちください」的なメールを送信
                                    // 送信するメール情報をセット
                                    $mail_info = array(
                                        'to'            => $plain_to,
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => 'template/mail/adjust/send_user_01',
                                        'msg_id'        => "",
                                        'body_data'     => array(
                                            'owner_name' => $emp_data['NAME1'],
                                            'owner_mail' => $emp_data['MAIL'],
                                            'bot_name'   => $this->config->item('adjust_from_admin_name'),
                                            'reply_info' => $this->adjust_lib->get_reply_info($msg),
                                        )
                                    );

                                    // ゲストにメール送信
                                    $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                    if($res_mail){
                                        // メール送信成功時にメール内容をDBに登録する
                                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                            'schedule_id' => $schedule_id,
                                            'msg_id'      => "",
                                            'from_name'   => $mail_info['from_name'],
                                            'from_mail'   => $mail_info['from_mail'],
                                            'to_name'     => $res_data["guest_name"],
                                            'to_mail'     => $mail_info['to'],
                                            'subject'     => $mail_info['subject'],
                                            'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                        ));
                                    }else{
                                        var_dump("ゲストへのスケジュール調整時の必要情報準備待機メール送信失敗:". $mail_info['to']. "\n");
                                    }
                                }
                            }
                        }else{
                            // オーナー側へ必要情報入力依頼メール送信
                            $msg_id = $this->adjust_lib->get_msg_id('o');
                            // 送信メッセージIDをDBに登録(owner_send_msg_idへ)
                            $upd_data = array();
                            $upd_data['owner_send_msg_id'] = $msg_id;
                            $upd_data['status'] = 0;	// ステータスを準備中に変更
                            $this->modelShedule->update_schedule($schedule_id, $upd_data);
                            // 送信するメール情報をセット
                            $mail_info = array(
                                'to'            => $emp_data['MAIL'],
                                'subject'       => "Re:". $msg->subject,
                                'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                'from_name'     => $this->config->item('adjust_from_admin_name'),
                                'template_path' => 'template/mail/adjust/error00',
                                'msg_id'        => $msg_id,
                                'body_data'     => array(
                                    'owner_name'     => $emp_data['NAME1'],
                                    'bot_name'       => $this->config->item('adjust_from_admin_name'),
                                    'start_datetime' => (!empty($err_msg['start_datetime']))? $err_msg['start_datetime']: $res_data['start_datetime'],
                                    'end_datetime'   => (!empty($err_msg['end_datetime']))?   $err_msg['end_datetime']:   $res_data['end_datetime'],
                                    'required'       => (!empty($err_msg['required']))?       $err_msg['required']:       $res_data['required'],
                                    'guest_name'     => (!empty($err_msg['guest_name']))?     $err_msg['guest_name']:     $res_data['guest_name'],
                                    'mtg_type'       => (!empty($err_msg['mtg_type']))?       $err_msg['mtg_type']:       $res_data['mtg_type'],
                                    'mtg_place'      => (!empty($err_msg['mtg_place']))?      $err_msg['mtg_place']:      $res_data['mtg_place'],
                                    'mtg_tel_no'     => (!empty($err_msg['mtg_tel_no']))?     $err_msg['mtg_tel_no']:     $res_data['mtg_tel_no'],
                                    'mtg_skype_id'   => (!empty($err_msg['mtg_skype_id']))?   $err_msg['mtg_skype_id']:   $res_data['mtg_skype_id'],
                                    'reply_info'     => $this->adjust_lib->get_reply_info($msg),
                                )
                            );
                            // オーナーにメール送信
                            $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                            if($res_mail){
                                // メール送信成功時にメール内容をDBに登録する
                                list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                    'schedule_id' => $schedule_id,
                                    'msg_id'      => $msg_id,
                                    'from_name'   => $mail_info['from_name'],
                                    'from_mail'   => $mail_info['from_mail'],
                                    'to_name'     => $emp_data['NAME0'],
                                    'to_mail'     => $mail_info['to'],
                                    'subject'     => $mail_info['subject'],
                                    'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                ));
                            }else{
                                var_dump("オーナーへのスケジュール調整時の必要情報準備依頼メール送信失敗:". $mail_info['to']. "\n");
                            }

                            if($mode == 1){
                                // ゲストにメールを送信している場合は「しばらくおまちください」的なメールを送信
                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $msg->to,
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/send_user_01',
                                    'msg_id'        => "",
                                    'body_data'     => array(
                                        'owner_name'     => $emp_data['NAME1'],
                                        'owner_mail'   => $emp_data['MAIL'],
                                        'bot_name'       => $this->config->item('adjust_from_admin_name'),
                                        'reply_info'     => $this->adjust_lib->get_reply_info($msg),
                                    )
                                );

                                // ゲストにメール送信
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                if($res_mail){
                                    // メール送信成功時にメール内容をDBに登録する
                                    list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                        'schedule_id' => $schedule_id,
                                        'msg_id'      => "",
                                        'from_name'   => $mail_info['from_name'],
                                        'from_mail'   => $mail_info['from_mail'],
                                        'to_name'     => $res_data["guest_name"],
                                        'to_mail'     => $mail_info['to'],
                                        'subject'     => $mail_info['subject'],
                                        'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                    ));
                                }else{
                                    var_dump("ゲストへのスケジュール調整時の必要情報準備待機メール送信失敗:". $mail_info['to']. "\n");
                                }
                            }
                        }
                        break;
                    case 3:     // 調整処理
                        // 対象メール(本文)解析
                        // メール本文の引用部分を削除
                        $target_body = $this->adjust_lib->get_plain_body($msg->plainbody);
                        list($mode2, $answer, $tar_date_s, $tar_date_e, $sender, $hope_data) = $this->adjust_lib->analysis_mail_body($emp_data, $target_body, $schedule_data, $plain_from);

                        // 受信メール情報をDBに登録
                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                            'schedule_id' => $schedule_data["ID"],
                            'msg_id'      => "",
                            'from_name'   => $schedule_data["GUEST_NAME"],
                            'from_mail'   => $plain_from,
                            'to_name'     => $this->config->item('adjust_from_admin_name'),
                            'to_mail'     => $plain_to,
                            'cc_name'     => "",
                            'cc_mail'     => "",
                            'subject'     => $msg->subject,
                            'mail_text'   => $msg->plainbody,
                        ));

                        switch($mode2){
                            case 1:     // 日程承認依頼処理
                                // 承認依頼対象日程のgoogleカレンダー内候補日データを承認依頼状態へ編集
                                $target_event_id = $this->calender_lib->set_schedule_app_req($emp_data, $tar_date_s, $tar_date_e, $msg->from);
                                // 承認依頼対象日程以外の候補日のスケジュールをgoogleカレンダーから削除
                                list($res_proposed, $res_proposed_data_temp) = $this->modelProposed->get_proposed($schedule_data['ID']);
                                // 削除対象イベントIDから承認依頼対象日程のイベントIDを除外
                                $res_proposed_data = array();
                                foreach($res_proposed_data_temp as $key => $val){
                                    if($val['EVENT_ID'] != $target_event_id){
                                        $res_proposed_data[]['EVENT_ID'] = $val['EVENT_ID'];
                                    }
                                }
                                $this->calender_lib->del_schedule_candidate_select_by_id($emp_data, $res_proposed_data);

                                if(!empty($schedule_data["MTG_PLACE"])){
                                    $sch_info = "【場所】". $hope_data['RESULT']. "\n";
                                }elseif(!empty($schedule_data["MTG_SKYPE_ID"])){
                                    $sch_info = "【SkypeID】". $schedule_data["MTG_SKYPE_ID"]. "\n";
                                }elseif(!empty($schedule_data["MTG_TEL_NO"])){
                                    $sch_info = "【TEL】". $schedule_data["MTG_TEL_NO"]. "\n";
                                }else{
                                    $sch_info = "\n";
                                }

                                $msg_id = $this->adjust_lib->get_msg_id('g');
                                // 送信メッセージIDをDBに登録(owner_send_msg_idへ)
                                $upd_data = array();
                                $upd_data['guest_send_msg_id'] = $msg_id;
                                $upd_data['google_event_id'] = $target_event_id;
                                $upd_data['mtg_place'] = $hope_data['RESULT'];
                                $upd_data['status'] = 2;	// ステータスを承認依頼中に変更
                                $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $schedule_data['GUEST_MAIL'],
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/send_user_02',
                                    'msg_id'        => $msg_id,
                                    'body_data'     => array(
                                        'owner_name' => $emp_data['NAME1'],
                                        'owner_mail' => $emp_data['MAIL'],
                                        'bot_name'   => $this->config->item('adjust_from_admin_name'),
                                        'answer'     => $answer,
                                        'sch_info'   => $sch_info,
                                        'reply_info' => $this->adjust_lib->get_reply_info($msg),
                                    )
                                );
                                // ゲストにメール送信
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                if($res_mail){
                                    // メール送信成功時にメール内容をDBに登録する
                                    list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                        'schedule_id' => $schedule_data["ID"],
                                        'msg_id'      => $msg_id,
                                        'from_name'   => $mail_info['from_name'],
                                        'from_mail'   => $mail_info['from_mail'],
                                        'to_name'     => $schedule_data["GUEST_NAME"],
                                        'to_mail'     => $mail_info['to'],
                                        'subject'     => $mail_info['subject'],
                                        'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                    ));
                                }else{
                                    var_dump("オーナーへのスケジュール調整時の必要情報準備依頼メール送信失敗:". $mail_info['to']. "\n");
                                }

                                // オーナーへスケジュール承認依頼メール送信(WEB画面のURL付き)
                                $member_msg_id = $this->adjust_lib->get_msg_id('o');
                                $upd_data = array();
                                $upd_data['owner_send_msg_id'] = $member_msg_id;
                                $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $schedule_data['OWNER_MAIL'],
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/send_owner_01',
                                    'msg_id'        => $member_msg_id,
                                    'body_data'     => array(
                                        'guest_name'   => $msg->from,
                                        'answer'       => $answer,
                                        'sch_info'     => $sch_info,
                                        'approval_url' => $this->config->item('approval_url'). '?id='. $schedule_data["ID"],
                                        'bot_name'     => $this->config->item('adjust_from_admin_name'),
                                    )
                                );
                                // オーナーにメール送信
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                if($res_mail){
                                    // メール送信成功時にメール内容をDBに登録する
                                    list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                        'schedule_id' => $schedule_data["ID"],
                                        'msg_id'      => $member_msg_id,
                                        'from_name'   => $mail_info['from_name'],
                                        'from_mail'   => $mail_info['from_mail'],
                                        'to_name'     => $emp_data["NAME0"],
                                        'to_mail'     => $mail_info['to'],
                                        'subject'     => $mail_info['subject'],
                                        'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                    ));
                                }else{
                                    var_dump("オーナーへのスケジュール承認依頼メール送信失敗:". $mail_info['to']. "\n");
                                }
                                break;
                            case 2:     // 別日提案処理
                                // MTG候補日を取得
                                list($check_schedule_res, $res_schedule_data) = $this->modelShedule->get_schedule_setting_by_msgid($in_reply_to, 1);
                                list($res_proposed, $proposed_data) = $this->modelProposed->get_proposed($res_schedule_data['ID']);

                                $g_mail_data = array();
                                $g_mail_data['start_datetime'] = $tar_date_s;
                                $g_mail_data['end_datetime'] = $tar_date_e;
                                $g_mail_data['required'] = $res_schedule_data['REQUIRED'];  // ゲストの返信メール内から所要時間を取得可能な場合はメール内からの所要時間を使用したい
                                // MTG候補日を取得
                                list($res_body, $example, $free_date) = $this->adjust_lib->get_choice_item($emp_data, $msg->from, $g_mail_data, $proposed_data);
                                list($res_proposed, $res_proposed_data) = $this->modelProposed->get_proposed($res_schedule_data['ID']);
                                // 候補日をDBに追加
                                $this->modelProposed->insert_choice_item($res_schedule_data['ID'], $free_date);
                                // 前に送信した候補日をgoogleカレンダーから削除(希望日を除く)
                                $this->calender_lib->del_schedule_candidate_select_by_id($emp_data, $res_proposed_data);

                                if(!empty($schedule_data["MTG_PLACE"])){
                                    $sch_info = "【場所】". $schedule_data['MTG_PLACE']. "\n";
                                }elseif(!empty($schedule_data["MTG_SKYPE_ID"])){
                                    $sch_info = "【SkypeID】". $schedule_data["MTG_SKYPE_ID"]. "\n";
                                }elseif(!empty($schedule_data["MTG_TEL_NO"])){
                                    $sch_info = "【TEL】". $schedule_data["MTG_TEL_NO"]. "\n";
                                }else{
                                    $sch_info = "\n";
                                }

                                $msg_id = $this->adjust_lib->get_msg_id('g');
                                // 送信メッセージIDをDBに登録(owner_send_msg_idへ)
                                $upd_data = array();
                                $upd_data['guest_send_msg_id'] = $msg_id;
                                $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $plain_from,
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/send_user_03',
                                    'msg_id'        => $msg_id,
                                    'body_data'     => array(
                                        'owner_name'         => $emp_data['NAME1'],
                                        'owner_mail'         => $emp_data['MAIL'],
                                        'bot_name'           => $this->config->item('adjust_from_admin_name'),
                                        'example01'          => $example,
                                        'example02'          => date("Y/m/d", strtotime("+1 day")),
                                        'out_mtg_info'       => $sch_info,
                                        'reply_info'         => $this->adjust_lib->get_reply_info($msg),
                                        'candidate_schedule' => $res_body,
                                    )
                                );
                                // ゲストにメール送信
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                if($res_mail){
                                    // メール送信成功時にメール内容をDBに登録する
                                    list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                        'schedule_id' => $schedule_data["ID"],
                                        'msg_id'      => $msg_id,
                                        'from_name'   => $mail_info['from_name'],
                                        'from_mail'   => $mail_info['from_mail'],
                                        'to_name'     => $schedule_data["GUEST_NAME"],
                                        'to_mail'     => $mail_info['to'],
                                        'subject'     => $mail_info['subject'],
                                        'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                    ));
                                }else{
                                    var_dump("ゲストへのスケジュール再調整メール送信失敗:". $mail_info['to']. "\n");
                                }
                                break;
                            case 3:     // 場所再提案処理
                                if($sender == 1){   // 送信元がオーナーの場合
                                    // ゲストへ確認する旨、オーナーへメール送信
                                    $member_msg_id = $this->adjust_lib->get_msg_id('o');
                                    $upd_data = array();
                                    $upd_data['owner_send_msg_id'] = $member_msg_id;
                                    $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                    // 送信するメール情報をセット
                                    $mail_info = array(
                                        'to'            => $schedule_data['OWNER_MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => 'template/mail/adjust/send_owner_02',
                                        'msg_id'        => $member_msg_id,
                                        'body_data'     => array(
                                            'owner_name'       => $emp_data['NAME1'],
                                            'owner_mail'       => $emp_data['MAIL'],
                                            'guest_name'       => $schedule_data['GUEST_NAME'],
                                            'owner_hope_place' => $hope_data["OWNER_HOPE_PLACE"],
                                            'guest_hope_place' => $hope_data["GUEST_HOPE_PLACE"],
                                            'bot_name'         => $this->config->item('adjust_from_admin_name'),
                                            'reply_info'       => $this->adjust_lib->get_reply_info($msg),
                                        )
                                    );
                                    $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                    if($res_mail){
                                        // メール送信成功時にメール内容をDBに登録する
                                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                            'schedule_id' => $schedule_data["ID"],
                                            'msg_id'      => $member_msg_id,
                                            'from_name'   => $mail_info['from_name'],
                                            'from_mail'   => $mail_info['from_mail'],
                                            'to_name'     => $emp_data["NAME0"],
                                            'to_mail'     => $mail_info['to'],
                                            'subject'     => $mail_info['subject'],
                                            'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                        ));
                                    }else{
                                        var_dump("ゲストへの場所再調整確認の旨オーナーへの待機メール送信失敗:". $mail_info['to']. "\n");
                                    }

                                    // ゲストへ確認メール送信
                                    $guest_msg_id = $this->adjust_lib->get_msg_id('g');
                                    $upd_data = array();
                                    $upd_data['guest_send_msg_id'] = $guest_msg_id;
                                    $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                    // 送信するメール情報をセット
                                    $mail_info = array(
                                        'to'            => $schedule_data['GUEST_MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => 'template/mail/adjust/send_user_04',
                                        'msg_id'        => $guest_msg_id,
                                        'body_data'     => array(
                                            'owner_name'         => $emp_data['NAME1'],
                                            'owner_mail'         => $emp_data['MAIL'],
                                            'bot_name'           => $this->config->item('adjust_from_admin_name'),
                                            'owner_hope_place'   => $hope_data["OWNER_HOPE_PLACE"],
                                            'guest_hope_place'   => $hope_data["GUEST_HOPE_PLACE"],
                                        )
                                    );
                                    // ゲストにメール送信
                                    $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                    if($res_mail){
                                        // メール送信成功時にメール内容をDBに登録する
                                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                            'schedule_id' => $schedule_data["ID"],
                                            'msg_id'      => $guest_msg_id,
                                            'from_name'   => $mail_info['from_name'],
                                            'from_mail'   => $mail_info['from_mail'],
                                            'to_name'     => $schedule_data["GUEST_NAME"],
                                            'to_mail'     => $mail_info['to'],
                                            'subject'     => $mail_info['subject'],
                                            'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                        ));
                                    }else{
                                        var_dump("ゲストへの場所再調整確認メール送信失敗:". $mail_info['to']. "\n");
                                    }
                                }elseif($sender == 2){  // 送信元がゲストの場合
                                    // オーナーへ確認する旨、ゲストへメール送信
                                    $guest_msg_id = $this->adjust_lib->get_msg_id('g');
                                    $upd_data = array();
                                    $upd_data['guest_send_msg_id'] = $guest_msg_id;
                                    $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                    // 送信するメール情報をセット
                                    $mail_info = array(
                                        'to'            => $schedule_data['GUEST_MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => 'template/mail/adjust/send_user_05',
                                        'msg_id'        => $guest_msg_id,
                                        'body_data'     => array(
                                            'owner_name'         => $emp_data['NAME1'],
                                            'owner_mail'         => $emp_data['MAIL'],
                                            'bot_name'           => $this->config->item('adjust_from_admin_name'),
                                            'owner_hope_place'   => $hope_data["OWNER_HOPE_PLACE"],
                                            'guest_hope_place'   => $hope_data["GUEST_HOPE_PLACE"],
                                        )
                                    );
                                    // ゲストにメール送信
                                    $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                    if($res_mail){
                                        // メール送信成功時にメール内容をDBに登録する
                                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                            'schedule_id' => $schedule_data["ID"],
                                            'msg_id'      => $guest_msg_id,
                                            'from_name'   => $mail_info['from_name'],
                                            'from_mail'   => $mail_info['from_mail'],
                                            'to_name'     => $schedule_data["GUEST_NAME"],
                                            'to_mail'     => $mail_info['to'],
                                            'subject'     => $mail_info['subject'],
                                            'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                        ));
                                    }else{
                                        var_dump("オーナーへの場所再調整確認の旨ゲストへの待機メール送信失敗:". $mail_info['to']. "\n");
                                    }

                                    // オーナーへ確認メール送信
                                    $member_msg_id = $this->adjust_lib->get_msg_id('o');
                                    $upd_data = array();
                                    $upd_data['owner_send_msg_id'] = $member_msg_id;
                                    $this->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
                                    // 送信するメール情報をセット
                                    $mail_info = array(
                                        'to'            => $schedule_data['OWNER_MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => 'template/mail/adjust/send_owner_02',
                                        'msg_id'        => $member_msg_id,
                                        'body_data'     => array(
                                            'owner_name'       => $emp_data['NAME1'],
                                            'owner_mail'       => $emp_data['MAIL'],
                                            'guest_name'       => $schedule_data['GUEST_NAME'],
                                            'owner_hope_place' => $hope_data["OWNER_HOPE_PLACE"],
                                            'guest_hope_place' => $hope_data["GUEST_HOPE_PLACE"],
                                            'bot_name'         => $this->config->item('adjust_from_admin_name'),
                                            'reply_info'       => $this->adjust_lib->get_reply_info($msg),
                                        )
                                    );
                                    $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                    if($res_mail){
                                        // メール送信成功時にメール内容をDBに登録する
                                        list($res_insert_mail, $res_insert_mail_id) = $this->adjust_lib->insert_mail_data(array(
                                            'schedule_id' => $schedule_data["ID"],
                                            'msg_id'      => $member_msg_id,
                                            'from_name'   => $mail_info['from_name'],
                                            'from_mail'   => $mail_info['from_mail'],
                                            'to_name'     => $emp_data["NAME0"],
                                            'to_mail'     => $mail_info['to'],
                                            'subject'     => $mail_info['subject'],
                                            'mail_text'   => $this->parser->parse($mail_info['template_path'], $mail_info['body_data'], TRUE),
                                        ));
                                    }else{
                                        var_dump("オーナーへの場所再調整確認メール送信失敗:". $mail_info['to']. "\n");
                                    }
                                }else{
                                    $res_mail = $from;
                                }
                                break;
                            default:
                                break;
                        }
                        break;
                    case 4:     // 個人スケジュール処理
                        // メール本文の引用部分を削除
                        $target_body = $this->adjust_lib->get_plain_body($msg->plainbody);
                        $res_data = $this->adjust_lib->analysis_mail_body_personal($emp_data, $target_body);

                        switch($res_data['ask_type']){
                            case 0:     // 登録
                                list($res, $out_put) = $this->adjust_lib->personal_schedule_insert($emp_data, $res_data);
                                // オーナーへ個人スケジュール登録完了メール送信
                                // 送信するメール情報をセット
                                $out_put['owner_name'] = $emp_data['NAME1'];
                                $out_put['owner_mail'] = $emp_data['MAIL'];
                                $out_put['bot_name'] = $this->config->item('adjust_from_admin_name');
                                $out_put['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                $mail_info = array(
                                    'to'            => $emp_data['MAIL'],
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => $out_put['template'],
                                    'msg_id'        => '',
                                    'body_data'     => $out_put,
                                );
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                break;
                            case 1:     // 変更
                                list($res, $guest_data, $out_put) = $this->adjust_lib->personal_schedule_update($emp_data, $res_data);
                                if($res){
                                    // オーナーへ送信するメール情報をセット
                                    $out_put['owner']['owner_name'] = $emp_data['NAME1'];
                                    $out_put['owner']['owner_mail'] = $emp_data['MAIL'];
                                    $out_put['owner']['bot_name']   = $this->config->item('adjust_from_admin_name');
                                    $out_put['owner']['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                    $o_mail_info = array(
                                        'to'            => $emp_data['MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => $out_put['owner']['template'],
                                        'msg_id'        => "",
                                        'body_data'     => $out_put['owner'],
                                    );
                                    $res_mail = $this->adjust_lib->_user_sendMail($o_mail_info);
                                    // 関連するゲストが存在する場合、ゲストへ予定変更通知メールを送信
                                    if(!empty($guest_data['mail']) && !empty($out_put['guest'])){
                                        // ゲストへ送信するメール情報をセット
                                        $out_put['guest']['owner_name'] = $emp_data['NAME1'];
                                        $out_put['guest']['owner_mail'] = $emp_data['MAIL'];
                                        $out_put['guest']['bot_name']   = $this->config->item('adjust_from_admin_name');
                                        $out_put['guest']['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                        $g_mail_info = array(
                                            'to'            => $guest_data['mail'],
                                            'subject'       => "Re:". $msg->subject,
                                            'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                            'from_name'     => $this->config->item('adjust_from_admin_name'),
                                            'template_path' => $out_put['guest']['template'],
                                            'msg_id'        => "",
                                            'body_data'     => $out_put['guest'],
                                        );
                                        $res_mail = $this->adjust_lib->_user_sendMail($g_mail_info);
                                    }
                                }else{
                                    // エラー
                                    // オーナーへ送信するメール情報をセット
                                    $out_put['owner_name'] = $emp_data['NAME1'];
                                    $out_put['owner_mail'] = $emp_data['MAIL'];
                                    $out_put['bot_name']   = $this->config->item('adjust_from_admin_name');
                                    $out_put['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                    $mail_info = array(
                                        'to'            => $emp_data['MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => $out_put['template'],
                                        'msg_id'        => "",
                                        'body_data'     => $out_put,
                                    );
                                    $res_mail = $this->adjust_lib->_user_sendMail($mail_info);
                                }

                                break;
                            case 2:     // 削除(キャンセル)
                                list($res, $guest_data, $out_put) = $this->adjust_lib->personal_schedule_cancel($emp_data, $res_data);
                                if($res){
                                    // オーナーへ送信するメール情報をセット
                                    $out_put['owner']['owner_name'] = $emp_data['NAME1'];
                                    $out_put['owner']['owner_mail'] = $emp_data['MAIL'];
                                    $out_put['owner']['bot_name']   = $this->config->item('adjust_from_admin_name');
                                    $out_put['owner']['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                    $o_mail_info = array(
                                        'to'            => $emp_data['MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => $out_put['owner']['template'],
                                        'msg_id'        => "",
                                        'body_data'     => $out_put['owner'],
                                    );
                                    $res_mail = $this->adjust_lib->_user_sendMail($o_mail_info);

                                    // 関連するゲストが存在する場合、ゲストへキャンセル通知メールを送信
                                    if(!empty($guest_data['mail']) && !empty($out_put['guest'])){
                                        // ゲストへ送信するメール情報をセット
                                        $out_put['guest']['owner_name'] = $emp_data['NAME1'];
                                        $out_put['guest']['owner_mail'] = $emp_data['MAIL'];
                                        $out_put['guest']['bot_name']   = $this->config->item('adjust_from_admin_name');
                                        $out_put['guest']['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                        $g_mail_info = array(
                                            'to'            => $guest_data['mail'],
                                            'subject'       => "Re:". $msg->subject,
                                            'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                            'from_name'     => $this->config->item('adjust_from_admin_name'),
                                            'template_path' => $out_put['guest']['template'],
                                            'msg_id'        => "",
                                            'body_data'     => $out_put['guest'],
                                        );
                                        $res_mail = $this->adjust_lib->_user_sendMail($g_mail_info);
                                    }
                                }else{
                                    // エラー
                                    // オーナーへ送信するメール情報をセット
                                    $out_put['owner_name'] = $emp_data['NAME1'];
                                    $out_put['owner_mail'] = $emp_data['MAIL'];
                                    $out_put['bot_name']   = $this->config->item('adjust_from_admin_name');
                                    $out_put['reply_info'] = $this->adjust_lib->get_reply_info($msg);
                                    $o_mail_info = array(
                                        'to'            => $emp_data['MAIL'],
                                        'subject'       => "Re:". $msg->subject,
                                        'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                        'from_name'     => $this->config->item('adjust_from_admin_name'),
                                        'template_path' => $out_put['template'],
                                        'msg_id'        => "",
                                        'body_data'     => $out_put,
                                    );
                                    $res_mail = $this->adjust_lib->_user_sendMail($o_mail_info);
                                }

                                break;
                            case 3:     // 確認
                                // googleカレンダーから指定条件のスケジュールを取得し、メール出力内容を生成する
                                list($res, $answer) = $this->adjust_lib->personal_schedule_conf($emp_data, $res_data);
                                // 送信するメール情報をセット
                                $mail_info = array(
                                    'to'            => $plain_from,
                                    'subject'       => "Re:". $msg->subject,
                                    'from_mail'     => $this->config->item('adjust_from_admin_mail'),
                                    'from_name'     => $this->config->item('adjust_from_admin_name'),
                                    'template_path' => 'template/mail/adjust/send_owner_14',
                                    'msg_id'        => "",
                                    'body_data'     => array(
                                        'owner_name' => $emp_data['NAME1'],
                                        'owner_mail' => $emp_data['MAIL'],
                                        'answer'     => $schedule_data['GUEST_NAME'],
                                        'bot_name'   => $this->config->item('adjust_from_admin_name'),
                                        'reply_info' => $this->adjust_lib->get_reply_info($msg),
                                    )
                                );
                                $res_mail = $this->adjust_lib->_user_sendMail($mail_info);

                                break;
                            case 9:     // 処理内容判別不可
                                break;
                            default:
                                break;
                        }

                        break;
                    case 9:     // 新規登録促し処理
                        var_dump("mode=9 entry!");
                        break;
                    default:
                        var_dump("mode=default entry!");
                        break;
                }

            }
        }
        var_dump("success!!");
    }

    protected function _errorprocess()
    {
        var_dump("baseメールデータ取得失敗");
    }

/********************* ↓ sub function ↓ *********************/
}
