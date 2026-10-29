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
    /** Запазва автоматичната ориентация на CAD форматите и при TCPDF 7. */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false)
    {
        if (tcpdf_Setup::get('VERSION') === '7.0.20' && $orientation === '' && is_array($format)
            && isset($format[0], $format[1])) {
            $orientation = $format[0] > $format[1] ? 'L' : 'P';
        }
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
    }


    /** Изчертава CAD път чрез избраната TCPDF библиотека. */
    public function drawPath($commands, $style = '', $line_style = array(), $fill_color = array())
    {
        $modern = tcpdf_Setup::get('VERSION') === '7.0.20';
        if (($modern ? $this->docstate : $this->state) != 2) {
            
            return;
        }
        
        if (!(false === stripos($style, 'F')) and isset($fill_color)) {
            $this->SetFillColorArray($fill_color);
        }
        $graph = $modern ? $this->engine()->graph : null;
        $op = $modern ? $graph->getPathPaintOp($style) : TCPDF_STATIC::getPathPaintOperator($style);
        if (trim($op) == 'f') {
            $line_style = array();
        }
        if ($line_style) {
            $this->SetLineStyle($line_style);
        }
        
        foreach ($commands as $d) {
            if ($d[0] == 'M') {
                if ($modern) $this->emitToPage($graph->getRawPoint($d[1], $d[2]));
                else $this->_outPoint($d[1], $d[2]);
            }
            if ($d[0] == 'C') {
                if ($modern) $this->emitToPage($graph->getRawCurve($d[1], $d[2], $d[3], $d[4], $d[5], $d[6]));
                else $this->_outCurve($d[1], $d[2], $d[3], $d[4], $d[5], $d[6]);
            }
            if ($d[0] == 'L') {
                if ($modern) $this->emitToPage($graph->getRawLine($d[1], $d[2]));
                else $this->_outLine($d[1], $d[2]);
            }
        }
        
        if ($modern) $this->emitToPage($op);
        else $this->_out($op);
    }
    
    
    /** Генерира шрифт във формата на избраната библиотека: PHP за 6.x, JSON за 7.x. */
    public function addTTFfont($fontfile, $fonttype = '', $enc = '', $flags = 32, $outpath = '', $platid = 3, $encid = 1, $addcbbox = false, $link = false)
    {
        if (tcpdf_Setup::get('VERSION') === '7.0.20') {
            $font = new \Com\Tecnick\Pdf\Font\Import($fontfile, $outpath, $fonttype, $enc, $flags, $platid, $encid, $link);

            return $font->getFontName();
        }
        return TCPDF_FONTS::addTTFfont($fontfile, $fonttype, $enc, $flags, $outpath, $platid, $encid, $addcbbox, $link);
    }
}
