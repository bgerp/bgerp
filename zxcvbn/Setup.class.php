<?php


/**
 * Версията на програмата
 */
defIfNot('ZXCVBN_MIN_SCORE', '3');


/**
 *
 *
 * @category  bgerp
 * @package   zxcvbn
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class zxcvbn_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = '';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Проверка на сложността на паролите';


    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'ZXCVBN_MIN_SCORE' => array('int(min=0,max=4)', 'caption=Сложност на паролата->Точки'),
    );
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();

        // Инсталираме плъгина
        $html .= core_Plugins::forcePlugin('Проверка на сложността на паролите', 'zxcvbn_Plugin', 'crm_Profiles', 'private');
        $html .= core_Plugins::forcePlugin('Проверка на сложността на паролите при вход', 'zxcvbn_Plugin', 'core_Users', 'private');

        return $html;
    }
}
