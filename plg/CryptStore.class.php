<?php


/**
 * Клас 'plg_CryptStore' - Полетата с атрибут crypt се записват криптрирани в базата
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_CryptStore extends core_Plugin
{
    
    
    /**
     * Изпълнява се преди записване на $rec
     */
    public function on_BeforeSave($mvc, &$res, &$rec, $fields = null, $mode = '')
    {
        $fieldsCrypt = $mvc->selectFields('#crypt');

        if (count($fieldsCrypt)) {
            foreach ($fieldsCrypt as $name => $fld) {
                if ($rec->{$name}) {
                    if (!static::decrypt($rec->{$name})) {
                        $rec->{$name} = static::encrypt($rec->{$name});
                    }
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след прочитане на $rec
     */
    public function on_AfterRead($mvc, &$rec)
    {
        $fields = $mvc->selectFields('#crypt');

        if (count($fields)) {
            foreach ($fields as $name => $fld) {
                if ($rec->{$name}) {
                    if ($val = self::decrypt($rec->{$name})) {
                        $rec->{$name} = $val;
                    } elseif ($val = core_Crypt::decodeVar($rec->{$name})) {
                        $rec->{$name} = $val;
                    }
                }
            }
        }
    }


    public static function encrypt($str)
    {
        $rnd = str::getRand('****');

        $key = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if ($key{$i} === '') {
                $key .= md5($rnd . EF_SALT . 'code' . $key, true);
            }

            $res .= $str{$i} ^ $key{$i};
        }
         
        $res = 'p|' . $rnd . base64_encode($res);

        return $res;
    }

    
    public static function decrypt($str)
    {
        if (substr($str, 0, 2) != 'p|') {
            return false;
        }

        $rnd = substr($str, 2, 4);
        
        $str = base64_decode(substr($str, 6));
        
        $len = strlen($str);
        
        if ($len == 0) {
            return false;
        }

        $key = '';
        for ($i = 0; $i < $len; $i++) {
            if ($key{$i} === '') {
                $key .= md5($rnd . EF_SALT . 'code' . $key, true);
            }

            $res .= $str{$i} ^ $key{$i};
        }
         
        return $res;
    }
}
