<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjust_lib {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function fix_mail_format($target, $sign_s, $sign_e){
        $res_mail = $target;
        if(mb_strpos($target, $sign_s) !== false && mb_strpos($target, $sign_e) !== false){
            $res_mail = mb_substr($target, mb_strpos($target, $sign_s) + mb_strlen($sign_s), mb_strpos($target, $sign_e) - mb_strpos($target, $sign_s) - mb_strlen($sign_s));
        }
        return $res_mail;
    }

    // 指定文字列からMessage-IDとin-reply-toを取得
    public function get_msg_id_by_mail($all){
        $res_msg = "";
        $res_reply = "";
        $all_array = explode("\n", $all);
        foreach($all_array as $key => $value){
            if((mb_strpos($value, "Message-ID:") !== false) && (mb_strpos($value, "@") !== false)){
                $res_msg = $this->fix_mail_format(trim(mb_substr($value, mb_strpos($value, "Message-ID:") + mb_strlen("Message-ID:"))), "<", ">");
            }
            if((mb_strpos($value, "In-Reply-To:") !== false) && (mb_strpos($value, "@") !== false)){
                $res_reply = $this->fix_mail_format(trim(mb_substr($value, mb_strpos($value, "In-Reply-To:") + mb_strlen("In-Reply-To:"))), "<", ">");
            }
            if($res_msg !== "" && $res_reply !== "") break;
        }
        return array($res_msg, $res_reply);
    }

    // スケジュール調整処理モード取得
    function get_mail_mode($p_to, $p_from, $in_reply_to){
        $res_mode = 0;
        $res_owner_mail = "";
        $res_schedule_data = array();

        if(empty($in_reply_to)){
            list($res_0, $emp_data) = $this->CI->modelUser->get_once_user_by_mail($p_from);
            if($res_0){
                $res_owner_mail = $emp_data['MAIL'];
                if(mb_strpos($p_to, $this->CI->config->item('adjust_from_admin_mail')) !== false){
                    $res_mode = 4;  // 個人スケジュール処理へ
                }else{
                    $res_mode = 1;  // 初期処理へ
                }
            }else{
                $res_mode = 9;      // 新規登録促し処理へ
            }
        }else{
            list($res_1, $schedule_data1) = $this->CI->modelShedule->get_schedule_setting_by_msgid($db, $in_reply_to, 0);
            if($res_1){
                // 2→準備処理へ、3→調整処理(場所)へ
                $res_mode = (empty($schedule_data1['GUEST_SEND_MSG_ID']))? 2: 3;
                $res_schedule_data = $schedule_data1;
                $res_owner_mail = $schedule_data1['OWNER_MAIL'];
            }else{
                list($res_2, $schedule_data2) = $this->CI->modelShedule->get_schedule_setting_by_msgid($db, $in_reply_to, 1);
                if($res_2){
                    $res_mode = 3;  // 調整処理へ
                    $res_schedule_data = $schedule_data2;
                    $res_owner_mail = $schedule_data2['OWNER_MAIL'];
                }else{
                    $res_mode = 8;  // error(何かの返信だが、当システムの管理外メール)
                }
            }
        }

        return array($res_mode, $res_owner_mail, $res_schedule_data);
    }

    // メール本文からスケジュール調整に必要な各入力データを抽出
    public function get_input_data($target_msg){
        $temp_res_data = array();

        if(!empty($target_msg)){
            // チェック対象データをそれぞれ取得
            // メッセージ本文を1行毎に分割
            $tar_str_array = explode("\r\n", $target_msg);

            // 各情報を取得
            foreach($tar_str_array as $key => $value){
                if(!empty($value)){
                    if((empty($temp_res_data["start_datetime"])) && (mb_strpos($value, "【開始日時】:") !== false)){
                        $temp_res_data["start_datetime"] = trim(mb_substr($value, mb_strpos($value, "【開始日時】:") + mb_strlen("【開始日時】:")));
                    }
                    if((empty($temp_res_data["end_datetime"])) && (mb_strpos($value, "【終了日時】:") !== false)){
                        $temp_res_data["end_datetime"] = trim(mb_substr($value, mb_strpos($value, "【終了日時】:") + mb_strlen("【終了日時】:")));
                    }
                    if((empty($temp_res_data["required"])) && (mb_strpos($value, "【所要時間】:") !== false)){
                        $temp_res_data["required"] = trim(mb_substr($value, mb_strpos($value, "【所要時間】:") + mb_strlen("【所要時間】:")));
                    }
                    if((empty($temp_res_data["guest_name"])) && (mb_strpos($value, "【MTG相手】:") !== false)){
                        $temp_res_data["guest_name"] = mb_substr($value, mb_strpos($value, "【MTG相手】:") + mb_strlen("【MTG相手】:"));
                    }
                    if((empty($temp_res_data["mtg_type"])) && (mb_strpos($value, "【MTG形式】:") !== false)){
                        $temp_res_data["mtg_type"] = mb_substr($value, mb_strpos($value, "【MTG形式】:") + mb_strlen("【MTG形式】:"));
                    }
                    if((empty($temp_res_data["mtg_place"])) && (mb_strpos($value, "【MTG場所】:") !== false)){
                        $temp_res_data["mtg_place"] = mb_substr($value, mb_strpos($value, "【MTG場所】:") + mb_strlen("【MTG場所】:"));
                    }
                    if((empty($temp_res_data["mtg_tel_no"])) && (mb_strpos($value, "【電話番号】:") !== false)){
                        $temp_res_data["mtg_tel_no"] = mb_substr($value, mb_strpos($value, "【電話番号】:") + mb_strlen("【電話番号】:"));
                    }
                    if((empty($temp_res_data["mtg_skype_id"])) && (mb_strpos($value, "【SKYPE ID】:") !== false)){
                        $temp_res_data["mtg_skype_id"] = mb_substr($value, mb_strpos($value, "【SKYPE ID】:") + mb_strlen("【SKYPE ID】:"));
                    }
                }
            }
        }

        return $temp_res_data;
    }

    // 指定日付を基に対象の曜日のMTG可能時間を取得
    public function get_possible_mtg_time($emp_data, $target_date_str, $start_time, $end_time){
        $res_flg = false;
        $res = array();

        // 指定日が祝日の場合は祝日のMTG可能日を優先
        $this->CI->load->library('common/other/holidayDateTime', $target_date_str);
        if($this->CI->holidayDateTime->holiday()){
            // 指定日が祝日
            if(!empty($emp_data["POSSIBLE_MTG_TIME_HOLIDAY"])){
                $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_HOLIDAY"]);
                $res_flg = true;
            }else{
                $res[0] = $start_time. "-". $end_time;
            }
        }else{
            // 指定日が祝日以外
            $target_week_no = date('w', strtotime($target_date_str. ' 00:00'));
            switch($target_week_no){
                case 0:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_SUN"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_SUN"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                case 1:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_MON"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_MON"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                case 2:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_TUE"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_TUE"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                case 3:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_WED"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_WED"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                case 4:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_THU"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_THU"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                case 5:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_FRI"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_FRI"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                case 6:
                    if(!empty($emp_data["POSSIBLE_MTG_TIME_SAT"])){
                        $res = explode(',', $emp_data["POSSIBLE_MTG_TIME_SAT"]);
                        $res_flg = true;
                    }else{
                        $res[0] = $start_time. "-". $end_time;
                    }
                    break;
                default:
                    $res[0] = $start_time. "-". $end_time;
                    break;
            }
        }

        return array($res_flg, $res);
    }

    // 開始時間取得
    public function get_start_datetime($emp_data, $input, $schedule_data){
        $res = '';
        if(empty($input)){
            if(!empty($schedule_data)){
                $res = $schedule_data;
            }else{
                $tomorrow = date("Y/m/d", strtotime("+1 day" ));
                list($res_possible, $possible_time_array) = $this->get_possible_mtg_time($emp_data, $tomorrow, $emp_data["POSSIBLE_MTG_TIME_START"], $emp_data["POSSIBLE_MTG_TIME_END"]);
                $time_buf = explode('-', $possible_time_array[0]);
                $start_time = $time_buf[0];
                $res = $tomorrow. ' '. $start_time;
            }
        }else{
            // 指定された日時が現在日時から2時間以内の場合は現在日時+2時間の日時を開始時間とする
            $date_min = new DateTime("+2 hour");
            if($input < strtotime($date_min->format('Y/m/d H:i'))){
                $check = intval($date_min->format('i'));
                if(0 < $check && $check <= 15){
                    $res = $date_min->format('Y/m/d H:15');
                }elseif(15 < $check && $check <= 30){
                    $res = $date_min->format('Y/m/d H:30');
                }elseif(30 < $check && $check <= 45){
                    $res = $date_min->format('Y/m/d H:45');
                }elseif(45 < $check && $check <= 59){
                    $date_min_buf = new DateTime("+3 hour");
                    $res = $date_min_buf->format('Y/m/d H:00');
                }else{
                    $res = $date_min->format('Y/m/d H:00');
                }
            }
        }

        return $res;
    }

    // 終了時間取得
    public function get_end_datetime($emp_data, $input, $schedule_data){
        $res = '';
        if(empty($input)){
            if(!empty($schedule_data)){
                $res = $schedule_data;
            }else{
                $max_end = date("Y/m/d", strtotime("+1 month" ));
                list($res_possible, $possible_time_array) = $this->get_possible_mtg_time($emp_data, $max_end, $emp_data["POSSIBLE_MTG_TIME_START"], $emp_data["POSSIBLE_MTG_TIME_END"]);
                $time_buf = explode('-', $possible_time_array[count($possible_time_array)-1]);
                $end_time = $time_buf[1];
                $res = $max_end. ' '. $end_time;
            }
        }
        return $res;
    }

    // ゲストメールアドレス取得
    public function get_guest_mail($to, $input, $schedule_data){
        $res = '';
        if(empty($input)){
            if(!empty($schedule_data)){
                $res = $schedule_data;
            }else{
                $res = (!empty($to) && mb_strpos($to, $this->CI->config->item('adjust_from_admin_mail')) === false)? $to: "";
            }
        }
        return $res;
    }

    public function get_required($emp_data, $input, $schedule_data){
        $res = '';
        if(empty($input["required"])){
            if(!empty($schedule_data)){
                $res = $schedule_data;
            }else{
                // ミーティング形式が対面、オンラインでデフォルト時間変動
                if((mb_strpos($input["mtg_type"], "skype") !== false) || (mb_strpos($input["mtg_type"], "tel") !== false)){
                    $res = (!empty($emp_data['POSSIBLE_MTG_TIME_DEF_ONLINE']))? $emp_data['POSSIBLE_MTG_TIME_DEF_ONLINE']: "";
                }else{
                    $res = (!empty($emp_data['POSSIBLE_MTG_TIME_DEF_OUT']))? $emp_data['POSSIBLE_MTG_TIME_DEF_OUT']: "";
                }
            }
        }
        return $res;
    }

    // MTG形式取得
    // MTG場所取得
    // MTG連絡先取得
    // MTG_SKYPE_ID取得
    public function get_mtg_base_data($emp_data, $input, $schedule_data){
        $res = '';
        if(empty($input)){
            if(!empty($schedule_data)){
                $res = $schedule_data;
            }else{
                $res = (!empty($emp_data))? $emp_data: "";
            }
        }
        return $res;
    }

    // 日時データのフォーマットチェック
    public function checkDatetimeFormat_user($datetime){
        $res = false;
        $base = array(
                '1' => "Y-m-d H:i:s",
                '2' => "Y/m/d H:i",
                '3' => "Y年m月d日 H時i分"
            );

        foreach ($base as $key => $value){
            if($datetime === date($value, strtotime($datetime))){
                $res = true;
                break;
            }
        }

        return $res;
    }

    // 開始日時と終了日時について入力チェック
    public function check_date($start, $end){
        $res = true;
        // フォーマットチェック
        if($res && ((empty($start)) || (!$this->checkDatetimeFormat_user($start)))){
            $res = false;
        }
        if($res && ((empty($end)) && (!$this->checkDatetimeFormat_user($end)))){
            $res = false;
        }
        // 存在する日付かどうかチェック
        if($res){
            $buf0 = date("Y-m-d", strtotime($start));
            $buf1 = date("Y-m-d", strtotime($end));
            list($y0, $m0, $d0) = explode('-', $buf0);
            list($y1, $m1, $d1) = explode('-', $buf1);
            if(!checkdate($m0, $d0, $y0) || !checkdate($m1, $d1, $y1)){
                $res = false;
            }
        }
        // 開始日時と終了日時の整合性チェック
        if($res && (strtotime($start) > strtotime($end))){
            $res = false;
        }
        return $res;
    }

    // 所要時間のフォーマットチェック
    public function checkRequiredFormat($target){
        return $target === date("H:i", strtotime($target));
    }

    // 一時的に取得した各種入力データを基に各データの整合性をチェックし、正常であれば結果配列へ代入、異常な場合エラー処理
    public function get_result_owner_mail($temp){
        $res_check = true;
        $res_data = array();
        $res_error = array();

        // 開始日時と終了日時について入力チェック
        if($this->check_date($temp["start_datetime"], $temp["end_datetime"])){
            $res_data["start_datetime"] = $temp["start_datetime"];
            $res_data["end_datetime"] = $temp["end_datetime"];
        }else{
            $res_check = false;
            $res_data["start_datetime"] = "";
            $res_data["end_datetime"] = "";
            $res_error['start_datetime'] = "この打ち合わせは何時間になる予定ですか？";
            $res_error['end_datetime'] = "この打ち合わせは何時間になる予定ですか？";
        }
        // 所要時間について入力チェック
        if((!empty($temp["required"])) && $this->checkRequiredFormat($temp["required"])){
            $res_data["required"] = $temp["required"];
        }else{
            // 所要時間取得不可
            $res_check = false;
            $res_data["required"] = "";
            $res_error['required'] = "この打ち合わせは何時間になる予定ですか？";
        }
        // ゲストのメールアドレスが取得不可だが、ゲスト名の指定がある場合
        if(empty($temp["guest_mail"]) && !empty($temp["guest_name"])){
            $res_check = false;
            $res_data["guest_name"] = "";
            $res_error['guest_name'] = "「". $temp["guest_name"]. "」さんのメールアドレスが分かりませんでした。". "\n";
            $res_error['guest_name'] .= "私から「". $temp["guest_name"]. "」さんに連絡いたしますので、";
            $res_error['guest_name'] .= "「". $temp["guest_name"]. "」さんのメールアドレスを教えていただけますか。";
        }else{
            // ゲストメールアドレスの記載があり、ゲスト名の記載がない場合はメールアドレスを宛名として採用
            $res_data["guest_name"] = (!empty($temp["guest_name"]))? $temp["guest_name"]: $temp["guest_mail"];
        }

        $res_data["guest_mail"] = (!empty($temp["guest_mail"]))? $temp["guest_mail"]: "";
        $res_data["mtg_type"] = (!empty($temp["mtg_type"]))? $temp["mtg_type"]: "";
        $res_data["mtg_skype_id"] = (!empty($temp["mtg_skype_id"]))? $temp["mtg_skype_id"]: "";
        $res_data["mtg_tel_no"] = (!empty($temp["mtg_tel_no"]))? $temp["mtg_tel_no"]: "";
        $res_data["mtg_place"] = (!empty($temp["mtg_place"]))? $temp["mtg_place"]: "";

        // MTG形式による取得情報チェック
        if(!empty($temp["mtg_type"])){
            if(mb_strpos($temp["mtg_type"], "skype") !== false){
                // skype MTG希望の場合はskype id の入力が必須
                if(!empty($temp["mtg_skype_id"])){
                    $res_data["out_mtg_info"] = "なお、Owner名のSkypeIDは". $temp["mtg_skype_id"]. "です。". "\n";
                }else{
                    $res_check = false;
                    $res_error['mtg_skype_id'] = "SKYPE MTGの場合SKYPE IDを教えてください。";
                }
            }elseif((mb_strpos($temp["mtg_type"], "tel") !== false) || (mb_strpos($temp["mtg_type"], "電話") !== false)){
                // 電話MTG希望の場合は電話番号の入力が必須
                if(!empty($temp["mtg_tel_no"])){
                    $res_data["out_mtg_info"] = "なお、Owner名の電話番号は". $temp["mtg_tel_no"]. "です。". "\n";
                }else{
                    $res_check = false;
                    $res_error['mtg_tel_no'] = "電話MTGの場合電話番号を教えてください。";
                }
            }else{
                // 上記以外は対面のMTGとし、場所の入力が必須
                if(!empty($temp["mtg_place"])){
                    $res_data["out_mtg_info"] = "また、差し支えがなければ場所は". $temp["mtg_place"]. "でよろしくお願いいたします。". "\n";
                }else{
                    $res_check = false;
                    $res_error['mtg_place'] = "対面MTGの場合打ち合わせ場所を教えてください。";
                }
            }
        }else{
            if(!empty($temp["mtg_skype_id"]) && empty($temp["mtg_tel_no"]) && empty($temp["mtg_place"])){
                // SKYPE IDのみ記載がある場合はMTG形式をSKYPEとする
                $res_data["mtg_type"] = "skype";
            }elseif(empty($temp["mtg_skype_id"]) && !empty($temp["mtg_tel_no"]) && empty($temp["mtg_place"])){
                // 連絡先のみ記載がある場合はMTG形式をtelとする
                $res_data["mtg_type"] = "tel";
            }elseif(empty($temp["mtg_skype_id"]) && empty($temp["mtg_tel_no"]) && !empty($temp["mtg_place"])){
                // 場所のみ記載がある場合はMTG形式を対面とする
                $res_data["mtg_type"] = "meet";
            }elseif(empty($temp["mtg_skype_id"]) && empty($temp["mtg_tel_no"]) && empty($temp["mtg_place"])){
                // MTG形式未入力の場合は個人予定登録 ※一旦エラー処理
                $res_check = false;
                $res_error['mtg_type'] = "オフィスでの打ち合わせ、Skypeなど、お打ち合わせの形式を教えてください。";
            }else{
                // MTG形式が未入力でMTGの必要情報が複数されている場合はMTG形式が判断不可の為エラー
                $res_check = false;
                $res_error['mtg_type'] = "オフィスでの打ち合わせ、Skypeなど、お打ち合わせの形式を教えてください。";
            }
        }

        return array($res_check, $res_data, $res_error);
    }

    // 対象のメールからスケジュール調整に必要な情報を揃える
    // メール文面から取得可能な情報→スケジュール用にDBに保持している情報→システム上のデフォルト情報(WEB画面登録)の優先順位で情報を取得
    public function check_owner_mail_msg($target_msg, $to, $emp_data, $schedule_data){
        $res_check = true;
        $temp_res_data = array();
        $res_data = array();
        $res_error = array();

        // メール本文からスケジュール調整に必要な各入力データを抽出
        $temp_res_data = $this->get_input_data($target_msg);

        // 各種得情報をデフォルト値も用いて整理
        // 開始時間
        $temp_res_data["start_datetime"] = $this->get_start_datetime($emp_data, $temp_res_data["start_datetime"], $schedule_data["START_DATETIME"]);
        // 終了時間
        $temp_res_data["end_datetime"] = $this->get_end_datetime($emp_data, $temp_res_data["end_datetime"], $schedule_data["END_DATETIME"]);
        // ゲストメールアドレス
        $temp_res_data["guest_mail"] = $this->get_guest_mail($to, $temp_res_data["guest_mail"], $schedule_data["GUEST_MAIL"]);
        // MTG形式
        $temp_res_data["mtg_type"] = $this->get_mtg_base_data($emp_data['DEFAULT_MTG_TYPE_NAME'], $temp_res_data["mtg_type"], $schedule_data["MTG_TYPE"]);
        // MTG場所
        $temp_res_data["mtg_place"] = $this->get_mtg_base_data($emp_data['DEFAULT_MTG_PLACE_NAME'], $temp_res_data["mtg_place"], $schedule_data["MTG_PLACE"]);
        // MTG連絡先
        $emp_tel = $emp_data['tel1']. $emp_data['tel2']. $emp_data['tel3'];
        $temp_res_data["mtg_tel_no"] = $this->get_mtg_base_data($emp_tel, $temp_res_data["mtg_tel_no"], $schedule_data["MTG_TEL_NO"]);
        // MTG_SKYPE_ID
        $temp_res_data["mtg_skype_id"] = $this->get_mtg_base_data($emp_data['SKYPE_ID'], $temp_res_data["mtg_skype_id"], $schedule_data["MTG_SKYPE_ID"]);
        // 所要時間
        $temp_res_data["required"] = $this->get_required($emp_data, $temp_res_data, $schedule_data["REQUIRED"]);

        // 一時的に取得した各種入力データを基に各データの整合性をチェックし、正常であれば結果配列へ代入、異常な場合エラー処理
        list($res_check, $res_data, $res_error) = $this->get_result_owner_mail($temp_res_data);
        return array($res_check, $res_data, $res_error);
    }

    // メール本文から引用返信部分を削除
    public function get_plain_body($target_data){
        $res = "";
        // メッセージ本文を1行毎に分割
        $tar_str_array = explode("\n", $target_data);
        // メッセージ本文を1行ずつチェック
        foreach($tar_str_array as $key => $value){
            // 対象データが存在しない場合はスキップ
            if(empty($value)) continue;

            if((mb_strpos($value, "<".$this->CI->config->item('adjust_from_admin_mail').">:") !== false) || (mb_strpos($value, "wrote:") !== false) || (mb_strpos($value, ":wrote") !== false)){
                break;
            }else{
                $res .= $value. "\n";
            }
        }
        return $res;
    }

    // 受信メール情報をDBに登録
    public function insert_mail_data($input){
        $mail_data = array();
        $mail_data['schedule_id'] = (!empty($input['schedule_id']))? $input['schedule_id']: "";
        $mail_data['msg_id']    = (!empty($input['msg_id']))? $input['msg_id']: "";
        $mail_data['from_name'] = (!empty($input['from_name']))? $input['from_name']: "";
        $mail_data['from_mail'] = (!empty($input['from_mail']))? $input['from_mail']: "";
        $mail_data['to_name']   = (!empty($input["to_name"]))? $input['to_name']: "";
        $mail_data['to_mail']   = (!empty($input['to_mail']))? $input['to_mail']: "";
        $mail_data['cc_name']   = (!empty($input['cc_name']))? $input['cc_name']: "";
        $mail_data['cc_mail']   = (!empty($input['cc_mail']))? $input['cc_mail']: "";
        $mail_data['subject']   = (!empty($input['subject']))? $input['subject']: "";
        $mail_data['mail_text'] = (!empty($input['mail_text']))? $this->get_plain_body($input['mail_text']): "";
        return $this->CI->modelMail->isnert_mail($mail_data);
    }

    // 2つの日付の差分を日数で取得
    public function day_diff($date1, $date2){
        // 日付をUNIXタイムスタンプに変換
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        // 何秒離れているかを計算
        $seconddiff = abs($timestamp2 - $timestamp1);
        // 日数に変換
        $daydiff = $seconddiff / (60 * 60 * 24);
        return $daydiff;
    }

    // 指定のスケジュールが終日かチェック
    public function check_oneday($schedules){
        $oneday = false;
        if (count($schedules->getItems()) != 0) {
            foreach ($schedules->getItems() as $event) {
                if(!empty($event->start->date) && !empty($event->end->date)){
                    $oneday = true;
                }
            }
        }
        return $oneday;
    }

    // 予定入力可能開始時間、終了時間を決定
    public function get_limit_time($cnt, $max, $mail_info, $emp_data, $def_time){
        $res_s = "";
        $res_e = "";
        $date = new DateTime();
        $today = $date->format('Y/m/d');
        $date_s = new DateTime($mail_info['start_datetime']);
        $date_e = new DateTime($mail_info['end_datetime']);

        // 処理開始時間を取得
        if($cnt == 0){
            $s_temp = $date_s->format('H:i:s');
            $mtg_pos_time_s = (!empty($emp_data['POSSIBLE_MTG_TIME_START']))? $emp_data['POSSIBLE_MTG_TIME_START']: $def_time['start'];
            $res_s = (strtotime($today. " ". $mtg_pos_time_s) > strtotime($today. " ". $s_temp))? $mtg_pos_time_s: $s_temp;
        }else{
            $res_s = (!empty($emp_data['POSSIBLE_MTG_TIME_START']))? $emp_data['POSSIBLE_MTG_TIME_START']: $def_time['start'];
        }

        // 処理終了時間を取得
        if($cnt == $max){
            $e_temp = $date_e->format('H:i:s');
            $mtg_pos_time_e = (!empty($emp_data['POSSIBLE_MTG_TIME_END']))? $emp_data['POSSIBLE_MTG_TIME_END']: $def_time['end'];
            $res_e = (strtotime($today. " ". $mtg_pos_time_e) < strtotime($today. " ". $e_temp))? $mtg_pos_time_e: $e_temp;
        }else{
            $res_e = (!empty($emp_data['POSSIBLE_MTG_TIME_END']))? $emp_data['POSSIBLE_MTG_TIME_END']: $def_time['end'];
        }

        return array($res_s, $res_e);
    }

    // 対象日の空き時間抽出処理
    public function get_free_time_by_google($google_schedule, $emp_data, $target_date_y, $target_date_m, $target_date_d, $start_time, $end_time, $need_time){
        $free_time_temp = array();
        $free_time_temp_cnt = 0;
        $target_date_str = $target_date_y. '-'. $target_date_m. '-'. $target_date_d;
        $free_time_temp[0]['start'] = date('Y/m/d H:i', strtotime($target_date_str. ' '. $start_time));

        if (count($google_schedule->getItems()) != 0) {
            foreach ($google_schedule->getItems() as $event) {
                $free_time_temp[$free_time_temp_cnt]['end'] = date('Y/m/d H:i', strtotime($event->start->dateTime));
                $free_time_temp_cnt++;
                $free_time_temp[$free_time_temp_cnt]['start'] = date('Y/m/d H:i', strtotime($event->end->dateTime));
            }
        }

        $free_time_temp[$free_time_temp_cnt]['end'] = date('Y/m/d H:i', strtotime($target_date_y. '-'. $target_date_m. '-'. $target_date_d. ' '. $end_time));

        // 対象日の空き時間抽出処理1
        $free_time = array();
        $free_time_cnt = 0;
        for($i = 0; $i < count($free_time_temp); $i++){
            $diff = strtotime($free_time_temp[$i]['end']) - strtotime($free_time_temp[$i]['start']);
            if(($diff / 3600) > idate('H',strtotime($need_time))){
                $free_time[$free_time_cnt]['start'] = $free_time_temp[$i]['start'];
                $free_time[$free_time_cnt]['end'] = $free_time_temp[$i]['end'];
                $free_time_cnt++;
            }
        }

        // 対象日の空き時間抽出処理2
        $proposal_arr = array();
        $proposal_cnt = 0;
        $req_time = explode(':', $need_time);
        for($i = 0; $i < count($free_time); $i++){
            $s_date = strtotime($free_time[$i]['start']);
            $e_date = strtotime('+'.$req_time[0].' hour', $s_date);
            $e_date = strtotime('+'.$req_time[1].' minute', $e_date);
            while($e_date < strtotime($free_time[$i]['end'])){
                $proposal_arr[$proposal_cnt]['start'] = date('Y/m/d H:i', $s_date);
                $proposal_arr[$proposal_cnt]['end'] = date('Y/m/d H:i', $e_date);
                $s_date = $e_date;
                $e_date = strtotime('+'.$req_time[0].' hour', $s_date);
                $e_date = strtotime('+'.$req_time[1].' minute', $e_date);
                $proposal_cnt++;
            }
        }

        // 指定日付を基に対象の曜日のMTG可能時間を取得
        list($res, $possible_time) = $this->get_possible_mtg_time($emp_data, $target_date_str, $start_time, $end_time);

        $free_time_res = array();
        if($res){
            foreach($proposal_arr as $key => $value){
                foreach($possible_time as $key2 => $value2){
                    $possible = explode('-', $value2);
                    if((strtotime($value['start']) >= strtotime($target_date_str. ' '. $possible[0])) && (strtotime($value['end']) <= strtotime($target_date_str. ' '. $possible[1]))){
                        $free_time_res[] = $value;
                        break;
                    }
                }
            }
        }

        return $free_time_res;
    }

    // 提案の優先順位の高い順に提案日程配列をソート
    public function sort_free_time($all_free_time){
        $free_first_cnt = 0;
        $free_second_cnt = 0;
        $res_cnt = 0;
        $res_first_cnt = 0;
        $res_second_cnt = 0;
        $buf_cnt = 0;
        $free_first = array();
        $free_second = array();
        $res_free = array();
        $buf_free = array();
        if(!empty($all_free_time)){
            $tar_date = date("Y/m/d", strtotime($all_free_time[0]['start']));
            $base_first_s = strtotime($tar_date. " 10:00:00");
            $base_first_e = strtotime($tar_date. " 12:00:01");
            $base_second_s = strtotime($tar_date. " 14:00:00");
            $base_second_e = strtotime($tar_date. " 20:00:01");
            foreach($all_free_time as $key => $val){
                $s = strtotime($val['start']);
                $e = strtotime($val['end']);
                if($base_first_s <= $s && $e <= $base_first_e){
                    $free_first[$free_first_cnt]['start'] = $val['start'];
                    $free_first[$free_first_cnt]['end'] = $val['end'];
                    $free_first_cnt++;
                }elseif($base_second_s <= $s && $e <= $base_second_e){
                    $free_second[$free_second_cnt]['start'] = $val['start'];
                    $free_second[$free_second_cnt]['end'] = $val['end'];
                    $free_second_cnt++;
                }else{
                    $buf_free[$buf_cnt]['start'] = $val['start'];
                    $buf_free[$buf_cnt]['end'] = $val['end'];
                    $buf_cnt++;
                }
            }

            $max_cnt = $free_first_cnt + $free_second_cnt;
            for($i = 0; $i < $max_cnt; $i++){
                if($i % 2 == 0){
                    if(!empty($free_first[$res_first_cnt]['start']) && !empty($free_first[$res_first_cnt]['end'])){
                        $res_free[$res_cnt]['start'] = $free_first[$res_first_cnt]['start'];
                        $res_free[$res_cnt]['end'] = $free_first[$res_first_cnt]['end'];
                        $res_first_cnt++;
                        $res_cnt++;
                    }elseif(!empty($free_second[$res_second_cnt]['start']) && !empty($free_second[$res_second_cnt]['end'])){
                        $res_free[$res_cnt]['start'] = $free_second[$res_second_cnt]['start'];
                        $res_free[$res_cnt]['end'] = $free_second[$res_second_cnt]['end'];
                        $res_second_cnt++;
                        $res_cnt++;
                    }
                }else{
                    if(!empty($free_second[$res_second_cnt]['start']) && !empty($free_second[$res_second_cnt]['end'])){
                        $res_free[$res_cnt]['start'] = $free_second[$res_second_cnt]['start'];
                        $res_free[$res_cnt]['end'] = $free_second[$res_second_cnt]['end'];
                        $res_second_cnt++;
                        $res_cnt++;
                    }elseif(!empty($free_first[$res_first_cnt]['start']) && !empty($free_first[$res_first_cnt]['end'])){
                        $res_free[$res_cnt]['start'] = $free_first[$res_first_cnt]['start'];
                        $res_free[$res_cnt]['end'] = $free_first[$res_first_cnt]['end'];
                        $res_first_cnt++;
                        $res_cnt++;
                    }
                }
            }
        }

        return array($res_free, $buf_free);
    }

    // 指定の件数でMTG候補日を取得する
    public function get_candidate($max_cnt, &$out_cnt, $res_free_time_arr, &$res_free, $ng_date){
        for($i = 0; $i < $max_cnt; $i++){
            if(empty($res_free_time_arr[$i])) break;

            if(!empty($ng_date)){
                // 提案済みの候補日は出力しない
                $out_flg = true;
                foreach($ng_date as $key => $value){
                    if(strtotime($res_free_time_arr[$i]['start']) == strtotime($value['DATE_TIME_START']) && strtotime($res_free_time_arr[$i]['end']) == strtotime($value['DATE_TIME_END'])){
                        $out_flg = false;
                        break;
                    }
                }
                if($out_flg){
                    $res_free[$out_cnt]['start'] = $res_free_time_arr[$i]['start'];
                    $res_free[$out_cnt]['end'] = $res_free_time_arr[$i]['end'];
                    $out_cnt++;
                }
            }else{
                $res_free[$out_cnt]['start'] = $res_free_time_arr[$i]['start'];
                $res_free[$out_cnt]['end'] = $res_free_time_arr[$i]['end'];
                $out_cnt++;
            }
        }
    }

    public function sort_res_free_time($free, $buf){
        $res = $free;
        if(!empty($free)){
            $res_temp = $free;
            $reg_cnt = count($free);
            $reg_max_cnt = 3 - $reg_cnt;
            // 提案日が足りない場合優先度が低い日程を追加する
            if($reg_max_cnt > 0){
                for($i = 0; $i < $reg_max_cnt; $i++){
                    $res_temp[$reg_cnt + $i] = $buf[$i];
                }
                // 提案日追加後、日時順にソート
                $buf_date = array();
                foreach($res_temp as $key => $val){
                    $buf_date[] = $val['start'];
                }
                array_multisort($buf_date, SORT_ASK, $res_temp);
            }

            $res = $res_temp;
        }

        return $res;
    }

    function make_google_datetime($datetime){
        $y = date('Y', strtotime($datetime));
        $m = date('m', strtotime($datetime));
        $d = date('d', strtotime($datetime));
        $h = date('H', strtotime($datetime));
        $s = date('i', strtotime($datetime));
        return date(DATE_ATOM, mktime($h, $s, 0, $m, $d, $y));
    }

    // googleカレンダーへMTG候補日を仮登録
    public function insert_temp_schedule($emp_data, $mail_info, $to_apply, $start, $end){
        $res_e_id = "";
        $summary = '【MTG候補】調整中 by Anna';
        $s_datetime = $this->make_google_datetime($start);
        $e_datetime = $this->make_google_datetime($end);
        $timezone = 'Asia/Tokyo';
        $description = '【申請先】'. $to_apply. ' 様'. "\n";
        $description .= '【MTG形式】'. $mail_info['mtg_type']. "\n";

        if(!empty($mail_info['mtg_place'])){
            $description .= '【MTG場所】'. $mail_info['mtg_place'];
        }elseif(!empty($mail_info['mtg_skype_id'])){
            $description .= '【MTG_SKYPE_ID】'. $mail_info['mtg_skype_id'];
        }elseif(!empty($mail_info['mtg_tel_no'])){
            $description .= '【MTG電話番号】'. $mail_info['mtg_tel_no'];
        }

        // googleカレンダーへ登録
        $res_e_id = $this->CI->calender_lib->_set_schedule_to_google($emp_data['google_calendar_id'], $summary, $description, $s_datetime, $e_datetime, $timezone);
        return $res_e_id;
    }

    // 対象のGoogleカレンダーを参照してMTG候補日を取得
    public function get_choice_item($emp_data, $to_apply, $mail_info, $ng_date){
        $res = "";
        $example = "";
        $out_cnt = 0;
        $res_free_time_arr = array();
        $res_free = array();
        $buf_free = array();
        $def_time = array();
        $def_time['start'] = '00:00:00';
        $def_time['end'] = '23:59:59';
        $def_date = (!empty($mail_info['start_datetime']))? date("Y/m/d", strtotime($mail_info['start_datetime'])): date("Y/m/d", strtotime("+1 day" ));
        $end_date = (!empty($mail_info['end_datetime']))? date("Y/m/d", strtotime($mail_info['end_datetime'])): date("Y/m/d", strtotime("+1 month" ));
        $req_time = (!empty($mail_info['required']))? $mail_info['required']: '1:00';
        $period = $this->day_diff($def_date, $end_date);

        // 指定の期間でMTG候補日を選出する
        for($i = 0; $i <= $period; $i++){
            $tar_date = ($i > 0)? date("Y/m/d", strtotime($def_date. " +". $i. " day")): $def_date;
            $y = date("Y", strtotime($tar_date));
            $m = date("m", strtotime($tar_date));
            $d = date("d", strtotime($tar_date));
            // googleカレンダーから対象日のスケジュール取得
            $results = $this->CI->calender_lib->_get_oneday_schedule_by_google($emp_data['google_calendar_id'], $y, $m, $d);
            // 取得したスケジュールが終日の場合continue
            if($this->check_oneday($results)) continue;
            // 対象日の空き時間抽出処理
            // 予定入力可能開始時間、終了時間を決定
            list($limit_time_s, $limit_time_e) = $this->get_limit_time($i, $period, $mail_info, $emp_data, $def_time);
            // メール本文より取得予定
            $res_free_time_arr_temp = $this->get_free_time_by_google($results, $emp_data, $y, $m, $d, $limit_time_s, $limit_time_e, $req_time);
            // 空き時間が取得不可の場合continue
            if(empty($res_free_time_arr_temp)) continue;
            // 提案の優先順位の高い順に提案日程配列をソート
            $buf_free_temp = array();
            list($res_free_time_arr, $buf_free_temp) = $this->sort_free_time($res_free_time_arr_temp);
            $buf_free = array_merge($buf_free, $buf_free_temp);
            // 処理対象日が最終日かチェック
            if(strtotime($tar_date) == strtotime($end_date)){
                $this->get_candidate(3 - $out_cnt, $out_cnt, $res_free_time_arr, $res_free, $ng_date);
            }else{
                if($out_cnt <= 0){
                    $this->get_candidate(2, $out_cnt, $res_free_time_arr, $res_free, $ng_date);
                }else{
                    $this->get_candidate(1, $out_cnt, $res_free_time_arr, $res_free, $ng_date);
                }
            }

            if($out_cnt >= 3) break;
        }

        // 提案日が足りない場合優先度が低い日程を追加する
        $res_free = $this->sort_res_free_time($res_free, $buf_free);

        // 最終的な提案日配列を昇順でソート
        $upd_key = array();
        foreach($res_free as $key => $val){
            $upd_key[$key] = $val['start'];
        }
        array_multisort($upd_key, SORT_ASC, $res_free);

        // 候補日の結果を基に各種処理を実施
        $res_cnt = 0;
        $res_event_id = array();
        foreach($res_free as $key => $value){
            // googleカレンダーへMTG候補日を仮登録
            $res_event_id[] = $this->insert_temp_schedule($emp_data, $mail_info, $to_apply, $value['start'], $value['end']);

            // メール送信用本文生成
            $res .= ($res_cnt + 1). " : ". $value['start']. " - ". $value['end']. "\n";
            if($example ==="") $example = ($res_cnt + 1). " : ". $value['start']. " - ". $value['end'];
            $res_cnt++;
        }
        $res .= ($res_cnt + 1). " : 上記日程以外の日程を希望(YYYY/MM/DD)". "\n\n";

        for($i = 0; $i < count($res_event_id); $i++){
            $res_free[$i]['event_id'] = $res_event_id[$i];
        }

        return array($res, $example, $res_free);
    }

    // メッセージID生成
    public function get_msg_id($subcode){
        return md5($subcode. "_". uniqid(microtime())). $this->CI->config->item('adjust_mail_domain');
    }

    // 対象メッセージを基にreply情報を生成
    public function get_reply_info($msg){
        $res = date("Y年m月d日 H:i", strtotime($msg->date)). " ". $msg->from. ":wrote\n". $this->CI->config->item('reply_mark'). "\n";
        $res .= $this->CI->config->item('reply_mark'). str_replace("\n", "\n". $this->CI->config->item('reply_mark'), $msg->plainbody);
        return $res;
    }

    // 固定フォーマットに当てはまる部分をメッセージから取得
    public function get_info_by_fixed($tar_info, $temp = array()){
        $res = array();
        // メッセージ本文を1行毎に分割
        $tar_str_array = explode("\r\n", $tar_info);
        // 各情報を取得
        foreach($tar_str_array as $key => $value){
            if(!empty($value)){
                if((empty($temp["start_datetime"])) && (mb_strpos($value, "【開始日時】:") !== false)){
                    $res["start_datetime"] = trim(mb_substr($value, mb_strpos($value, "【開始日時】:") + mb_strlen("【開始日時】:")));
                }
                if((empty($temp["end_datetime"])) && (mb_strpos($value, "【終了日時】:") !== false)){
                    $res["end_datetime"] = trim(mb_substr($value, mb_strpos($value, "【終了日時】:") + mb_strlen("【終了日時】:")));
                }
                if((empty($temp["required"])) && (mb_strpos($value, "【所要時間】:") !== false)){
                    $res["required"] = trim(mb_substr($value, mb_strpos($value, "【所要時間】:") + mb_strlen("【所要時間】:")));
                }
                if((empty($temp["guest_name"])) && (mb_strpos($value, "【MTG相手_名前】:") !== false)){
                    $res["guest_name"] = mb_substr($value, mb_strpos($value, "【MTG相手_名前】:") + mb_strlen("【MTG相手_名前】:"));
                }
                if((empty($temp["guest_mail"])) && (mb_strpos($value, "【MTG相手_メールアドレス】:") !== false)){
                    $res["guest_mail"] = mb_substr($value, mb_strpos($value, "【MTG相手_メールアドレス】:") + mb_strlen("【MTG相手_メールアドレス】:"));
                }
                if((empty($temp["mtg_type"])) && (mb_strpos($value, "【MTG形式】:") !== false)){
                    $res["mtg_type"] = mb_substr($value, mb_strpos($value, "【MTG形式】:") + mb_strlen("【MTG形式】:"));
                }
                if((empty($temp["mtg_place"])) && (mb_strpos($value, "【MTG場所】:") !== false)){
                    $res["mtg_place"] = mb_substr($value, mb_strpos($value, "【MTG場所】:") + mb_strlen("【MTG場所】:"));
                }
                if((empty($temp["mtg_tel_no"])) && (mb_strpos($value, "【電話番号】:") !== false)){
                    $res["mtg_tel_no"] = mb_substr($value, mb_strpos($value, "【電話番号】:") + mb_strlen("【電話番号】:"));
                }
                if((empty($temp["mtg_skype_id"])) && (mb_strpos($value, "【SKYPE ID】:") !== false)){
                    $res["mtg_skype_id"] = mb_substr($value, mb_strpos($value, "【SKYPE ID】:") + mb_strlen("【SKYPE ID】:"));
                }
            }
        }
        return $res;
    }

    // メール送信者がオーナーかゲストか判定
    public function get_sender($schedule_id, $from){
        $res = 0;
        $res_o = false;
        $res_g = false;
        $res_o_data = array();
        $res_g_data = array();
        list($res_o, $res_o_data) = $this->CI->modelShedule->get_schedule_data_by_email($schedule_id, $from, 0);
        if($res_o){
            $res = 1;	// 送信元はオーナー
        }else{
            list($res_g, $res_g_data) = $this->CI->modelShedule->get_schedule_data_by_email($schedule_id, $from, 1);
            if($res_g) $res = 2;	// 送信元はゲスト
        }
        return $res;
    }

    public function check_target_date($target, $base_s, $base_e){
        $res = 0;
        if(strtotime($target) < strtotime($base_s)){
            $res = 1;
        }elseif(strtotime($target) == strtotime($base_s)){
            $res = 2;
        }elseif(strtotime($base_s) < strtotime($target) && strtotime($target) < strtotime($base_e)){
            $res = 3;
        }elseif(strtotime($target) == strtotime($base_e)){
            $res = 4;
        }elseif(strtotime($base_e) < strtotime($target)){
            $res = 5;
        }
        return $res;
    }

    public function get_target_date($guest, $owner, $index){
        $check_s = 0;
        $check_e = 0;
        $res_s = $owner['START_DATETIME'];
        $res_e = $owner['END_DATETIME'];
        $o_s_datetime = new DateTime($res_s);
        $o_e_datetime = new DateTime($res_e);
        $o_s = $o_s_datetime->format('Y-m-d H:i');
        $o_e = $o_e_datetime->format('Y-m-d H:i');

        switch($index){
            case 1:		// 開始日時○ ＆ 終了日時○
                $g_s_datetime = new DateTime($guest['start_datetime']);
                $g_e_datetime = new DateTime($guest['end_datetime']);
                $g_s = $g_s_datetime->format('Y-m-d H:i');
                $g_e = $g_e_datetime->format('Y-m-d H:i');

                // 指定日時の整合性チェック
                if(strtotime($g_s) <= strtotime($g_e)){
                    // 開始日時判定
                    $check_s = $this->check_target_date($g_s, $o_s, $o_e);
                    // 終了日時判定
                    $check_e = $this->check_target_date($g_e, $o_s, $o_e);

                    // 開始日時＆終了日時判定
                    switch($check_s){
                        case 1:
                            switch($check_e){
                                case 1:
                                    $res_s = $g_s;
                                    $res_e = $g_e;
                                    break;
                                case 2:
                                    $res_s = $o_s;
                                    $res_e = $o_s;
                                    break;
                                case 3:
                                    $res_s = $o_s;
                                    $res_e = $g_e;
                                    break;
                                case 4:
                                    $res_s = $o_s;
                                    $res_e = $o_e;
                                    break;
                                case 5:
                                    $res_s = $o_s;
                                    $res_e = $o_e;
                                    break;
                                default:
                                    break;
                            }
                            break;
                        case 2:
                            switch($check_e){
                                case 2:
                                    $res_s = $o_s;
                                    $res_e = $o_s;
                                    break;
                                case 3:
                                    $res_s = $o_s;
                                    $res_e = $g_e;
                                    break;
                                case 4:
                                    $res_s = $o_s;
                                    $res_e = $o_e;
                                    break;
                                case 5:
                                    $res_s = $o_s;
                                    $res_e = $o_e;
                                    break;
                                default:
                                    break;
                            }
                            break;
                        case 3:
                            switch($check_e){
                                case 3:
                                    $res_s = $g_s;
                                    $res_e = $g_e;
                                    break;
                                case 4:
                                    $res_s = $g_s;
                                    $res_e = $o_e;
                                    break;
                                case 5:
                                    $res_s = $g_s;
                                    $res_e = $o_e;
                                    break;
                                default:
                                    break;
                            }
                            break;
                        case 4:
                            switch($check_e){
                                case 4:
                                    $res_s = $o_e;
                                    $res_e = $o_e;
                                    break;
                                case 5:
                                    $res_s = $o_e;
                                    $res_e = $o_e;
                                    break;
                                default:
                                    break;
                            }
                            break;
                        case 5:
                            switch($check_e){
                                case 5:
                                    $res_s = $g_s;
                                    $res_e = $g_e;
                                    break;
                                default:
                                    break;
                            }
                            break;
                        default:
                            break;
                    }

                }

                break;
            case 2:		// 開始日時○ ＆ 終了日時×
                $g_s_datetime = new DateTime($guest['start_datetime']);
                $g_s = $g_s_datetime->format('Y-m-d H:i');

                // 開始日時判定
                $check_s = $this->check_target_date($g_s, $o_s, $o_e);

                switch($check_s){
                    case 1:
                        $res_s = $o_s;
                        $res_e = $o_e;
                        break;
                    case 2:
                        $res_s = $o_s;
                        $res_e = $o_e;
                        break;
                    case 3:
                        $res_s = $g_s;
                        $res_e = $o_e;
                        break;
                    case 4:
                        $res_s = $o_e;
                        $res_e = $o_e;
                        break;
                    case 5:
                        $res_s = $g_s;
                        $res_e = $g_s_datetime->modify('+1 months')->format('Y-m-d H:i');
                        break;
                    default:
                        break;
                }

                break;
            case 3:		// 開始日時× ＆ 終了日時○
                $g_e_datetime = new DateTime($guest['end_datetime']);
                $g_e = $g_e_datetime->format('Y-m-d H:i');
                $tomorrow = new DateTime('+1 days');

                // 終了日時判定
                $check_e = $this->check_target_date($g_e, $o_s, $o_e);

                switch($check_e){
                    case 1:
                        $res_s = $tomorrow->format('Y-m-d H:i');
                        $res_e = $g_e;
                        break;
                    case 2:
                        $res_s = $o_s;
                        $res_e = $o_s;
                        break;
                    case 3:
                        $res_s = $o_s;
                        $res_e = $g_e;
                        break;
                    case 4:
                        $res_s = $o_s;
                        $res_e = $o_e;
                        break;
                    case 5:
                        $res_s = $o_s;
                        $res_e = $o_s;
                        break;
                    default:
                        break;
                }

                break;
            default:	// 開始日時× ＆ 終了日時×
                break;
        }

        return array($res_s, $res_e);
    }

    // 対象メッセージを解析
    public function analysis_mail_body($emp_data, $tar_mail_body, $schedule_data, $from){
        $res = false;
        $res_date = array();
        $upd_data = array();
        $mode = 2;      // デフォルト(開始日時× ＆ 終了日時×)→再調整
        $get_index = 0;
        $answer ="";

        // 自由文処理で取得不可で固定値で取得可能な場合は下記処理で各内容を取得
        // チェック対象データをそれぞれ取得
        $temp_res_data = $this->get_info_by_fixed($tar_mail_body, $temp_res_data);

        $db_flg = false;
        if(!empty($temp_res_data['start_datetime']) && !empty($temp_res_data['end_datetime'])){
            // 開始日時○ ＆ 終了日時○
            list($res, $res_date) = $this->CI->modelProposed->check_proposed($schedule_data['ID'], $temp_res_data, 0);
            $mode = ($res)? 1: 2;		// 日程決定:1、日程再調整:2
            $get_index = 1;
        }elseif(!empty($temp_res_data['start_datetime']) && empty($temp_res_data['end_datetime'])){
            // 開始日時○ ＆ 終了日時×
            list($res, $res_date) = $this->CI->modelProposed->check_proposed($schedule_data['ID'], $temp_res_data, 1);
            $mode = ($res)? 1: 2;		// 日程決定:1、日程再調整:2
            $get_index = 2;
        }elseif(empty($temp_res_data['start_datetime']) && !empty($temp_res_data['end_datetime'])){
            // 開始日時× ＆ 終了日時○
            list($res, $res_date) = $this->CI->modelProposed->check_proposed($schedule_data['ID'], $temp_res_data, 2);
            $mode = ($res)? 1: 2;		// 日程決定:1、日程再調整:2
            $get_index = 3;
        }else{
            list($res_db, $sch_db_data) = $this->CI->modelShedule->get_schedule_data_by_id($schedule_data['ID']);
            if($res_db && !empty($sch_db_data['RES_START_DATETIME']) && !empty($sch_db_data['RES_END_DATETIME'])){
                $mode = 1;
                $db_flg = true;
                $res_date['DATE_TIME_START'] = $sch_db_data['RES_START_DATETIME'];
                $res_date['DATE_TIME_END'] = $sch_db_data['RES_END_DATETIME'];
            }
        }

        if($mode === 1 && !$db_flg){
            $upd_data = array();
            $upd_data['res_start_datetime'] = $res_date['DATE_TIME_START'];
            $upd_data['res_end_datetime'] = $res_date['DATE_TIME_END'];
            $this->CI->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
        }

        // ミーティング場所の再提案処理
        // メール送信者がオーナーかゲストか判定
        $sender_flg = $this->get_sender($schedule_data['ID'], $from);

        if(!empty($temp_res_data["mtg_place"])){
            $sender = -1;
            if($sender_flg == 1){
                $sender = 0;    // 送信元がオーナーの場合、オーナーの希望場所をDBに登録
            }elseif($sender_flg == 2){
                $sender = 1;    // 送信元がゲストの場合、ゲストの希望場所をDBに登録
            }
            if($sender >= 0) $this->CI->modelHope_place->update_hope_place($db, $schedule_data['ID'], $temp_res_data["mtg_place"], $sender);
        }

        // 日程調整完了後に場所の再提案処理を開始
        $res_mail = "";
        if($mode === 1){
            // 双方の現状の希望場所を取得
            list($res_hope, $hope_data) = $this->CI->modelHope_place->get_hope_place($schedule_data['ID']);
            if(!empty($hope_data["GUEST_HOPE_PLACE"]) && $hope_data["OWNER_HOPE_PLACE"] != $hope_data["GUEST_HOPE_PLACE"]){
                $mode = 3;  // 場所の再調整へ
            }
        }

        if($mode === 1){    // 日程決定
            $tar_date_s = $res_date['DATE_TIME_START'];
            $tar_date_e = $res_date['DATE_TIME_END'];
            $s_datetime = new DateTime($res_date['DATE_TIME_START']);
            $e_datetime = new DateTime($res_date['DATE_TIME_END']);
            $answer = $s_datetime->format('Y/m/d H:i'). " - ". $e_datetime->format('Y/m/d H:i');
            $hope_data["RESULT"] = $hope_data["OWNER_HOPE_PLACE"];
        }elseif($mode === 2){   // 日程再調整
            list($tar_date_s, $tar_date_e) = $this->get_target_date($temp_res_data, $schedule_data, $get_index);
            // DB内のスケジュールデータ更新
            $upd_data['start_datetime'] = $tar_date_s;
            $upd_data['end_datetime'] = $tar_date_e;
            $this->CI->modelShedule->update_schedule($schedule_data["ID"], $upd_data);
        }

        return array($mode, $answer, $tar_date_s, $tar_date_e, $sender_flg, $hope_data);
    }

    public function get_info_by_fixed_personal($tar_info, $temp = array()){
        $res = (empty($temp))? array(): $temp;
        // メッセージ本文を1行毎に分割
        $tar_str_array = explode("\r\n", $tar_info);
        // 各情報を取得
        foreach($tar_str_array as $key => $value){
            if(!empty($value)){
                if((empty($temp['ask_type']) || $temp['ask_type'] == 9) && (mb_strpos($value, "【依頼内容】:") !== false)){
                    $buf_ask_type = trim(mb_substr($value, mb_strpos($value, "【依頼内容】:") + mb_strlen("【依頼内容】:")));
                    //【処理内容】→0(0:新規登録、1:変更、2:キャンセル、3:確認、9:エラー)
                    if(mb_strpos($buf_ask_type, "確認") !== false){
                        $res['ask_type'] = 3;
                    }elseif(mb_strpos($buf_ask_type, "登録") !== false){
                        $res['ask_type'] = 0;
                    }elseif(mb_strpos($buf_ask_type, "変更") !== false){
                        $res['ask_type'] = 1;
                    }elseif(mb_strpos($buf_ask_type, "削除") !== false){
                        $res['ask_type'] = 2;
                    }else{
                        $res['ask_type'] = 9;
                    }
                }
                if((empty($temp["start_datetime"])) && (mb_strpos($value, "【開始日時】:") !== false)){
                    $res["start_datetime"] = trim(mb_substr($value, mb_strpos($value, "【開始日時】:") + mb_strlen("【開始日時】:")));
                }
                if((empty($temp["title"])) && (mb_strpos($value, "【予定タイトル】：") !== false)){
                    $res["title"] = trim(mb_substr($value, mb_strpos($value, "【予定タイトル】：") + mb_strlen("【予定タイトル】：")));
                }
                if((empty($temp["description"])) && (mb_strpos($value, "【概要】：") !== false)){
                    $res["description"] = trim(mb_substr($value, mb_strpos($value, "【概要】：") + mb_strlen("【概要】：")));
                }
                if((empty($temp["end_datetime"])) && (mb_strpos($value, "【終了日時】:") !== false)){
                    $res["end_datetime"] = trim(mb_substr($value, mb_strpos($value, "【終了日時】:") + mb_strlen("【終了日時】:")));
                }
                if((empty($temp["upd_start_datetime"])) && (mb_strpos($value, "【開始日時-変更後】:") !== false)){
                    $res["upd_start_datetime"] = trim(mb_substr($value, mb_strpos($value, "【開始日時-変更後】:") + mb_strlen("【開始日時-変更後】:")));
                }
                if((empty($temp["upd_end_datetime"])) && (mb_strpos($value, "【終了日時-変更後】:") !== false)){
                    $res["upd_end_datetime"] = trim(mb_substr($value, mb_strpos($value, "【終了日時-変更後】:") + mb_strlen("【終了日時-変更後】:")));
                }
                if((empty($temp["required"])) && (mb_strpos($value, "【所要時間】:") !== false)){
                    $res["required"] = trim(mb_substr($value, mb_strpos($value, "【所要時間】:") + mb_strlen("【所要時間】:")));
                }
                if((empty($temp["guest_name"])) && (mb_strpos($value, "【MTG相手_名前】:") !== false)){
                    $res["guest_name"] = mb_substr($value, mb_strpos($value, "【MTG相手_名前】:") + mb_strlen("【MTG相手_名前】:"));
                }
                if((empty($temp["guest_mail"])) && (mb_strpos($value, "【MTG相手_メールアドレス】:") !== false)){
                    $res["guest_mail"] = mb_substr($value, mb_strpos($value, "【MTG相手_メールアドレス】:") + mb_strlen("【MTG相手_メールアドレス】:"));
                }
                if((empty($temp["mtg_type"])) && (mb_strpos($value, "【MTG形式】:") !== false)){
                    $res["mtg_type"] = mb_substr($value, mb_strpos($value, "【MTG形式】:") + mb_strlen("【MTG形式】:"));
                }
                if((empty($temp["mtg_place"])) && (mb_strpos($value, "【MTG場所】:") !== false)){
                    $res["mtg_place"] = mb_substr($value, mb_strpos($value, "【MTG場所】:") + mb_strlen("【MTG場所】:"));
                }
                if((empty($temp["mtg_tel_no"])) && (mb_strpos($value, "【電話番号】:") !== false)){
                    $res["mtg_tel_no"] = mb_substr($value, mb_strpos($value, "【電話番号】:") + mb_strlen("【電話番号】:"));
                }
                if((empty($temp["mtg_skype_id"])) && (mb_strpos($value, "【SKYPE ID】:") !== false)){
                    $res["mtg_skype_id"] = mb_substr($value, mb_strpos($value, "【SKYPE ID】:") + mb_strlen("【SKYPE ID】:"));
                }
            }
        }

        return $res;
    }

    public function analysis_mail_body_personal($emp_data, $tar_mail_body){
        $res = false;
        $answer ="";

        $temp_res_data = array();

        // 自由分処理

        // 自由文処理で取得不可で固定値で取得可能な場合は下記処理で各内容を取得
        // チェック対象データをそれぞれ取得
        $temp_res_data = $this->get_info_by_fixed_personal($tar_mail_body);

        $cnt_flg = false;
        if(!empty($temp_res_data['start_datetime']) && empty($temp_res_data['end_datetime'])){
            $buf = new datetime($temp_res_data['start_datetime']);
            $temp_res_data['start_datetime'] = $buf->format("Y/m/d H:i");
            $temp_res_data['end_datetime'] = $buf->format("Y/m/d 23:59");
        }elseif(empty($temp_res_data['start_datetime']) && !empty($temp_res_data['end_datetime'])){
            $buf = new datetime($temp_res_data['end_datetime']);
            $temp_res_data['start_datetime'] = $buf->format("Y/m/d 00:00");
            $temp_res_data['end_datetime'] = $buf->format("Y/m/d H:i");
        }elseif(empty($temp_res_data['start_datetime']) && empty($temp_res_data['end_datetime'])){
            // 開始日時＆終了日時が共に取得不可の場合
            if(!empty($temp_res_data["guest_name"]) || !empty($temp_res_data["guest_mail"])){
                // ゲスト名かゲストメールアドレスが取得可能な場合
                $buf_s = new datetime();
                $buf_e = new datetime('+1 years');
                $temp_res_data['start_datetime'] = $buf_s->format("Y/m/d 00:00");
                $temp_res_data['end_datetime'] = $buf_e->format("Y/m/d 23:59");
                $cnt_flg = true;
            }else{
                // 開始日時＆終了日時＆ゲスト名かゲストメールアドレスが全て取得不可の場合は当日の00:00～23:59を指定
                $buf = new datetime();
                $temp_res_data['start_datetime'] = $buf->format("Y/m/d 00:00");
                $temp_res_data['end_datetime'] = $buf->format("Y/m/d 23:59");
            }
        }

        return $temp_res_data;
    }

    function search_google_schedule($calendar_id, $datetime_s, $datetime_e, $str_summary){
        $res = false;
        $res_data = array();

        // googleカレンダーから指定期間の予定を取得
        $search_s = date('Y/m/d H:i', strtotime("-1 day", strtotime($datetime_s)));
        $search_e = date('Y/m/d H:i', strtotime("+1 day", strtotime($datetime_e)));
        $results = $this->CI->calender_lib->_get_range_schedule_by_google($calendar_id, $search_s, $search_e);

        if(count($results->getItems()) > 0){
            foreach($results->getItems() as $event){
                $g_s = strtotime($event->start->dateTime);
                $g_e = strtotime($event->end->dateTime);
                $t_s = strtotime($datetime_s);
                $t_e = strtotime($datetime_e);

                if(!empty($str_summary)){
                    if($g_s == $t_s && $g_e == $t_e && mb_strpos($event->summary, $str_summary) !== false){
                        $res = true;
                        $res_data['summary'] = $event->summary;
                        $res_data['description'] = $event->description;
                        $res_data['event_id'] = $event->getId();
                        break;
                    }
                }else{
                    if($g_s == $t_s && $g_e == $t_e){
                        $res = true;
                        $res_data['summary'] = $event->summary;
                        $res_data['description'] = $event->description;
                        $res_data['event_id'] = $event->getId();
                        break;
                    }
                }
            }
        }

        return array($res, $res_data);
    }

    // 個人処理 - 新規登録処理
    function personal_schedule_insert($emp_data, $analysis_data){
        $res_param = array();
        // 登録日程のチェック
        if(empty($analysis_data['start_datetime']) || empty($analysis_data['end_datetime'])){
            $res_param['template'] = 'template/mail/adjust/send_owner_04';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            return array(false, $res_param);
        }
        // googleカレンダーから指定期間の予定を取得
        list($res_search, $res_search_data) = $this->search_google_schedule($emp_data['GOOGLE_CALENDER_ID'], $analysis_data['start_datetime'], $analysis_data['end_datetime'], "");
        if($res_search){
            // 既存の予定あり→エラー
            $res_param['template'] = 'template/mail/adjust/send_owner_05';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            return array(false, $res_param);
        }
        // 各チェック問題なければ予定をgoogleカレンダーへ新規登録
        $summary = (!empty($analysis_data['title']))? '【個人予定】'.$analysis_data['title']: '【個人予定】by Kate';
        $s_datetime = $this->make_google_datetime($analysis_data['start_datetime']);
        $e_datetime = $this->make_google_datetime($analysis_data['end_datetime']);
        $timezone = 'Asia/Tokyo';
        $description = (!empty($analysis_data['description']))? $analysis_data['description']: '';
        $event_id = $this->CI->calender_lib->_set_schedule_to_google($emp_data['GOOGLE_CALENDER_ID'], $summary, $description, $s_datetime, $e_datetime, $time_zone);
        if(!empty($event_id)){
            $res_param['template'] = 'template/mail/adjust/send_owner_06';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            $res_param['title'] = $summary;
            $res_param['description'] = (!empty($description))? "概要：". $description. "\n": "";
            return array(true, $res_param);
        }else{
            // googleカレンダーへの登録エラー
            $res_param['template'] = 'template/mail/adjust/send_owner_07';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            return array(false, $res_param);
        }
    }

    // 個人処理 - 変更処理
    public function personal_schedule_update($emp_data, $analysis_data){
        $res_param = array();
        $guest_data = array();
        $res_mtg_param = '';
        $res_g_name_param = '';
        // 指定期間チェック
        if(empty($analysis_data['upd_start_datetime']) || empty($analysis_data['upd_end_datetime'])){
            // 変更後の日付エラー
            $res_param['template'] = 'template/mail/adjust/send_owner_08';
            $res_param['upd_start_datetime'] = $analysis_data['upd_start_datetime'];
            $res_param['upd_end_datetime'] = $analysis_data['upd_end_datetime'];
            return array(false, $res_param, $guest_data);
        }
        // googleカレンダーから指定期間の予定を取得
        list($res_search, $res_search_data) = $this->search_google_schedule($emp_data['GOOGLE_CALENDER_ID'], $analysis_data['start_datetime'], $analysis_data['end_datetime'], "【MTG設定済み】");
        if(!$res_search || empty($res_search_data['event_id'])){
            // 変更対象データがない
            $res_param['template'] = 'template/mail/adjust/send_owner_09';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            return array(false, $res_param, $guest_data);
        }
        // 変更先に既存のスケジュールデータの存在チェック
        list($res_upd_search, $res_upd_search_data) = $this->search_google_schedule($emp_data['GOOGLE_CALENDER_ID'], $analysis_data['upd_start_datetime'], $analysis_data['upd_end_datetime'], "");
        if($res_upd_search){
            // 変更先に既にスケジュールデータが存在する → エラー
            $res_param['template'] = 'template/mail/adjust/send_owner_10';
            $res_param['upd_start_datetime'] = $analysis_data['upd_start_datetime'];
            $res_param['upd_end_datetime'] = $analysis_data['upd_end_datetime'];
            return array(false, $res_param, $guest_data);
        }
        // Googleカレンダー情報を更新する
        $s_datetime = $this->make_google_datetime($analysis_data['upd_start_datetime']);
        $e_datetime = $this->make_google_datetime($analysis_data['upd_end_datetime']);
        $timezone = 'Asia/Tokyo';
        $this->CI->calender_lib->_update_schedule_to_google($emp_data['GOOGLE_CALENDER_ID'], $res_search_data['event_id'], $res_search_data['summary'], $res_search_data['description'], $s_datetime, $e_datetime, $timezone);
        // DB内に対象データが存在した場合に内容を更新し、ゲスト情報を取得する
        list($res_sch, $schedule) = $this->CI->modelShedule->get_schedule_data_by_eventid($res_search_data['event_id']);
        if($res_sch){
            // 関連するゲスト情報を取得
            $guest_data['name'] = $schedule['GUEST_NAME'];
            $guest_data['mail'] = $schedule['GUEST_MAIL'];
            $res_mtg_param = (!empty($schedule['MTG_PLACE']))? "場所：". $schedule['MTG_PLACE']: "場所：指定されていません。";
            $res_g_name_param = (!empty($schedule['GUEST_NAME']))? "相手：". $schedule['GUEST_NAME']. "様": "相手：指定されていません。";

            $upd_schedule_data = array();
            $upd_schedule_data['res_start_datetime'] = $analysis_data['upd_start_datetime'];
            $upd_schedule_data['res_end_datetime'] = $analysis_data['upd_end_datetime'];
            $upd_schedule_data['required'] = $analysis_data['required'];
            $this->CI->modelShedule->update_schedule($schedule['ID'], $upd_schedule_data);
        }

        $res_param['owner'] = array(
            'template'           => 'template/mail/adjust/send_owner_11',
            'start_datetime'     => $analysis_data['start_datetime'],
            'end_datetime'       => $analysis_data['end_datetime'],
            'mtg_place'          => $res_mtg_param,
            'guest_name'         => $res_g_name_param,
            'upd_start_datetime' => $analysis_data['upd_start_datetime'],
            'upd_end_datetime'   => $analysis_data['upd_end_datetime'],
            'upd_mtg_place'      => $res_mtg_param,
            'upd_guest_name'     => $res_g_name_param,
        );

        if($res_sch && !empty($schedule['guest_name']) && !empty($schedule['guest_mail'])){
            $res_param['guest'] = array(
                'template'           => 'template/mail/adjust/send_user_06',
                'start_datetime'     => $analysis_data['start_datetime'],
                'end_datetime'       => $analysis_data['end_datetime'],
                'mtg_place'          => $res_mtg_param,
                'upd_start_datetime' => $analysis_data['upd_start_datetime'],
                'upd_end_datetime'   => $analysis_data['upd_end_datetime'],
                'upd_mtg_place'      => $res_mtg_param,
            );
        }

        return array(true, $guest_data, $res_param);
    }

    // 個人処理 - キャンセル処理
    public function personal_schedule_cancel($emp_data, $analysis_data){
        $res_search = false;
        $event_id = "";
        $res_param = array();
        $guest_data = array();

        // googleカレンダーから指定期間の予定を取得
        $search_s = date('Y/m/d H:i', strtotime("-1 day", strtotime($analysis_data['start_datetime'])));
        $search_e = date('Y/m/d H:i', strtotime("+1 day", strtotime($analysis_data['end_datetime'])));
        $results = $this->CI->calender_lib->_get_range_schedule_by_google($emp_data['GOOGLE_CALENDER_ID'], $search_s, $search_e);
        if(count($results->getItems()) <= 0){
            // キャンセル対象のスケジュールデータが存在しない場合
            $res_param['template'] = 'template/mail/adjust/send_owner_12';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            return array(false, $guest_data, $res_param);
        }
        foreach($results->getItems() as $event){
            $g_s = strtotime($event->start->dateTime);
            $g_e = strtotime($event->end->dateTime);
            $t_s = strtotime($analysis_data['start_datetime']);
            $t_e = strtotime($analysis_data['end_datetime']);
            if($g_s == $t_s && $g_e == $t_e){
                $res_search = true;
                $event_id = $event->getId();
                break;
            }
        }
        if(!$res_search || empty($event_id)){
            // キャンセル対象のスケジュールデータが存在しない場合
            $res_param['template'] = 'template/mail/adjust/send_owner_12';
            $res_param['start_datetime'] = $analysis_data['start_datetime'];
            $res_param['end_datetime'] = $analysis_data['end_datetime'];
            return array(false, $guest_data, $res_param);
        }

        // $event_idを基にgoogleカレンダーから削除
        $this->CI->calender_lib->_delete_schedule_to_google($emp_data['GOOGLE_CALENDER_ID'], $event_id);
        // $event_idを基にDB更新(schedule_settingのstatusを9に)→戻り値にスケジュールID取得
        list($res_sch, $schedule) = $this->CI->modelShedule->get_schedule_data_by_eventid($event_id);
        if(!$res_sch){
            // スケジュールテーブルから直接データ取得不可の場合は候補日テーブルを経由してデータ取得
            list($res_prop_temp, $proposed_temp) = $this->CI->modelProposed->get_proposed_by_eventid($event_id);
            if($res_prop_temp){
                list($res_sch, $schedule) = $this->CI->modelShedule->get_schedule_data_by_id($proposed_temp['SCHEDULE_ID']);
            }
        }
        if($res_sch){
            // 関連するゲスト情報を取得
            $guest_data['name'] = $schedule['GUEST_NAME'];
            $guest_data['mail'] = $schedule['GUEST_MAIL'];

            $upd_schedule_data = array();
            $upd_schedule_data['status'] = 9;
            $this->CI->modelShedule->update_schedule($schedule['ID'], $upd_schedule_data);
            // 関連する候補日データをキャンセル状態とする
            list($res_prop, $proposed) = $this->CI->modelProposed->get_proposed($schedule['ID']);
            if($res_prop){
                $upd_proposed_data = array();
                $upd_proposed_data['del_flg'] = "1";
                $this->CI->modelProposed->update_proposed($schedule['ID'], $upd_proposed_data);
                // 各候補日のイベントIDを基にgoogleカレンダーから削除
                foreach($proposed as $key => $val){
                    $this->CI->calender_lib->_delete_schedule_to_google($emp_data['GOOGLE_CALENDER_ID'], $val['EVENT_ID']);
                }
            }
        }

        $res_param['owner'] = array(
            'template'       => 'template/mail/adjust/send_owner_13',
            'start_datetime' => $analysis_data['start_datetime'],
            'end_datetime'   => $analysis_data['end_datetime'],
            'mtg_place'      => ($res_sch)? (!empty($schedule['MTG_PLACE']))? "場所：". $schedule['MTG_PLACE']: "場所：指定されていません。": "",
            'guest_name'     => ($res_sch)? (!empty($schedule['GUEST_NAME']))? "相手：". $schedule['GUEST_NAME']: "相手：指定されていません。": "",
            'guest_msg'      => ($res_sch && !empty($schedule['GUEST_NAME']))? $schedule['GUEST_NAME']. "様にもキャンセルの連絡をしておきます。": "",
        );
        if($res_sch && !empty($schedule['GUEST_NAME']) && !empty($schedule['GUEST_MAIL'])){
            $res_param['guest'] = array(
                'template'       => 'template/mail/adjust/send_user_07',
                'start_datetime' => $analysis_data['start_datetime'],
                'end_datetime'   => $analysis_data['end_datetime'],
                'mtg_place'      => (!empty($schedule['MTG_PLACE']))? "場所：". $schedule['MTG_PLACE']: "場所：指定されていません。",
            );
        }

        return array(true, $guest_data, $out_put);
    }

    // googleカレンダーからの予定について説明内からゲスト情報を取得
    public function get_guest_by_description($description){
        $res = "";
        $temp_array = explode("\r\n", $description);
        if(!empty($temp_array)){
            foreach($temp_array as $key => $val){
                if(!empty($val) && mb_strpos($val, "【申請先】")){
                    $res = trim(mb_substr($val, mb_strpos($val, "【申請先】") + mb_strlen("【申請先】")));
                    break;
                }
            }
            if(empty($res)) $res = "指定されていません。";
        }
        return $res;
    }

    // googleカレンダーから取得したスケジュール情報を基にスケジュール確認メールへ出力する形式を生成
    public function get_conf_answer_part($data){
        $res = "";
        $res .= "・". $data->summary. "\n";
        $res .= "日時：". date('Y/m/d H:i', strtotime($data->start->dateTime)). " - ". date('Y/m/d H:i', strtotime($data->end->dateTime)). "\n";
        $res .= "相手：". $this->get_guest_by_description($data->description). "\n";
        $res .= "場所：";
        $res .= (!empty($data->location))? $data->location: "指定されていません。";
        $res .= "\n\n";
        return $res;
    }

    // googleカレンダーから指定条件のスケジュールを取得し、メール出力内容を生成する
    public function personal_schedule_conf($emp_data, $analysis_data){
        $res = false;
        $answer = "";
        $answer_temp = "";
        $answer_cnt = 0;

        // ゲスト情報が取得可能な場合は取得
        $guest = "";
        if(!empty($analysis_data["guest_name"])){
            $guest = $analysis_data["guest_name"];
        }elseif(!empty($analysis_data["guest_mail"])){
            $guest = $analysis_data["guest_mail"];
        }

        // googleカレンダーから指定期間の予定を取得
        $results = $this->CI->calender_lib->_get_range_schedule_by_google($emp_data['GOOGLE_CALENDER_ID'], $analysis_data['start_datetime'], $analysis_data['end_datetime']);
        if(count($results->getItems()) > 0){
            foreach($results->getItems() as $event){
                if(!empty($guest)){
                    // ゲスト情報が取得可能な場合、ゲストに関するスケジュールデータに絞る
                    if(mb_strpos($event->description, $guest)){
                        $answer_temp .= $this->get_conf_answer_part($event);
                        $answer_cnt++;
                    }
                }else{
                    // ゲスト情報が取得不可の場合、指定期間内のスケジュールデータを最大5件出力(※設定できるようにする)
                    $answer_temp .= $this->get_conf_answer_part($event);
                    $answer_cnt++;
                }
                if($answer_cnt >= 5) break;
            }
        }

        if(!empty($guest)){
            if($answer_cnt > 0){
                $res = true;
                $answer = $emp_data['NAME1']. "さんの ". $analysis_data['start_datetime']. " - ". $analysis_data['end_datetime']. " の期間での". $guest. "様との今後の予定は下記になります。\n\n". $answer_temp;
            }else{
                $answer = $emp_data['NAME1']. "さんの ". $analysis_data['start_datetime']. " - ". $analysis_data['end_datetime']. " の期間での". $guest. "様との今後の予定はありません。\n\n";
            }
        }else{
            if($answer_cnt > 0){
                $res = true;
                $answer = $emp_data['NAME1']. "さんの ". $analysis_data['start_datetime']. " - ". $analysis_data['end_datetime']. "の予定は下記になります。\n\n". $answer_temp;
            }else{
                $answer = $emp_data['NAME1']. "さんの ". $analysis_data['start_datetime']. " - ". $analysis_data['end_datetime']. "の予定はありません。\n\n";
            }
        }

        return array($res, $answer);
    }

    // ユーザーへメール送信
    public function _user_sendMail($info)
    {
        $res = false;
        if(!empty($info)){
/*            $mailData = array(
                'name'          => $data['name'],
                'bot_name'      => $data['bot_name'],
                'schedule_data' => $data['schedule_data'],
            );
*/
/*            $res = $this->CI->my_mail->_my_sendmail('template/mail/adjust/send00',
                                                     $mailData,
                                                     $this->CI->config->item('adjust_from_admin_mail'),
                                                     $this->CI->config->item('adjust_from_admin_name'),
                                                     $data['mail'],
                                                     $data['subject']);
*/
            $res = $this->CI->my_mail->_my_sendmail($info['template_path'],
                                                    $info['body_data'],
                                                    $info['from_mail'],
                                                    $info['from_name'],
                                                    $info['to'],
                                                    $info['subject'],
                                                    $info['msg_id']);
        }
        return $res;
    }

}
