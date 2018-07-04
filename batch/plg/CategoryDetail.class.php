<?php



/**
 * Клас 'batch_plg_CategoryDetail' - за добавяне на детайл към категория
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class batch_plg_CategoryDetail extends core_Plugin
{
    
    
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['Definitions'] = 'batch_CategoryDefinitions';
        $details = arr::fromArray($details);
    }
}
