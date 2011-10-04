<?php

/**
 * Мениджър за параметрите в лабораторията
 */
class lab_Parameters extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Параметри за лабораторни тестове";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_State2,
                             plg_RowTools, plg_Printing, lab_Wrapper,
                             plg_Sorting, fileman_Files';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,tools=Пулт,name,type,dimention,
                             precision,description,state';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Права
     */
    var $canWrite = 'lab,admin';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canRead = 'lab,admin';
    
    
    /**
     *
     */
    var $singleLayoutFile = 'lab/tpl/SingleLayoutParameters.thtml';

    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Параметър');
        $this->FLD('type', 'enum(number=Числов,bool=Да/Не,text=Текстов)', 'caption=Тип');
        $this->FLD('dimention', 'varchar(255)', 'caption=Размерност,notSorting');
        $this->FLD('precision', 'int', 'caption=Прецизност,notSorting');
        $this->FLD('description', 'richtext', 'caption=Описание,notSorting');
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Сортиране на записите по name
        $data->query->orderBy('name=ASC');
    }
    
    
    /**
     *  Линкове към single
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = Ht::createLink($row->name, array($mvc, 'single', $rec->id));
    }
    
}