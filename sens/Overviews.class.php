<?php

/**
 * Меню
 */
class sens_Overviews extends core_Master
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Менъджър изгледи";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listItemsPerPage = 6;
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Наблюдение";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, plg_Sorting, 
                             sens_Wrapper,
                             OverviewDetails=sens_OverviewDetails,
                             Locations=common_Locations';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, locationId, title=Заглавие, tools=Пулт';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $details = array('sens_OverviewDetails');
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Изглед');
        $this->FLD('locationId', 'key(mvc=common_Locations, select=title)', 'caption=Локация');
        $this->FLD('panWidth', 'int', 'caption=Широчина');
        $this->FLD('panHeight', 'int', 'caption=Височина');
    }
    
    
    /**
     *
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->title = Ht::createLink($row->title, array($this, 'single', $rec->id));
    }
    
    
    /**
     * Показва изглед
     *
     * @return core_ET
     */
    function act_Overview()
    {
        // Prepare 'act_Overview' params
        $overviewId = Request::get('id', 'int');
        $panWidth = $this->fetchField($overviewId, 'panWidth');
        $panHeight = $this->fetchField($overviewId, 'panHeight');
        
        // Prepare $overviewDetailsArr
        $queryOverviewDetails = $this->OverviewDetails->getQuery();
        $where = "#overviewId = {$overviewId}";
        
        while($recOverviewDetails = $queryOverviewDetails->fetch($where)) {
            $overviewDetailsArr[] = $recOverviewDetails;
        }
        // END Prepare $overviewDetailsArr
        
        // If $overviewDetailsArr has elements display them
        if (count($overviewDetailsArr)) {
            $tpl = new ET("<div style='float: left;
                                       padding: 0px; 
                                       border: solid 1px red; 
                                       background: #f0f0f0; 
                                       width: " . $panWidth . "px; 
                                       height: " . $panHeight . "px; 
                                       position: relative;'>");
            // Loop for every blocks
            foreach ($overviewDetailsArr as $v) {
                // block open tag
                $tpl .= "<div style='border: solid 1px green;
                                     position: absolute;
                                     width: " . $v->blockWidth . "px;
                                     height: " . $v->blockHeight . "px;
                                     top: " . $v->blockPosTop . "px;
                                     left: " . $v->blockPosLeft . "px;            
                                     background: " . $v->blockBackground . ";'>";
                
                // block title
                $tpl .= "<div style='float: left;
                                     width: " . $v->blockWidth . "px;
                                     height: 30px;
                                     line-height: 30px;
                                     color: #ffffff;
                                     background-image: -moz-linear-gradient(
                                                       center left,
                                                       rgb(26,75,120) 48%,
                                                       rgb(36,133,171) 74%);
                                     
                                     text-align: center;'>" . $v->blockTitle . "</div>";
                
                // block content
                $tpl .= "<div style='float: left; 
                                     clear: left;
                                     overflow-x: hidden;
                                     overflow-y: auto;
                                     width: " . ($v->blockWidth - 10) . "px;
                                     height: " . ($v->blockHeight - 40) . "px;                       
                                     padding: 5px;
                                     line-height: 15px;'>" . $v->blockContent . "</div>";
                
                // block close tag
                $tpl .= "</div>";
            }
            // END Loop for every blocks
            
            $tpl .= "</div><div style='clear: both;'></div>";
        }
        // If $panDetailsArr has elements display them
        
        // If $panDetails has no elements
        else {
            $tpl = new ET("Няма дефинирани обекти за този изглед.");
        }
        // END If $panDetails has no elements
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Шаблон за менюто
     *
     * @param stdClass $data
     * @return core_Et $tpl
     */
    function renderSingleLayout_($data)
    {
        $view = Request::get('view');
        
        if (!$view) $view = 'table';
        
        if ($view == 'table') {
            $data->toolbar->addBtn('Изглед', array('Ctr' => $this,
                'Act' => 'single',
                'id' => $data->rec->id,
                'view' => 'overview',
                'ret_url' => TRUE));
        }
        
        if ($view == 'overview') {
            $data->toolbar->removeBtn('btnEdit');
            $data->toolbar->addBtn('Таблица', array('Ctr' => $this,
                'Act' => 'single',
                'id' => $data->rec->id,
                'view' => 'table',
                'ret_url' => TRUE));
        }
        
        // Show 'table'
        if ($view == 'table') {
            
            if (count($data->singleFields) ) {
                $captionHiddenArr = array('Локация',
                    'Изглед',
                    'План',
                    'Създаване->На',
                    'Създаване->От',
                    '№');
                
                foreach($data->singleFields as $field => $caption) {
                    if (!in_array($caption, $captionHiddenArr)) {
                        $fieldsHtml .= "<tr><td>{$caption}</td><td>[#{$field}#]</td></tr>";
                    }
                }
            }
            
            return new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2><table class=listTable>{$fieldsHtml}</table><br/>[#DETAILS#]");
        }
        // END Show 'table'
        
        // Show 'overview'
        if ($view == 'overview') {
            
            $viewSingle = cls::get('sens_tpl_ViewSingleLayoutOverview', array('data' => $data));
            
            // return $viewSingle;
            return new ET("[#SingleToolbar#]<h2>[#SingleTitle#]</h2>{$viewSingle}");
        }
    }
    
    
    /**
     * Преди извличане на записите от БД филтър по date
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#locationId', 'ASC');
        $data->query->orderBy('#title', 'ASC');
    }
    
    
    /**
     * Смяна на заглавието на single
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleTitle($mvc, $data)
    {
        $locationId = $mvc->fetchField($data->rec->id, 'locationId');
        $locationName = $mvc->Locations->fetchField($locationId, 'title');
        
        $data->title = $locationName . ", " . $data->title;
    }
}