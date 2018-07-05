<?php



/**
 * Плъгин за добавяне на полета в cat_Products за импорт/експорт от БН
 *
 *
 * @category  bgerp
 * @package   bnav
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bnav_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     *
     * @param core_Mvc $mvc
     */
    public function on_AfterDescription(core_Mvc $mvc)
    {
        // Проверка за приложимост на плъгина към зададения $mvc
        if (!static::checkApplicability($mvc)) {
            return;
        }
        
        // Добавяне на необходимите полета
        $mvc->FLD('bnavCode', 'varchar(150)', 'caption=Код БН,remember=info,width=15em,mandatory');
        
        if ($mvc->fields['eanCode']) {
            
            // Полето се слага след баркода на продукта
            $mvc->fields = array_slice($mvc->fields, 0, 4, true) +
            array('bnavCode' => $mvc->fields['bnavCode']) +
            array_slice($mvc->fields, 4, null, true);
        }
    }
    
    
    /**
     * Проверява дали този плъгин е приложим към зададен мениджър
     */
    protected static function checkApplicability($mvc)
    {
        // Прикачане е допустимо само към наследник на cat_Products ...
        if (!$mvc instanceof cat_Products) {
            return false;
        }
        
        return true;
    }
}
