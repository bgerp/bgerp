<?php


/**
 * Клас 'openai_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'openai'
 *
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('openai_Cache', 'Кеш', 'openai');
        $this->TAB('openai_Prompt', 'Въпроси', 'openai');
    }
}
