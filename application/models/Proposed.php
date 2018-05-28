<?php
class Proposed extends CI_Model
{
    protected $tableName = 'PROPOSED';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

/************************* ↓SELECT↓ ****************************/
    // 全メールデータ取得
    public function get_all_proposed()
    {
        $query = $this->db->get($this->tableName);
        return $query->result();
    }

    // スケジュールデータの有無をチェックし、データが存在する場合はidを返却
    public function check_proposed($s_id, $check_date, $mode){
        $res = false;
        $resData = array();

        $where = array(
                    'SCHEDULE_ID' => $s_id,
                    'DEL_FLG'     => 0,
                );

        switch($mode){
            case 0:
                $where['DATE_TIME_START'] = (!empty($check_date['start_datetime']))? $check_date['start_datetime']:'';
                $where['DATE_TIME_END'] = (!empty($check_date['end_datetime']))? $check_date['end_datetime']:'';
                break;
            case 1:
                $where['DATE_TIME_START'] = (!empty($check_date['start_datetime']))? $check_date['start_datetime']:'';
                break;
            case 2:
                $where['DATE_TIME_END'] = (!empty($check_date['end_datetime']))? $check_date['end_datetime']:'';
                break;
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

    // スケジュールデータの有無をチェックし、データが存在する場合はidを返却
    public function get_proposed($s_id){
        $res = false;
        $resData = array();

        $where = array(
                    'SCHEDULE_ID' => $s_id,
                    'DEL_FLG'     => 0,
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
    public function get_proposed_by_eventid($e_id){
        $res = false;
        $resData = array();

        $where = array(
                    'EVENT_ID' => $e_id,
                    'DEL_FLG'  => 0,
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
/************************* ↑SELECT↑ ****************************/
/************************* ↓INSERT↓ ****************************/
    // 会員データ登録
    public function insert_proposed($schedule_id, $choice_item)
    {
        $res = false;
        $insert_id = array();

        if(!empty($schedule_id) && !empty($choice_item)){
            foreach ($choice_item as $key => $value){
                $insertData = array(
                                'SCHEDULE_ID'     => $schedule_id,
                                'EVENT_ID'        => (!empty($value['event_id']))? $value['event_id']: '',
                                'DATE_TIME_START' => (!empty($value['start']))? $value['start']: '',
                                'DATE_TIME_END'   => (!empty($value['end']))? $value['end']: '',
                                'DEL_FLG'         => '0',
                              );
                // 配列内で now() しても何故か入らないので一旦↓これで
                $this->db->set('REG_DATE', 'NOW()', FALSE);
                $this->db->set('UPD_DATE', 'NOW()', FALSE);

                $res_insert[] = $this->db->insert($this->tableName, $insertData);
                $insert_id[] = $this->db->insert_id();
            }
            if(!empty($insert_id)) $res = true;
        }
        return array($res, $insert_id);
    }
/************************* ↑INSERT↑ ****************************/
/************************* ↓UPDATE↓ ****************************/
// スケジュールデータ更新
public function update_proposed($target_id, $target_data){
    $res = false;
    if(!empty($target_id) && !empty($target_data)){
        if(!empty($target_data['schedule_id']))     $this->db->set('SCHEDULE_ID',     $target_data['schedule_id'],     FALSE);
        if(!empty($target_data['event_id']))        $this->db->set('EVENT_ID',        $target_data['event_id'],        FALSE);
        if(!empty($target_data['date_time_start'])) $this->db->set('DATE_TIME_START', $target_data['date_time_start'], FALSE);
        if(!empty($target_data['date_time_end']))   $this->db->set('DATE_TIME_END',   $target_data['date_time_end'],   FALSE);
        if(!empty($target_data['del_flg']))         $this->db->set('DEL_FLG',         $target_data['del_flg'],         FALSE);
        $this->db->set('UPD_DATE', 'NOW()', FALSE);
        $this->db->where('SCHEDULE_ID', $target_id);
        $this->db->where('DEL_FLG', '0');
        $res = $this->db->update($this->tableName);
    }
    return $res;
}
/************************* ↑UPDATE↑ ****************************/
/************************* ↓DELETE↓ ****************************/
/************************* ↑DELETE↑ ****************************/
}
