<?php


/**
 * Клас 'newsbar_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'newsbar'
 *
 *
 * @category  bgerp
 * @package   social
 *
 * @author    Gabriela Petrova <gpetrova@experta.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class newsbar_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('newsbar_News', 'Новини', 'cms, newsbar, admin, ceo');
        
        $this->title = 'Новини « Сайт';
        Mode::set('menuPage', 'Сайт:Новини');
    }
}
