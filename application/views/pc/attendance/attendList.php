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
                                <form method="post" action="/JobCoordinator/attendance/input">
                                    <td><?php echo $date . " (" . $this->my_date->_get_day_of_week($date) . ")"?></td>
                                    <td>
                                        <?php echo isset($result['attendance'][$date]) ? $this->my_date->_ceil_per_time($result['attendance'][$date]['CHECKIN_TIME']) : '' ?>
                                    </td>
                                    <td>
                                        <?php if ($date < $this->my_date->_get_now_date() && !empty($result['attendance'][$date]['CHECKIN_TIME']) && empty($result['attendance'][$date]['CHECKOUT_TIME'])) {?>
                                            <input type="time" name="manual_checkout" value="" />
                                            <button type='submit' name='check' value='3'>登録</button>
                                        <?php }else{ ?>
                                            <?php echo isset($result['attendance'][$date]) ? $this->my_date->_floor_per_time($result['attendance'][$date]['CHECKOUT_TIME']) : '' ?>
                                        <?php } ?>                                
                                    </td>
                                    <td>
                                        <?php if (!empty($result['attendance'][$date]['CHECKIN_TIME']) && !empty($result['attendance'][$date]['CHECKOUT_TIME'])) {?>
                                            <input type="number" step="0.25" name="manual_breaktime" value="<?php echo !empty($result['attendance'][$date]['BREAK_TIME']) ? $result['attendance'][$date]['BREAK_TIME']: ''  ?>" />
                                            <button type='submit' name='check' value='3'>登録</button>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($result['attendance'][$date]['WORKING_TIME']) ? $result['attendance'][$date]['WORKING_TIME'] . 'h' : ''  ?>
                                    </td>
                                    <td>
                                        <textarea wrap="hard" name="manual_remarks"><?php echo !empty($result['attendance'][$date]['REMARKS']) ? $result['attendance'][$date]['REMARKS'] : ''  ?></textarea>
                                        <button type='submit' name='check' value='3'>登録</button>
                                    </td>
                                    <input type="hidden" name="manual_selected_date" value="<?=$date;?>" >
                                    <input type="hidden" name="<?=$csrf['name'];?>" value="<?=$csrf['hash'];?>" >
                                    <input type="hidden" name="date_start" value="<?php echo $result['attendanceFrom'] ?>">
                                    <input type="hidden" name="date_end" value="<?php echo $result['attendanceTo'] ?>">
                                </form>
                            </tr>
                            <?php }?>
	                    </tbody>
	                </table>
	            </div>
