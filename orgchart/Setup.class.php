<?php


/** 
 * Организациони структурии
 *
 * @category  bgerp
 * @package   orgchart
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class orgchart_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Инструмент за чертане на организационна графика';
    
    
    /**
     * Път до js файла
     */
    //	var $commonJS = 'orgchart/lib/jquery.orgchart.js';
    
    
    /**
     * Път до css файла
     */
    //	var $commonCSS = 'orgchart/lib/jquery.orgchart.css';

    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
