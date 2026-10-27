<?php


tcpdf_Setup::loadLibrary();


/**
 * Alias на TCPDF
 *
 * @category  bgerp
 * @package   tcpdf
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tcpdf_Instance extends tcpdf
{
    /** Изчертава CAD път чрез избраната TCPDF библиотека. */
    public function drawPath($commands, $style = '', $line_style = array(), $fill_color = array())
    {
        if ($this->state != 2) {
            
            return;
        }
        
        if (!(false === stripos($style, 'F')) and isset($fill_color)) {
            $this->SetFillColorArray($fill_color);
        }
        $op = TCPDF_STATIC::getPathPaintOperator($style);
        if ($op == 'f') {
            $line_style = array();
        }
        if ($line_style) {
            $this->SetLineStyle($line_style);
        }
        
        foreach ($commands as $d) {
            if ($d[0] == 'M') {
                $this->_outPoint($d[1], $d[2]);
            }
            if ($d[0] == 'C') {
                $this->_outCurve($d[1], $d[2], $d[3], $d[4], $d[5], $d[6]);
            }
            if ($d[0] == 'L') {
                $this->_outLine($d[1], $d[2]);
            }
        }
        
        $this->_out($op);
    }
    
    
    /** Генерира шрифт с вече заредения TCPDF_FONTS от същата версия. */
    public function addTTFfont($fontfile, $fonttype = '', $enc = '', $flags = 32, $outpath = '', $platid = 3, $encid = 1, $addcbbox = false, $link = false)
    {
        return TCPDF_FONTS::addTTFfont($fontfile, $fonttype, $enc, $flags, $outpath, $platid, $encid, $addcbbox, $link);
    }
}
