<?php
/**
 * Клас 'feed_Wrapper'
 *
 * Обвивка на Хранилките
 *
 *
 * @category  vendors
 * @package   feed
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class feed_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
       $this->TAB('feed_Generator', 'Хранилки', 'ceo,admin,cms');
    }
}