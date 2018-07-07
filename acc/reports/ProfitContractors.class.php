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

        $form->setDefault('orderField', 'blAmount');
        $form->setOptions('orderField', array('blAmount' => 'Сума'));
        
        $form->setField('from', 'refreshForm,silent');
        $form->setField('to', 'refreshForm,silent');
    }


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $form->setOptions('orderField', array('blAmount' => 'Сума'));
        
        foreach (range(1, 3) as $i) {
            $form->setHidden("feat{$i}");
        }

        $contragentPositionId = acc_Lists::getPosition($mvc->baseAccountId, 'crm_ContragentAccRegIntf');

        $form->setDefault("feat{$contragentPositionId}", '*');
        
        // Поставяме удобни опции за избор на период
        $query = acc_Periods::getQuery();
        $query->where("#state = 'closed'");
        $query->orderBy('#end', 'DESC');
        
        $yesterday = dt::verbal2mysql(dt::addDays(-1, dt::today()), false);
        $daybefore = dt::verbal2mysql(dt::addDays(-2, dt::today()), false);
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
        unset($data->listFields['debitQuantityCompare']);
        unset($data->listFields['debitAmountCompare']);
        unset($data->listFields['creditQuantityCompare']);
        unset($data->listFields['creditAmountCompare']);
        unset($data->listFields['blQuantityCompare']);
        
        // Кои полета ще се показват
        if ($mvc->innerForm->compare != 'no') {
            $fromVerbalOld = dt::mysql2verbal($data->fromOld, 'd.m.Y');
            $toVerbalOld = dt::mysql2verbal($data->toOld, 'd.m.Y');
            $prefixOld = (string) $fromVerbalOld . ' - ' . $toVerbalOld;
        
            $fromVerbal = dt::mysql2verbal($mvc->innerForm->from, 'd.m.Y');
            $toVerbal = dt::mysql2verbal($mvc->innerForm->to, 'd.m.Y');
            $prefix = (string) $fromVerbal . ' - ' . $toVerbal;
        
            $fields = arr::make("id=№,item1=Контрагенти,blAmount={$prefix}->Сума,delta={$prefix}->Дял,blAmountNew={$prefixOld}->Сума,deltaNew={$prefixOld}->Дял", true);
            $data->listFields = $fields;
        } else {
            $data->listFields['blAmount'] = str_replace('->Остатък', '', $data->listFields['blAmount']);
            $data->listFields['blAmount'] = str_replace('Остатък->', '', $data->listFields['blAmount']);
            $data->listFields['blAmountCompare'] = str_replace('->Остатък', '', $data->listFields['blAmountCompare']);
        }
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
        $activateOn = "{$this->innerForm->to} 23:59:59";

        return $activateOn;
    }
    
    
    /**
     * Връща дефолт заглавието на репорта
     */
    public function getReportTitle()
    {
        $explodeTitle = explode(' » ', $this->title);
        
        $title = tr("|{$explodeTitle[1]}|*");
         
        return $title;
    }


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    public function getExportFields()
    {
        $exportFields['item1'] = 'Контрагенти';
        $exportFields['blAmount'] = 'Сума';
        $exportFields['delta'] = 'Дял';

        return $exportFields;
    }
}
