<?php


/**
 * Конвертиране на файлове
 *
 * @category  bgerp
 * @package   export
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class export_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Експортване на документи";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'export_Link',
            'export_Pdf',
            'export_Html',
            'export_HtmlEditor',
            'export_Csv',
            'export_Xls',
            'export_Doc',
    );
}