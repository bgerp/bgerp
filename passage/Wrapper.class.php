<?php


/**
 * Пасаж
 *
 *
 * @category  bgerp
 * @package   passage
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class passage_Wrapper extends plg_ProtoWrapper
{

    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('passage_Texts', 'Пасажи', 'ceo,admin');
        $this->title = 'Фрагменти';
    }
}