<?php



/**
 * Клас  'type_Blob' - Представя двоични данни
 *
 *
 * @category  ef
 * @package   type
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class type_Blob extends core_Type
{
    
    
    /**
     * Стойност по подразбиране
     */
    public $defaultValue = '';
    
    
    /**
     * Рендира HTML инпут поле
     */
    public function renderInput_($name, $value = '', &$attr = array())
    {
        if (Mode::is('screenMode', 'narrow')) {
            setIfNot($attr['rows'], 5);
            setIfNot($attr['cols'], 20);
        } else {
            setIfNot($attr['rows'], 15);
            setIfNot($attr['cols'], 60);
        }
        
        $attr['cols'] = $name;

        return ht::createElement('textarea', $attr, $value, true);
    }
    
    
    /**
     * Връща текста за MySQL типа
     */
    public function getMysqlAttr()
    {
        // Размера в байтове на полето
        $size = $this->getDbFieldSize();
        
        if (!$size) {
            $this->dbFieldType = 'BLOB';
        } elseif ($size < 256) {
            $this->dbFieldType = 'TINYBLOB';
        } elseif ($size < 65536) {
            $this->dbFieldType = 'BLOB';
        } elseif ($size < 16777216) {
            $this->dbFieldType = 'MEDIUMBLOB';
        } else {
            $this->dbFieldType = 'LONGBLOB';
        }
        
        return parent::getMysqlAttr();
    }
    
    
    /**
     * Връща вербално представяне на стойността на двоичното поле
     */
    public function toVerbal($value)
    {
        if (empty($value)) {
            return;
        }
        $value = $this->fromMysql($value);

        if ($value && !$this->params['binary']) {
            $value = ht::wrapMixedToHtml(ht::mixedToHtml($value, 1));

            return $value;
        }

        setIfNot($rowLen, $this->params['rowLen'], 16);
        setIfNot($maxRows, $this->params['maxRows'], 100);
        $len = min(strlen($value), $rowLen * $maxRows);
        
        $dbAttr = $this->getMysqlAttr();
        
        switch ($dbAttr->dbFieldType) {
            case 'TINYBLOB': $offsetLen = 2; break;
            case 'BLOB': $offsetLen = 4; break;
            case 'MEDIUMBLOB': $offsetLen = 6; break;
            case 'LONGBLOB': $offsetLen = 8; break;
        }
        
        $res = new ET("<pre style='font-family:Courier New;'>[#ROWS#]</pre>");
        
        $rowsCnt = $len / $rowLen;

        for ($i = 0; $i < $rowsCnt; $i++) {
            $offset = sprintf("%0{$offsetLen}X", $i * $rowLen);
            $str = '';
            $hex = '';
            
            for ($j = 0; $j < 16; $j++) {
                if ($i * $rowLen + $j < $len) {
                    $c = $value{$i * $rowLen + $j};
                    
                    if (ord($c) >= 32 && ord($c) <= 127) {
                        $str .= htmlentities($c, ENT_COMPAT | ENT_HTML401, 'UTF-8');
                    } else {
                        if (ord($c) < 32) {
                            $str .= '<span style=\"color:grey\">&copy;</span>';
                        } else {
                            $str .= '<span style=\"color:grey\">&reg;</span>';
                        }
                    }
                    $hex .= sprintf('%02X', ord($c)) . '&nbsp;';
                } else {
                    $str .= ' ';
                    $hex .= '  &nbsp;';
                }
            }
            
            $res->append(new ET("[#1#]: [#2#] [#3#]\n", $offset, $str, $hex), 'ROWS');
        }
        
        return $res;
    }
    
    
    /**
     * Връща представяне подходящо за MySQL на дълги двоични данни
     * По-точно това е дълго 16-тично число
     *
     * @param  string $value
     * @return string
     */
    public function toMysql($value, $db, $notNull, $defValue)
    {
        // Ако е указано - сериализираме
        if ($value !== null && $value !== '' && $this->params['serialize']) {
            $value = serialize($value);
        }
        
        // Ако е указано - компресираме
        if ($value !== null && $value !== '' && $this->params['compress']) {
            if (($level = (int) $this->params['compress']) > 0) {
                $value = gzcompress($value, $level);
            } else {
                $value = gzcompress($value);
            }
        }

        if ($value !== null && $value !== '') {
            $value = (string) $value;

            if ($value) {
                $res = "'" . $db->escape($value) . "'";

            //$res = '0x' . bin2hex($value);
            } else {
                $res = "''";
            }
        } else {
            $res = parent::toMysql($value, $db, $notNull, $defValue);
        }
        
        return $res;
    }


    /**
     * @see core_Type::fromMysql()
     * @param  string $value
     * @return mixed
     */
    public function fromMysql($value)
    {
        if (is_scalar($value)) {
            // Ако е указано - декомпресираме
            if ($value !== null && $value !== '' && $this->params['compress']) {
                $valueUnCompr = @gzuncompress($value);
                
                // Ако компресирането е било успешно
                if ($valueUnCompr !== false) {
                    
                    // Използваме го
                    $value = $valueUnCompr;
                }
            }
            
            // Ако е указано - десериализираме
            if ($value !== null && $value !== '' && $this->params['serialize']) {
                $value = @unserialize($value);
            }
        }
        
        return parent::fromMysql($value);
    }
}
