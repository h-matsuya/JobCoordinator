<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once __DIR__ . '/vendor/autoload.php';

class Calender_lib {

    protected $CI;

    protected $appName = 'JobCoordinator';
    protected $credentialsPath = __DIR__ . '/calendar-php-quickstart.json';
    protected $clientSecretPath = __DIR__ . '/client_secret.json';
    protected $scopes = '';

    public function __construct()
    {
        $this->scopes = implode(' ', array(Google_Service_Calendar::CALENDAR));
        $this->CI =& get_instance();
        date_default_timezone_set('Asia/Tokyo');
    }

    /**
     * Returns an authorized API client.
     * @return Google_Client the authorized client object
     */
    public function getClient()
    {
        $client = new Google_Client();
        $client->setApplicationName($this->appName);
        $client->setScopes($this->scopes);
        $client->setAuthConfig($this->clientSecretPath);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory($this->credentialsPath);
        if (file_exists($credentialsPath)) {
            $accessToken = json_decode(file_get_contents($credentialsPath), true);
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

            // Store the credentials to disk.
            if(!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, json_encode($accessToken));
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    public function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

    public function _get_schedule()
    {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Calendar($client);

        // Print the next 10 events on the user's calendar.
//        $calendarId = 'primary';
        $calendarId = '2tm8362ftut5lnpka6ia1kqi9k@group.calendar.google.com';
        $optParams = array(
            'maxResults' => 10,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c'),
        );

        $resData = array();
        $results = $service->events->listEvents($calendarId, $optParams);
        if (count($results->getItems()) != 0) {
            foreach ($results->getItems() as $event) {
                $start = $event->start->dateTime;
                if (empty($start)) {
                    $start = $event->start->date;
                }
                $resData[] = array(
                    'start'   => $start,
                    'summary' => $event->getSummary(),
                );
            }
        }
        return $resData;
    }





    // googleカレンダー登録用日時データ生成
    function make_google_datetime($datetime){
        $y = date('Y', strtotime($datetime));
        $m = date('m', strtotime($datetime));
        $d = date('d', strtotime($datetime));
        $h = date('H', strtotime($datetime));
        $s = date('i', strtotime($datetime));
        return date(DATE_ATOM, mktime($h, $s, 0, $m, $d, $y));
    }

    public function _get_oneday_schedule_by_google($calendarId, $target_date_y, $target_date_m, $target_date_d)
    {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Calendar($client);

        // Print the next 100 events on the user's calendar.
        // データ取得範囲を決定する
        $t = mktime(0, 0, 0, $target_date_m, $target_date_d, $target_date_y);
        $t2 = mktime(23, 59, 59, $target_date_m, $target_date_d, $target_date_y);
        $optParams = array(
            'maxResults' => 100,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c', $t),
            'timeMax' => date('c', $t2),
        );

        $resData = array();
        return $service->events->listEvents($calendarId, $optParams);
    }

    public function _get_range_schedule_by_google($calendarId, $target_date_s, $target_date_e)
    {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Calendar($client);

        // Print the next 100 events on the user's calendar.
        // データ取得範囲を決定する
        $t = mktime(date("H", strtotime($tar_datetime_s)), date("i", strtotime($tar_datetime_s)), 0, date("m", strtotime($tar_datetime_s)), date("d", strtotime($tar_datetime_s)), date("Y", strtotime($tar_datetime_s)));
        $t2 = mktime(date("H", strtotime($tar_datetime_e)), date("i", strtotime($tar_datetime_e)), 59, date("m", strtotime($tar_datetime_e)), date("d", strtotime($tar_datetime_e)), date("Y", strtotime($tar_datetime_e)));
        $optParams = array(
            'maxResults' => 100,
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c', $t),
            'timeMax' => date('c', $t2),
        );

        $resData = array();
        return $service->events->listEvents($calendarId, $optParams);
    }

    public function _set_schedule_to_google($calendarId, $summary, $description, $start_datetime, $end_datetime, $time_zone)
    {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Calendar($client);

        // カレンダーに登録する内容をセット
        $optParams = new Google_Service_Calendar_Event(array(
                'summary' => $summary,//予定のタイトル
                'description' => $description,
                'start' => array(
                        'dateTime' => $start_datetime,// 開始日時
                        'timeZone' => $time_zone,
                ),
                'end' => array(
                        'dateTime' => $end_datetime, // 終了日時
                        'timeZone' => $time_zone,
                ),
        ));

        $event = $service->events->insert($calendarId, $optParams);
        return $event->getId();
    }

    public function _update_schedule_to_google($calendarId, $event_id, $summary, $description, $start_datetime, $end_datetime, $time_zone){
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Calendar($client);

        // カレンダーに登録する内容をセット
        $optParams = new Google_Service_Calendar_Event(array(
                'summary' => $summary,//予定のタイトル
                'description' => $description,
                'start' => array(
                        'dateTime' => $start_datetime,// 開始日時
                        'timeZone' => $time_zone,
                ),
                'end' => array(
                        'dateTime' => $end_datetime, // 終了日時
                        'timeZone' => $time_zone,
                ),
        ));

        $event = $service->events->update($calendarId, $event_id, $optParams);
        return $event->getId();
    }

    public function _delete_schedule_to_google($calendarId, $event_id){
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new Google_Service_Calendar($client);

        // 処理日から前後3ヶ月分のスケジュールデータを取得
        $limit_min_date = new DateTime('-3 months');
        $limit_max_date = new DateTime('+3 months');
        $t = mktime(0, 0, 0, $limit_min_date->format("n"), $limit_min_date->format("j"), $limit_min_date->format("Y"));
        $t2 = mktime(23, 59, 59, $limit_max_date->format("n"), $limit_max_date->format("j"), $limit_max_date->format("Y"));
        $optParams = array(
            'orderBy' => 'startTime',
            'singleEvents' => TRUE,
            'timeMin' => date('c', $t),
            'timeMax' => date('c', $t2),
        );

        // 指定されたイベントIDがGoogleカレンダー上に存在しているかチェック
        $events_list = $service->events->listEvents($calendarId, $optParams);
        $res_do = false;
        foreach($events_list->getItems() as $key => $val){
            if($event_id == $val->getId()){
                $res_do = true;
                break;
            }
        }

        // 指定のイベントIDがGoogleカレンダー上に存在する場合のみ削除実施
        if($res_do) $event = $service->events->delete($calendarId, $event_id);
    }

    public function set_schedule_app_req($emp_data, $tar_date_s, $tar_date_e, $to_apply){
        // googleカレンダーから指定期間の予定を取得
        $results = $this->_get_range_schedule_by_google($emp_data['GOOGLE_CALENDER_ID'], $tar_date_s, $tar_date_e);
        // 承認依頼対象イベントのイベントIDを取得
        $tar_event_id = "";
        foreach($results['items'] as $key => $value){
            if(mb_strpos($value['summary'], "【MTG候補】") !== false){
                $tar_event_id = $value->getId();
                break;
            }
        }

        $summary = '【MTG候補】承認待ち by Cate';
        $s_datetime = $this->make_google_datetime($tar_date_s);
        $e_datetime = $this->make_google_datetime($tar_date_e);
        $timezone = 'Asia/Tokyo';
        $description = '【申請先】'. $to_apply. ' 様';
        // googleカレンダーへ登録
        $tar_event_id = $this->_set_schedule_to_google($emp_data['GOOGLE_CALENDER_ID'], $summary, $description, $s_datetime, $e_datetime, $time_zone);
        return $tar_event_id;
    }

    // 指定されたイベントIDの候補日のスケジュールをgoogleカレンダーから削除
    function del_schedule_candidate_select_by_id($emp_data, $escape_data){
        if(!empty($escape_data)){
            foreach($escape_data as $key => $value){
                $this->_delete_schedule_to_google($emp_data['GOOGLE_CALENDER_ID'], $value['EVENT_ID']);
            }
        }
    }

}
