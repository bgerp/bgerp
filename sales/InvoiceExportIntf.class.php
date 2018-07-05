<?php



/**
 * Интерфейс за експортиране на изходящи фактури
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за експортиране на изходящи фактури
 */
class sales_InvoiceExportIntf
{
    
    
    /**
     * Клас имплементиращ мениджъра
     */
    public $class;
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param  date  $recs - записите на фактурите, които ще се експортират
     * @return mixed - експортираните данни
     */
    public function export($recs)
    {
        $this->class->export($recs);
    }
}
