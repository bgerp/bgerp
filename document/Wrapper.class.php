<?php



/**
 * Клас 'store_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'store'
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class document_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('document_Orders', 'Поръчки', 'ceo');
        $this->TAB('document_Products', 'Продукти', 'ceo');
        $this->TAB('document_Tags', 'Тагове', 'ceo');
        $this->title = 'Продукти';
    }
}