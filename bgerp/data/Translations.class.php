<?php


/**
 * Клас 'bgerp_data_Translations'
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_data_Translations
{
    
    
    /**
     * Зареждане на стирнговете, които ще се превеждат
     */
    static function loadData()
    {
        //Пътя до CSV файла
        $csvFile = self::getCsvFile();
        
        //Коко записа са създадени
        $created = 0;
        
        //Ако не може да се намери файла или нямаме права за работа с него
        if (($handle = @fopen($csvFile, "r")) !== FALSE) {
            
            //обхождаме целия файл
            while (($csvRow = fgetcsv($handle, 1000, "|")) !== FALSE) {
                
                //Създаваме обект, който ще записваме в БД
                $rec = new stdClass();
                
                //Езика на който ще се превежда
                $rec->lg = $csvRow[0];
                
                //Стринга, който ще се превежда
                $rec->kstring = $csvRow[1];
                
                //Преведения стринг
                $rec->translated = $csvRow[2];
                
                //Създаден от системата
                $rec->createdBy = -1;

                //Ако запишем успешно, добава единица в общия брой записи
                if (core_Lg::save($rec, NULL, 'REPLACE')) {
                    $created++;    
                }
            }
            
            //Затваряме файла
            fclose($handle);
            
            //Съобщението което ще се показва след като приключим
            $res = $created ? "<li style='color:green;'>" : "<li style='color:#660000'>";
            $res .= "Създадени {$created} нови превода.</li>";
        } else {
            //Ако има проблем при отварянето на файла
            $res = "<li style='color:red'>Не може да бъде отворен файла '{$csvFile}'";
        }
        
        $res .= static::addForAllLg();
        
        return $res;
    }
    
    
    /**
     * Добавя съдържанието на преводите, които са зададени в EF_LANGUAGES
     * Добавя за всички езици без `en` и `bg`
     */
    static function addForAllLg()
    {
        // Масив в всички езици
        $langArr = arr::make(EF_LANGUAGES, TRUE);
        
        // Премахваме английския и българския
        unset($langArr['en']);
        unset($langArr['bg']);
        
        // Ако няма повече езици, не се изпълянва
        if (!count($langArr)) return ;

        // Вземаме всички преводи на английски
        $query = core_Lg::getQuery();
        $query->where("#lg = 'en'");
        while ($enLangRec = $query->fetch()) {
            
            // Добавяме ги в масив
            $enLangRecArr[$enLangRec->id] = $enLangRec;
        }
        
        // Обхождаме езиците
        foreach ($langArr as $lang => $dummy) {
            
            // Обхождаме всички преводи на английски
            foreach ((array)$enLangRecArr as $enLangRec) {
                
                // Създаваме запис
                $nRec = new stdClass();
                $nRec->lg = $lang;
                $nRec->kstring = $enLangRec->kstring;
                $nRec->translated = $enLangRec->translated;
                
                // Опитваме се да запишем данните за съответния език
                core_Lg::save($nRec, NULL, 'IGNORE');
                
                // Ако запишем успешно
                if ($nRec->id) {
                    
                    // Увеличаваме брояча за съответния език
                    $nArr[$lang]++;
                }
            }
        }
        
        // Обхождаме всички записани резултати
        foreach ((array)$nArr as $lg => $times) {
            
            // Добавяме информационен стринг за всеки език
            $res .= "<li style='color:green'>Към {$langArr[$lg]} са добавени {$times} превода на английски.";
        }
        
        return $res;
    }
    
    
    /**
     * Връща пътя до CSV файла
     */
    static private function getCsvFile()
    {
        
        return __DIR__ . "/csv/Translations.csv";
    }
}