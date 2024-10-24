<?php


/**
 * Плъгин за разширяване на периферните устройства, да могат да работят по brid или IP
 *
 *
 * @category  bgerp
 * @package   wscales
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class peripheral_DeviceWebPlg extends core_Plugin
{
    /**
     * След полетата за добавяне
     *
     * @param core_Mvc $Driver   - драйвер
     * @param embed_Manager       $Embedder - ембедър
     * @param core_Fieldset       $fieldset - форма
     */
    protected static function on_AfterAddFields($Driver, embed_Manager $Embedder, core_Fieldset &$fieldset)
    {
        $fieldset->FLD('brid', 'text(rows=2)', 'caption=Компютър->Браузър, after=name');
        $fieldset->FLD('ip', 'text(rows=2)', 'caption=Компютър->IP, after=brid');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Mvc $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $bridAndIpArr = log_Data::getLastLoginBridAndIpArr();

        $data->form->setSuggestions('brid', $bridAndIpArr['bridArr']);
        
        $data->form->setSuggestions('ip', $bridAndIpArr['ipArr']);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $Embedder
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($Driver, $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            if (!$form->rec->brid && !$form->rec->ip) {
                $form->setError('brid, ip', 'Непопълнено задължително поле');
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $Embedder
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($Driver, $Embedder, &$row, $rec, $fields = array())
    {
        $row->ip = '';
        $rec->ip = str_replace(array(',', ';'), ' ', $rec->ip);
        $ipArr = explode(' ', $rec->ip);
        foreach ($ipArr as $ip)  {
            $row->ip .= $row->ip ? "<br>" : '';
            $row->ip .= type_Ip::decorateIp($ip);
        }
        
        $row->brid = '';
        $rec->brid = str_replace(array(',', ';'), ' ', $rec->brid);
        $bridArr = explode(' ', $rec->brid);
        foreach ($bridArr as $brid)  {
            $row->brid .= $row->brid ? "<br>" : '';
            $row->brid .= " " . log_Browsers::getLink($brid);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    public static function on_AfterGetSearchKeywords($Driver, embed_Manager $Embedder, &$res, $rec)
    {
        $rec = $Embedder->fetchRec($rec);
        if (!isset($res)) {
            $res = plg_Search::getKeywords($Embedder, $rec);
        }
        
        if ($rec->brid || $rec->ip) {
            $p = trim($rec->brid);
            $p .= $p ? ' ' : '';
            $p = trim($rec->ip);
        }
        $res .= ' ' . plg_Search::normalizeText($p);
    }
    
    
    /**
     * 
     * @param core_Mvc $Driver
     * @param boolean $res
     * @param stdClass $rec
     * @param array $params
     */
    public static function on_AfterCheckDevice($Driver, &$res, $rec, $params)
    {
        if ($res === false) {
            
            return ;
        }
        
        setIfNot($params['brid'], log_Browsers::getBrid());
        setIfNot($params['ip'], core_Users::getRealIpAddr());
        
        if (!core_String::checkExist($params['brid'], $rec->brid) || !core_String::checkExist($params['ip'], $rec->ip, '*')) {
            $res = false;
        } else {
            $res = true;
        }
    }
    
    
    /**
    * Подготовка за рендиране на единичния изглед
    *
    * @param core_Mvc $Driver
    * @param embed_Manager     $Embedder
    * @param stdClass          $res
    * @param stdClass          $data
    */
    public static function on_AfterPrepareSingle($Driver, embed_Manager $Embedder, &$res, &$data)
    {
        if (Request::get('update')) {
            if ((stripos($data->rec->serverIp, '127.0.0.1') !== false) || (stripos($data->rec->serverIp, 'localhost') !== false)) {
                if (!$Driver->checkDevice($data->rec, array('brid' => log_Browsers::getBrid(true), 'ip' => core_Users::getRealIpAddr()))) {
                    Request::push(array('update' => 0));
                }
            }
        }
    }
}
