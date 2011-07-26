<?php
defIfNot('CAT_GROUPS_PREFIX', 'МЦ');


/**
 * Класа менаджира групите на продуктите
 */
class cat_Groups extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Групи на продуктите";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, cat_Wrapper, plg_State2';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,title, inPriceLists,state,groupIcon';
    
    
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
        $this->FLD('title', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('inPriceLists', 'enum(yes,no)', 'caption=В Ц.Л., mandatory');
        $this->FLD('groupIcon', 'fileman_FileType(bucket=productsGroupsIcons)', 'caption=Икона');
    }
    
    
    /**
     * Създаваме кофа
     *
     * @param core_MVC $mvc
     * @param stdClass $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('productsGroupsIcons', 'Икони на продуктови групи', 'jpg,jpeg', '3MB', 'user', 'every_one');
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        //bp($rec);
    }
}