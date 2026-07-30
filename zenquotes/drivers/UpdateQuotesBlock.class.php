<?php

/**
 * Показване на блок от цитати
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    David Dimitriev
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Цитати
 */
class zenquotes_drivers_UpdateQuotesBlock extends core_BaseClass
{
    /**
     * Максимален брой блокове, които да могат да се поакзват в портала
     */
    public $maxCnt = 0;


    public $interfaces = 'bgerp_PortalBlockIntf';


    public $cacheable = true;


    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('refreshTime', 'time', 'caption=Период на обновяване->Време, mandatory');
    }
    

    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return true;
    }


    /**
     * Рендира данните
     *
     * @param stdClass $dRec
     * @param int|NULL $userId
     *
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        if ($userId === null) {
            $userId = core_Users::getCurrent();
        }

        
        // Генериране на тип и ключ за кеша
        $cacheType = $this->getCacheTypeName($userId);
        $cacheKey = $this->getCacheKey($dRec, $userId);


        // Опит за вземане на данните от кеша
        $cached = core_Cache::get($cacheType, $cacheKey);
        if ($cached !== false) {
            
            return $cached;
        }

        $resData = new stdClass();
        $resData->data = new stdClass();

        $query = zenquotes_Quotes::getQuery();
        $refresh = (int)$dRec->refreshTime;
        
        if ($refresh <= 0) {
            // Случаен избор при всяко зареждане
            $query->XPR('order', 'double', "RAND()");
        } else {
            // Изчисляване на уникален сийд спрямо времето и ID на блока
            $seed = floor((time() + (int)$dRec->id * 17) / $refresh);
            $query->XPR('order', 'double', "RAND({$seed})");
        }
        
        $query->orderBy('#order');
        $query->show('author, quote');
        $query->limit(1);


        // Извличане на записа от базата данни
        $rec = $query->fetch();

        if ($rec) {
            $resData->data->quote = $rec->quote;
            $resData->data->author = $rec->author;
        } else {
            $resData->data->quote = 'Нема достапни цитати';
            $resData->data->author = '';
        }


        // Записване на резултата в кеша за определения период
        $cacheTtl = ($refresh > 0) ? ($refresh / 60) : 0.01;
        core_Cache::set($cacheType, $cacheKey, $resData, $cacheTtl); 

        return $resData;
    }
    

    /**
     * Рендира блока
     * 
     * @param stdClass $data
     * 
     * @return core_ET
     * 
     */
    public function render($data)
    {
        if (empty($data->data->quote)) {
            return new core_ET('');
        }

        $tpl = new core_ET('
            <div class="clearfix21 portal">
                <div class="legend">[#LEGEND#]</div>
                <div style="padding: 10px 15px; text-align: right;">
                    <div style="font-style: italic; font-family: Georgia, serif; font-size: 18px; color: #444; line-height: 1.5; max-width: 80%; margin-left: auto;">
                        „[#QUOTE#]“
                    </div>
                    <div style="margin-top: 8px; font-size: 14px; color: #777;">
                        — [#AUTHOR#]
                    </div>
                </div>
            </div>
        ');

        $tpl->replace($data->data->quote, 'QUOTE');
        $tpl->replace($data->data->author, 'AUTHOR');
        $tpl->replace(tr('Велика мисъл'), 'LEGEND');

        return $tpl;
    }


    /**
     * Връща заглавието за таба на съответния блок
     *
     * @param stdClass $dRec
     *
     * @return string
     */
    public function getBlockTabName($dRec)
    {
        return tr('Цитат на деня');
    }


    /**
     * Името на стойността за кеша
     *
     * @param integer $userId
     *
     * @return string
     */
    public function getCacheTypeName($userId = null)
    {
        if (!isset($userId)) {
            $userId = core_Users::getCurrent();
        }
        return 'Portal_Quotes_' . $userId;
    }


    /**
     * Помощна функция за вземане на ключа за кеша
     *
     * @param stdClass $dRec
     * @param null|integer $userId
     *
     * @return string
     */
    public function getCacheKey($dRec, $userId = null)
    {
        if ($userId === null) {
            $userId = core_Users::getCurrent();
        }

        $cArr = bgerp_Portal::getPortalCacheKey($dRec, $userId);
        
        $cArr[] = $dRec->id; 
        
        $refresh = (int)$dRec->refreshTime;

        if ($refresh <= 0) {
            $cArr[] = microtime(); 
        } else {
            $cArr[] = floor((time() + (int)$dRec->id * 17) / $refresh);
        }

        return md5(implode('|', $cArr));
    }
}
