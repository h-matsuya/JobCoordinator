<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH.'../vendor/autoload.php');

class Pdf_lib extends \Mpdf\Mpdf {

    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function _init_pdf($params = null)
    {
        $mode = 'ja';
        $format = 'A4';
        $default_font_size = '';
        $default_font = '';
        $margin_left = 15;
        $margin_right = 15;
        $margin_top = 16;
        $margin_bottom = 16;
        $margin_header = 9;
        $margin_footer = 9;
        $orientation = 'P';

        if (isset($params['mode'])) {
            $mode = $params['mode'];
        }
        if (isset($params['format'])) {
            $format = $params['format'];
        }
        if (isset($params['default_font_size'])) {
            $default_font_size = $params['default_font_size'];
        }
        if (isset($params['default_font'])) {
            $default_font = $params['default_font'];
        }
        if (isset($params['margin_left'])) {
            $margin_left = $params['margin_left'];
        }
        if (isset($params['margin_right'])) {
            $margin_right = $params['margin_right'];
        }
        if (isset($params['margin_top'])) {
            $margin_top = $params['margin_top'];
        }
        if (isset($params['margin_bottom'])) {
            $margin_bottom = $params['margin_bottom'];
        }
        if (isset($params['margin_header'])) {
            $margin_header = $params['margin_header'];
        }
        if (isset($params['margin_footer'])) {
            $margin_footer = $params['margin_footer'];
        }
        if (isset($params['orientation'])) {
            $orientation = $params['orientation'];
        }

        # initialize mPDF
        parent::__construct([
            'mode'          => $mode,
            'format'        => $format,
            'default_font_size' => $default_font_size,
            'default_font'  => $default_font,
            'margin_left'   => $margin_left,
            'margin_right'  => $margin_right,
            'margin_top'    => $margin_top,
            'margin_bottom' => $margin_bottom,
            'margin_header' => $margin_header,
            'margin_footer' => $margin_footer,
            'orientation'   => $orientation,
        ]);
    }

}
