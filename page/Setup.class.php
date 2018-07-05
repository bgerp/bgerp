<?php


/**
 * Клас 'page_Setup'
 *
 * Исталиране/деинсталиране на Apachetika
 *
 *
 * @category  bgerp
 * @package   page
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class page_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Пакет за работа със страници';

    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
    
    
    /**
     * Интерфейсен метод
     *
     * @see core_page_WrapperIntf
     */
    public function prepare()
    {
    }
}
