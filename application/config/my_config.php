<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH. 'controllers/Setting/env.php';

$config['reg_user_to_admin_mail']     = $_SERVER['ADMIN_MAIL'];
$config['reg_user_from_admin_mail']   = $_SERVER['ADMIN_MAIL'];
$config['reg_user_from_admin_name']   = 'JobCoordinator';
$config['reg_user_subject_user_temp'] = 'JobCoordinatorの仮登録が完了しました。';
$config['reg_user_subject_user']      = 'JobCoordinatorの本登録が完了しました。';
$config['reg_user_subject_admin']     = 'JobCoordinator会員登録通知';

$config['adjust_from_admin_mail'] = 'test.kate.jobcoordinator@gmail.com';
$config['adjust_from_admin_name'] = 'JobCoordinator ケイト';
$config['adjust_mail_domain']     = '@h-matsuya.sakura.ne.jp';
$config['reply_mark'] = '>';
$config['approval_url'] = 'http://h-matsuya.sakura.ne.jp/JobCoordinator/member/approval_schedule';

$config['magic_count'] = $_SERVER['MAGIC_COUNT'];
$config['magic_code']  = $_SERVER['MAGIC_CODE'];
$config['magic_type']  = $_SERVER['MAGIC_TYPE'];

// session 名
$config['sess_entry']  = 'entry_data';   // 会員登録時
$config['sess_member'] = 'member_data';   // ログイン

$config['sex_list'] = array(
    1 => '男性',
    2 => '女性',
);

$config['pref_list'] = array(
     0 => '選択してください',
     1 => '北海道',
     2 => '青森県',
     3 => '岩手県',
     4 => '宮城県',
     5 => '秋田県',
     6 => '山形県',
     7 => '福島県',
     8 => '茨城県',
     9 => '栃木県',
    10 => '群馬県',
    11 => '埼玉県',
    12 => '千葉県',
    13 => '東京都',
    14 => '神奈川県',
    15 => '新潟県',
    16 => '富山県',
    17 => '石川県',
    18 => '福井県',
    19 => '山梨県',
    20 => '長野県',
    21 => '岐阜県',
    22 => '静岡県',
    23 => '愛知県',
    24 => '三重県',
    25 => '滋賀県',
    26 => '京都府',
    27 => '大阪府',
    28 => '兵庫県',
    29 => '奈良県',
    30 => '和歌山県',
    31 => '鳥取県',
    32 => '島根県',
    33 => '岡山県',
    34 => '広島県',
    35 => '山口県',
    36 => '徳島県',
    37 => '香川県',
    38 => '愛媛県',
    39 => '高知県',
    40 => '福岡県',
    41 => '佐賀県',
    42 => '長崎県',
    43 => '熊本県',
    44 => '大分県',
    45 => '宮崎県',
    46 => '鹿児島県',
    47 => '沖縄県',
);
