<?php



/**
 * Клас 'cams_tpl_ViewSingleLayoutOverview' -
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class cams_tpl_ViewSingleLayoutOverview extends core_ET
{
    
    
    /**
     * Инициализиране на обекта
     */
    public function init($params = array())
    {        
        // Prepare 'act_Overview' params
        $overviewId = Request::get('id', 'int');
        $panWidth = $params['data']->rec->panWidth;
        $panHeight = $params['data']->rec->panHeight;
        
        // Prepare $overviewDetailsArr
        $OverviewDetails = cls::get('cams_OverviewDetails');
        $queryOverviewDetails = $OverviewDetails->getQuery();
        $where = "#overviewId = {$overviewId}";
        
        while($recOverviewDetails = $queryOverviewDetails->fetch($where)) {
            $overviewDetailsArr[] = $recOverviewDetails;
        }
        
        // END Prepare $overviewDetailsArr
        
        // If $overviewDetailsArr has elements display them
        if (count($overviewDetailsArr)) {
            $htmlBlock .= new ET("<div style='float: left;
                                       padding: 0px; 
                                       border: solid 1px red; 
                                       background: #f0f0f0; 
                                       width: " . $panWidth . "px; 
                                       height: " . $panHeight . "px;
                                       overflow: hidden;
                                       position: relative;'>");
            
            // Loop for every blocks
            foreach ($overviewDetailsArr as $v) {
                $sensorHtml = NULL;
                $detailsHtml = NULL;
                
                // block open tag
                $htmlBlock .= "<div style='border: solid 1px " . $v->blockBorderColor . ";
                                     position: absolute;
                                     width: " . $v->blockWidth . "px;
                                     height: " . $v->blockHeight . "px;
                                     top: " . $v->blockPosTop . "px;
                                     left: " . $v->blockPosLeft . "px;            
                                     background: " . $v->blockBackground . ";'>";
                
                // block title
                $htmlBlock .= "<div style='display: " . $v->showBlockTitle . ";
                                     float: left;
                                     width: " . $v->blockWidth . "px;
                                     height: 30px;
                                     line-height: 30px;
                                     color: #ffffff;
                                     background: " . $v->blockTitleBackground . ";
                                     text-align: center;'>" . $v->blockTitle . "</div>";
                
                // block content
                $htmlBlock .= "<div style='float: left; 
                                     clear: left;
                                     width: " . ($v->blockWidth - 10) . "px;";
                
                if ($v->showBlockTitle == 'block') {
                    $blockHeight = $v->blockHeight - 40;
                }
                
                if ($v->showBlockTitle == 'none') {
                    $blockHeight = $v->blockHeight - 10;
                }
                $htmlBlock .= "height: " . $blockHeight . "px;       
                               padding: 5px;
                               line-height: 15px;'>";
                
                // Prepare content
                // Вземаме текущите показания на сензора (всички параметри)
                $Sensors = cls::get('cams_Sensors');
                $sensorDriver = $Sensors->fetchField($v->sensorId, 'driver');
                $sensorParams = $Sensors->fetchField($v->sensorId, 'params');
                
                $driver = cls::getInterface('sens_DriverIntf', $sensorDriver, $sensorParams);
                
                // END Prepare content          
                
                // Add content from sensor
                $sensorHtml = "<div style='clear: left; 
                                            margin: 0 auto;
                                            background: #ffdd99;
                                            padding: 20px;
                                            width: " . ($v->blockWidth - 50) . "px'>";
                $sensorHtml .= $driver->renderHtml();
                $sensorHtml .= "</div>";
                
                // END Add content from sensor    
                
                // Add content from overview details
                $detailsHtml .= "<div style='float: left;
                                            clear: left;
                                            margin-top: 20px;'>" . $v->blockContent . "
                                        </div>";
                
                // END Add content from overview details
                
                // Add to template
                $htmlBlock .= $sensorHtml . $detailsHtml;
                
                $htmlBlock .= "</div>";
                
                // END block content      
                
                // block close tag
                $htmlBlock .= "</div>";
            }
            
            // END Loop for every blocks
            
            $htmlBlock .= "</div><div style='clear: both;'></div>";
        }
        
        // If $panDetailsArr has elements display them
        
        // If $panDetails has no elements
        else {
            $htmlBlock = new ET("Няма дефинирани обекти за този изглед.");
        }
        
        // END If $panDetails has no elements
        
        return parent::core_ET($htmlBlock);
    }
}