<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<style>
/**
 * clone bootstrap css.
 */
body {
    background-color: #fff;
    margin: 40px;
    /* font: 10px normal Helvetica, Arial, sans-serif; */
    color: #4F5155;
}
td {
    padding: 8px;
    line-height: 1.42857143;
    vertical-align: top;
    border-top: 1px solid #ddd;
  }

table {
    border-spacing: 0;
    border-collapse: collapse;
    width: 100%;
    max-width: 100%;
    margin-bottom: 20px;
}
.striped {
    background-color: #f5f5f5;
}

/**
 * make original css
 */

/* header部の要素横並び */
.box_left {
    float:left;
    width:150px;
    height:100px;
}

.header-icon-area .sub-text{
    color:gray;
    font-size: 10px;
}

.box_bottom {
    vertical-align: bottom;
}
</style>


<header>
<div>
   <div class="header-icon-area box_left">
       <div class="icon-area">
           <img class="icon" src="https://www.google.com/images/branding/googlelogo/2x/googlelogo_color_150x54dp.png" />
       </div>
       <div class="sub-text">
           <span>勤怠表</span>
       </div>
   </div>
   <div class="box_bottom">
       <div class="title">
           サンプル会社所属 <?php echo $result['user']['NAME1'] . " " . $result['user']['NAME2']?>
       </div>
       <hr>
   </div>
</div>
</header>

<!-- pdf body -->
<div class="pdf-body">
    <?php $this->load->view($this->my_device->_get_user_device() . '/attendance/pdf/body'); ?>
</div>
