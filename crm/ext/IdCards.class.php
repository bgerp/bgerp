<?php
class crm_ext_IdCards extends core_Detail
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
        $this->FLD('idCardNumber', 'varchar(16)', 'caption=Номер');
        $this->FLD('idCardIssuedOn', 'date', 'caption=Издадена на');
        $this->FLD('idCardExpiredOn', 'date', 'caption=Валидна до');
        $this->FLD('idCardIssuedBy', 'varchar(64)', 'caption=Издадена от');
    }
    
    public static function prepareIdCard($data)
    {
        if (get_class($data->masterMvc) != 'crm_Persons') {
            // Позволено само за физически лица
            return;
        }
        
        expect($data->masterId);
        
        if(!$data->IdCard) {
            $data->IdCard = new stdClass();
        }

        $data->IdCard->rec = static::fetch("#personId = {$data->masterId}");
        $data->IdCard->row = static::recToVerbal($data->IdCard->rec);
        $data->canChange         = static::haveRightFor('edit');
    }
    
    public static function renderIdCard($data)
    {
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        $tpl->append(tr('Лична карта'), 'title');
        
        $idCardTpl = new ET(getFileContent('crm/tpl/IdCard.shtml'));

        if ($data->canChange && !Mode::is('printing')) {
            if ($data->IdCard->rec) {
                $url = array(get_called_class(), 'edit', $data->IdCard->rec->id, 'ret_url' => TRUE);
                $idCardTpl->placeObject($data->IdCard->row);
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
        
        $tpl->append($idCardTpl, 'content');
        
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

        $data->form->title = 'Лична карта на |*' .  $mvc->Master->getVerbal($data->masterRec, 'name');
    }
}