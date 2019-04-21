<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
                <!-- list -->
	            <div class="table-responsive">
	                <table class="table table-striped table-hover">
	                    <thead>
	                        <tr>
	                            <th>日付</th>
	                            <th>出勤時刻</th>
	                            <th>退勤時刻</th>
	                            <th>休憩時間</th>
                                <th>稼働時間</th>
	                            <th>備考</th>
	                        </tr>
	                    </thead>
                        <tfoot>
                            <tr>
                                <td><strong>合計</strong></td>
                                <td></td>
                                <td></td>
                                <td><strong><?php echo !empty($result['sumBreakTime']) ? $result['sumBreakTime'] . 'h' : ''  ?></strong></td>
                                <td><strong><?php echo !empty($result['sumWorkTime']) ? $result['sumWorkTime'] . 'h' : ''  ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
	                    <tbody>
                            <?php foreach ($result['datePropaty']['range'] as $key => $date) { ?>
                            <tr>
                                <td><?php echo $date . " (" . $this->my_date->_get_day_of_week($date) . ")"?></td>
                                <td>
                                    <?php echo isset($result['attendance'][$date]) ? $this->my_date->_ceil_per_time($result['attendance'][$date]['CHECKIN_TIME']) : '' ?>
                                </td>
                                <td>
                                    <?php echo isset($result['attendance'][$date]) ? $this->my_date->_floor_per_time($result['attendance'][$date]['CHECKOUT_TIME']) : '' ?>
                                </td>
                                <td>
                                    <?php echo !empty($result['attendance'][$date]['BREAK_TIME']) ? $result['attendance'][$date]['BREAK_TIME']: ''  ?>
                                <td>
                                    <?php echo !empty($result['attendance'][$date]['WORKING_TIME']) ? $result['attendance'][$date]['WORKING_TIME'] . 'h' : ''  ?>
                                </td>
                                <td>
                                    <?php echo !empty($result['attendance'][$date]['REMARKS']) ? nl2br($result['attendance'][$date]['REMARKS']) : ''  ?>
                                </td>
                            </tr>
                            <?php }?>
	                    </tbody>
	                </table>
	            </div>
