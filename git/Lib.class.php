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
     * Проверява за валидна git команда
     */
    private static function Exists ()
	{
	    // Проверяваме дали Git е инсталиран
	    exec('git', $output, $returnVar);
	    if (strpos($output['0'], "usage: git") !== FALSE) {
	
	        return TRUE;
	    }
    
    	return FALSE;    
	}

	/**
	 * Връща текущият бранч на репозитори
	 */
	private static function currentBranch($repoPath, &$log)
	{
		if (!self::Exists()) {
	    	$log[] = "err:Не е открит Git!";
	    	
	    	return FALSE;
	    }
		
	    $command = "git --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$repoPath}\" branch";
	
	    
		exec($command, $arrRes, $returnVar);
		// Търсим реда с текущият бранч
		foreach ($arrRes as $row) {
			if (strpos($row, "*") !== FALSE) {
				return trim(substr($row, strpos($row, "*")+1, strlen($row)));
			}
		}
		$repoName = basename($repoPath);
	    $log[] = "err: {$repoName} няма текущ бранч!";
	    
		return FALSE;
	}

	
	/**
	 * Сетва репозитори в зададен бранч.
	 */
	private static function checkout($repoPath, &$log, $branch='master')
	{
		if (!self::Exists()) {
	    	$log[] = "err:Не е открит Git!";
	    	
	    	return FALSE;
	    }
		
	    $repoName = basename($repoPath);
	    
	    $currentBranch = self::currentBranch($repoPath, $log);
	    
	    if ($currentBranch === FALSE) {
	    	return FALSE;
	    }
	    
	    if ($branch == $currentBranch) {
	    	return TRUE;
	    }
	    
		$commandFetch = "git --git-dir=\"{$repoPath}/.git\" fetch origin +{$branch}:{$branch} 2>&1";
		
		$commandCheckOut = "git --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$branch}\" checkout {$branch} 2>&1";
			
		exec($commandFetch, $arrRes, $returnVar);
		exec($commandCheckOut , $arrRes, $returnVar);
		// Проверяваме резултата
		foreach ($arrRes as $row) {
			if (strpos($row, "Switched to branch '{$branch}'") !== FALSE) {
				$log[] = "info: $repoName превключен {$branch} бранч.";
				
				return TRUE;
			}
		}
	    $log[] = "err: Грешка при превключване в бранч {$branch} на репозитори - $repoName";
	    
		return FALSE;
	}
	
	
	/**
	 * Прилага последните промени в даден бранч.
	 */
	private static function fetch($repoPath, &$log, $branch='master')
	{
		// Ако желания бранч е текущия: fetch -> megre origin/$branch
		// Ako бранча не е текущия: git fetch origin +$branch:$branch
	}

	
	/**
	 * Мърджва 2 бранча.
	 */
	public static function merge($repoPath, &$log, $branch1, $branch2)
	{
		if (!self::fetch($repoPath, $log, $branch1)) return FALSE;
		if (!self::fetch($repoPath, $log, $branch2)) return FALSE;
		if (!self::checkout($repoPath, $log, $branch2)) return FALSE;
		$commandMerge = "git --git-dir=\"{$repoPath}/.git\" --work-tree=\"{$branch}\" merge "
		exec();
		
	}
}