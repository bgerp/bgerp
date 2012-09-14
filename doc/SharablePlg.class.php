<?php

/**
 * Плъгин за проследяване и показване на историята на споделянията на документ
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_SharablePlg extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела - добавя поле за споделените потребители.
     * 
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Този плъгин може да се прикача само към документи
        expect(cls::haveInterface('doc_DocumentIntf', $mvc), 'doc_SharablePlg е приложим само към документи');
        
        // Поле за потребителите, с които е споделен документа (ако няма)
        if (!$mvc->getField('sharedUsers', FALSE)) {
            $mvc->FLD('sharedUsers', 'keylist(mvc=core_Users,select=nick)', 'caption=Споделяне->Потребители');
        }
        // Поле за първите виждания на документа от потребителите с които той е споделен
        if (!$mvc->getField('sharedViews', FALSE)) {
            // Стойността на полето е сериализиран масив с ключ - потребител и стойност - дата
            // на първо виждане от потребителя
            $mvc->FLD('sharedViews', 'blob', 'caption=Споделяне->Виждания,input=none');
        }
    }
    
    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public function on_AfterRenderSingle(core_Mvc $mvc, &$tpl, $data)
    {
        if (Request::get('Printing')) {
            // В режим на печат, маркираме документа като видян.
            // Ако не сме в режим печат, маркирането става в on_AfterRenderDocument() 
            static::markViewed($mvc, $data);
        } elseif (!Mode::is('text', 'xhtml')) {
            $history = static::prepareHistory($data->rec);
                
            // показваме (ако има) с кого е споделен файла
            if (!empty($history)) {
                $tpl->replace(static::renderSharedHistory($history), 'shareLog');
            }
        }
    }

    
    /**
     * След рендиране на документ отбелязва акта на виждането му от тек. потребител
     * 
     * @param core_Mvc $mvc
     * @param core_ET $tpl
     * @param unknown_type $data
     */
    public static function on_AfterRenderDocument(core_Mvc $mvc, &$tpl, $id, $data)
    {
        static::markViewed($mvc, $data);
    }

    
    /**
     * Помощен метод: маркиране на споделен док. като видян от тек. потребител
     * 
     * @param stdClass $data
     */
    protected static function markViewed($mvc, $data)
    {
        $rec = $data->rec;
        
        if ($rec->state == 'draft' || $rec->state == 'rejected') {
            // На практика документа не е споделен
            return;
        }
        
        $userId = core_Users::getCurrent('id');
        
        if (!type_Keylist::isIn($userId, $rec->sharedUsers)) {
            // Документа не е споделен с текущия потребител - не правим нищо
            return;
        }
        
        $viewedBy = array();
        
        if (!empty($rec->sharedViews)) {
            $viewedBy = unserialize($rec->sharedViews);
        }
        
        if (!isset($viewedBy[$userId])) {
            // Първо виждане на документа от страна на $userId
            $viewedBy[$userId] = dt::now(TRUE);
            $rec->sharedViews = serialize($viewedBy);
            if ($mvc::save($rec)) {
                core_Cache::remove($mvc->className, $data->cacheKey . '%');
            }
        }
    }
    
    
    /**
     * Помощен метод: подготовка на информацията за споделяне на документ
     * 
     * @param stdClass $rec обект-контейнер
     * @return array масив с ключове - потребителите, с които е споделен документа и стойност
     *                 датата, на която съотв. потребител е видял документа за пръв път (или
     *                 NULL, ако не го е виждал никога)
     */
    protected static function prepareHistory($rec)
    {
        $history = type_Keylist::toArray($rec->sharedUsers);
        $history = array_fill_keys($history, NULL);
        
        if (!empty($rec->sharedViews)) {
            $history = unserialize($rec->sharedViews) + $history;
        }
        
        
        return $history;
    }
    
    
    /**
     * Помощен метод: рендира историята на споделянията и вижданията
     *
     * @param array $sharedWith масив с ключ ИД на потребител и стойност - дата
     * @return string
     */
    public static function renderSharedHistory($sharedWith)
    {
        expect(is_array($sharedWith), $sharedWith);
        
        $html = array();
        
        foreach ($sharedWith as $userId => $seenDate) {
            $userRec = core_Users::fetch($userId);
            $nick = mb_convert_case(core_Users::getVerbal($userRec, 'nick'), MB_CASE_TITLE, "UTF-8");
            
            $nick = crm_Profiles::createLink($nick, $userId);
            
            if (!empty($seenDate)) {
                $seenDate = mb_strtolower(core_DateTime::mysql2verbal($seenDate, 'smartTime'));
                $seenDate = " ({$seenDate})";
            }

            $html[] = "<span style='color:black;'>" . $nick . "</span>{$seenDate}";
        }
        
        return implode(', ', $html);
    }
}