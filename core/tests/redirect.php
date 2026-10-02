<?php
// php core/tests/redirect.php; optional HTTP fixture: php -S 127.0.0.1:<port> core/tests/redirect.php
require __DIR__ . '/redirectFixture.php';
if (PHP_SAPI !== 'cli') {
    define('BGERP_GIT_BRANCH','test'); define('EF_HTTPS',false);
    ob_start(); ob_start();
    Request::push(array('ajax_mode'=>isset($_GET['ajax']), 'hit_id'=>'http-hit'));
    try {
        if (isset($_GET['template'])) {
            $template = new Redirect('/destination','Saved','notice');
            $template->getContent();
        } else {
            redirect('/destination',false,'Saved','notice',isset($_GET['permanent']));
        }
    } catch (core_exception_Redirect $e) {
        core_App::sendRedirect($e);
    }
    exit();
}
$checks=0;
function checkRedirect($ok,$message) { global $checks; if(!$ok)throw new RuntimeException($message);$checks++; }
class RedirectController extends core_BaseClass {
    public function action($action) { redirect(array('Target','single',5,'ret_url'=>true),false,'Saved','warning',true); }
}
Request::push(array('Ctr'=>'Original','Act'=>'list','ajax_mode'=>false,'ret_url'=>'/original'));
$before=Request::$vars;
try {
    try {
        Request::forward(array('RedirectController','go','ajax_mode'=>true,'ret_url'=>'/forwarded'));
    } finally { $finally=true; }
    throw new RuntimeException('Redirect did not interrupt dispatch');
} catch (core_exception_Redirect $e) {
    checkRedirect($e instanceof Exception && $e instanceof Throwable && !($e instanceof Error),'Exception hierarchy');
    checkRedirect($finally && Request::$vars===$before,'Finally restores forwarded request');
    checkRedirect($e->ajax && $e->permanent && $e->hitId==='test-hit','AJAX, permanent and hit ID frozen');
    checkRedirect(strpos($e->url,'ret_url=%2Fforwarded')!==false && strpos($e->url,'hit_id=test-hit')!==false,'URL resolved in forwarded context');
    checkRedirect($e->statusMessage==='Saved' && $e->messageType==='warning' && $e->statusUserId===7,'Status data frozen');
    checkRedirect(core_Statuses::$messages===array(),'Interception has no status side effects');
}
$template=new Redirect('/template','Template saved');
checkRedirect($template instanceof core_ET && !($template instanceof Throwable),'Returned Redirect remains a template');
try { $template->getContent(); throw new RuntimeException('Template did not redirect at render'); }
catch(core_exception_Redirect $e) {checkRedirect(strpos($e->url,'/template')===0 && $e->statusMessage==='Template saved','Template redirects only at rendering');}
ob_start();echo 'ordinary rendered output';
try {redirect('/buffered');}catch(core_exception_Redirect $e){$buffered=$e;}
ob_end_clean();
checkRedirect($buffered->url==='/buffered','An intercepted redirect does not depend on output-buffer tricks');
echo "OK: {$checks} core redirect checks.\n";
