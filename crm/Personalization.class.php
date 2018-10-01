<?php


/**
 * Детайл на профилите
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.12
 * @deprecated
 */
class crm_Personalization extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'profileId';
    
    
    public $title = 'Персонализация';
    
    
    /**
     * Плъгини и MVC класове, които се зареждат при инициализация
     */
    public $loadList = 'crm_Wrapper,plg_RowTools';
    
    
    public $currentTab = 'Профили';
    
    
    public $canAdd = 'powerUser';
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'crm_ext_Personalization';
    
    
    public $canEdit = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('profileId', 'key(mvc=crm_Profiles)', 'input=hidden,silent');
        $this->FLD('inbox', 'key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Основен имейл->Кутия,hint=Кутия по подразбиране за изпращане на писма');
        $this->FLD('header', 'text', 'caption=Изходящ имейл->Привет', array('hint' => 'Текст, който ще се използва за приветствие в писмата'));
        $this->FLD('signature', 'text', 'caption=Изходящ имейл->Подпис', array('hint' => 'Текст, който ще се използва за подпис на писмата'));
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Бланка->Български', array('hint' => 'Лого, което ще се използва в бланките на документите'));
        $this->FLD('logoEn', 'fileman_FileType(bucket=pictures)', 'caption=Бланка->Английски', array('hint' => 'Лого, което ще се използва в бланките на документите на английски'));
        
        $this->setDbUnique('profileId');
    }
    
    
    public static function preparePersonalization($data)
    {
        // Очакваме да има masterId
        expect($data->masterId);
        
        // Ако няма
        if (!$data->Personalization) {
            
            // Създаваме клас
            $data->Personalization = new stdClass();
        }
        
        // Вземаме записите
        $data->Personalization->rec = static::fetch("#profileId = {$data->masterId}");
        
        // Ако има записи
        if ($data->Personalization->rec) {
            
            // Вземаме вербалните им стойности
            $data->Personalization->row = static::recToVerbal($data->Personalization->rec);
        }
        
        // Кой може да променя
        $data->canChange = crm_Profiles::haveRightFor('edit', $data->masterId) && static::haveRightFor('edit', $data->Personalization->rec);
    }
    
    
    public static function renderPersonalization($data)
    {
        // Ако нямаме права да не се показва
        if (!$data->canChange) {
            
            return;
        }
        
        // Шаблона за детейлите
        $tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
        
        // Титлата
        $tpl->append(tr('Персонализация'), 'title');
        
        // Записите от модела
        $rec = $data->Personalization->rec;
        
        // Ако има една от стойностите
        if ($rec->signature || $rec->header || $rec->logo || $rec->logoEn || $rec->inbox) {
            
            // Шаблона
            $idCardTpl = new ET(tr('|*' . getFileContent('crm/tpl/Personalization.shtml')));
            
            // Вкарваме вербалние данни
            $idCardTpl->placeObject($data->Personalization->row);
        } else {
            
            // Ако няма запис
            $idCardTpl = new ET(tr('Няма данни'));
        }
        
        // Ако не принтираме и имаме права
        if (!Mode::is('printing')) {
            
            // Ако има записи
            if ($rec->id) {
                
                // URL за промяна
                $url = array('crm_Personalization', 'edit', $rec->id, 'ret_url' => true);
            } else {
                
                // URL за добавяне
                $url = array('crm_Personalization', 'add', 'profileId' => $data->masterId, 'ret_url' => true);
            }
            
            // Иконата за редактиране
            $img = '<img src=' . sbf('img/16/edit.png') . " width='16' height='16'>";
            
            // Създаме линка
            $link = ht::createLink($img, $url, false, 'title=Промяна на персонализация');
            
            // Добавяме линка
            $tpl->append($link, 'title');
        }
        
        // Добавяме шаблона
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
        $form = $data->form;
        
        // За да гарантираме релацията 1:1
        $form->rec->id = $mvc->fetchField("#profileId = {$form->rec->profileId}", 'id');
        
        // Титлата
        $form->title = 'Персонализация на|* ' .  crm_Persons::getVerbal($data->masterRec->personId, 'name');
        
        // id на потребителя, за този профил
        $userId = crm_Profiles::fetchField($form->rec->profileId, 'userId');
        
        try {
            // Имейлите за този профил
            $emailOptions = email_Inboxes::getFromEmailOptions(false, $userId, true);
        } catch (core_exception_Expect $e) {
            $emailOptions[] = '';
        }
        
        // Задаваме опциите за съответния потребител
        $form->setOptions('inbox', $emailOptions);
    }
    
    
    /**
     * Връща логото на профила
     *
     * @param int|FALSE $userId - id' то на съответния потребител
     * @param bool      $en     - Дали логото да е на английски
     */
    public static function getLogo($userId = false, $en = false)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) {
            
            return ;
        }
        
        // Ако е зададен да се връща логото на английски
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
     * @param int $userId - id' то на съответния потребител
     */
    public static function getSignature($userId = null)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) {
            
            return ;
        }
        
        // Връщаме подписа на потребителя
        return $rec->signature;
    }
    
    
    /**
     * Връща подписа на съответния потребител
     *
     * @param int $userId - id' то на съответния потребител
     */
    public static function getHeader($userId = null)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) {
            
            return ;
        }
        
        // Връщаме подписа на потребителя
        return $rec->header;
    }
    
    
    /**
     * Връща кутията по подразбиране
     *
     * @param int $userId - id' то на съответния потребител
     *
     * @return int
     */
    public static function getInboxId($userId = null)
    {
        // Вземаме записа
        $rec = static::getRec($userId);
        
        // Ако няма запис, връщаме
        if (!$rec) {
            
            return ;
        }
        
        // Връщаме подписа на потребителя
        return $rec->inbox;
    }
    
    
    /**
     * Връща записа за съответния потребител
     *
     * @param int|NULL|FALSE $userId - id' то на съответния потребител
     */
    public static function getRec($userId = null)
    {
        // Ако не е подаден потребител
        if (!$userId) {
            
            // Използваме текущия
            $userId = core_Users::getCurrent();
            
            // Ако няма текущ потребител
            if (!$userId) {
                
                return false;
            }
        }
        
        // id на потребителя
        $profileId = crm_Profiles::fetchField("#userId = {$userId}");
        
        // Ако няма потребител
        if (!$profileId) {
            
            return ;
        }
        
        // Вземаме записа
        $rec = static::fetch("#profileId = '{$profileId}'");
        
        return $rec;
    }
    
    
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        $requiredRoles = 'no_one';
    }
}
