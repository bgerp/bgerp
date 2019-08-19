<?php


/**
 * Блог - Опаковка
 *
 *
 * @category  bgerp
 * @package   blog
 *
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class blogm_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB(array('blogm_Articles', 'list'), 'Статии', 'admin,blog,ceo,cms');
        $this->TAB('blogm_Comments', 'Коментари', 'blog,admin,ceo,cms');
        $this->TAB('blogm_Categories', 'Категории', 'admin,blog,ceo,cms');
        $this->TAB('blogm_Links', 'Препратки', 'admin,blog,ceo,cms');
    }
}
