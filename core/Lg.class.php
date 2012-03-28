<?php



/**
 * Клас 'core_Lg' - Мениджър за многоезичен превод на интерфейса
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Lg extends core_Manager
{
    
    
    /**
     * Речник
     */
    var $dict = array();
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Превод на интерфейса';
    
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'translator,admin';
    
    
    /**
     * Кой може да записва?
     */
    var $canWrite = 'translator,admin';
    
    
    /**
     * Плъгини и MVC класове за предварително зареждане
     */
    var $loadList = 'plg_Created,plg_SystemWrapper,plg_RowTools';
    
    
    /**
     * Описание на полетата на модела
     */
    function description()
    {
        $this->FLD('kstring', 'varchar(size=34)', array('caption' => 'Стринг'));
        $this->FLD('translated', 'text', array('caption' => 'Превод'));
        $this->FLD('lg', 'varchar(2)', array('caption' => 'Език'));
        
        $this->setDbUnique('kstring,lg');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Set()
    {
        $lg = Request::get('lg');
        
        $this->set($lg);
        
        followRetUrl();
    }
    
    
    /**
     * Задава за текущия език на интерфейса, валиден за сесията
     */
    function set($lg)
    {
        if ($lg) {
            Mode::setPermanent('lg', $lg);
        }
    }
    
    
    /**
     * Временно (до извикването на self::pop()) променя текущия език
     */
    static function push($lg)
    {
        Mode::push('lg', $lg);
    }
    
    
    /**
     * Връща старата стойност на текущия език
     */
    static function pop()
    {
        Mode::pop('lg');
    }
    
    
    /**
     * Превежда зададения ключов стринг
     */
    function translate($kstring, $key = FALSE, $lg = NULL)
    {
        // Празните стрингове не се превеждат
        if (is_Object($kstring) || !trim($kstring)) return $kstring;
        
        if (!$key) {
            // Разбиваме стринга на участъци, който са разделени със символа '|'
            $strArr = explode('|', $kstring);
            
            if (count($strArr) > 1) {
                $translated = '';
                
                // Ако последната или първата фраза за празни - махаме ги
                if($strArr[count($strArr)-1] == '') {
                    unset($strArr[count($strArr)-1]);
                }
                
                if($strArr[0] == '') {
                    unset($strArr[0]);
                }
                
                foreach ($strArr as $i => $phrase) {
                    
                    // Две черти една до друга, ескейпват една
                    if ($phrase === '') {
                        $translated .= '|';
                        continue;
                    }
                    
                    $isFirst = FALSE;
                    
                    // Ако фразата започва с '*' не се превежда
                    if ($phrase{0} === '*') {
                        $translated .= substr($phrase, 1);
                        continue;
                    }
                    $translated .= $this->translate($phrase);
                }
                
                return $translated;
            }
            
            $key = $kstring;
        }
        
        $key = str::convertToFixedKey($key, 32, 4);
        
        // Ако не е зададен език, превеждаме на текущия
        if (!$lg) {
            $lg = core_LG::getCurrent();
        }
        
        if(!count($this->dict)) {
            $this->dict = core_Cache::get('translation', $lg, 2 * 60 * 24, array('core_Lg'));
            
            if(!$this->dict) {
                $query = self::getQuery();
                
                while($rec = $query->fetch("#lg = '{$lg}'")) {
                    $this->dict[$rec->kstring][$lg] = $rec->translated;
                }
                core_Cache::set('translation', $lg, $this->dict, 2 * 60 * 24, array('core_Lg'));
            }
        }
        
        // Ако имаме превода в речника, го връщаме
        if (isset($this->dict[$key][$lg])) return $this->dict[$key][$lg];
        
        // Попълваме речника от базата
        $rec = $this->fetch(array(
                "#kstring = '[#1#]' AND #lg = '[#2#]'",
                $key,
                $lg
            ));
        
        if ($rec) {
            $this->dict[$key][$lg] = $rec->translated;
        } else {
            // Ако и в базата нямаме превода, тогава приемаме 
            // че превода не променя ключовия стринг
            if (!$translated) {
                $translated = $kstring;
            }
            
            $rec = new stdClass();
            $rec->kstring = $key;
            $rec->translated = $translated;
            $rec->lg = $lg;
            
            // Записваме в модела
            $this->save($rec);
            
            // Записваме в кеш-масива
            $this->dict[$key][$lg] = $rec->translated;
        }
        
        return $rec->translated;
    }
    
    
    /**
     * Връща текущия език
     */
    static function getCurrent()
    {
        $lg = Mode::get('lg');
        
        if (!$lg) {
            $lg = EF_DEFAULT_LANGUAGE;
        }
        
        return $lg;
    }
    
    
    /**
     * Извиква се преди подготовката на масивите $data->recs и $data->rows
     */
    function on_BeforePrepareListRecs($invoker, &$res, $data)
    {
        // Подрежда словосъчетанията по обратен на постъпването им ред
        $data->query->orderBy(array(
                'id' => 'DESC'
            ));
        
        $data->listFilter->FNC('filter', 'varchar', 'caption=Филтър,input');
        
        $data->listFilter->setOptions('lg', array(
                'bg' => 'Български',
                'en' => 'Английски'
            ));
        
        $data->listFilter->showFields = 'filter,lg';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        $filterRec = $data->listFilter->input();
        
        if ($filterRec) {
            if ($filterRec->lg) {
                $data->query->where("#lg = '{$filterRec->lg}'");
            }
            
            if ($filterRec->filter) {
                $data->query->where(array(
                        "#kstring LIKE '%[#1#]%'",
                        $filterRec->filter
                    ));
            }
        }
        
        $data->listFilter->layout = new ET(
            "\n<form style='margin:0px;'  method=\"[#FORM_METHOD#]\" action=\"[#FORM_ACTION#]\"" .
            "<!--ET_BEGIN ON_SUBMIT-->onSubmit=\"[#ON_SUBMIT#]\"<!--ET_END ON_SUBMIT-->>" .
            "\n<table cellspacing=0 >" .
            "\n<tr>[#FORM_FIELDS#]<td>[#FORM_TOOLBAR#]</td></tr>" .
            "\n</table></form>\n");
        
        $data->listFilter->fieldsLayout = "<td>[#filter#]</td><td>[#lg#]</td>";
    }
    
    
    /**
     * Връща последователност от хипервръзки (<а>) за установяване
     * на езиците, посочени в аргумента array ('language' => 'title')
     */
    static function getLink($lgArr)
    {
        $tpl = new ET();
        
        foreach ($lgArr as $lg => $title) {
            if (core_Lg::getCurrent() != $lg) {
                if ($div)
                $tpl->append(' | ');
                $tpl->append(ht::createLink($title, array(
                            'core_Lg',
                            'Set',
                            'lg' => $lg,
                            'ret_url' => TRUE
                        )));
                $div = TRUE;
            }
        }
        
        return $tpl;
    }
}


/**
 * Езикът по подразбиране е български
 */
defIfNot('EF_DEFAULT_LANGUAGE', 'bg');
