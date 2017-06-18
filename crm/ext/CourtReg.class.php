<?php


/**
 * Мениджър на съдебни регистрации
 *
 * @category  bgerp
 * @package   crm
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     0.12
 */
class crm_ext_CourtReg extends core_Detail
{
	
	
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'companyId';

    
    /**
     * Заглавие
     */
    var $title = 'Съдебни регистрации';

    
    /**
     * Единично заглавие
     */
    var $singleTitle = 'Съдебна регистрация';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'crm_Wrapper,plg_RowTools2';
    
    
    /**
     * Текущ таб
     */
    var $currentTab = 'Фирми';
    
   
    /**
     * Кой може да редактира
     */
    var $canEdit = 'powerUser';


    /**  
     * Предлог в формата за добавяне/редактиране  
     */  
    public $formTitlePreposition = 'на';  
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('companyId', 'key(mvc=crm_Companies)', 'input=hidden,silent');
        
        // Данни за съдебната регистрация
        $this->FLD('regCourt', 'varchar', 'caption=Решение по регистрация->Съдилище,width=100%');
        $this->FLD('regDecisionNumber', 'varchar(16)', 'caption=Решение по регистрация->Номер');
        $this->FLD('regDecisionDate', 'date', 'caption=Решение по регистрация->Дата');
        
        // Фирмено дело
        $this->FLD('regCompanyFileNumber', 'varchar(16)', 'caption=Фирмено дело->Номер');
        $this->FLD('regCompanyFileYear', 'int', 'caption=Фирмено дело->Година');

        $this->setDbUnique('companyId');
    }
    

    /**
     * Подготовка за показване в указателя
     */
    public static function prepareCourtReg($data)
    {
        $data->TabCaption = 'Регистрация';
		
        if($data->isCurrent === FALSE) return;

        expect($data->masterId);
        
        if(!$data->CourtReg) {
            $data->CourtReg = new stdClass();
        }

        $data->CourtReg->rec = static::fetch("#companyId = {$data->masterId}");
        if ($data->CourtReg->rec) {
            $data->CourtReg->row = static::recToVerbal($data->CourtReg->rec);    
        }
        $data->canChange = static::haveRightFor('edit');
    }
    

    /**
     * Рендиране на списъчния изглед в указателя
     */
    public static function renderCourtReg($data)
    {
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        $tpl->append(tr('Съдебна регистрация'), 'title');        

        if ($data->canChange && !Mode::is('printing')) {
            
            $rec = $data->CourtReg->rec;

            if ($rec->regCourt || $rec->regDecisionNumber || $rec->regDecisionDate || $rec->regCompanyFileNumber || $rec->regCompanyFileYear) {
                $url = array(get_called_class(), 'edit', $rec->id, 'ret_url' => TRUE);
                $courtRegTpl = new ET(getFileContent('crm/tpl/CourtReg.shtml'));
                $courtRegTpl->placeObject($data->CourtReg->row);
            } else {
                $courtRegTpl = new ET(tr('Няма данни'));
                $url = array(get_called_class(), 'add', 'companyId' => $data->masterId, 'ret_url' => TRUE);
            }
            
            if($data->masterMvc->haveRightFor('edit', $data->masterId)){
            	$img = "<img src=" . sbf('img/16/add.png') . " width='16' height='16'>";
	            $tpl->append(
	                ht::createLink(
	                    $img, $url, FALSE,
	                    'title=Промяна на данните'
	                ),
	                'title'
	            );
            }
        }
        
        $tpl->append($courtRegTpl, 'content');
        
        return $tpl;
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
    	$conf = core_Packs::getConfig('crm');
    	
        $form = $data->form;
        
        // За да гарантираме релацията 1:1
        $form->rec->id = $mvc->fetchField("#companyId = {$form->rec->companyId}", 'id');
        
        for($i = 1989; $i <= date('Y'); $i++) $years[$i] = $i;
        
        $form->setSuggestions('regCompanyFileYear', $years);
        
        $dcQuery = bglocal_DistrictCourts::getQuery();
        
        while($dcRec = $dcQuery->fetch()) {
            $dcName = bglocal_DistrictCourts::getVerbal($dcRec, 'type');
            $dcName .= ' - ';
            $dcName .= bglocal_DistrictCourts::getVerbal($dcRec, 'city');
            $dcSug[$dcName] = $dcName;
        }
        
        $form->setSuggestions('regCourt', $dcSug);
 		$data->form->title = 'Съдебна регистрация на |*' .  $mvc->Master->getVerbal($data->masterRec, 'name');
    }
    
    
	/**
     * Изпълнява се след подготовката на ролите
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'edit' && isset($rec)){
    		$res = $mvc->getRequiredRoles('add', $rec);
    	}
    }
}
