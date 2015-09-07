<?php



/**
 * Мениджър на отчети от Печалба от продажби по клиенти
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_reports_ProfitContractors extends acc_reports_CorespondingImpl
{


	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_ProfitContractorsReport';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Печалба по клиенти';

    
    /**
     * Дефолт сметка
     */
    public $baseAccountId = '700';
    
    
    /**
     * Кореспондент сметка
     */
    public $corespondentAccountId = '123';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields($mvc, core_FieldSet &$form)
    {

       // Искаме да покажим оборотната ведомост за сметката на касите
        $baseAccId = acc_Accounts::getRecBySystemId($mvc->baseAccountId)->id;
        $form->setDefault('baseAccountId', $baseAccId);
        $form->setHidden('baseAccountId');
        
        $corespondentAccId = acc_Accounts::getRecBySystemId($mvc->corespondentAccountId)->id;
        $form->setDefault('corespondentAccountId', $corespondentAccId);
        $form->setHidden('corespondentAccountId');
        
        $form->setDefault('side', 'all');
        $form->setHidden('side');
        
        $form->setDefault('orderBy', 'DESC');
        $form->setHidden('orderBy');
        
        $form->setDefault('orderField', 'blAmount');
        $form->setHidden('orderField');
        
        $form->setField('from','refreshForm,silent');
        $form->setField('to','refreshForm,silent');

    }


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {

        foreach (range(1, 3) as $i) {

            $form->setHidden("feat{$i}");

        }

        $contragentPositionId = acc_Lists::getPosition($mvc->baseAccountId, 'crm_ContragentAccRegIntf');

        $form->setDefault("feat{$contragentPositionId}", "*");   
        
        // Поставяме удобни опции за избор на период
        $query = acc_Periods::getQuery();
        $query->where("#state = 'closed'");
        $query->orderBy("#end", "DESC");
        
        $yesterday = dt::verbal2mysql(dt::addDays(-1, dt::today()), FALSE);
        $daybefore = dt::verbal2mysql(dt::addDays(-2, dt::today()), FALSE);
        $optionsFrom = $optionsTo = array();
        $optionsFrom[dt::today()] = 'Днес';
        $optionsFrom[$yesterday] = 'Вчера';
        $optionsFrom[$daybefore] = 'Завчера';
        $optionsTo[dt::today()] = 'Днес';
        $optionsTo[$yesterday] = 'Вчера';
        $optionsTo[$daybefore] = 'Завчера';
        
        while ($op = $query->fetch()) {
        	$optionsFrom[$op->start] = $op->title;
        	$optionsTo[$op->end] = $op->title;
        }
        
        $form->setSuggestions('from', array('' => '') + $optionsFrom);
        $form->setSuggestions('to', array('' => '') + $optionsTo);
    }
    

    public static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {

        unset($data->listFields['debitQuantity']);
        unset($data->listFields['debitAmount']);
        unset($data->listFields['creditQuantity']);
        unset($data->listFields['creditAmount']);
        unset($data->listFields['blQuantity']);

        $data->listFields['blAmount'] = "Сума";
    }


    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
        $innerState = &$this->innerState;

        unset($innerState->recs);
    }


    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
    	
    	$today = dt::today();
    	$activateOn = "{$today} 23:59:59";

        return $activateOn;
    }
    
    
    /**
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {

    	$explodeTitle = explode(" » ", $this->title);
    	
    	$title = tr("|{$explodeTitle[1]}|*");
    	 
    	return $title;
    }


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    public function getExportFields ()
    {

        $exportFields['item1']  = "Контрагенти";
        $exportFields['blAmount']  = "Сума";
        $exportFields['delta']  = "Дял";

        return $exportFields;
    }

}