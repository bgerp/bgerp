<?php


/**
 * Версията на JQPlot, която се използва
 */
defIfNot(JQPLOT_VERSION, '1.0.8r1250');


/**
 *
 *
 * @category  bgerp
 * @package   jqplot
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class jqplot_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Изтрумент за рендиране на графики, използващ JQPlot';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'JQPLOT_VERSION' => array('enum(1.0.0r1012,1.0.8r1250)', 'caption=Версия на JQPlot->Версия'),
    );
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Връща CSS файлове за компактиране
     *
     * @see core_ProtoSetup::getCommonCss()
     *
     * @return string
     */
    public function getCommonCss()
    {
        $files = parent::getCommonCss();
        
        if ($files) {
            $files .= ', ';
        }
        
        $files .= jqplot_Chart::resource('jquery.jqplot.css');
        
        return $files;
    }
    
    
    /**
     * Връща JS файлове за компактиране
     *
     * @see core_ProtoSetup::getCommonJs()
     *
     * @return string
     */
    public function getCommonJs()
    {
        $files = parent::getCommonJs();
        
        if ($files) {
            $files .= ', ';
        }
        
        $files .= jqplot_Chart::resource('jquery.jqplot.js');
        $files .= ', ' . jqplot_Chart::resource('excanvas.js');
        
        return $files;
    }
}
