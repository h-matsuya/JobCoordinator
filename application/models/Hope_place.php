<?php
class Hope_place extends CI_Model
{
    protected $tableName = 'HOPE_PLACE';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

/************************* ↓SELECT↓ ****************************/
    // 全メールデータ取得
    public function get_all_hope_place()
    {
        $query = $this->db->get($this->tableName);
        return $query->result();
    }

    // スケジュールIDを基にオーナー、ゲストの希望場所をDBから取得
    public function get_hope_place($schedule_id){
        $res = false;
        $resData = array();

        $where = array(
                    'SCHEDULE_ID' => $tar_sch_id,
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
/************************* ↑SELECT↑ ****************************/
/************************* ↓INSERT↓ ****************************/
    // 会員データ登録
    public function insert_hope_place($schedule_id, $hope_item)
    {
        $res = false;

        if(!empty($schedule_id) && !empty($hope_item)){
                $insertData = array(
                                'SCHEDULE_ID'      => $schedule_id,
                                'OWNER_HOPE_PLACE' => (!empty($hope_item['owner']))? $hope_item['owner']: '',
                                'GUEST_HOPE_PLACE' => (!empty($hope_item['guest']))? $hope_item['guest']: '',
                                'DEL_FLG'          => '0',
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
// 希望場所を更新
function update_hope_place($schedule_id, $target_place, $tar_mode_flg){
    $res = array();
    if(!empty($schedule_id) && !empty($target_place)){
        $updateData = array();
        if($tar_mode_flg === 0){
            $updateData['OWNER_HOPE_PLACE'] = $target_place;
        }else{
            $updateData['GUEST_HOPE_PLACE'] = $target_place;
        }
        $this->db->set('UPD_DATE', 'NOW()', FALSE);
        $this->db->where('SCHEDULE_ID'  , $schedule_id);
        $this->db->where('DEL_FLG'   , '0');
        $res['res'] = $this->db->update($this->tableName, $updateData);
    }
    return $res;
}
/************************* ↑UPDATE↑ ****************************/
/************************* ↓DELETE↓ ****************************/
/************************* ↑DELETE↑ ****************************/
}
