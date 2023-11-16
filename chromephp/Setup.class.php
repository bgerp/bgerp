<?php


/**
 * Дали да се показват броя на страниците
 */
defIfNot('CHROMEPHP_SHOW_PAGE_NUMBERS', 'no');


/**
 * Път до изпълнимия файл на Chrome
 * chrome
 * chromium-browser
 */
defIfNot('CHROMEPHP_BIN_PATH', '');


/**
 *
 *
 * @package   chromephp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/chrome-php/chrome
 */
class chromephp_Setup extends core_ProtoSetup
{

    /**
     * Информация за пакета
     */
    public $info = 'Chrome PHP конвертиране на HTML към PDF';


    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = 'chromephp_Browser';



    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CHROMEPHP_SHOW_PAGE_NUMBERS' => array('enum(no=Не,yes=Да)', 'caption=Дали да се показват броя на страниците->Избор'),
    );


    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        $html .= core_Composer::install('chrome-php/chrome');

        if (!core_Composer::isInUse()) {
            $html .= "<li class='red'>Проблем при зареждането на composer</li>";
        }

        return $html;
    }
}
