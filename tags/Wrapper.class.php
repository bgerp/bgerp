<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   tags
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class tags_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('tags_Tags', 'Маркери', 'ceo,admin');
        $this->TAB('tags_Logs', 'Логове', 'ceo,admin');
    }
}
