<?php


/**
 *
 *
 * @category  bgerp
 * @package   eventhub
 *
 * @author    Ивета Мошева <ivetamosheva@gmail.com>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eventhub_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';


    /**
     * Описание на модула
     */
    public $info = 'Публикуване на събития';


    /**
     * Роли за достъп до модула
     */
    public $roles = array('eventhub');


    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.99, 'Сайт', 'Събития', 'eventhub_Events', 'default', 'ceo, eventhub'),
    );


    /**
     * Мениджъри за инсталиране
     */
    public $managers = array(
        'eventhub_Categories',
        'eventhub_Forms',
        'eventhub_Series',
        'eventhub_Venues',
        'eventhub_Events',
    );
}
