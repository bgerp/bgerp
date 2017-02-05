<?php



/**
 * Интерфейс за документи, които ще изпълняват автоматизации
 *
 *
 * @category  bgerp
 * @package   auto
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class auto_AutomationIntf
{
    
    
    /**
     * Класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Изпълняване на автоматизация по събитието
     */
    public function doAutomation($event, $data)
    {
    	return $this->class->doAutomation($event, $data);
    }
    
    
    /**
     * Можели класа да обработи събититето
     */
    public function canHandleEvent($event)
    {
    	return $this->class->canHandleEvent($event);
    }
}