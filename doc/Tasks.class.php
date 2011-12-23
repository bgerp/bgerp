<?php
/**
 * Клас 'doc_Tasks' - Документ - задача
 */
class doc_Tasks extends core_Master
{   
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';	
	
    var $loadList = 'plg_Created, plg_RowTools, doc_Wrapper, plg_State, doc_DocumentPlg';

    var $title    = "Документ - задача";

    var $listFields = 'title, details, tools=Пулт';
    
    var $rowToolsField = 'tools';
    
    /**
     * Права
     */
    var $canRead = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,doc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,doc';    
    
     
    function description()
    {
    	$string = new type_Varchar();
    	$string->load('jqdatepick_Plugin');
        $string->suggestions = arr::make(tr(",Днес,
                                             Утре, 
                                             Началото на следващата седмица,
                                             Началото на следващия месец,
                                             Началото на следващата година"), TRUE);
    	
    	$this->FLD('title',        'varchar(64)', 'caption=Заглавие,mandatory');
    	$this->FLD('details',      'richtext',    'caption=Описание,mandatory');
    	$this->FLD('timeStart',    $string,       'caption=Време->Старт,mandatory');
    	$this->FLD('timeDuration', 'varchar(64)', 'caption=Време->Продължителност');
    	$this->FLD('timeEnd',      $string,       'caption=Време->Край');
    	$this->FLD('repeat',       'enum(none=няма,
    	                                 everyDay=всеки ден,
    	                                 everyTwoDays=на всеки 2 дена,
    	                                 everyThreeDays=на всеки 3 дена,
    	                                 everyWeek=всяка седмица,
    	                                 everyMonthy=всеки месец,
    	                                 everyThreeMonths=на всеки 3 месеца,
    	                                 everySixMonths=на всяко полугодие,
    	                                 everyYear=всяка година)', 'caption=Повторение,mandatory');
        $this->FLD('notification', 'enum(NULL=няма,
                                         0=на момента,
                                         -5=5 мин. предварително,
                                         -10=10 мин. предварително,
                                         -30=30 мин. предварително,
                                         -60=1 час предварително,
                                         -120=2 час предварително,
                                         -480=8 часа предварително,
                                         -1440=1 ден предварително,
                                         -2880=2 дни предварително,
                                         -4320=3 дни предварително,
                                         -10080=7 дни предварително)', 'caption=Известяване,mandatory');
        $this->FLD('priority',     'enum(low=нисък,
                                         normal=нормален,
                                         high=висок,
                                         critical=критичен)', 'caption=Приоритет');    	
    }

}