<?php


/**
 * Клас 'core_Expert'
 *
 * Клас-родител за експертизи
 *
 *
 * @category  vendors
 * @package   expert
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class expert_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Реализиране на постъпкова експертна система';
    
    
    /**
     * Пакет без инсталация
     */
    public $noInstall = true;
}
