<?php
class Mail_history extends CI_Model
{
    protected $tableName = 'MAIL_HISTORY';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

/************************* ↓SELECT↓ ****************************/
    // 全メールデータ取得
    public function get_all_mail()
    {
        $query = $this->db->get($this->tableName);
        return $query->result();
    }
/************************* ↑SELECT↑ ****************************/
/************************* ↓INSERT↓ ****************************/
    // 会員データ登録
    public function insert_mail($data)
    {
        $res = array();
        if(!empty($data)){
            $this->db->set('SCHEDULE_ID', $data['schedule_id'], FALSE);
            $this->db->set('MSG_ID', $data['msg_id'], FALSE);
            if(!empty($data['from_name'])) $this->db->set('FROM_NAME', $data['from_name'], FALSE);
            $this->db->set('FROM_MAIL', $data['from_mail'], FALSE);
            if(!empty($data['to_name'])) $this->db->set('TO_NAME', $data['to_name'], FALSE);
            $this->db->set('TO_MAIL', $data['to_mail'], FALSE);
            if(!empty($data['cc_name'])) $this->db->set('CC_NAME', $data['cc_name'], FALSE);
            if(!empty($data['cc_mail'])) $this->db->set('CC_MAIL', $data['cc_mail'], FALSE);
            if(!empty($data['subject'])) $this->db->set('SUBJECT', $data['subject'], FALSE);
            $this->db->set('MAIL_TEXT', $data['mail_text'], FALSE);
            $this->db->set('REG_DATE', 'NOW()', FALSE);
            $this->db->set('UPD_DATE', 'NOW()', FALSE);
            $this->db->set('DEL_FLG', '0', FALSE);

            $res = $this->db->insert($this->tableName);
            $insert_id = $this->db->insert_id();
        }
        return array($res, $insert_id);
    }
/************************* ↑INSERT↑ ****************************/
/************************* ↓UPDATE↓ ****************************/
/************************* ↑UPDATE↑ ****************************/
/************************* ↓DELETE↓ ****************************/
/************************* ↑DELETE↑ ****************************/
}
