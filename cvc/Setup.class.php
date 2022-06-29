<?php


/**
 * Ключ за достъп
 */
defIfNot('CVC_TOKEN', '');


/**
 * Урл
 */
defIfNot('CVC_URL', 'https://lox.e-cvc.bg/');


/**
 *
 *
 * @category  bgerp
 * @package   cvc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class cvc_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Интеграция с CVC API';

    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'CVC_TOKEN' => array('password(show)', 'caption=Ключ,class=w100'),
        'CVC_URL' => array('url', 'caption=УРЛ'),
    );


    /**
     * Роли за достъп до модула
     */
    public $roles = array(
        array('cvc'),
    );
}
