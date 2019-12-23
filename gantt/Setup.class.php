<?php


/** Гант таблица
 *
 * @category  bgerp
 * @package   gantt
 *
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class gantt_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Създаване на Gantt таблици';
    
    
    /**
     * Път до js файла
     */
    //	var $commonJS = 'gantt/lib/ganttCustom.js';
    
    
    /**
     * Път до css файла
     */
    //	var $commonCSS = 'gantt/lib/ganttCustom.css';
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
