<?php


/**
 * Клас 'batch_plg_ProductDetail' - За генериране на партидни движения на протокола за производство
 *
 *
 * @category  bgerp
 * @package   batch
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_plg_ProductDetail extends core_Plugin
{
    /**
     * Изпълнява се след закачане на детайлите
     */
    public static function on_BeforeAttachDetails(core_Mvc $mvc, &$res, &$details)
    {
        $details = arr::make($details);
        $details['pallets'] = 'rack_Products';
        $details = arr::fromArray($details);
    }
}
