<?php 

/**
 * Менаджира детайлите на Overviews (Details)
 */
class sens_OverviewDetails extends core_Detail
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Детайли на Менъджър изгледи";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Наблюдение";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, 
                          plg_Printing, sens_Wrapper, plg_Sorting, 
                          Overviews=sens_Overviews';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $masterKey = 'overviewId';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'overviewId, blockTitle, tools=Ред';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $tabName = "sens_Overviews";
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('overviewId', 'key(mvc=sens_Overviews)', 'caption=Към изглед');
        $this->FLD('blockTitle', 'varchar(255)', 'caption=Заглавие на блока');
        $this->FLD('showBlockTitle', 'enum(block=Да,none=Не)', 'caption=Заглавие визуализация');
        $this->FLD('blockWidth', 'enum(100, 200, 300, 400)', 'caption=Размери->Широчина');
        $this->FLD('blockHeight', 'enum(100, 200, 300, 400)', 'caption=Размери->Височина');
        $this->FLD('blockPosTop', 'varchar(255)', 'caption=Позициониране->Top');
        $this->FLD('blockPosLeft', 'varchar(255)', 'caption=Позициониране->Left');
        
        // Prepare color type
        $color_Type = new type_Varchar();
        $color_Type->load('jqcolorpicker_Plugin');
        
        $this->FLD('blockBackground', $color_Type, 'caption=Цветове->Фон на блока ');
        $this->FLD('blockTitleBackground', $color_Type, 'caption=Цветове->Фон на заглавието');
        $this->FLD('blockBorderColor', $color_Type, 'caption=Цветове->Цвят на контура');
        
        $this->FLD('sensorId', 'key(mvc=sens_Sensors, select=title)', 'caption=Сензор, allowEmpty');
        $this->FLD('refreshTime', 'int', 'caption=Време за обновяване, allowEmpty');
        
        $this->FLD('blockContent', 'text', 'caption=Съдържание');
    }
}