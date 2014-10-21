<?php


/**
 * Информация за доставката по сделка
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_iface_DealDelivery
{
    /**
     * Условие на доставка
     *
     * @var int key(mvc=cond_DeliveryTerms)
     * @see cond_DeliveryTerms
     */
    public $term;
    
    /**
     * Място на доставка
     *
     * @var int key(mvc=crm_Locations)
     * @see crm_Locations
     *
     */
    public $location;
    
    /**
     * Срок на доставка (до)
     *
     * @var string
     */
    public $time;
    
    /**
     * Склад на доставка
     *
     * @var string
     */
    public $storeId;
}
