<?php
class Shedule_setting extends CI_Model
{
    protected $tableName = 'SCHEDULE_SETTING';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

/************************* ↓SELECT↓ ****************************/
    // 全スケジュール調整データ取得
    public function get_all_shedule()
    {
        $query = $this->db->get($this->tableName);
        return $query->result();
    }

    // スケジュール調整データを取得(0→オーナー：1→ゲスト)
    public function get_schedule_setting_by_msgid($tar_msg_id, $tar_mode_flg){
        $resData = array();

        if($tar_mode_flg === 0){
            $where = array(
                        'OWNER_SEND_MSG_ID' => $tar_msg_id,
                        'DEL_FLG'  => 0,
                    );
        }else{
            $where = array(
                        'GUEST_SEND_MSG_ID' => $tar_msg_id,
                        'DEL_FLG'  => 0,
                    );
        }
        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where($where);
        $query = $this->db->get();
        $resDataTemp = $query->result('array');

        $res = false;
        if(!empty($resDataTemp)){
            $resData = $resDataTemp[0];
            $res = true;
        }

        return array($res, $resData);
    }

    // id を基にスケジュール調整データを取得
    public function get_schedule_data_by_id($tar_sch_id){
        $res = false;
        $resData = array();

        $where = array(
                    'ID'      => $tar_sch_id,
                    'DEL_FLG' => 0,
                );

        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where($where);
        $query = $this->db->get();
        $resDataTemp = $query->result('array');

        if(!empty($resDataTemp)){
            $res = true;
            $resData = $resDataTemp[0];
        }

        return array($res, $resData);
    }

    // イベントidを基にスケジュールデータの有無をチェックし、データが存在する場合はスケジュールを返却
    public function get_schedule_data_by_eventid($event_id){
        $res = false;
        $resData = array();

        $where = array(
                    'GOOGLE_EVENT_ID' => $event_id,
                    'DEL_FLG'         => 0,
                );

        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where($where);
        $query = $this->db->get();
        $resDataTemp = $query->result('array');

        if(!empty($resDataTemp)){
            $res = true;
            $resData = $resDataTemp[0];
        }

        return array($res, $resData);
    }

    // スケジュールデータの有無をチェックし、データが存在する場合はidを返却
    public function get_schedule_data_by_email($schedule_id, $mail, $tar_mode_flg){
        $res = false;
        $res_data = array();

        $where = array(
                    'DEL_FLG' => 0,
                );

        if($tar_mode_flg === 0){
            $where['OWNER_MAIL'] = $mail;
        }else{
            $where['GUEST_MAIL'] = $mail;
        }

        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where($where);
        $query = $this->db->get();
        $resDataTemp = $query->result('array');

        if(!empty($resDataTemp)){
            $res = true;
            $resData = $resDataTemp[0];
        }

        return array($res, $resData);
    }
/************************* ↑SELECT↑ ****************************/
/************************* ↓INSERT↓ ****************************/
    // 会員データ登録
    public function set_new_schedule($schedule_data)
    {
        $res = array();
        if(!empty($data)){
            $insertData = array(
                            'OWNER_SEND_MSG_ID'  => (!empty($schedule_data['OWNER_SEND_MSG_ID']))? $schedule_data['OWNER_SEND_MSG_ID']:'',
                            'GUEST_SEND_MSG_ID'  => (!empty($schedule_data['GUEST_SEND_MSG_ID']))? $schedule_data['GUEST_SEND_MSG_ID']:'',
                            'OWNER_MAIL'         => (!empty($schedule_data['OWNER_MAIL']))? $schedule_data['OWNER_MAIL']:'',
                            'GUEST_MAIL'         => (!empty($schedule_data['GUEST_MAIL']))? $schedule_data['GUEST_MAIL']:'',
                            'GUEST_NAME'         => (!empty($schedule_data['GUEST_NAME']))? $schedule_data['GUEST_NAME']:'',
                            'START_DATETIME'     => (!empty($schedule_data['START_DATETIME']))? $schedule_data['START_DATETIME']:'',
                            'END_DATETIME'       => (!empty($schedule_data['END_DATETIME']))? $schedule_data['END_DATETIME']:'',
                            'RES_START_DATETIME' => (!empty($schedule_data['RES_START_DATETIME']))? $schedule_data['RES_START_DATETIME']:'',
                            'RES_END_DATETIME'   => (!empty($schedule_data['RES_END_DATETIME']))? $schedule_data['RES_END_DATETIME']:'',
                            'REQUIRED'           => (!empty($schedule_data['REQUIRED']))? $schedule_data['REQUIRED']:'',
                            'MTG_TYPE'           => (!empty($schedule_data['MTG_TYPE']))? $schedule_data['MTG_TYPE']:'',
                            'MTG_PLACE'          => (!empty($schedule_data['MTG_PLACE']))? $schedule_data['MTG_PLACE']:'',
                            'MTG_SKYPE_ID'       => (!empty($schedule_data['MTG_SKYPE_ID']))? $schedule_data['MTG_SKYPE_ID']:'',
                            'MTG_TEL_NO'         => (!empty($schedule_data['MTG_TEL_NO']))? $schedule_data['MTG_TEL_NO']:'',
                            'GOOGLE_EVENT_ID'    => (!empty($schedule_data['GOOGLE_EVENT_ID']))? $schedule_data['GOOGLE_EVENT_ID']:'',
                            'STATUS'             => (!empty($schedule_data['STATUS']))? $schedule_data['STATUS']:'',
                            'DEL_FLG'            => '0',
                          );
            // 配列内で now() しても何故か入らないので一旦↓これで
            $this->db->set('REG_DATE', 'NOW()', FALSE);
            $this->db->set('UPD_DATE', 'NOW()', FALSE);

            $res = $this->db->insert($this->tableName, $insertData);
            $insert_id = $this->db->insert_id();
        }
        return array($res, $insert_id);
    }
/************************* ↑INSERT↑ ****************************/
/************************* ↓UPDATE↓ ****************************/
    // スケジュールデータ更新
    public function update_schedule($target_id, $target_data)
    {
        $res = array();
        if(!empty($target_id) && !empty($target_data)){
            if(!empty($target_data['owner_send_msg_id'])) $this->db->set('OWNER_SEND_MSG_ID', $target_data['owner_send_msg_id'], FALSE);
            if(!empty($target_data['guest_send_msg_id'])) $this->db->set('GUEST_SEND_MSG_ID', $target_data['guest_send_msg_id'], FALSE);
            if(!empty($target_data['owner_mail'])) $this->db->set('OWNER_MAIL', $target_data['owner_mail'], FALSE);
            if(!empty($target_data['guest_mail'])) $this->db->set('GUEST_MAIL', $target_data['guest_mail'], FALSE);
            if(!empty($target_data['guest_name'])) $this->db->set('GUEST_NAME', $target_data['guest_name'], FALSE);
            if(!empty($target_data['start_datetime'])) $this->db->set('START_DATETIME', $target_data['start_datetime'], FALSE);
            if(!empty($target_data['end_datetime'])) $this->db->set('END_DATETIME', $target_data['end_datetime'], FALSE);
            if(!empty($target_data['res_start_datetime'])) $this->db->set('RES_START_DATETIME', $target_data['res_start_datetime'], FALSE);
            if(!empty($target_data['res_end_datetime'])) $this->db->set('RES_END_DATETIME', $target_data['res_end_datetime'], FALSE);
            if(!empty($target_data['required'])) $this->db->set('REQUIRED', $target_data['required'], FALSE);
            if(!empty($target_data['mtg_type'])) $this->db->set('MTG_TYPE', $target_data['mtg_type'], FALSE);
            if(!empty($target_data['mtg_place'])) $this->db->set('MTG_PLACE', $target_data['mtg_place'], FALSE);
            if(!empty($target_data['mtg_skype_id'])) $this->db->set('MTG_SKYPE_ID', $target_data['mtg_skype_id'], FALSE);
            if(!empty($target_data['google_event_id'])) $this->db->set('GOOGLE_EVENT_ID', $target_data['google_event_id'], FALSE);
            if(!empty($target_data['status'])) $this->db->set('STATUS', $target_data['status'], FALSE);
            $this->db->set('UPD_DATE', 'NOW()', FALSE);
            $this->db->where('ID', $target_id);
            $this->db->where('DEL_FLG', '0');
            $res['res'] = $this->db->update($this->tableName);
        }
        return $res;
    }
/************************* ↑UPDATE↑ ****************************/
/************************* ↓DELETE↓ ****************************/
/************************* ↑DELETE↑ ****************************/
}
