<?php



/**
 * Клас  'cat_type_Density'
 * Тип за плътност
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_type_Density extends cat_type_Uom
{
    
    
    /**
     * Параметър по подразбиране
     */
    public function init($params = array())
    {
        // Основната мярка на типа е метри
        $this->params['unit'] = 'kg/m3';
        parent::init($this->params);
    }
}
