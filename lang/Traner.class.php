<?php
define(WORDS_SAMPLE_CNT, 150);
define(AVRG_WORD_SCORE, 10);

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
        $this->FLD('sample', 'text(1000000)', 'caption=Текст');
    }


    /**
     *  @todo Чака за документация...
     */
    function act_Analyze()
    {
    	mb_internal_encoding("UTF-8");
    	
   		$query = $this->getQuery();

        // Създава масив с първи индекс езиците, и втори индекс - триграмите, като
        // стойностите са броя на срещанията на триграмите в съответния език
		while ($rec = $query->fetch()) {
            
            $words = lang_Encoding::makeLgArray($rec->sample);

            foreach($words as $w => $cnt) {
                $stat[$rec->lg][$w] += $cnt;
            }
        }
			
		// Преброява в колко различни езика се срещат най-изплзваните 
        // (WORDS_SAMPLE_CNT/2) думи за всеки език 
		foreach ($stat as $lg => $arr) {
            
            arsort($stat[$lg]);
            
            $i = 0;
            
			foreach ($stat[$lg] as $word => $times) {
                if($i++ < (WORDS_SAMPLE_CNT/2)) {
			        $matchAllLang[$word] += 1;
                }
 			}  
		}


        foreach ($stat as $lg => $sArr){

			foreach ($sArr as $word => $times) {
                if($matchAllLang[$word]) {
				   $sArr[$word] = $times/(pow($matchAllLang[$word], 2));
                }
			}

            // Сортира думите за текущия език, от най-често към по-рядко срещаните
			arsort($sArr); 

			// Отделя зададеното количество мострени думи и им изчислява общия сбор
			$nm = 0; $total = 0;
			foreach ($sArr as $sWord => $cnt){
				if ($nm < WORDS_SAMPLE_CNT){
				    $total += $cnt;
				    $nm++;
				} else {
					unset($sArr[$sWord]);
				}
			}	

			foreach($sArr as $sWord => $cnt){
					$statFinal[$lg][$sWord] = round( ($cnt / $total) * AVRG_WORD_SCORE * WORDS_SAMPLE_CNT);
			}
		}

		$code = base64_encode(gzcompress(serialize($statFinal)));
        
        $code = implode("<br>", str_split($code, 80));

		return  "<h1>Код за разпознаване на езици</h1><pre style='font-size:0.6em'>$code</pre>";
    }
    
    
    /**
     * Ако има различия в откиването на езика със зададения език оцветява реда в червен
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$showOnlyWrong = (Request::get('PerPage') == 1000);
    	//if($rec->lg == 'bg') echo "<li> $rec->sample";
    	$lg = lang_Encoding::getLgRates($rec->sample);
    	
    	if (is_array($lg)) {
    		foreach ($lg as $l => $r){
    			$row->lg .= "<br> $l => $r";
    		}
    		$key = arr::getMaxValueKey($lg);
        }
	    
        if ($key != $rec->lg) {
	        $row->ROW_ATTR['style'] .= 'background-color:red; ';
	        $row->ROW_ATTR['title'] .= $key;
	        self::$count++;
        } else {
            if ($showOnlyWrong) {
	            $row->sample = NULL;
     			$row->ROW_ATTR['style'] .= 'display: none;';
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
