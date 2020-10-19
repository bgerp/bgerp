<?php


/**
 * class school_ReqDocuments
 *
 * Изискуеми документи
 *
 *
 * @category  bgerp
 * @package   edu
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class school_ReqDocuments extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Изискуеми документи';
    
    
    /**
     * Заглавие в единично число
     */
    public $singleTitle = 'Документ';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, school_Wrapper, plg_Sorting, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name,ext';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, edu';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, edu';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, edu';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, edu';
    
    
    /**
     * Шаблон за единичния изглед
     */
    // public $singleLayoutFile = '';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Наименование,mandatory');
        $this->FLD('ext', 'set(pdf,rtf,doc,xls,tiff,jpg,jpeg,png,bmp)', 'caption=Разширения');

        $this->setDbUnique('name');
    }
    
}
