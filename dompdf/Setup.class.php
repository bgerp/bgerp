<?php

/**
 * Разделителна способност по подразбиране
 */
defIfNot("DOMPDF_DPI", "120");

/**
 * @todo Чака за документация...
 */
defIfNot('DOMPDF_VER', '0.6.0b3');


/**
 * Дефинира име на папка в която ще се съхраняват временните данни данните
 */
defIfNot('DOMPDF_TEMP_DIR', EF_TEMP_PATH . "/dompdf");


/**
 * Файла, където се записва лога
 */
defIfNot('DOMPDF_LOG_OUTPUT_FILE', DOMPDF_TEMP_DIR . "/log.htm");


/**
 * Възможност да се използват ресурси от Интернет
 */
 defIfNot("DOMPDF_ENABLE_REMOTE", TRUE);

 
/**
 * class dompdf_Setup
 *
 * Инсталиране/Деинсталиране на
 * пакета за конвертиране html -> pdf
 *
 *
 * @category  vendors
 * @package   dompdf
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dompdf_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    /**
     * Мениджър - входна точка в пакета
     */
    // var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    // var $startAct = 'default';
    
    
    
    /**
     * Описание на модула
     */
    var $info = "Конвертиране .html => .pdf";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
            'DOMPDF_VER' => array ('enum(0.6.0b3)', 'caption=Коя версия на dompdf да се използва->Версия'),
            'DOMPDF_DPI'   => array ('int', 
                                     'caption=Плътност на растеризацията->Точки на инч',
                                     'suggestions=120|96|75'),
        );
    
        
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'dompdf_Converter'
        );
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	// Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}