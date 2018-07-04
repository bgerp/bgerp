<?php



/**
 * Клас 'batch_plg_ProductDetail' - За генериране на партидни движения на протокола за производство
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_plg_ProductDetail extends core_Plugin
{
    
    
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['Batches'] = 'batch_Items';
        $details = arr::fromArray($details);
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        if ($rec->canStore == 'yes') {
            batch_Defs::force($rec);
        }
    }
}
