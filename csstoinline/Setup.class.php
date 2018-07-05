<?php


/**
 * Класа, който ще се използва за конвертиране
 */
defIfNot('CSSTOINLINE_CONVERTER_CLASS', 'csstoinline_Emogrifier');


/**
 * Версията на emogrifier
 */
defIfNot('CSSTOINLINE_EMOGRIFIER_VERSION', '2014.11.10');


/**
 * Версията на csstoinline
 */
defIfNot('CSSTOINLINE_CSSTOINLINE_VERSION', '1.0.3');


/**
 *
 * @category  bgerp
 * @package   csstoinline
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class csstoinline_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Конвертиране на CSS стилове в inline';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
    
        // Клас за инлайн вмъкване на CSS дефиниции
        'CSSTOINLINE_CONVERTER_CLASS' => array('class(interface=csstoinline_ConverterIntf, select=title)', 'mandatory, caption=Клас за инлайн вмъкване на CSS дефиниции->Клас'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
            'csstoinline_CssToInline',
            'csstoinline_Emogrifier',
        );
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
