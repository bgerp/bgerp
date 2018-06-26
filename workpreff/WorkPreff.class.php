<?php



/**
 * Избор на данни на Форма за CV
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Детайли на Форма за CV
 */


class workpreff_WorkPreff extends core_Master
{

    public $title = "Избор";

    public $loadList = 'plg_RowTools2,plg_Sorting, hr_Wrapper';
    
    /**
     * Детайла, на модела
     */
    public $details = 'workpreff_WorkPreffDetails';

    function description()

    {

        $this->FLD('name', 'varchar', 'caption=Предпочитания->Възможности,class=contactData,mandatory,remember=info,silent');
        $this->FLD('type', 'enum(set=Фиксиране, enum=Избор)', 'notNull,caption=Тип на избора,maxRadio=2,after=name');
        $this->FLD('typeOfPosition', 'set(adm=Администрация,man=Производство, log=Логистика,sall=Продажби)', 'caption=Тип на позицията,mandatory');
      

    }
   

  public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	
    	$form = $data->form;
    	
 
   
    }

    public static function on_AfterInputeditForm($mvc, &$form)
    {
		 
      

      


    }
    


    /**
     * Връща масив с опции за възможен избор
     * @return array
     */
    public static function getOptionsForChoice()
    {
    	
    	$parts=array();
       
    	$detQuery = workpreff_WorkPreffDetails::getQuery();
    	
    	while ($detail = $detQuery->fetch()){
    		
    		$detArr[$detail->id]=$detail;
    	}
    
    	$query = self::getQuery();

        while ($rec = $query->fetch()){
        	
        $typeOfPosition = explode(',', $rec->typeOfPosition);
        
        
        if (is_array($detArr)){
        	
		  foreach ($detArr as $v){
		  
		  	if($rec->id == $v->choiceId){
		  	
		  		$parts[$v->id]=$v->name;
		  		
		  	}
		  	
		  }
        }
            $workPreffOptions[$rec->id] = (object)array(

                'id' => $rec->id,
                'type' => $rec->type,
                'name' => $rec->name,
            	'parts' => $parts,	
                'count' => count($parts),
            	'typeOfPosition' =>$typeOfPosition,

            );
            
            $parts=array();
        }

        if (!$workPreffOptions){

            $workPreffOptions = array();

            return $workPreffOptions;

        }else{return $workPreffOptions;}

    }

}