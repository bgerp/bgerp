<?php



/**
 * Меню
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens_Overviews extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Мениджър изгледи";
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 6;
    
    
    /**
     * Страница от менюто
     */
    var $pageMenu = "Наблюдение";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, plg_Created, plg_Sorting, 
                             sens_Wrapper,
                             OverviewDetails=sens_OverviewDetails 
                           ';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title=Заглавие';

    
    /**
     * Детайла, на модела
     */
    var $details = 'sens_OverviewDetails';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,admin,sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,admin,sens';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Изглед');
        $this->FLD('panWidth', 'int', 'caption=Широчина');
        $this->FLD('panHeight', 'int', 'caption=Височина');
    }
    
    
    /**
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
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
    function renderSingleLayout_(&$data)
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
            
            if (count($data->singleFields)) {
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
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->query->orderBy('#title', 'ASC');
    }
}