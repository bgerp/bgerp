<?php
class crm_ext_PersonalData extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'personId';


    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'crm_Wrapper';
    
    var $currentTab = 'Лица';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent');
        $this->FLD('idCardNumber', 'varchar(16)', 'caption=Лична карта->Номер');
        $this->FLD('idCardIssuedOn', 'date', 'caption=Лична карта->Издадена на');
        $this->FLD('idCardExpiredOn', 'date', 'caption=Лична карта->Валидна до');
        $this->FLD('idCardIssuedBy', 'varchar(64)', 'caption=Лична карта->Издадена от');
    }
    
    public static function preparePersonalData($data)
    {
        if (get_class($data->masterMvc) != 'crm_Persons') {
            // Позволено само за физически лица
            return;
        }
        
        expect($data->masterId);
        
        $data->PersonalData->rec = static::fetch("#personId = {$data->masterId}");
        $data->PersonalData->row = static::recToVerbal($data->PersonalData->rec);
        $data->canChange         = static::haveRightFor('edit');
    }
    
    public static function renderPersonalData($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        $tpl->append(tr('Лични данни'), 'title');
        
        $personalDataTpl = new ET(getFileContent('crm/tpl/PersonalData.shtml'));

        if ($data->canChange && !Mode::is('printing')) {
            if ($data->PersonalData->rec) {
                $url = array(get_called_class(), 'edit', $data->PersonalData->rec->id, 'ret_url' => TRUE);
                $personalDataTpl->placeObject($data->PersonalData->row);
            } else {
                $url = array(get_called_class(), 'add', 'personId'=>$data->masterId, 'ret_url' => TRUE);
            }
            $img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16'>";
            $tpl->append(
                ht::createLink(
                    $img, $url, FALSE,
                    'title=' . tr('Промяна')
                ),
                'title'
            );
        }
        
        $tpl->append($personalDataTpl, 'content');
        
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

        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" . $conf->BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }

        $mvrQuery = drdata_Mvr::getQuery();

        while($mvrRec = $mvrQuery->fetch()) {
            $mvrName = 'МВР - ';
            $mvrName .= drdata_Mvr::getVerbal($mvrRec, 'city');
            $mvrSug[$mvrName] = $mvrName;
        }

        $form->setSuggestions('idCardIssuedBy', $mvrSug);

        $data->form->title = 'Лични данни на |*' .  $mvc->Master->getVerbal($data->masterRec, 'name');
    }
}