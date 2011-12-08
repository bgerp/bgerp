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
    
    var $singleTitle = "Текст";

    var $loadList = 'plg_Created,plg_RowTools';
    
    var $stat;
    
    var $txt;
    
	static $count = 0;
    
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
			
			//if($rec->lg == 'it') echo "<li>" . mb_strtolower($sample);
 			// $sample = preg_replace('/[^\w\d\p{L}]/u', " ", $rec->sample);
			// $sample = preg_replace('/_/u', " ", $sample);
			 $txt = explode(" ", mb_strtolower($sample));
			 
			 	foreach ($txt as  $p){
			 		$br = mb_strlen($p);
			 		if(($br == 2) || ($br == 3)){
			 			
			 			$stat[$rec->lg][$p]++;
			 			
			 		}elseif ($br >= 4){
			 			$a = '#' . mb_substr($p, 0, 3);
			 			$b = '@' . mb_substr($p, $br-3);
			 			$stat[$rec->lg][$a] += 1;
			 			$stat[$rec->lg][$b] += 1;
			 			
			 		}
			 		
			 	}	
			}
			
			//Опеделя коя дума, колко пъти се среща във всички езици
			foreach ($stat as $lg => $arr) {
				foreach ($arr as $word => $times) {
					$matchAllLang[$word]++;
				}
			}
			 
			foreach ($stat as $lg => $sArr){
				echo "<li> $lg ->".count($sArr);
				foreach ($sArr as $word => $times) {
					$sArr[$word] = $times/(pow($matchAllLang[$word],5.5));
				}
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
				
				
					
				
		}	//bp($stat1);
		$code = base64_encode(gzcompress(serialize($stat1)));
		bp($code);
    }
    
    
    /**
     * Ако има различия в откиването на езика със зададения език оцветява реда в червен
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$showOnlyWrong = Request::get('showWrong');
    	
    	$lg = lang_Encoding::getLgRates($rec->sample);
    	
    	if (is_array($lg)) {
    		foreach ($lg as $l => $r){
    			$row->lg .= "<br> $l => $r";
    		}
    		$key = arr::getMaxValueKey($lg);
	    	if ($key != $rec->lg) {
	    		$row->ROW_ATTR['style'] .= 'background-color:red; ';
	    		$row->ROW_ATTR['title'] .= $key;
	    		self::$count++;
	    	} else {
		    	if ($showOnlyWrong) {
	    			$row->ROW_ATTR['style'] .= 'display: none;';
	    		}
	    	}
    	}
    	
    }
    
    
    /**
     * Показва броя на различните езици, които се детектват
     */
    function on_BeforeRenderListTitle($mvc, $res, &$data)
    {
    	$data->title .= ' <br />	Брой различия: ' . self::$count;
    }
    
    
    
    
    
}
