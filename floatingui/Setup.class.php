<?php


/**
 * Версията на Floating UI, която се използва
 */
defIfNot('FLOATINGUI_VERSION', '1.8.0');


/**
 * Клас 'floatingui_Setup' - библиотека за позициониране на балончета и падащи менюта
 *
 *
 * @category  бгерп
 * @package   floatingui
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link      https://floating-ui.com/
 */
class floatingui_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Описание на модула
     */
    public $info = 'Floating UI - позициониране на балончета, хинтове и падащи менюта';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'FLOATINGUI_VERSION' => array('enum(1.8.0)', 'mandatory, caption=Версия на библиотеката->Версия'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Закачане на библиотеката към всички HTML страници
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->installPlugin('Floating UI', 'floatingui_plg_AddFiles', 'page_Html', 'family');

        return $html;
    }
}
