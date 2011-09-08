<?php
/**
 * Мениджър на опаковки
 * 
 * Всяка категория (@see cat_Categories) има нула или повече опаковки. Това са опаковките, в
 * които могат да бъдат пакетирани продуктите (@see cat_Products), принадлежащи на категорията.
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 * @title Опаковки
 *
 */
class cat_Packagings extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Опаковки";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper, plg_State2';
    
    
    /**
     *  @todo Чака за документация...
     */
//    var $listFields = 'id,title, inPriceLists,state,groupIcon';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(32)', 'caption=Име, mandatory');
        $this->FLD('contentPlastic', 'percent', 'caption=Полимер');
        $this->FLD('contentPaper', 'percent', 'caption=Хартия');
        $this->FLD('contentGlass', 'percent', 'caption=Стъкло');
        $this->FLD('contentMetals', 'percent', 'caption=Метали');
        $this->FLD('contentWood', 'percent', 'caption=Дървесина');
    }
}