<?php


/**
 * Урл за изпращане на SMS-и през Мобио
 */
defIfNot('MOBIO_URL', '');


/**
 * Дали поддържа UTF-8
 */
defIfNot('MOBIO_SUPPORT_UTF8', false);


/**
 * Максималната дължина на стринга
 */
defIfNot('MOBIO_MAX_STRING_LEN', 160);


/**
 * Стринг с позволените имена за изпращач
 */
defIfNot('MOBIO_ALLOWED_USER_NAMES', '');


/**
 * class mobio_Setup
 *
 * Инсталиране/Деинсталиране на плъгина за изпращане на SMS-и чрез mobio
 *
 *
 * @category  vendors
 * @package   mobio
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class mobio_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'SMS изпращане чрез Mobio';
    
    
    public $configDescription = array(
        'MOBIO_URL' => array('url', 'mandatory, caption=Адрес за изпращане на SMS-и през Мобио->URL адрес'),
        
        'MOBIO_SUPPORT_UTF8' => array('enum(no=Не, yes=Да)', 'caption=Дали поддържа UTF-8->Да/Не'),
        'MOBIO_MAX_STRING_LEN' => array('int',  'caption=Максималната дължина на стринга->Бр. символи'),
        'MOBIO_ALLOWED_USER_NAMES' => array('text(rows=1)', 'caption=Стринг с позволените имена за изпращач->Списък с имена'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'mobio_SMS',
    );
}
