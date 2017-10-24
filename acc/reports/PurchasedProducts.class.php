<?php



/**
 * Мениджър на отчети от Закупени продукти
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
class acc_reports_PurchasedProducts extends acc_reports_CorespondingImpl
{


	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'acc_PurchasedProductsReport';
	
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';


    /**
     * Заглавие
     */
    public $title = 'Счетоводство » Закупени продукти';


    /**
     * Дефолт сметка
     */
    public $baseAccountId = '321';


    /**
     * Кореспондент сметка
     */
    public $corespondentAccountId = '401';


    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterAddEmbeddedFields ($mvc, core_FieldSet &$form)
    {
     
        // Искаме да покажим оборотната ведомост за сметката на касите
        $baseAccId = acc_Accounts::getRecBySystemId($mvc->baseAccountId)->id;
        $form->setDefault('baseAccountId', $baseAccId);
        $form->setHidden('baseAccountId');
        
        $corespondentAccId = acc_Accounts::getRecBySystemId($mvc->corespondentAccountId)->id;
        $form->setDefault('corespondentAccountId', $corespondentAccId);
        $form->setHidden('corespondentAccountId');
        
        $form->setHidden("orderField");
        $form->setHidden("side");
    }
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {

        $storePositionId = acc_Lists::getPosition($mvc->baseAccountId, 'store_AccRegIntf');
        $form->setHidden("feat{$storePositionId}");
        foreach (range(4, 6) as $i) {
            $form->setHidden("feat{$i}");
        }

        $articlePositionId = acc_Lists::fetchField("#systemId = 'catProducts'",'id');
        $storePositionId = acc_Lists::getPosition($mvc->baseAccountId, 'store_AccRegIntf');
         
        foreach(range(1, 3) as $i) {
            if ($form->rec->{"list{$i}"} == $articlePositionId) {

                $form->setDefault("feat{$i}", "*");
                $form->setField("feat{$i}", 'caption=Артикул');
            }
        }
        
        $form->setDefault("orderField", "blAmount");
        $form->setDefault("side", "all");
        
        $accInfo = acc_Accounts::getAccountInfo($form->rec->corespondentAccountId);
        
        foreach (range(1, 3) as $i){
            if(isset($accInfo->groups[$i]) && $accInfo->groups[$i]->rec->systemId == "contractors"){
                $gr = $accInfo->groups[$i];
                $form->FLD("ent{$i}Id", "acc_type_Item(lists={$gr->rec->num}, allowEmpty, select=titleNum)", "caption=Контрагенти->Име,input");
            }
        }

        $contragentPositionId = acc_Lists::getPosition($mvc->baseAccountId, 'cat_ProductAccRegIntf');
         
        $form->setDefault("feat{$contragentPositionId}", "*");
        $form->setHidden("feat{$contragentPositionId}");
    }
    
    
    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('acc/tpl/PurchaseReportLayout.shtml');
    
        if($this->innerForm->compare == 'no') {
            $tpl->removeBlock('summeryNew');
        }
    
        return $tpl;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$data)
    {
        if ($data->contragent) {
            if ($data->compare != "no") {
                foreach ($data->recsAll as $id => $rec) {
                    $contragentId = strstr($id, "|", TRUE);
                
                    if ($data->contragent != $contragentId) {
                
                        unset($data->recsAll[$id]);
                    }
                }
            } else {
                foreach ($data->recs as $id => $rec) {
                    $contragentId = strstr($id, "|", TRUE);
                    
                    if ($data->contragent != $contragentId) {
        
                        unset($data->recs[$id]);
                    }
                }
            }
        }
        $data->summBottom = new stdClass();
        $data->summBottomVer = new stdClass();
        
        foreach ($data->recs as $r){
            $data->summBottom->quantity += $r->debitQuantity;
            $data->summBottom->amount += $r->debitAmount;
        }

        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $data->summBottomVer->quantity = $Double->toVerbal($r->debitQuantity);
        $data->summBottomVer->amount = $Double->toVerbal($r->debitAmount);
    }
    
    
    public static function on_AfterGetReportLayout($mvc, &$tpl)
    {

        $tpl->removeBlock('debit');
        $tpl->removeBlock('credit');
        $tpl->removeBlock('debitNew');
        $tpl->removeBlock('creditNew');
        $tpl->removeBlock('blName');

        if($mvc->innerForm->compare == 'no') {
            $tpl->removeBlock('summeryNew');
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
    	$explodeTitle = explode(" » ", $this->title);
    	
    	$title = tr("|{$explodeTitle[1]}|*");
    	 
    	return $title;
    }
    
    
    /**
     * Какви са полетата на таблицата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {

        $form = $mvc->innerForm;
        $newFields = array();

        if (!$data->contragent) {
            $data->listFields['item2'] = 'Контрагенти';
            $data->listFields['item3'] = 'Артикул';
            $data->listFields['blQuantity'] = 'Количество';
            $data->listFields['blAmount'] = 'Сума';
            $data->listFields['delta'] = 'Дял';
        } else {
            $data->listFields['item3'] = 'Артикул';
            $data->listFields['blQuantity'] = 'Количество';
            $data->listFields['blAmount'] = 'Сума';
            $data->listFields['delta'] = 'Дял';
        }

        // Кои полета ще се показват
        if($mvc->innerForm->compare != 'no'){
            $fromVerbalOld = dt::mysql2verbal($data->fromOld, 'd.m.Y');
    		$toVerbalOld = dt::mysql2verbal($data->toOld, 'd.m.Y');
    		$prefixOld = (string) $fromVerbalOld . " - " . $toVerbalOld;
    		
    		$fromVerbal = dt::mysql2verbal($form->from, 'd.m.Y');
    		$toVerbal = dt::mysql2verbal($form->to, 'd.m.Y');
    		$prefix = (string) $fromVerbal . " - " . $toVerbal;

    		if(!$data->contragent) {
    		    $fields = arr::make("id=№,item2=Контрагенти,item3=Артикул,blQuantity={$prefix}->Количество,blAmount={$prefix}->Сума,delta={$prefix}->Дял,blQuantityNew={$prefixOld}->Количество,blAmountNew={$prefixOld}->Сума,deltaNew={$prefixOld}->Дял", TRUE);
    		
    		} else {
    		    $fields = arr::make("id=№,item3=Артикул,blQuantity={$prefix}->Количество,blAmount={$prefix}->Сума,delta={$prefix}->Дял,blQuantityNew={$prefixOld}->Количество,blAmountNew={$prefixOld}->Сума,deltaNew={$prefixOld}->Дял", TRUE);
    		    
    		}
    		$data->listFields = $fields;
        }
        
        $articlePositionId = acc_Lists::fetchField("#systemId = 'catProducts'",'id');
        foreach(range(1, 3) as $i) {
            if ($form->{"list{$i}"} == $articlePositionId) {
                 if($form->{"feat{$i}"} != "*") {
                     unset($data->listFields['item3']);
                 }
            }
        }
         
        unset($data->listFields['debitQuantity'],$data->listFields['debitAmount'],$data->listFields['creditQuantity'],$data->listFields['creditAmount']);
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
        $tpl = $this->getReportLayout();
    
        $explodeTitle = explode(" » ", $this->title);
    
        $title = tr("|{$explodeTitle[1]}|*");
    
        $tpl->replace($title, 'TITLE');
        $this->prependStaticForm($tpl, 'FORM');
        
        $tpl->replace(acc_Periods::getBaseCurrencyCode(), 'baseCurrencyCode');
        
        $tpl->placeObject($data->row);
    
        $tableMvc = new core_Mvc;

        $tableMvc->FLD('blQuantity', 'double', 'tdClass=accCell');
        $tableMvc->FLD('blAmount', 'double', 'tdClass=accCell');
        $tableMvc->FLD('delta', 'percent', 'tdClass=accCell');
    
        $table = cls::get('core_TableView', array('mvc' => $tableMvc));

        $tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

        $data->summBottomVer->colspan = count($data->listFields);
        if($data->summBottom ){ 
            $data->summBottomVer->colspan -= 3;
            if($data->summBottomVer->colspan != 0 && count($data->rows)){
                $afterRow = new core_ET("<tr style = 'background-color: #eee'><td colspan=[#colspan#]><b>" . tr('ОБЩО') . "</b></td><td style='text-align:right'><b>[#quantity#]</b></td><td style='text-align:right'><b>[#amount#]</b></td><td style='text-align:right'></td></tr>");
            }
        }
    
        if($afterRow){
            $afterRow->placeObject($data->summBottomVer);
            $tpl->append($afterRow, 'ROW_AFTER');
        }
    
        if($data->pager){
            $tpl->append($data->pager->getHtml(), 'PAGER_BOTTOM');
            $tpl->append($data->pager->getHtml(), 'PAGER_TOP');
        }
    
        $embedderTpl->append($tpl, 'data');
    }
}