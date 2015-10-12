<?php



/**
 * Клас 'git_Lib' - Пакет за работа с git репозиторита
 *
 *
 * @category  vendors
 * @package   git
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class git_Lib
{


    /**
     * Изпълнява git команда и връща стрингoвия резултат
     *
     * @param string $cmd - Git командни параметри
     * @param array() $output - масив с резултати
     * @return boolean - При успешна команда - TRUE
     */
    private static function cmdExec ($cmd, &$output)
    {
        exec("git {$cmd}", $output, $returnVar);
			
        return ($returnVar == 0); 
    }

    /**
     * Връща текущият бранч на репозитори
     * 
     * @param string $repoPath - път до git репозитори
     * @param array() $output - масив с резултати
     * @return boolean - При неуспех - FALSE или текущият бранч
     */
    private static function currentBranch($repoPath, &$log)
    {
        $command = " --git-dir=\"{$repoPath}/.git\" rev-parse --abbrev-ref HEAD 2>&1";

        $repoName = basename($repoPath);

        // Първият ред съдържа резултата
        if (self::cmdExec($command, $res)) {
            
            return trim($res[0]);
        }
        
        return FALSE;
    }

    
    /**
     * Сетва репозитори в зададен бранч.
     *
     * @param string $repoPath - път до git репозитори
     * @param array() $log - масив с резултати
     * @return boolean - При неуспех - FALSE или текущият бранч
     */
    public static function checkout($repoPath, &$log, $branch='master')
    {
        $repoName = basename($repoPath);

        $currentBranch = self::currentBranch($repoPath, $log);

        if ($currentBranch == $branch) {
            return TRUE;
        }
        
        $commandFetch = " --git-dir=\"{$repoPath}/.git\" fetch origin +{$branch}:{$branch} 2>&1";
        
        $commandCheckOut = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" checkout -f {$branch} 2>&1";
     
        if (!self::cmdExec($commandFetch, $arrRes)) {
            foreach ($arrRes as $val) {
                $log[] = (!empty($val))?("[$repoName]: грешка при превключване в {$branch} fetch:" . $val):"";
            }
            
            return FALSE;
        } else {
            if (!self::cmdExec($commandCheckOut, $arrRes)) {
                foreach ($arrRes as $val) {
                    $log[] = (!empty($val))?("[$repoName]: грешка при превключване в {$branch} checkOut:" . $val):"";
                }
                
                return FALSE;
            } else {
                // Ако и двете команди са успешни значи всичко е ОК
                $log[] = "[$repoName]: превключен {$branch} бранч.";
                
                return TRUE;
            }
            
        }

        return FALSE;
    }
    
    
    /**
     * Прилага последните промени в текущия бранч.
     *
     * @param string $repoPath - път до git репозитори
     * @param array() $log - масив с резултати
     * @return boolean - При неуспех - FALSE
     */
    public static function pull($repoPath, &$log)
    {
        $repoName = basename($repoPath);
        
        $currBranch = self::currentBranch($repoPath, $log);
        
        $commandFetch = " --git-dir=\"{$repoPath}/.git\" fetch origin " . $currBranch . " 2>&1";

        $commandMerge = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge FETCH_HEAD 2>&1"; //origin/" . BGERP_GIT_BRANCH ." 2>&1";
        
        // За по голяма прецизност е добре да се пусне и git fetch
        
        if (!self::cmdExec($commandFetch, $arrResFetch)) {
            foreach ($arrResFetch as $val) {
                $log[] = (!empty($val))?("[$repoName]: грешка при fetch: " . $val):"";
            }
            
            return FALSE;
        }
      
        if (!self::cmdExec($commandMerge, $arrResMerge)) {
            foreach ($arrResMerge as $val) {
                $log[] = (!empty($val))?("[$repoName]: грешка при merge origin/" . $currBranch .": " . $val):"";
            }
            
            return FALSE;
        }
        
        $log[] = "[{$repoName}]: е обновено.";
                
        return TRUE;
    }

    /**
     * Проверява мърджа дали ще е успешен между branch1 -> branch2
     * 
     * @param string $repoPath - път до git репозитори
     * @param array() $log - масив с резултати
     * @param string $branch1 - име на бранч източник
     * @param string $branch2 - име на бранч приемник
     * @return boolean - При неуспех - FALSE
     */
    public static function mergeBeSuccess($repoPath, &$log, $branch1, $branch2)
    {
        
        if (!self::checkout($repoPath, $log, $branch1)) return FALSE;
        if (!self::pull($repoPath, $log)) return FALSE;
        if (!self::checkout($repoPath, $log, $branch2)) return FALSE;
        if (!self::pull($repoPath, $log)) return FALSE;
        
        $commandMerge = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge --no-commit {$branch1}";
        $res = self::cmdExec($commandMerge, $log);
        
        $commandMergeAbort = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge --abort";
        self::cmdExec($commandMergeAbort, $log);
            
        if (!$res) {
            $log[] = "Бъдещ ПРОБЛЕМЕН merge.";
            return FALSE;
        }
        $log[] = "Бъдещ безпроблемен merge $branch1 -> $branch2";
        
        return TRUE;
    }

    
    /**
     * Мърджва 2 бранча branch1 -> branch2
     * 
     * @param string $repoPath - път до git репозитори
     * @param array() $log - масив с резултати
     * @param string $branch1 - име на бранч източник
     * @param string $branch2 - име на бранч приемник
     * @return boolean - При неуспех - FALSE
     */
    public static function merge($repoPath, &$log, $branch1, $branch2)
    {
        
        if (!self::checkout($repoPath, $log, $branch1)) return FALSE;
        if (!self::pull($repoPath, $log)) return FALSE;
        if (!self::checkout($repoPath, $log, $branch2)) return FALSE;
        if (!self::pull($repoPath, $log)) return FALSE;
        $commandMerge = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge {$branch1}";
        if(!self::cmdExec($commandMerge, $log)) return FALSE;
        $log[] = "Успешен merge $branch1 -> $branch2";
        
        return TRUE;
    }


    /**
     * Качва промените от резултатния бранч
     *
     * @param string $repoPath - път до git репозитори
     * @param array() $log - масив с резултати
     * @return boolean - При неуспех - FALSE
     */
    public static function push($repoPath, &$log)
    {
        $repoName = basename($repoPath);
        
        $currBranch = self::currentBranch($repoPath, $log);
        
        $commandPush = " --git-dir=\"{$repoPath}/.git\" push origin {$currBranch}";
        if(!self::cmdExec($commandPush, $log)) return FALSE;
        $log[] = "[{$repoName}]: успешен push {$currBranch}";
        
        return TRUE;
    }
}

