<?php


/**
 * Клас 'bgerp_Index' -
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Index extends core_Manager
{
    /**
     * Дефолт екшън след логване
     */
    public function act_Default()
    {
        if (!cms_Content::fetch("#state = 'active'")) {
            requireRole('user');
            
            if (haveRole('powerUser')) {
                
                return new Redirect(array('bgerp_Portal', 'Show'));
            }
            
            return new Redirect(array('cms_Profiles', 'Single'));
        }
        
        return Request::forward(array('Ctr' => 'cms_Content', 'Act' => 'Show'));
    }
    
    
    /**
     * Екшън за покзване на информация за инсталацията 
     */
    public function act_About()
    {
        requireRole('powerUser');

        $resData = new stdClass();
        $resData->APP_TITLE = core_Setup::get('EF_APP_TITLE', true);
        $resData->INFO = tr('Интегрирана система за управление на бизнеса');
        $resData->VERSION = core_setup::CURRENT_VERSION;
        $resData->SN = core_setup::getBGERPUniqId();
        $resData->AUTHOR = ht::createElement('a', array('target' => '_blank',  'href' => 'https://experta.bg'), tr('Експерта ООД'));
        $resData->LOGO_IMG = ht::createElement('img', array('src' => sbf('img/logo.png', ''), 'class' => "aboutImg", 'width' => 40));
        $resData->LOGO_LINK = ht::createElement('a', array('target' => '_blank',  'href' =>'https://bgerp.com'), 'bgERP');
        
        $oCompany = crm_Companies::fetchOwnCompany();
        $resData->MY_COMPANY = $oCompany->companyVerb . ' (' . $oCompany->country . ')';
        
        $rows = csv_Lib::getCsvRows(getFileContent('bgerp/data/gratitude.csv'), '|');
        $resData->THANKS = '<table>';
        foreach ($rows as $row) {
            $resData->THANKS .= '<tr><td>' . ht::createElement('a', array('target' => '_blank',  'href' => $row[1]), $row[2]) . '</td><td>' . core_String::mbUcfirst($row[3]) . '</td></tr>';
        }
        $resData->THANKS .= '</table>';
        
        // Зареждаме хранилищата
        require_once(EF_APP_PATH . '/core/Setup.inc.php');
        $repos = array_reverse(core_App::getRepos());
        $showRepoStatus = haveRole('admin');
        $reposLastDate = '';
        $log = '';
        foreach ($repos as $repoPath => $branch) {
            $licensePath = rtrim($repoPath, '/') . '/' . 'LICENSE.md';
            
            $baseName = basename($repoPath);
            
            $licenseText = @file_get_contents($licensePath);
            if ($licenseText) {
                $firstRow = '';
                foreach (explode("\n", $licenseText) as $line) {
                    $line = trim($line);
                    if ($line) {
                        $firstRow = trim($line, '#');
                        $firstRow = trim($firstRow, '*');
                        
                        break;
                    }
                }
                
                if ($lName = Request::get('license')) {
                    if ($lName == $baseName) {
                        $resData->LICENSE_TEXT = markdown_Render::Convert($licenseText);
                    }
                }
                
                if (!$firstRow) {
                    $firstRow = 'LICENSE';
                }
                
                $lName = ht::createLink($firstRow, array('Bgerp', 'About', 'license' => $baseName));
            } else {
                $lName = tr('Private license');
            }
                
            
            $versionInfo = '';
            if ($showRepoStatus) {
                $lastCommitDate = gitLastCommitDate($repoPath, $log);
                if ($lastCommitDate) {
                    $lastCommitDate = dt::mysql2verbal($lastCommitDate);
                }
                $hash = gitLastCommitHash($repoPath);
                $currentBranch = gitCurrentBranch($repoPath, $log);
                $cacheKey = md5($repoPath . '|' . $currentBranch . '|' . $hash);
                $status = core_Cache::get('bgerp_RepoStatus', $cacheKey);
                $behindStatus = ht::createElement('span', array('id' => 'repoStatus' . md5($repoPath)), self::renderRepoStatus($status));
                $versionInfo = ': <b>' . $lastCommitDate . ' </b>(' . $currentBranch . ' - ' . $hash . ") {$behindStatus}";
            }
            $reposLastDate .= "<div>" . $baseName . "{$versionInfo} <span class='fright'>{$lName}</span></div>";
        }
        $resData->REPOS = $reposLastDate;

        $resData->LICENSES = '';

        $lQuery = bgerp_Licences::getQuery();
        $lQuery->orderBy('id', 'ASC');
        while ($lRec = $lQuery->fetch()) {
            $lCode = bgerp_Licences::checkLicense($lRec->feature, true);
            $until = '';
            $class = '';
            if ($lCode) {
                if (isset($lRec->validUntil)) {
                    $t = 'изтича';
                    if ($lRec->validUntil < dt::now()) {
                        $t = 'изтекло';
                        $class = 'state-rejected';
                    }

                    $until = ' - ' . tr($t) . ': ' . bgerp_Licences::getVerbal($lRec, 'validUntil');
                }
            } else {
                $lCode = tr('Лицензът не е валиден');
                $class = 'state-rejected';
            }
            $resData->LICENSES .= "<div class='{$class}'><b>{$lRec->feature}{$until}</b><span class='fright'>" . $lCode . "</span></div>";
        }

        $tpl = getTplFromFile('/bgerp/tpl/About.shtml');

        if ($showRepoStatus && $tpl->isPlaceholderExists('REPOS')) {
            core_Ajax::subscribe($tpl, array('Bgerp', 'RepoStatus'), 'aboutRepos', 2000);
        }
        
        jquery_Jquery::run($tpl, '$(".scrollable").css("height", $(window).height() - $(".inner-framecontentTop").height() - 88)');
        
        $tpl->placeObject($resData);
        
        return $tpl;
    }


    /**
     * Проверява по едно хранилище на AJAX заявка, без да бави отварянето на страницата.
     */
    public function act_RepoStatus()
    {
        requireRole('admin');
        expect(Request::get('ajax_mode'));

        require_once(EF_APP_PATH . '/core/Setup.inc.php');
        $res = array();
        $checked = false;
        $pending = false;
        $log = '';
        foreach (array_reverse(core_App::getRepos()) as $repoPath => $branch) {
            $currentBranch = gitCurrentBranch($repoPath, $log);
            $hash = gitLastCommitHash($repoPath);
            $cacheKey = md5($repoPath . '|' . $currentBranch . '|' . $hash);
            $status = core_Cache::get('bgerp_RepoStatus', $cacheKey);
            if ($status === false) {
                $lockKey = 'aboutRepo' . md5($repoPath);
                if ($checked || !core_Locks::obtain($lockKey, 20, 0, 0)) {
                    $pending = true;
                    continue;
                }
                try {
                    core_Session::pause();
                    $status = array('behind' => gitCommitsBehind($repoPath, $currentBranch, true), 'checkedOn' => dt::now());
                    core_Cache::set('bgerp_RepoStatus', $cacheKey, $status, 5);
                    $checked = true;
                } finally {
                    core_Locks::release($lockKey);
                }
            }
            $res[] = (object) array('func' => 'html', 'arg' => array(
                'id' => 'repoStatus' . md5($repoPath),
                'html' => self::renderRepoStatus($status)->getContent(),
                'replace' => true,
            ));
        }

        if (!$pending) {
            $res[] = (object) array('func' => 'js', 'arg' => array('js' => "delete getEfae().subscribedArr.aboutRepos;"));
        }

        return $res;
    }


    /**
     * Статус на проверката, общ за първоначалното показване и AJAX опресняването.
     */
    protected static function renderRepoStatus($status)
    {
        if ($status === false) {
            return ht::createElement('span', array('class' => 'quiet'), tr('Проверка...||Checking...'));
        }

        $behind = $status['behind'] ?? false;
        if ($behind !== false) {
            $text = new ET(tr($behind == 1 ? '[#count#] комит назад||[#count#] commit behind' : '[#count#] комита назад||[#count#] commits behind'));
            $text->replace($behind, 'count');
            $hint = new ET(tr('Спрямо origin на показания клон. Проверено на [#date#].||Compared with origin for the displayed branch. Checked at [#date#].'));
            $hint->replace(dt::mysql2verbal($status['checkedOn'] ?? ''), 'date');
        } else {
            $text = tr('Няма данни||Unavailable');
            $hint = tr('Неуспешна проверка на отдалечения клон||Could not check the remote branch');
        }

        $class = $behind > 0 ? 'red' : 'quiet';

        return ht::createHint("<span class='{$class}'>{$text}</span>", $hint, 'noicon');
    }
    
    
    /**
     * Връща линк към подадения обект
     *
     * @param int $objId
     *
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        return ht::createLink(get_called_class(), array());
    }
}
