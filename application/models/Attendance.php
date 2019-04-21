<?php
class Attendance extends CI_Model
{

    // テーブルカラムを定義
    protected $id = null;
    protected $userUniquekey = null;
    protected $attenDanceDate = null;
    protected $checkinTime = null;
    protected $checkoutTime = null;
    protected $workingTime = null;
    protected $breakTime = null;
    protected $remarks = null;
    // 検索用の内部変数
    protected $attenDanceDateFrom = null;
    protected $attenDanceDateTo = null;

    /**
     * Attendance constructor.
     * @param array $param 入力パラメータ
     */
    public function __construct($param = array())
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     *
     * 操作対象の入力パラメータを内部変数に格納します
     *
     * @param $param 入力パラメータ
     */
    public function setParam($param)
    {
        foreach ($param as $key => $val) {
            // 入力データをクラス変数に格納
            $this->id = ($key === 'ID') ? $param['ID'] : $this->id;
            $this->userUniquekey = ($key === 'UNIQUE_KEY') ? $param['UNIQUE_KEY'] : $this->userUniquekey;
            $this->attenDanceDate = ($key === 'ATTENDANCE_DATE') ? $param['ATTENDANCE_DATE'] : $this->attenDanceDate;
            $this->attenDanceDateFrom = ($key === 'ATTENDANCE_DATE_FROM') ? $param['ATTENDANCE_DATE_FROM'] : $this->attenDanceDateFrom;
            $this->attenDanceDateTo = ($key === 'ATTENDANCE_DATE_TO') ? $param['ATTENDANCE_DATE_TO'] : $this->attenDanceDateTo;
            $this->checkinTime = ($key === 'CHECKIN_TIME') ? $param['CHECKIN_TIME'] : $this->checkinTime;
            $this->checkoutTime = ($key === 'CHECKOUT_TIME') ? $param['CHECKOUT_TIME'] : $this->checkoutTime;
            $this->workingTime = ($key === 'WORKING_TIME') ? $param['WORKING_TIME'] : $this->workingTime;
            $this->breakTime = ($key === 'BREAK_TIME') ? $param['BREAK_TIME'] : $this->breakTime;
            $this->remarks = ($key === 'REMARKS') ? $param['REMARKS'] : $this->remarks;
        }
    }

    /**
     *
     * 内部で保持している属性情報を全てクリアします。
     *
     */
    public function clear_all_propaty()
    {
        $this->id = null;
        $this->userUniquekey = null;
        $this->attenDanceDate = null;
        $this->attenDanceDateFrom = null;
        $this->attenDanceDateTo = null;
        $this->checkinTime = null;
        $this->checkoutTime = null;
        $this->workingTime = null;
        $this->breakTime = null;
        $this->remarks = null;
    }

    /**
     * すべての出退勤情報を取得します
     * @return mixed
     */
    public function get_all_attendance()
    {
        $query = $this->db->get('ATTENDANCE');
        return $query->result();
    }

    /**
     *
     * 内部プロパティを基に出退勤情報の先頭を返却します
     *
     * @return array
     */
    public function get_once_user_attendance()
    {
        $resData = array();
        $resDataTemp = $this->_get();
        if(!empty($resDataTemp)){
            $resData = $resDataTemp[0];
        }

        return $resData;
    }

    /**
     *
     * 内部プロパティを基に出退勤情報を取得します
     *
     * @return array
     */
    public function get_once_user_attendances()
    {
        $resData = array();
        $resDataTemp = $this->_get();
        if(!empty($resDataTemp)){
            $resData = $resDataTemp;
        }

        return $resData;
    }

    /**
     *
     * 内部プロパティに保存した情報を基に
     * 出退勤情報を配列で返却します。
     *
     * @return array
     */
    private function _get()
    {
        $where = array(
            'UNIQUE_KEY' => $this->userUniquekey,
        );
        if (!empty($this->attenDanceDateFrom)) {
            $this->db->where('ATTENDANCE_DATE >=', $this->attenDanceDateFrom);
        }
        if (!empty($this->attenDanceDateTo)) {
            $this->db->where('ATTENDANCE_DATE <=', $this->attenDanceDateTo);
        }
        if (!empty($this->attenDanceDate) && empty($this->attenDanceDateFrom) && empty($this->attenDanceDateTo)) {
            $where['ATTENDANCE_DATE'] = $this->attenDanceDate;
        }
        $this->db->select('*');
        $this->db->from('ATTENDANCE');
        $this->db->where($where);
        $query = $this->db->get();
        $resDataTemp = $query->result('array');

        return $resDataTemp;
    }


    /**
     * 内部プロパティに保存した情報を基に
     * 情報を登録します。
     * 保存時に取得したPKを内部プロパティに保存します。
     *
     * @return array
     */
    public function insert()
    {
        $res = array();
        $insertData = array(
            'UNIQUE_KEY'      => $this->userUniquekey,
            'ATTENDANCE_DATE' => $this->attenDanceDate,
            'CHECKIN_TIME'    => $this->checkinTime,
            'CHECKOUT_TIME'   => $this->checkoutTime,
        );

        $res['res'] = $this->db->insert('ATTENDANCE', $insertData);
        $this->id = $this->db->insert_id();

        return $res;
    }

    /**
     *
     * PKを基に内部プロパティに保存された情報に置き換えます。
     *
     * 勤務時刻が未設定かつ出勤時刻と退勤打刻が存在した場合は自動で勤務時刻を補完します。
     * (退社時刻 - 出社時刻) 休憩時間は加味しない
     * 
     * @return array
     */
    public function update()
    {
        $res = array();
        if(!empty($this->id)){
            $updateData = array(
                            'CHECKIN_TIME'  => $this->checkinTime,
                            'CHECKOUT_TIME' => $this->checkoutTime,
                            'BREAK_TIME'    => $this->breakTime,
                            'REMARKS'       => $this->remarks,
                          );
            if ($this->workingTime == null && $this->checkinTime != null && $this->checkoutTime != null) {
                $this->workingTime = $this->my_date->_get_diff_time($this->checkinTime, $this->checkoutTime);
                $updateData['WORKING_TIME'] = $this->workingTime;
            }
            $this->db->where('ID'  , $this->id);
            $res['res'] = $this->db->update('ATTENDANCE', $updateData);
        }
        return $res;
    }


}
