<?php


/**
 * Клас 'git_Lib' - Пакет за работа с git репозиторита
 *
 *
 * @category  vendors
 * @package   git
 *
 * @author    Dimitar Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class git_Lib
{
    /**
     * Изпълнява git команда и връща стринговия резултат
     *
     * @param string  $cmd   - Git командни параметри
     * @param array() $lines - масив с резултати
     * @param $path - път до репозиторито
     *
     * @return bool - При успешна команда - TRUE
     */
    private static function cmdExec($cmd, &$lines, $path)
    {
        $path = escapeshellarg($path);

        $c = "git -c safe.directory={$path} -C {$path} {$cmd} 2>&1";
        
        exec($c, $lines, $returnVar);
        
        return ($returnVar === 0);
    }
    
    
    /**
     * Връща текущият бранч на репозитори
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с логове
     *
     * @return bool - При неуспех - FALSE или текущият бранч
     */
    public static function currentBranch($repoPath, &$log)
    {
        $command = 'rev-parse --abbrev-ref HEAD 2>&1';
        
        // Първият ред съдържа резултата
        if (self::cmdExec($command, $res, $repoPath)) {
            
            return trim($res[0]);
        }
        
        $repoName = basename($repoPath);
        $log[] = "[{$repoName}]: Неуспешно извличане на текущия бранч";
        
        return false;
    }
    
    
    /**
     * Връща текущият бранч на репозитори
     *
     * @param string $repoPath - път до git репозитори
     * @param array  $log      - масив с логове
     *
     * @return array масив със всички тагове
     */
    public static function getTags($repoPath, &$log)
    {
        $command = 'tag 2>&1';
        
        // Първият ред съдържа резултата
        if (self::cmdExec($command, $res, $repoPath)) {
            
            return $res;
        }
        
        $repoName = basename($repoPath);
        $log[] = "[{$repoName}]: Неуспешно извличане на таговете";
        
        return false;
    }

    
    /**
     * Обновява таговете на репозиторито
     *
     * @param string $repoPath - път до git репозитори
     * @param array  $log      - масив с логове
     *
     * @return  bool - FALSE при неуспех - или резултат от командата
     */
    public static function fetchTags($repoPath, &$log = null)
    {
        $command = 'fetch --tags 2>&1';
        $res = '';
        // Първият ред съдържа резултата
        if (self::cmdExec($command, $res, $repoPath)) {
            
            return $res;
        }
        
        $repoName = basename($repoPath);
        $log[] = "[{$repoName}]: Неуспешно фечване на тагове";
        
        return false;
    }
    
    
    /**
     * Връща отдалеченото git URL на репозиторито
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с логове
     *
     * @return bool - При неуспех - FALSE или URL
     */
    public static function getRemoteUrl($repoPath, &$log)
    {
        $command = 'config --get remote.origin.url 2>&1';
        
        // Първият ред съдържа резултата
        if (self::cmdExec($command, $res, $repoPath)) {
            
            return trim($res[0]);
        }
        
        $repoName = basename($repoPath);
        $log[] = "[{$repoName}]: Неуспешно извличане на отдалечено URL";
        
        return false;
    }
    
    
    /**
     * Извлича информация за последния комит
     */
    public static function getLastCommit($repoPath, &$log = null)
    {
        $command = 'log -1 --abbrev-commit';
        
        if (self::cmdExec($command, $out, $repoPath)) {
            $res = (object) array('commit' => null, 'date' => null, 'author' => null);
            
            foreach ($out as $line) {
                $parts = explode(' ', $line, 2);
                $h = $parts[0];
                $value = $parts[1] ?? null;
                if ($h == 'commit' && !$res->commit) {
                    $res->commit = $value;
                }
                if ($h == 'Date:' && !$res->date) {
                    $res->date = date('Y-m-d H:i:s', strtotime($value));
                }
                if ($h == 'Author:' && !$res->author) {
                    $res->author = $value;
                }
            }
            
            return $res;
        }
        
        $repoName = basename($repoPath);
        
        $log[] = "[{$repoName}]: Неуспешно извличане на последен комит";
        
        return false;
    }
    
    
    /**
     * Сетва репозитори в зададен бранч.
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с логове
     *
     * @return bool - При неуспех - FALSE или текущият бранч
     */
    public static function checkout($repoPath, &$log, $branch = 'master')
    {
        $repoName = basename($repoPath);
        
        $currentBranch = self::currentBranch($repoPath, $log);
        
        if ($currentBranch == $branch) {
            
            return true;
        }
        
        $commandFetch = " fetch origin +{$branch}:{$branch} 2>&1";
        
        $commandCheckOut = " --work-tree=\"{$repoPath}\" checkout -f {$branch} 2>&1";
        
        if (!self::cmdExec($commandFetch, $arrRes, $repoPath)) {
            foreach ($arrRes as $val) {
                $log[] = (!empty($val))?("[{$repoName}]: грешка при превключване в {$branch} fetch:" . $val):'';
            }
            
            return false;
        }
        if (!self::cmdExec($commandCheckOut, $arrRes, $repoPath)) {
            foreach ($arrRes as $val) {
                $log[] = (!empty($val))?("[{$repoName}]: грешка при превключване в {$branch} checkOut:" . $val):'';
            }
            
            return false;
        }
        
        // Ако и двете команди са успешни значи всичко е ОК
        $log[] = "[{$repoName}]: превключен {$branch} бранч.";
        
        return true;
        
        return false;
    }
    
    
    /**
     * Прилага последните промени в текущия бранч.
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с резултати
     *
     * @return bool - При неуспех - FALSE
     */
    public static function pull($repoPath, &$log)
    {
        $repoName = basename($repoPath);
        
        $currBranch = self::currentBranch($repoPath, $log);
        
        $commandFetch = ' fetch origin ' . $currBranch . ' 2>&1';
        
        $commandMerge = " --work-tree=\"{$repoPath}\" merge FETCH_HEAD 2>&1";
        
        // За по голяма прецизност е добре да се пусне и git fetch
        
        if (!self::cmdExec($commandFetch, $lines, $repoPath)) {
            foreach ($lines as $val) {
                $log[] = (!empty($val))?("[{$repoName}]: грешка при fetch: " . $val):'';
            }
            
            return false;
        }
        
        if (!self::cmdExec($commandMerge, $lines, $repoPath)) {
            foreach ($lines as $val) {
                $log[] = (!empty($val))?("[{$repoName}]: грешка при merge origin/" . $currBranch .': ' . $val):'';
            }
            
            return false;
        }
        
        $log[] = "[{$repoName}]: е обновено.";
        
        return true;
    }
    
    
    /**
     * Проверява мърджа дали ще е успешен между branch1 -> branch2
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с резултати
     * @param string  $branch1  - име на бранч източник
     * @param string  $branch2  - име на бранч приемник
     *
     * @return bool - При неуспех - FALSE
     */
    public static function mergeBeSuccess($repoPath, &$log, $branch1, $branch2)
    {
        if (!self::checkout($repoPath, $log, $branch1)) {
            
            return false;
        }
        if (!self::pull($repoPath, $log)) {
            
            return false;
        }
        if (!self::checkout($repoPath, $log, $branch2)) {
            
            return false;
        }
        if (!self::pull($repoPath, $log)) {
            
            return false;
        }
        
        $commandMerge = " --work-tree=\"{$repoPath}\" merge --no-commit {$branch1}";
        $res = self::cmdExec($commandMerge, $lines, $repoPath);
        
        $commandMergeAbort = " --work-tree=\"{$repoPath}\" merge --abort";
        self::cmdExec($commandMergeAbort, $lines, $repoPath);
        
        if (!$res) {
            $log[] = 'Бъдещ ПРОБЛЕМЕН merge. -> ' . var_export($res, true) . ' ->' . var_export($lines, true);
            
            return false;
        }
        $log[] = "Бъдещ безпроблемен merge {$branch1} -> {$branch2}";
        
        return true;
    }
    
    
    /**
     * Мърджва 2 бранча branch1 -> branch2
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с резултати
     * @param string  $branch1  - име на бранч източник
     * @param string  $branch2  - име на бранч приемник
     *
     * @return bool - При неуспех - FALSE
     */
    public static function merge($repoPath, &$log, $branch1, $branch2)
    {
        if (!self::checkout($repoPath, $log, $branch1)) {
            
            return false;
        }
        if (!self::pull($repoPath, $log)) {
            
            return false;
        }
        if (!self::checkout($repoPath, $log, $branch2)) {
            
            return false;
        }
        if (!self::pull($repoPath, $log)) {
            
            return false;
        }
        
        $commandMerge = " --work-tree=\"{$repoPath}\" merge {$branch1}";
        
        if (!self::cmdExec($commandMerge, $lines, $repoPath)) {
            
            return false;
        }
        
        $log[] = "Успешен merge {$branch1} -> {$branch2}";
        
        return true;
    }
    
    
    /**
     * Качва промените от резултатния бранч
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с резултати
     *
     * @return bool - При неуспех - FALSE
     */
    public static function push($repoPath, &$log)
    {
        $repoName = basename($repoPath);
        
        $currBranch = self::currentBranch($repoPath, $log);
        
        $commandPush = " push origin {$currBranch}";
        
        if (!self::cmdExec($commandPush, $lines, $repoPath)) {
            
            return false;
        }
        
        $log[] = "[{$repoName}]: успешен push {$currBranch}";
        
        return true;
    }
    
    
    /**
     * Извикаване на URL от GitHub API
     */
    public static function gitHubApiCall($url)
    {
        // Initialize session and set URL.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        // Set so curl_exec returns the result instead of outputting it.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        
        $headers = array('User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0');
        
        if ($token = git_Setup::get('GITHUB_TOKEN')) {
            $headers[] = 'Authorization: token ' . $token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Get the response and close the channel.
        $result = curl_exec($ch);
        curl_close($ch);
        
        return $result;
    }
    
    
    /**
     * Връща unified diff-а между два бранча (origin/branch1 -> origin/branch2)
     *
     * Сравнява локалните remote-tracking бранчове.
     *
     * @param string  $repoPath - път до git репозитори
     * @param array() $log      - масив с логове
     * @param string  $branch1  - име на бранч източник
     * @param string  $branch2  - име на бранч приемник
     * @param bool    $fetch    - да се обновят ли remote-tracking рефовете преди сравнението
     *
     * @return string|false - текстът на diff-а или FALSE при грешка
     */
    public static function diff($repoPath, &$log, $branch1, $branch2, $fetch = true)
    {
        $repoName = basename($repoPath);

        $ref1 = escapeshellarg("origin/{$branch1}");
        $ref2 = escapeshellarg("origin/{$branch2}");

        // Обновяваме само refs/remotes/origin/* - не се пипат работната директория и локалните бранчове
        if ($fetch) {
            $fetchCmd = 'fetch origin ' . escapeshellarg($branch1) . ' ' . escapeshellarg($branch2);

            if (!self::cmdExec($fetchCmd, $fRes, $repoPath)) {
                $log[] = "[{$repoName}]: ВНИМАНИЕ - неуспешен fetch, показаните разлики може да са остарели: " . implode("\n", (array) $fRes);
            }
        }

        if (!self::cmdExec("diff -w {$ref1} {$ref2}", $res, $repoPath)) {
            $log[] = "[{$repoName}]: Неуспешно извличане на diff {$branch1} -> {$branch2}: " . implode("\n", (array) $res);

            return false;
        }

        return implode("\n", $res);
    }


    /**
     * Връща масив с файловете, които са променени в посоченото репозитори
     *
     * @param string  $repoPath          - път до git репозитори
     * @param array() $log               - масив с логове
     * @param bool    $includeLastCommit - да се включат ли и файловете от последния комит
     *
     * @return array - Масив с относителни пътища до променените файлове
     */
    public static function getDiffFiles($repoPath, &$log, $includeLastCommit = false)
    {
        if ($includeLastCommit) {
            $command = 'HEAD~1 diff --name-only';
        } else {
            $command = 'diff --name-only';
        }
        
        // Първият ред съдържа резултата
        if (self::cmdExec($command, $res, $repoPath)) {
            
            return $res;
        }
        
        $repoName = basename($repoPath);
        $log[] = "[{$repoName}]: Неуспешно извличане на променените файлове";
        
        return false;
    }
}
