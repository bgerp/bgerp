<?php


/**
 * Интерфейс за връзка към куриерско API
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cond_CourierApiIntf extends embed_DriverIntf
{

    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'cond_DeliveryExternalApiIntf';


    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
}