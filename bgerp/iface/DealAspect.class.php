<?php

/**
 * Описание на един аспект от бизнес сделка, напр. запитване, офериране, договор, експедиция, 
 * плащане, фактуриране и (вероятно) други. 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 *
 */
class bgerp_iface_DealAspect
{
    /**
     * Списък (масив) от характеристики на продукти
     *
     * @var array of bgerp_iface_DealProduct
     */
    public $products;


    /**
     * 3-буквен ISO код на валута
     *
     * @var string
     */
    public $currency;


    /**
     * Обща сума на съответната част от сделката - пари във валутата `$currency`
     *
     * @var double
     */
    public $amount;


    /**
     * Информация за доставката
     *
     * @var bgerp_iface_DealDelivery
     */
    public $delivery;


    /**
     * Информация за плащането
     *
     * @var bgerp_iface_DealPayment
     */
    public $payment;
}
