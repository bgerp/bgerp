<?php


/**
 * Клас 'deepl_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'deepl'
 *
 *
 * @category  bgerp
 * @package   deepl
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class deepl_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('deepl_Cache', 'Кеш', 'deepl');
    }
}
