<?php

/**
 * Форум - Опаковка
 *
 *
 * @category  bgerp
 * @package   forum
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class forum_Wrapper extends plg_ProtoWrapper
{


    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('forum_Categories', 'Категории', 'forum,admin,ceo,cms');
        $this->TAB('forum_Boards', 'Дъски', 'admin,forum,ceo,cms');
        $this->TAB('forum_Postings', 'Постинги', 'forum,admin,ceo,cms');
    }
}
