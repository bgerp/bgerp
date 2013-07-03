<?php



/**
 * Клас 'salecond_PaymentMethods' -
 *
 *
 * @category  bgerp
 * @package   salecond
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class salecond_PaymentMethods extends core_Master
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, salecond_Wrapper, plg_State';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, name';
    
    
    /**
     * Заглавие
     */
    var $title = 'Начини на плащане';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    var $oldClassName = 'bank_PaymentMethods';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo, salecond';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'ceo, salecond';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'ceo, salecond';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'ceo, salecond';
    
    
    /**
     * Шаблон за единичен изглед
     */
    var $singleLayoutFile = "salecond/tpl/SinglePaymentMethod.shtml";
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име, mandatory');
        $this->FLD('description', 'varchar', 'caption=Описание, mandatory,width=100%');
        
        $this->FLD('payAdvanceShare', 'percent(min=0,max=1)', 'caption=Авансово плащане->Дял,width=7em,hint=Процент');
        $this->FLD('payAdvanceTerm', 'time(uom=days,suggestions=веднага|3 дни|5 дни|7 дни)', 'caption=Авансово плащане->Срок,width=7em');
        
        $this->FLD('payBeforeReceiveShare', 'percent(min=0,max=1)', 'caption=Плащане преди получаване->Дял,width=7em,hint=Процент');
        $this->FLD('payBeforeReceiveTerm', 'time(uom=days,suggestions=веднага|3 дни|5 дни|10 дни|15 дни|30 дни|45 дни)', 'caption=Плащане преди получаване->Срок,width=7em');
        
        $this->FLD('payBeforeInvShare', 'percent(min=0,max=1)', 'caption=Плащане след фактуриране->Дял,width=7em,hint=Процент');
        $this->FLD('payBeforeInvTerm', 'time(uom=days,suggestions=веднага|15 дни|30 дни|60 дни)', 'caption=Плащане след фактуриране->Срок,width=7em');
        
        $this->FLD('state', 'enum(draft,closed)', 'caption=Състояние, input=none');
        $this->setDbUnique('name');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
	    	$rec = &$form->rec;
	    	$total = 0;
	    	$termArr = array('payAdvanceTerm', 'payBeforeReceiveTerm', 'payBeforeInvTerm');
	    	$sharesArr = array('payAdvanceShare', 'payBeforeReceiveShare', 'payBeforeInvShare');
	    	foreach($sharesArr as $i => $share){
	    		if(empty($rec->$share) && isset($rec->$termArr[$i])){
	    			$form->setError($share, 'Полето неможе да е празно, ако е въведен срок !');
	    		} else {
	    			$total += $rec->$share;
	    		}
	    	}
	    	
	    	if($total != 1){
	    		$form->setError('payAdvanceShare,payBeforeReceiveShare,payBeforeInvShare', 'Въведените проценти трябва да правят 100 %');
	    	}
    	}
    }
    
    
    /**
     * Начин на плащане по подразбиране според клиента
     * 
     * @see doc_ContragentDataIntf
     * @param stdClass $contragentInfo
     * @return int key(mvc=salecond_PaymentMethods) 
     */
    public static function getDefault($contragentInfo)
    {
        // @TODO
        return static::fetchField("#name = 'COD'", 'id'); // за тест
    }
    
    
    /**
     * Сортиране по name
     */
    static function on_BeforePrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('#name');
    }
    
    
	/**
     * Зареждане на началните празници в базата данни
     */
    static function loadData()
    {
    	$csvFile = __DIR__ . "/csv/PaymentMethods.csv";
        $created = $updated = 0;
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
         	while (($csvRow = fgetcsv($handle, 2000, ",", '"', '\\')) !== FALSE) {
                $rec = new stdClass();
              	$rec->name= $csvRow[0];
               	$rec->description= $csvRow[1];
               	if($rec->id = static::fetchField(array("#name = '[#1#]'", $rec->name), 'id')){
               		$updated++;
               	} else {
               		$created++;
               	}
                static::save($rec);
            }
            
            fclose($handle);
            
            $res .= "<li style='color:green;'>Създадени са {$created} начина за плащане, обновени са {$updated}</li>";
        } else {
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        return $res;
    }
}