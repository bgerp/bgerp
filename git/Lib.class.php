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
     */
    private static function cmdExec ($cmd, &$output)
    {
        exec(BGERP_GIT_PATH . " {$cmd}", $output, $returnVar);
        
        return ($returnVar == 0);  
    }

    /**
     * Връща текущият бранч на репозитори
     */
    private static function currentBranch($repoPath, &$log)
    {
        $command = " --git-dir=\"{$repoPath}/.git\" rev-parse --abbrev-ref HEAD 2>&1";

        $repoName = basename($repoPath);

        // Първият ред съдържа резултата
        if (cmdExec($command, $res)) {
            
            return trim($res[0]);
        }
        
        return FALSE;
    }

    
    /**
     * Сетва репозитори в зададен бранч.
     */
    private static function checkout($repoPath, &$log, $branch='master')
    {
        $repoName = basename($repoPath);

        $currentBranch = self::currentBranch($repoPath, $log);

        if ($currentBranch == $branch) {
            return TRUE;
        }
        
        $commandFetch = " --git-dir=\"{$repoPath}/.git\" fetch origin +{$branch}:{$branch} 2>&1";
        
        $commandCheckOut = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" checkout {$branch} 2>&1";
     
        if (!self::cmdExec($commandFetch, $arrRes)) {
            foreach ($arrRes as $val) {
                $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при превключване в {$branch} fetch:" . $val):"";
            }
            
            return FALSE;
        } else {
            if (!self::cmdExec($commandCheckOut, $arrRes)) {
                foreach ($arrRes as $val) {
                    $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при превключване в {$branch} checkOut:" . $val):"";
                }
                
                return FALSE;
            } else {
                // Ако и двете команди са успешни значи всичко е ОК
                $log[] = "info: [<b>$repoName</b>] превключен {$branch} бранч.";
                
                return TRUE;
            }
            
        }

        return FALSE;
    }
    
    
    /**
     * Прилага последните промени в текущия бранч.
     */
    private static function pull($repoPath, &$log)
    {
        $repoName = basename($repoPath);
        
        $commandFetch = " --git-dir=\"{$repoPath}/.git\" fetch origin " . self::currentBranch() . " 2>&1";

        $commandMerge = " --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge FETCH_HEAD 2>&1"; //origin/" . BGERP_GIT_BRANCH ." 2>&1";
        
        // За по голяма прецизност е добре да се пусне и git fetch
        
        if (!self::cmdExec($commandFetch, $arrResFetch)) {
            foreach ($arrResFetch as $val) {
                $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при fetch: " . $val):"";
            }
            
            return FALSE;
        }
      
        if (!self::cmdExec($commandMerge, $arrResMerge)) {
            foreach ($arrResMerge as $val) {
                $log[] = (!empty($val))?("err: [<b>$repoName</b>] грешка при merge origin/" . BGERP_GIT_BRANCH .": " . $val):"";
            }
            
            return FALSE;
        }
        
        $log[] = "new:<b>[{$repoName}]</b> е обновено.";
                
        return TRUE;
    }

    
    /**
     * Мърджва 2 бранча.
     * branch1 -> branch2
     */
    public static function merge($repoPath, &$log, $branch1, $branch2)
    {
        if (!self::pull($repoPath, $log, $branch1)) return FALSE;
        if (!self::pull($repoPath, $log, $branch2)) return FALSE;
        if (!self::checkout($repoPath, $log, $branch2)) return FALSE;
        $commandMerge = "git --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" merge {$branch1}";
        exec($commandMerge);
        
    }


    /**
     * Качва промените от резултатния бранч
     *
     */
    public static function push($repoPath, &$log, $branch)
    {
    }
}
