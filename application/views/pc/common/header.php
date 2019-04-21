<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo $title;?></title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="format-detection" content="telephone=no">

    <meta name="viewport" content="width=device-width,initial-scale=0.8">

    <meta property="og:type" content="website">
    <meta property="og:url" content="http://h-matsuya.sakura.ne.jp/JobCoordinator/">
    <meta property="og:image" content="">
    <meta property="og:title" content="">
    <meta property="og:description" content="">

    <link href="/JobCoordinator/css/vendor/bootstrap/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">
    <link href="/JobCoordinator/css/vendor/bootstrap/bootstrap-datepicker3.min.css" rel="stylesheet" type="text/css">
    <link href="/JobCoordinator/css/common.css" rel="stylesheet" type="text/css">
    <script src="/JobCoordinator/js/jquery-3.2.1.min.js"></script>
    <script src="/JobCoordinator/js/vendor/bootstrap/bootstrap.min.js"></script>
    <script src="/JobCoordinator/js/vendor/bootstrap/bootstrap-datepicker.min.js"></script>
    <script src="/JobCoordinator/js/vendor/bootstrap/locales/bootstrap-datepicker.ja.min.js"></script>
    <script src="/JobCoordinator/js/common.js"></script>
    
<script>
$(function(){
    $('#datepicker-daterange .input-daterange').datepicker({
        language: 'ja',
        format: "yyyy年mm月dd日",
        maxViewMode: 2,
        todayBtn: "linked",
        clearBtn: true,
        todayHighlight: true
    });
});
$(function(){
    //Default
    $('#datepicker-default .date').datepicker({
        format: "yyyy年mm月dd日",
        startView: 1,
        minViewMode: 1,
        maxViewMode: 2,
        clearBtn: true,
        language: "ja",
        todayHighlight: true
    });
    
});
</script>
    
</head>
<body>
    <div id='header'>
    </div>
