<?php


/**
 * Мениджър на лични карти
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_IdCards extends core_Detail
{
	
	
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'personId';

    
    /**
     * Заглавие
     */
    public $title = 'Лични карти';

    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Лична карта';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'crm_Wrapper';
    
    
    /**
     * Текущ таб
     */
    public $currentTab = 'Лица';
    
    
    /**
     * Кой може да редактира
     */
    public $canEdit = 'powerUser';


    /**
     * Кой може да изтрива
     */
    public $canDelete = 'powerUser';
    
    
    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent,mandatory');
        $this->FLD('idCardNumber', 'varchar(16)', 'caption=Номер,mandatory');
        $this->FLD('idCardIssuedOn', 'date', 'caption=Издадена на');
        $this->FLD('idCardExpiredOn', 'date', 'caption=Валидна до');
        $this->FLD('idCardIssuedBy', 'varchar', 'caption=Издадена от');

        $this->setDbUnique('personId');
	}
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        $form = $data->form;
        
        // За да гарантираме релацията 1:1
        $form->rec->id = $mvc->fetchField("#personId = {$form->rec->personId}", 'id');

        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }

        $mvrQuery = bglocal_Mvr::getQuery();

        $mvrSug[''] = '';
        
        while($mvrRec = $mvrQuery->fetch()) {
            $mvrName = 'МВР - ';
            $mvrName .= $mvrRec->city;
            $mvrSug[$mvrName] = $mvrName;
        }

        $form->setSuggestions('idCardIssuedBy', $mvrSug);

        $data->form->title = 'Лична карта на|* ' .  $mvc->Master->getVerbal($data->masterRec, 'name');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'add' || $action == 'delete' || $action == 'edit') && isset($rec->personId)){
    		if(!crm_Persons::haveRightFor('edit', $rec->personId)){
    			$res = 'no_one';
    		}
    	}
    }
    

    /**
     * Подготовка за показване в указателя
     */
    public function prepareIdCard($data)
    {
    	$data->IdCard = new stdClass();
    	$rec = crm_ext_IdCards::fetch("#personId = {$data->masterId}");
    	if(!empty($rec)){
    		$data->IdCard->rec = $rec;
    		$row = crm_ext_IdCards::recToVerbal($data->IdCard->rec);
    		$data->IdCard->row = $row;
    	}
    }
    
    
    /**
     * Рендиране на показването в указателя
     */
    public function renderIdCard($data)
    {
    	$tpl = new core_ET("");
    
    	$tpl->append(tr('Лична карта'), 'idCardTitle');
    
    	$rec = $data->IdCard->rec;
    	$url = array();
    
    	if ($rec->idCardNumber || $rec->idCardIssuedOn || $rec->idCardExpiredOn || $rec->idCardIssuedBy) {
    		$idCardTpl = new ET(getFileContent('crm/tpl/IdCard.shtml'));
    		$idCardTpl->placeObject($data->IdCard->row);
    			
    		if(crm_ext_IdCards::haveRightFor('edit', $rec->id)){
    			$url = array('crm_ext_IdCards', 'edit', $rec->id, 'ret_url' => TRUE);
    			$efIcon = 'img/16/edit.png';
    		}
    	} else {
    		$idCardTpl = new ET(tr('Няма данни'));
    			
    		if(crm_ext_IdCards::haveRightFor('add', (object)array('personId' => $data->masterId))){
    			$url = array('crm_ext_IdCards', 'add', 'personId' => $data->masterId, 'ret_url' => TRUE);
    			$efIcon = 'img/16/add.png';
    		}
    	}
    
    	if(count($url)){
    		$link = ht::createLink('', $url, FALSE, "title=Промяна на лична карта,ef_icon={$efIcon}");
    		$tpl->append($link, 'idCardTitle');
    	}
    
    	if(isset($rec->id) && crm_ext_IdCards::haveRightFor('delete', $rec->id)){
    		$delUrl = array('crm_ext_IdCards', 'delete', 'id' => $rec->id, 'ret_url' => TRUE);
    		$link = ht::createLink('', $delUrl, 'Наистина ли искате да изтриете личната карта на лицето', "title=Изтриване на данни за лична карта,ef_icon=img/16/delete.png");
    		$tpl->append($link, 'idCardTitle');
    	}
    
    
    	$tpl->append($idCardTpl);
    
    	return $tpl;
    }
}