<?php


/**
 * Парола
 */
defIfNot('OPENAI_TOKEN', '');

/**
 * Парола
 */
defIfNot('OPENAI_URL', 'https://api.openai.com/v1/completions');


/**
 *
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class openai_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с openai API';

    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'OPENAI_TOKEN' => array('password(show)', 'caption=Ключ,class=w100'),
        'OPENAI_URL' => array('url', 'caption=УРЛ'),
    );


    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'openai_Cache';


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('openai'),
    );


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.98, 'Система', 'AI', 'openai_Cache', 'default', 'openai'),
    );


    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'openai_Cache',
        'openai_Prompt',
    );
}
