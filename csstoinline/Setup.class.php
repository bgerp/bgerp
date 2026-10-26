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
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
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
    public $info = 'Конвертиране на CSS стилове в inline чрез Emogrifier или CssToInline';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        // Клас за инлайн вмъкване на CSS дефиниции
        'CSSTOINLINE_CONVERTER_CLASS' => array('class(interface=csstoinline_ConverterIntf, select=title)', 'mandatory, refreshForm, caption=Клас за инлайн вмъкване на CSS дефиниции->Клас'),
        'CSSTOINLINE_EMOGRIFIER_VERSION' => array('enum(2011.10.26,2014.11.10,3.0.0)', 'mandatory, caption=Emogrifier->Версия'),
        'CSSTOINLINE_CSSTOINLINE_VERSION' => array('enum(1.0.3,2.2.2)', 'mandatory, caption=CssToInline->Версия'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'csstoinline_CssToInline',
        'csstoinline_Emogrifier',
    );


    /**
     * Показва версията само на избрания конвертор, без да губи другата настройка.
     *
     * @param core_Form $configForm
     * @return void
     */
    public function manageConfigDescriptionForm(&$configForm)
    {
        $configForm->input('CSSTOINLINE_CONVERTER_CLASS', 'silent');
        $converter = $configForm->rec->CSSTOINLINE_CONVERTER_CLASS ?? null;
        if ($converter) {
            $converter = cls::getClassName($converter);
        }

        // Скритата версия се запазва и при превключване на класа преди запис.
        if ($converter !== 'csstoinline_Emogrifier') {
            $configForm->setField('CSSTOINLINE_EMOGRIFIER_VERSION', 'input=hidden');
        }
        if ($converter !== 'csstoinline_CssToInline') {
            $configForm->setField('CSSTOINLINE_CSSTOINLINE_VERSION', 'input=hidden');
        }
    }


    /**
     * Инсталира CSS Selector само за версиите, които го изискват.
     *
     * @return string Резултат от инициализацията
     */
    public function install()
    {
        $html = parent::install();
        $converter = cls::getClassName(self::get('CONVERTER_CLASS'));
        if (($converter === 'csstoinline_Emogrifier' && self::get('EMOGRIFIER_VERSION') === '3.0.0')
            || ($converter === 'csstoinline_CssToInline' && self::get('CSSTOINLINE_VERSION') === '2.2.2')) {
            $html .= core_Composer::install('symfony/css-selector', '^5.4');
        }
        return $html;
    }


    /**
     * Зарежда Composer и проверява зависимостта на по-новите конвертори.
     *
     * @return void
     */
    public static function loadCssSelector()
    {
        core_Composer::isInUse();
        expect(class_exists('Symfony\\Component\\CssSelector\\CssSelectorConverter'),
            'Избраната версия изисква Symfony CSS Selector. Инициализирайте пакета csstoinline.');
    }
}
