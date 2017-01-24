<?php 


/**
 * Мениджира детайлите на Overviews (Details)
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens_OverviewDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    var $title = "Детайли на Мениджър изгледи";
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Наблюдение";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools2, 
                          plg_Printing, sens_Wrapper, plg_Sorting, 
                          Overviews=sens_Overviews,plg_PrevAndNext, plg_SaveAndNew';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'overviewId';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'overviewId, blockTitle';

    
    /**
     * Активния таб в случай, че wrapper-а е таб контрол..
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