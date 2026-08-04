<?php

/**
 * Клас 'zenquotes_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'zenquotes'
 *
 *
 * @category  bgerp
 * @package   zenquotes
 *
 * @author    David Dimitriev
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class zenquotes_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('zenquotes_Quotes', 'Статии');

        $this->title = 'Статии';
    }
}

