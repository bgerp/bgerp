<?php
define(WORDS_SAMPLE_CNT, 500);
define(TOTAL_RAID, 100000);

/**
 * Клас 'lang_Traner' - 
 *
 * @category   Experta Framework
 * @package    lang
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 3
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class lang_Traner extends core_Manager
{
    
    var $title = "Анализиране на текст";

    var $loadList = 'plg_Created';
    
    var $stat;
    
    var $txt;
    
   
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('lg', 'varchar(2)', 'caption=Език');
        $this->FLD('sample', 'text(100000)', 'caption=Текст');
    }


    /**
     *  @todo Чака за документация...
     */
    function act_Analyze()
    {
    	mb_internal_encoding("UTF-8");
    	
   		$query = lang_Traner::getQuery();
 		

		while ($rec = $query->fetch()) {
			$pattern = '/[^\p{L}]+/u';
			$sample = preg_replace($pattern, " ", $rec->sample);
			$sample = mb_strtolower($sample);
			
 			// $sample = preg_replace('/[^\w\d\p{L}]/u', " ", $rec->sample);
			// $sample = preg_replace('/_/u', " ", $sample);
			 $txt = explode(" ", mb_strtolower($sample));
			 
			 	foreach ($txt as  $p){
			 		$br = mb_strlen($p);
			 		if(($br == 2) || ($br == 3) || ($br == 4)){
			 			
			 			$stat[$rec->lg][$p]++;
			 			
			 		}elseif ($br >= 5){
			 			$a = mb_substr($p,0,4);
			 			$b = mb_substr($p,$br-4);
			 			$stat[$rec->lg][$a]++;
			 			$stat[$rec->lg][$b]++;
			 			
			 		}
			 		
			 	}	
			}
			    
				  
			 
			foreach ($stat as $lg => $sArr){
				
				array_multisort(&$sArr,  SORT_DESC); 
				
				$total = 0;
				$nm = 0;
				
				foreach ($sArr as $sWord => $cnt){
					 
					if ($nm < WORDS_SAMPLE_CNT){
					    $total += $cnt;
					    $nm++;
					} else {
						unset($sArr[$sWord]);
						 $total += $cnt;
					}
					
				}	
				
				
				
				foreach($sArr as $sWord => $cnt){
						$stat1[$lg][$sWord] = round( ($cnt / $total) * TOTAL_RAID) ;
				}
				
				
					
				
		}	
		
		bp($stat1);
    }
}