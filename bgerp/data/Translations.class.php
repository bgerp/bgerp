<?php


/**
 * Клас 'bgerp_data_Translations'
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_data_Translations extends core_MVC
{
    /**
     * Зареждане на стирнговете, които ще се превеждат
     */
    public static function loadData($fromZero = false)
    {
        $file = 'bgerp/data/csv/Translations.csv';
        
        $mvc = cls::get('core_Lg');
        
        $fields = array(
            0 => 'lg',
            1 => 'kstring',
            2 => 'translated',
            3 => 'csv_createdBy',
        );
        
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields, null, null, $fromZero);
        
        $res = static::addForAllLg();
        
        $res .= $cntObj->html;
        
        return $res;
    }
    
    
    /**
     * Добавя съдържанието на преводите, които са зададени в EF_LANGUAGES
     * Добавя за всички езици без `en` и `bg`
     */
    public static function addForAllLg()
    {
        // Масив в всички езици
        $langArr = core_Lg::getLangs();
        
        // Премахваме английския и българския
        unset($langArr['en']);
        unset($langArr['bg']);
        
        // Ако няма повече езици, не се изпълянва
        if (!count($langArr)) {
            
            return ;
        }
        
        // Вземаме всички преводи на английски
        $query = core_Lg::getQuery();
        $query->where("#lg = 'en'");
        
        $enLangRecArr = array();
        
        while ($enLangRec = $query->fetch()) {
            
            // Добавяме ги в масив
            $enLangRecArr[$enLangRec->id] = $enLangRec;
        }
        
        $nArr = array();
        
        // Обхождаме езиците
        foreach ($langArr as $lang => $dummy) {
            
            // Обхождаме всички преводи на английски
            foreach ((array) $enLangRecArr as $enLangRec) {
                
                // Създаваме запис
                $nRec = new stdClass();
                $nRec->lg = $lang;
                $nRec->kstring = $enLangRec->kstring;
                $nRec->translated = $enLangRec->translated;
                $nRec->createdBy = -1;
                
                // Опитваме се да запишем данните за съответния език
                core_Lg::save($nRec, null, 'IGNORE');
                
                // Ако запишем успешно
                if ($nRec->id) {
                    
                    // Увеличаваме брояча за съответния език
                    $nArr[$lang]++;
                }
            }
        }
        
        // Обхождаме всички записани резултати
        foreach ((array) $nArr as $lg => $times) {
            
            // Добавяме информационен стринг за всеки език
            $res .= "<li style='color:green'>Към {$langArr[$lg]} са добавени {$times} превода на английски.";
        }
        
        return $res;
    }
}
