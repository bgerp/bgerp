<?php


/**
 * Токен за работа с GitHub API
 */
defIfNot('GIT_GITHUB_TOKEN', '');


/**
 * Ключ за извикване от GitHub на hooks
 */
defIfNot('GIT_GITHUB_HOOK_KEY', '');


/**
 * Клас 'git_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   git
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class git_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за работа с git репозиторита';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'GIT_GITHUB_TOKEN' => array('password(show)', 'caption=GitHub API->Token'),
        'GIT_GITHUB_HOOK_KEY' => array('password(show)', 'caption=GitHub API->Hook key'),
    
    );
}
