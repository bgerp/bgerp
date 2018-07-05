<?php


/**
 * class swf_Object
 *
 * Предоставя възможностите на пакета SWFObject2
 *
 *
 * @category  bgerp
 * @package   swf
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class swf_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за работа с SWFObject2';
    
    
    /**
     * Път до js файла
     */
    //	var $commonJS = 'swf/2.2/swfobject.js';

    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
