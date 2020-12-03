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
        $time = dt::mysql2timestamp(dt::subtractSecs(3600));
        $dQuery = log_Data::getQuery();
        $dQuery->where("#type = 'login'");
        $dQuery->where(array("#classCrc = '[#1#]'"), log_Classes::getClassCrc('core_Users'));
        $dQuery->where(array("#time >= '[#1#]'", $time));
        
        $dQuery->EXT('bridStr', 'log_Browsers', 'externalName=brid,externalKey=brId');
        $dQuery->EXT('ipStr', 'log_Ips', 'externalName=ip,externalKey=ipId');
        $dQuery->EXT('roles', 'core_Users', 'externalName=roles,externalKey=objectId');
        
        $pu = core_Roles::fetchByName('powerUser');
        
        $dQuery->like("roles", type_Keylist::fromArray(array($pu => $pu)));
        
        $dQuery->orderBy('time', 'DESC');
        
        $bridAr = array();
        $ipArr = array();
        while ($dRec = $dQuery->fetch()) {
            $nick = core_Users::getNick($dRec->objectId);
            $names = core_Users::fetchField($dRec->objectId, 'names');
            $names = core_Users::prepareUserNames($names);
            
            if (!$bridArr[$dRec->bridStr]) {
                $template = "{$nick} <span class='autocomplete-name'>{$names} ({$dRec->bridStr})</span>";
                $bridArr[$dRec->bridStr] = array('val' => $dRec->bridStr, 'template' => $template, 'search' => $dRec->bridStr . ' ' . $nick . ' ' . $names);
            }
            
            if (!$ipArr[$dRec->ipStr]) {
                $template = "{$nick} <span class='autocomplete-name'>{$names} ({$dRec->ipStr})</span>";
                $ipArr[$dRec->ipStr] = array('val' => $dRec->ipStr, 'template' => $template, 'search' => $dRec->ipStr . ' ' . $nick . ' ' . $names);
            }
        }
        
        $brid = log_Browsers::getBrid();
        $data->form->setSuggestions('brid', $bridArr);
        
        $ip = core_Users::getRealIpAddr();
        $data->form->setSuggestions('ip', $ipArr);
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
        
        $me = cls::get(get_called_class());
        
        setIfNot($params['brid'], log_Browsers::getBrid());
        setIfNot($params['ip'], core_Users::getRealIpAddr());
        
        if (!$me->checkExist($params['brid'], $rec->brid) || !$me->checkExist($params['ip'], $rec->ip, '*')) {
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
    
    
    /**
     * Помощна фунцкия за проверка дали пододана стойност я има в стинг
     *
     * @param string $val
     * @param string $str
     * @param string|null $matchStr
     *
     * @return boolean
     */
    private static function checkExist($val, $str, $matchStr = null)
    {
        $str = str_replace(array(',', ';'), ' ', $str);
        
        $val = trim($val);
        $str = trim($str);
        $exist = false;
        if ($val) {
            if ($str) {
                $valArr = explode(' ', $str);
                foreach ($valArr as $valStr) {
                    $valStr = trim($valStr);
                    if ($valStr == $val) {
                        $exist = true;
                        break;
                    } else {
                        
                        // Ако има символ, за заместване на израз
                        if (isset($matchStr) && (stripos($valStr, $matchStr) !== false)) {
                            $pattern = preg_quote($valStr, '/');
                            
                            $pattern = str_replace(preg_quote($matchStr, '/'), '.*', $pattern);
                            
                            $pattern = "/^{$pattern}$/";
                            if (preg_match($pattern, $val)) {
                                $exist = true;
                                break;
                            }
                        }
                    }
                }
            } else {
                $exist = true;
            }
        } else {
            $exist = true;
        }
        
        return $exist;
    }
}
