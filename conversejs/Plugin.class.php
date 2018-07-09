<?php


/**
 * Клас 'conversejs_Plugin'
 *
 * Добавяне на бутон за отваряне на чат клиент conversejs
 *
 *
 * @category  bgerp
 * @package   conversejs
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class conversejs_Plugin extends core_Plugin
{
    public function on_Output(&$invoker)
    {
        $chat = ht::createLink(
            tr('Чат'),
            array('conversejs_Adapter', 'show'),
            null,
            array('target' => 'conversejs', 'ef_icon' => 'conversejs/img/16/converse.png', 'title' => 'Отваряне на прозорец за чат')
        );
        
        $invoker->append($chat, 'PROFILE_MENU_ITEM');
    }
}
