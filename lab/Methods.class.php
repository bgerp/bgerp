<?php

/**
 * Мениджър за методите в лабораторията
 */
class lab_Methods extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Методи за лабораторни тестове";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_State,
                             Params=lab_Parameters, plg_RowTools, plg_Printing, 
                             lab_Wrapper, plg_Sorting, fileman_Files';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'name,equipment,paramId,
                             minVal,maxVal,tools=Пулт';
    
    
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
     *  Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(255)', 'caption=Наименование');
        $this->FLD('equipment', 'varchar(255)', 'caption=Оборудване,notSorting');
        $this->FLD('paramId', 'key(mvc=lab_Parameters,select=name,allowEmpty,remember)', 'caption=Параметър,notSorting');
        $this->FLD('description', 'richtext', 'caption=Описание,notSorting');
        $this->FLD('minVal', 'double(decimals=2)', 'caption=Възможни стойности->Минимална,notSorting');
        $this->FLD('maxVal', 'double(decimals=2)', 'caption=Възможни стойности->Максимална,notSorting');
    }
    
    
    /**
     *  Линк към single
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = Ht::createLink($row->name, array($mvc, 'single', $rec->id));
    }
    
    
    /**
     * Шаблон за детайлите на метода
     *
     * @return core_Et $tpl
     */
    function renderSingleLayout_($data)
    {
        $id = Request::get('id', 'int');
        
        $recMethods = new stdClass;
        
        $query = $this->getQuery();
        
        while($rec = $query->fetch("#id = {$id}")) {
            $recMethods = $rec;
        }
        
        // BEGIN Подготвяме шаблона и правим субституция на всички параметри
        $tpl = cls::get('lab_tpl_ViewSingleLayoutMethods', array('recMethods' => $recMethods));
        
        /*
        foreach ($recMethods as $k => $v) {
           if (is_string($v)) {
                  $recMethods->$k = type_Varchar::toVerbal($v);    
           }    
        }        
        
        foreach ($recMethods as $k => $v) {
           $viewSingle->replace($recMethods->{$k}, $k);
        }
        */
        // END Подготвяме шаблона и правим субституция на всички параметри         
        
        return $tpl;
    }
}