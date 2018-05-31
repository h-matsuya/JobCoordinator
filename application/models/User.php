<?php
class User extends CI_Model
{
    protected $tableName = 'USER';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

/************************* ↓SELECT↓ ****************************/
    // 全ユーザーデータ取得
    public function get_all_user()
    {
        $query = $this->db->get($this->tableName);
        return $query->result();
    }

    // 単体ユーザーデータ取得(ログインIDから)
    public function get_once_user($loginId)
    {
        $res = false;
        $resData = array();
        $where = array(
                    'LOGIN_ID' => $loginId,
                    'REG_STATUS >=' => 1,
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

    // 単体ユーザーデータ取得(メールアドレスから)
    public function get_once_user_by_mail($mail)
    {
        $res = false;
        $resData = array();
        $where = array(
                    'MAIL' => $mail,
                    'REG_STATUS >=' => 1,
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

    public function get_user_by_ukey($uKey)
    {
        $resData = array();
        $where = array(
                    'UNIQUE_KEY' => $uKey,
                    'REG_STATUS =' => 0,
                    'DEL_FLG'  => 0,
                );
        $this->db->select('*');
        $this->db->from($this->tableName);
        $this->db->where($where);
        $query = $this->db->get();
        $resDataTemp = $query->result('array');

        if(!empty($resDataTemp)){
            $resData = $resDataTemp[0];
        }

        return $resData;
    }

    public function get_max_user_id()
    {
        $this->db->select_max('ID');
        $query = $this->db->get($this->tableName);
        $res = $query->result();
        return $res[0]->ID;
    }
/************************* ↑SELECT↑ ****************************/
/************************* ↓INSERT↓ ****************************/
    // 会員データ登録
    public function insert_user_data($data)
    {
        $res = array();
        if(!empty($data)){
            $insertData = array(
                            'LOGIN_ID'   => $data['user_id'],
                            'PASSWORD'   => $data['hash_pass'],
                            'USER_TYPE'  => '0',
                            'NAME1'      => $data['name1'],
                            'NAME2'      => $data['name2'],
                            'NAME1_KANA' => $data['name1_kana'],
                            'NAME2_KANA' => $data['name2_kana'],
                            'SEX'        => $data['sex'],
                            'ZIP1'       => $data['zip1'],
                            'ZIP2'       => $data['zip2'],
                            'PREF'       => $data['pref'],
                            'ADDRESS1'   => $data['address1'],
                            'ADDRESS2'   => $data['address2'],
                            'TEL1'       => $data['tel1'],
                            'TEL2'       => $data['tel2'],
                            'TEL3'       => $data['tel3'],
                            'MAIL'       => $data['mail'],
                            'SALT'       => $data['salt'],
                            'STRETCH'    => $data['stretch'],
                            'UNIQUE_KEY' => $data['unique_key'],
                            'DEL_FLG'    => '0',
                          );
            // 配列内で now() しても何故か入らないので一旦↓これで
            $this->db->set('CREATE_DATETIME', 'NOW()', FALSE);
            $this->db->set('UPDATE_DATETIME', 'NOW()', FALSE);

            $res['res'] = $this->db->insert($this->tableName, $insertData);
            $res['insert_id'] = $this->db->insert_id();
        }
        return $res;
    }
/************************* ↑INSERT↑ ****************************/
/************************* ↓UPDATE↓ ****************************/
    // 会員本登録(フラグ更新)
    public function update_user_data($id)
    {
        $res = array();
        if(!empty($id)){
            $updateData = array(
                            'REG_STATUS' => '1',
                            'UNIQUE_KEY' => '',
                          );
            $this->db->set('UPDATE_DATETIME', 'NOW()', FALSE);
            $this->db->where('LOGIN_ID'  , $id);
            $this->db->where('REG_STATUS', '0');
            $this->db->where('DEL_FLG'   , '0');
            $res['res'] = $this->db->update($this->tableName, $updateData);
        }
        return $res;
    }
/************************* ↑UPDATE↑ ****************************/
/************************* ↓DELETE↓ ****************************/
/************************* ↑DELETE↑ ****************************/
}
