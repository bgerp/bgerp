<?php 

/**
 * Кеш за търсения
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class core_QueryCnts extends core_Manager
{
    /**
     * Константа за записване в кеша
     */
    const CACHE_PREFIX = 'pagerCnt2';
    
    
    /**
     * Колко време да се кешира информацията за броя на резултатите
     */
    const CACHE_LIFETIME = 4320; // Три дни в минути
    
    
    /**
     * Заявки, чакащи за преброяване
     */
    protected $queries = array();
    
    
    /**
     * Отложено на shutdown изчисляване броя на записите в заявката
     */
    public static function delayCount($query)
    {
        $me = cls::get('core_QueryCnts');
        $me->queries[self::getHash($query)] = $query;
        
        //$me->on_Shutdown();
    }
    
    
    /**
     * Връща кешираната стойност за броя на резултатите в заявката
     */
    public static function getFromChache($query, $part = 'cnt')
    {
        if (is_object($query)) {
            $hash = self::getHash($query);
        } else {
            $hash = $query;
        }
        
        $data = core_Cache::get(self::CACHE_PREFIX, $hash);
        
        if (!empty($part)) {
            $res = $data->{$part};
        } else {
            $res = $data;
        }
        
        return $res;
    }
    
    
    /**
     * Връща кешираната стойност за броя на резултатите в заявката
     */
    public static function set($query, $cnt, $start = null)
    {
        if (is_object($query)) {
            $hash = self::getHash($query);
        } else {
            $hash = $query;
        }
        
        $data = (object) array('cnt' => $cnt, 'time' => time());
        
        if ($start) {
            $data->calcTime = time() - $start;
        }
        
        $res = core_Cache::set(self::CACHE_PREFIX, $hash, $data, self::CACHE_LIFETIME);
        
        return $res;
    }
    
    
    /**
     * Връща хеш за посочената заявка.
     * Като страничен резултат я оптимизира за преброяване
     */
    private static function getHash($query)
    {
        $query->orderBy = array();
        $query->show('id');
        $hash = $query->getHash(true);
        
        return $hash;
    }
    
    
    /**
     * Изпълнява се преди терминиране на процеса, но след изпращане на резултата към клиента
     */
    public function on_Shutdown()
    {
        $divorceSearchMin = 100000; // Когато id-то е над 100К - тогава да сработва
        $divorceSearchMax = 1000000; // Ако записите са над 1М - да ги разделя спрямо бройката, като мин е 3
        
        foreach ($this->queries as $hash => $qCnt) {
            $slowQueryDelimiter = 3; // По подразбиране да ги разделя на 3 групи
            $lastRec = self::getFromChache($hash, null);
            $cnt = false;
            
            if ($lastRec) {
                $cnt = $lastRec->cnt;
                if (time() - $lastRec->time < 60) {
                    continue;
                }
            }
            self::set($hash, $cnt);
            
            $start = time();
            
            // Разделяме заявката на интервали - за да не блокира SELECT заявките със същия приоритет
            $haveCnt = false;
            if ($qCnt->isSlowQuery) {
                if ($qCnt->mvc) {
                    $q = $qCnt->mvc->getQuery();
                    $q->XPR('maxId', 'int', 'max(#id)');
                    $q->show('maxId');
                    $qRec = $q->fetch();
                    
                    if ($qRec) {
                        $maxId = $qRec->maxId;
                        if ($maxId && ($maxId > $divorceSearchMin)) {
                            if ($maxId > $divorceSearchMax) {
                                $slowQueryDelimiterTmp = floor($maxId / $divorceSearchMax);
                                $slowQueryDelimiter = max($slowQueryDelimiterTmp, $slowQueryDelimiter);
                            }
                            
                            $sDelim = (int) ($maxId / $slowQueryDelimiter);
                            $cnt = 0;
                            $haveCnt = true;
                            for ($i = 1; $i <= $slowQueryDelimiter; $i++) {
                                $cQuery = clone $qCnt;
                                $sDelimTmp = $sDelim * $i;
                                if ($i == 1) {
                                    $cQuery->where("#id < {$sDelim}");
                                } elseif ($i != $slowQueryDelimiter) {
                                    $sDelimTmp = $sDelim * $i;
                                    $cQuery->where("#id >= {$sDelimTmpFrom}");
                                    $cQuery->where("#id < {$sDelimTmp}");
                                } else {
                                    $cQuery->where("#id >= {$sDelimTmpFrom}");
                                }
                                
                                $sDelimTmpFrom = $sDelimTmp;
                                
                                $cnt += $cQuery->count();
                                
                                if (haveRole('debug')) {
                                    // 0.01-0.05 сек
                                    $ms = rand(10000, 50000);
                                } else {
                                    // 0.1-0.5 сек
                                    $ms = rand(100000, 500000);
                                }
                                
                                usleep($ms);
                            }
                        }
                    }
                }
            }
            
            if (!$haveCnt) {
                $cnt = $qCnt->count();
            }
            
            self::set($hash, $cnt, $start);
        }
    }
}
