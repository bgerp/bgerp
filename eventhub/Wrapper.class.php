<?php


/**
 * Клас 'eventhub_Wrapper'
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ивета Мошева <ivetamosheva@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class eventhub_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('eventhub_Events', 'Събития', 'ceo,eventhub');
        $this->TAB('eventhub_Categories', 'Категории', 'ceo,eventhub');
        $this->TAB('eventhub_Forms', 'Формати', 'ceo,eventhub');
        $this->TAB('eventhub_Series', 'Поредици', 'ceo,eventhub');
        $this->TAB('eventhub_Venues', 'Места', 'ceo,eventhub');
    }
}