<?php



/**
 * Мениджър на отчети от Произведени продукти
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
class acc_reports_ManufacturedProducts extends acc_reports_CorespondingImpl
{


    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'acc_ManufacturedProductsReport';
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Произведени продукти';


    /**
     * Дефолт сметка
     */
    public $baseAccountId = '321';


    /**
     * Кореспондент сметка
     */
    public $corespondentAccountId = '611';


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
        
        $today = dt::today();
         
        $form->setDefault('from', date('Y-m-01', strtotime('-1 months', dt::mysql2timestamp(dt::now()))));
        $form->setDefault('to', dt::addDays(-1, $today));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
        $articlePositionId = acc_Lists::fetchField("#systemId = 'catProducts'", 'id');
        $storePositionId = acc_Lists::getPosition($mvc->baseAccountId, 'store_AccRegIntf');
        
        foreach (range(1, 3) as $i) {
            if ($form->rec->{"list{$i}"} == $articlePositionId) {
                $form->setDefault("feat{$i}", '*');
            }
            
            $form->setDefault("feat{$storePositionId}", '*');
            $form->setHidden("feat{$i}");
            $form->setHidden("grouping{$i}");
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
}
