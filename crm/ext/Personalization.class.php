<?php
class crm_ext_Personalization extends core_Detail
{
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'personId';

    
    /**
     * 
     */
    var $title = 'Персонализация';

    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    var $loadList = 'crm_Wrapper,plg_RowTools';
    
    
    /**
     * 
     */
    var $currentTab = 'Персонализация';
    
    
    /**
     * 
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * 
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('personId', 'key(mvc=crm_Persons)', 'input=hidden,silent');
        $this->FLD('signature', 'text', 'caption=Писмо->Подпис');
        $this->FLD('header', 'text', 'caption=Писмо->Привет');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Бланка->Български');
        $this->FLD('logoEn', 'fileman_FileType(bucket=pictures)', 'caption=Бланка->Английски');

        $this->setDbUnique('personId');
    }
    
    
    /**
     * 
     */
    public static function preparePersonalization($data)
    {
        // Името на таба
        $data->TabCaption = 'Персонализация';

        // Очакваме да има masterId
        expect($data->masterId);

        if(!$data->Personalization) {
            $data->Personalization = new stdClass();
        }
        
        // Вземаме записите
        $data->Personalization->rec = static::fetch("#personId = {$data->masterId}");
        
        // Ако има записи
        if ($data->Personalization->rec) {
            
            // Вземаме вербалните им стойности
            $data->Personalization->row = static::recToVerbal($data->Personalization->rec);    
        }
        
        // Кой може да променя
        $data->canChange = static::haveRightFor('edit');
    }
    
    
    /**
     * 
     */
    public static function renderPersonalization($data)
    {
        // Шаблона за детейлите
        $tpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        
        // Титлата
        $tpl->append(tr('Персонализация'), 'title');        

        // Ако не принтираме
        if (!Mode::is('printing')) {
            
            $rec = $data->Personalization->rec;

            // Ако има една от стойностите
            if ($rec->signature || $rec->header || $rec->logo || $rec->logoEn) {
                
                // URL за промяна
                $url = array(get_called_class(), 'edit', $rec->id, 'ret_url' => TRUE);
                
                // Шаблона
                $idCardTpl = new ET(tr("|*" . getFileContent('crm/tpl/Personalization.shtml')));
                
                // Вкарваме вербалние данни
                $idCardTpl->placeObject($data->Personalization->row);
            } else {
                
                // Ако няма запис
                $idCardTpl = new ET(tr('Няма данни'));
                
                // URL' то
                $url = array(get_called_class(), 'add', 'personId'=>$data->masterId, 'ret_url' => TRUE);
            }
            
            // Иконата за редактиране
            $img = "<img src=" . sbf('img/16/edit.png') . " width='16' height='16'>";
            
            // Добавяме линка
            $tpl->append(
                ht::createLink(
                    $img, $url, FALSE,
                    'title=' . tr('Промяна на персонализация')
                ),
                'title'
            );
            
            // Добавяме шаблона
            $tpl->append($idCardTpl, 'content');
        }
        
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
        $form = $data->form;
        
        // За да гарантираме релацията 1:1
        $form->rec->id = $mvc->fetchField("#personId = {$form->rec->personId}", 'id');

        // Титлата
        $form->title = 'Персонализация на|* ' .  $mvc->Master->getVerbal($data->masterRec, 'name');
    }
    
    
    /**
     * Връща логото на профила
     * 
     * @param integer $userId - id' то на съответния потребител
     * @param boolean $en - Дали логото да е на английски
     */
    static function getLogo($userId = FALSE, $en = FALSE)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) return ;
        
        // Ако е зададен дасе връща логото на английски
        if ($en) {
            
            // Връщаме него
            return $rec->logoEn;    
        }
        
        // Връщаме логото на потребителя
        return $rec->logo;
    }
    
    
    /**
     * Връща подписа на съответния потребител
     * 
     * @param integer $userId - id' то на съответния потребител
     */
    static function getSignature($userId = NULL)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) return ;
        
        // Връщаме подписа на потребителя
        return $rec->signature;
    }
    

    /**
     * Връща подписа на съответния потребител
     * 
     * @param integer $userId - id' то на съответния потребител
     */
    static function getHeader($userId = NULL)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) return ;
        
        // Връщаме подписа на потребителя
        return $rec->header;
    }
    
    
    /**
     * Връща записа за съответния потребител
     * 
     * @param integer $userId - id' то на съответния потребител
     */
    static function getRec($userId=NULL)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            
            // Използваме текущия
            $userId = core_Users::getCurrent();
        }
        
        // id на потребителя
        $personId = crm_Profiles::getProfile($userId)->id;

        // Ако няма потребител
        if (!$personId) return ;
        
        // Вземаме записа
        $rec = static::fetch("#personId = '{$personId}'");
        
        return $rec;
    }
}