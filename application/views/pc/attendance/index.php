<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
 
<div id="container">
    <article>
        <h1>出退勤管理ページ</h1>

        <div id="body">
            <p>Attendance調整成功！</p>
            <p><?php echo $result['user']['NAME1'] . " " . $result['user']['NAME2'] . " さん" ?></p>
        </div>
        <form method="post" action="/JobCoordinator/attendance/input">
            <ul style="list-style:none;">
                <li>
                    <span>ログインID : <?php echo $result['user']['LOGIN_ID'] ?></span>
                </li>
                <li>
                    <?php if (isset($result['todayAttend'][$this->my_date->_get_now_date()]['CHECKIN_TIME'])) { ?>
                        <button type='submit' class="btn btn-default" name='check' value='1' disabled="disabled">出勤</button>
                    <?php }else{ ?>
                        <button type='submit' class="btn btn-default" name='check' value='1'>出勤</button>
                    <?php } ?>
                    <?php if (isset($result['todayAttend'][$this->my_date->_get_now_date()]['CHECKIN_TIME']) && !isset($result['todayAttend'][$this->my_date->_get_now_date()]['CHECKOUT_TIME'])) { ?>
                        <button type='submit' class="btn btn-default" name='check' value='2'>退勤</button>
                    <?php }else{ ?>
                        <button type='submit' class="btn btn-default" name='check' value='2' disabled="disabled">退勤</button>
                    <?php } ?>
                </li>
            </ul>
            <span style="color :red;"> [!!MEMO!!]<br>出社：打刻時刻は15分単位で切り上げられます<br>退社：打刻した時刻は15分単位で切り捨てられます</span>
            <input type="hidden" name="<?=$csrf['name'];?>" value="<?=$csrf['hash'];?>" />
            <input type="hidden" name="date_start" value="<?php echo $result['attendanceFrom'] ?>">
            <input type="hidden" name="date_end" value="<?php echo $result['attendanceTo'] ?>">
        </form>

        <!-- Attendance History -->
        <hr>
        <section>
            <div class="form-inline">
                <label style="display:inline-block;">勤怠一覧</label>
                <!-- 1.モーダル表示のためのボタン -->
                <button data-toggle="modal" data-target="#modal-example">
                    勤務表を提出する
                </button>
            </div>

            <!-- filter -->
            <ul style="list-style:none;">
                <li><input type="radio" name="btn" id="a" <?php if ( $result['selectedMonth']) echo 'checked = "checked"' ?>>表示月を選択する</li>
                <li><input type="radio" name="btn" id="b" <?php if ( $result['selectedrange']) echo 'checked = "checked"' ?>>期間指定をする</li>
            </ul>
            <form method="get" action="/JobCoordinator/attendance/input" id="datepicker-default" <?php if (!$result['selectedMonth']) echo 'style="display: none;"' ?>>
                <div class="form-group well">
                    <span class="control-label">表示月指定</span>
                    <div class="form-inline">
                        <div class="input-group date">
                            <input type="text" class="form-control" name="date_month" value="<?php echo $result['attendanceMonth'] ?>">
                            <div class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                    <button type='submit' name='view' id="input-datemonth">出力</button>
                </div>
            </form>
            <form method="get" action="/JobCoordinator/attendance/input" id="datepicker-daterange" <?php if (!$result['selectedrange']) echo 'style="display: none;"' ?>>
                <div class="form-group well">
                    <span class="control-label">期間</span>
                    <div class="form-inline">
                        <div class="input-daterange input-group" id="datepicker">
                            <input type="text" class="input-sm form-control" name="date_start" value="<?php echo $result['attendanceFrom'] ?>">
                            <span class="input-group-addon">to</span>
                            <input type="text" class="input-sm form-control" name="date_end" value="<?php echo $result['attendanceTo'] ?>">
                        </div>
                    </div>
                    <button type='submit' name='view' id="input-daterange">出力</button>
                </div>
            </form>
            <!-- List -->
            <?php $this->load->view($this->my_device->_get_user_device(). '/attendance/attendList'); ?>

        </section>
    </article>
</div>

<!-- modal -->
<div class="modal fade" id="modal-example" tabindex="-1">
    <div class="modal-dialog modal-lg">

        <!-- modal contents -->
        <div class="modal-content">
            <!-- modal header -->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modal-label">ダイアログ</h4>
            </div>
            <!-- modal body -->
            <div class="modal-body">
                <?php $this->load->view($this->my_device->_get_user_device(). '/attendance/pdf/layout'); ?>
            </div>
            <!-- modal footer -->
            <form method="post" action="/JobCoordinator/attendance/pdf">
                <input type="hidden" name="<?=$csrf['name'];?>" value="<?=$csrf['hash'];?>" />
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">戻る</button>
                    <button type="submit" class="btn btn-primary">出力</button>
                    <button type="button" class="btn btn-primary">提出</button>
                </div>
                <input type="hidden" name="<?=$csrf['name'];?>" value="<?=$csrf['hash'];?>" />
                <input type="hidden" name="date_start" value="<?php echo $result['attendanceFrom'] ?>">
                <input type="hidden" name="date_end" value="<?php echo $result['attendanceTo'] ?>">
            </form>
        </div>
    </div>
</div>

<script>
    $(function() {
        /**
         * 期間指定の切り替えトグルのイベント発火制御
         * 期間指定の項目を切り替えます
         */
        $('[name="btn"]:radio').change( function() {
            if($('[id=a]').prop('checked')){
                $('#datepicker-daterange').hide();
                $('#datepicker-default').fadeIn();
            } else if ($('[id=b]').prop('checked')) {
                $('#datepicker-default').hide();
                $('#datepicker-daterange').fadeIn();
            }
        });

        /**
         * 表示月指定時の確定ボタン選択時のフォームサブミットイベント
         */
        $('#input-datemonth').click(function(e) {
            console.log($('input[name="date_month"]').text());
        });

        /**
         * 表示期間期間指定時の確定ボタン選択時のフォームサブミットイベント
         */
        $('#input-daterange').click(function(e) {
            console.log($('input[name="date_start"]').text());
            console.log($('input[name="date_end"]').text());
        });

    });
</script>
