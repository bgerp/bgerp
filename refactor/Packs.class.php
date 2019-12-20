<?php


/**
 * Клас 'refactor_Interfaces' - Търсене на нереализирани методи
 *
 * Зарежда последователно всички класове от core_Classes.
 * Проверява техните интерфейси и плъгини за реализация
 * на интерфейсни методи. При липса на такава реализация
 *
 * @return array $missingMethod[$cInst->className][$iRefl->name] = $method;
 *               Масив с името на класа, името на интерфейса и не реализирания метод
 *
 * @category  vendors
 * @package   php
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class refactor_Packs extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Пакети в системата';
    
    
    /**
     * @todo Чака за документация...
     */
    public $loadList = 'plg_Sorting,plg_Created,refactor_Wrapper';
    
    
    /**
     * Описанието на модела
     */
    public function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Пакет');
        
        $this->FLD('description', 'varchar', 'caption=Описание');
        
        $this->FLD('usedIn', 'text', 'caption=Използване');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Проверка за реализация на интерфейсните методи.
     * Връща списък с всички липсващи методи
     */
    public function act_Extract()
    {
        requireRole('debug');
        
        core_App::setTimeLimit(200);
        $repos = core_App::getRepos();
        foreach (array_keys($repos) as $r) {
            if (strpos($r, 'bgerp') === false) {
                continue;
            }
            foreach (refactor_Formater::readAllFiles($r, '#Setup.class\\.php#i') as $f) {
                $files[] = $f;
            }
            $repo = $r;
        }
        
        asort($files);
        
        foreach ($files as $f) {
            $f = str_replace($repo . '/', '', $f);
            $f = str_replace('/', '_', $f);
            $f = str_replace('.class.php', '', $f);
            
            if (!cls::load($f, true)) {
                $debug[] = $f;
                continue;
            }
            $cls = cls::get($f);
            
            $rec = new stdClass();
            
            list($rec->name, ) = explode('_', $f);
            $rec->description = $cls->info;
            
            $res[$rec->name] = $rec;
            
            $packPtr .= ($packPtr ? '|' : '') . $rec->name;
        }
        
        // От всеки файл вадим стринговете, които изглеждат, като имена на пакети: /[^a-z]([a-z]+)\_[A-Z][a-zA-Z0-9]+/
        $ptr = "/[^a-z]({$packPtr})\\_[a-z0-9_]{0,30}[A-Z][a-z0-9]+/";
        
        
        // Извличаме всички файлове
        foreach ($res as &$p) {
            $path = $repo . '/' . $p->name;
            
            $p->credit = 0;
            
            foreach (refactor_Formater::readAllFiles($path, '#\\.class\\.php#i') as $f) {
                $str = file_get_contents($f);
                $matches = array();
                preg_match_all($ptr, $str, $matches);
                if (is_array($matches[1]) && count($matches[1])) {
                    foreach ($matches[1] as $pName) {
                        if ($pName == $p->name) {
                            continue;
                        }
                        $p->used[$pName] = $pName;
                    }
                }
            }
            
            foreach ($p->used as $pName) {
                $res[$pName]->credit++;
            }
        }
        
        
        // За всеки пакет гледаме
        for ($i = 1; $i < 500; $i++) {
            foreach ($res as &$p) {
                foreach ($p->used as $usedP) {
                    $d = min(rand(0, 15) / 10, $p->credit / (count($res[$usedP]) * rand(80, 110)));
                    
                    if ($res[$usedP]->credit < $p->credit) {
                        $res[$usedP]->credit += $d;
                        $p->credit -= $d;
                    } else {
                        $res[$usedP]->credit += $d / 6;
                        $p->credit -= $d / 6;
                    }
                }
            }
            
            $bad = self::calcBad($res);
            if (!$best || $bad < $bestBad) {
                $best = $res;
                $bestBad = $bad;
            } else {
                if (rand(1, 160) == 50) {
                    $res = $best;
                }
            }
        }
        
        $res = $best;
        
        $bad = self::calcBad($res);
        
        $html = "[h2]Пресичания: {$bad}[/h2]<br>";
        
        $res1 = $res;
        
        foreach ($res as &$p) {
            $html .= "Пакет: [b]{$p->name}[/b]<br>";
            $html .= "Описание: {$p->description}<br>";
            $used = $usedFrom = '';
            foreach ($p->used as $usedP) {
                if ($res[$usedP]->credit < $p->credit) {
                    $used .= ', [color=red]' . $usedP . '[/color]';
                } else {
                    $used .= ', ' . $usedP . '';
                }
            }
            $used = trim($used, ' ,');
            
            foreach ($res1 as $p1) {
                if ($p1->used[$p->name]) {
                    if ($p1->credit > $p->credit) {
                        $usedFrom .= ', [color=red]' . $p1->name . '[/color]';
                    } else {
                        $usedFrom .= ', ' . $p1->name . '';
                    }
                }
            }
            $usedFrom = trim($usedFrom, ' ,');
            
            $html .= "Използва: {$used}<br>";
            $html .= "Използва се от: {$usedFrom}<br>";
            
            $html .= '<br>';
        }
        
        return $html;
        
        // Добавяме масива с пакети към текущия пакет $depends[$pack] += $packs
    }
    
    
    private static function calcBad(&$res)
    {
        $bad = 0;
        arr::sortObjects($res, 'credit', 'desc');
        foreach ($res as &$p) {
            foreach ($p->used as $usedP) {
                if ($res[$usedP]->credit < $p->credit) {
                    $bad++;
                }
            }
        }
        
        return $bad;
    }
}
