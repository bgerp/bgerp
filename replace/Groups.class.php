<?php


/**
 * ГТрупи за текстовете за заместване
 *
 * @category  vendors
 * @package   replace
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class replace_Groups extends core_Manager
{
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created,plg_RowTools2,plg_State2,replace_Wrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Групи на заместванията';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    // var $listFields = '';
    
    
    /**
     * Кой може да го прочете?
     */
    public $canRead = 'admin';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'admin';
    
    const DEFAULT_CACHE_AGE = 2592000; // 30 дни в секунди
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,width=100%');
        $this->FLD('info', 'richtext(bucket=Notes)', 'caption=информация');
    }
}
