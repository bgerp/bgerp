<?php


/**
 * Потвърждение на външен сигнал и незадължителна покана за публичен отзив.
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_ReviewInvitation extends core_BaseClass
{
    const LIFETIME = 60;


    /**
     * Допуска външни посетители и колаборатори без вътрешни или системни права.
     *
     * @return bool
     */
    public static function isExternalVisitor()
    {
        return !core_Users::isPowerUser() && !core_Users::isSystemUser();
    }


    /**
     * Проверява пълен HTTP(S) адрес без идентификационни данни и контролни символи.
     *
     * @param string $url
     * @return bool
     */
    public static function isValidUrl($url)
    {
        $parts = parse_url((string) $url);

        return is_array($parts) && in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
            && !preg_match('/[\x00-\x20]/', (string) $url);
    }


    /**
     * Добавя езика и сесиен ключ за достъп и защита от повторно изпращане.
     *
     * @param core_Form $form
     * @param int $systemId
     * @return void
     */
    public static function prepareForm($form, $systemId)
    {
        $token = Request::get('reviewToken', 'varchar');
        if (!$token) {
            $token = bin2hex(random_bytes(24));
            $context = array('owner' => self::owner(), 'userId' => (int) core_Users::getCurrent(),
                'systemId' => (int) $systemId, 'expires' => time() + self::LIFETIME * 60, 'state' => 'form');
            self::store($token, $context);
        } else {
            $context = self::context($token);
            expect404(($context['systemId'] ?? null) === (int) $systemId);
        }
        $form->FLD('reviewToken', 'varchar(48)', 'input=hidden,silent');
        $form->setDefault('reviewToken', $token);
        $form->FLD('Lg', 'varchar(2)', 'input=hidden,silent');
        $form->setDefault('Lg', core_Lg::getCurrent() === 'en' ? 'en' : 'bg');
    }


    /**
     * Записва сигнала еднократно и подготвя контекста преди външната оценка.
     *
     * @param stdClass $rec
     * @param int $systemId
     * @return Redirect
     */
    public static function submit($rec, $systemId)
    {
        expect404(self::isExternalVisitor());
        $token = $rec->reviewToken ?? '';
        $lock = 'supportReview:' . $token;
        expect(core_Locks::obtain($lock, 30, 3, 1), 'Изпращането още се обработва||Submission is still being processed');
        try {
            $context = self::context($token);
            expect404(($context['systemId'] ?? null) === (int) $systemId);
            if (empty($context['taskId'])) {
                expect(($context['state'] ?? '') === 'form', 'Изпращането още се обработва||Submission is still being processed');
                $context['state'] = 'saving';
                self::store($token, $context);
                cal_Tasks::save($rec);
                $context['taskId'] = $rec->id ?? null;
                $context['text'] = (string) ($rec->description ?? '');
                $context['language'] = core_Lg::getCurrent() === 'en' ? 'en' : 'bg';
                $system = support_Systems::fetch($systemId);
                $context['mode'] = $system->reviewMode ?? 'off';
                $context['url'] = $system->reviewUrl ?? '';
                $context['state'] = 'new';
                $context['deadline'] = time() + 60;
                self::store($token, $context);
                vislog_History::add('Изпращане на сигнал');
            }
        } finally {
            core_Locks::release($lock);
        }

        return new Redirect(array('support_ReviewInvitation', 'finish', 'token' => $token));
    }


    /**
     * Връща или създава случаен идентификатор на текущата браузърна сесия.
     *
     * @return string
     */
    protected static function owner()
    {
        $owner = Mode::get('supportReviewOwner');
        if (!$owner) {
            $owner = bin2hex(random_bytes(24));
            Mode::setPermanent('supportReviewOwner', $owner);
        }

        return $owner;
    }


    /**
     * Зарежда контекста след проверка на срока, сесията и самоличността.
     *
     * @param string $token
     * @return array
     */
    public static function context($token)
    {
        expect404(is_string($token) && preg_match('/^[a-f0-9]{48}$/D', $token));
        $context = core_Permanent::get('supportReview:' . $token);
        expect404(is_array($context) && ($context['expires'] ?? 0) >= time()
            && hash_equals((string) ($context['owner'] ?? ''), (string) Mode::get('supportReviewOwner'))
            && ($context['userId'] ?? null) === (int) core_Users::getCurrent());

        return $context;
    }


    /**
     * Съхранява временно състоянието на потвърждението.
     *
     * @param string $token
     * @param array $context
     * @return void
     */
    protected static function store($token, $context)
    {
        core_Permanent::set('supportReview:' . $token, $context, self::LIFETIME);
    }


    /**
     * Отказва поканата без доставчик; плъгините връщат allow, deny или pending.
     *
     * @param stdClass $system
     * @param array $context
     * @return array Решение в state и незадължителни reason и providerData
     */
    public function evaluate_($system, $context)
    {
        return array('state' => 'deny', 'reason' => 'Няма модул за оценка');
    }


    /**
     * Проверява текущите настройки и придвижва оценката под заключване.
     *
     * @param string $token
     * @return array Контекстът и записът на системата, ако още съществува
     */
    protected function advance($token)
    {
        $lock = 'supportReview:' . $token;
        expect(core_Locks::obtain($lock, 30, 3, 1));
        try {
            $context = self::context($token);
            expect404(!empty($context['taskId']));
            $system = support_Systems::fetch((int) ($context['systemId'] ?? 0));
            $mode = $context['mode'] ?? 'off';
            $modes = cls::get('support_Systems')->getReviewModes();
            $enabled = self::isExternalVisitor() && $system && $mode !== 'off'
                && isset($modes[$mode])
                && $mode === ($system->reviewMode ?? 'off') && ($context['url'] ?? '') === ($system->reviewUrl ?? '')
                && !in_array($system->state ?? '', array('closed', 'rejected'), true)
                && self::isValidUrl($context['url'] ?? '');
            $previous = $context['state'] ?? 'new';
            if (!$enabled) {
                $context['state'] = 'deny';
            } elseif (in_array($previous, array('new', 'pending'), true) || ($previous === 'allow' && $mode !== 'always')) {
                $result = array('state' => 'deny', 'reason' => 'Изтекло време за оценка');
                if ($mode === 'always') {
                    $result = array('state' => 'allow', 'reason' => 'Режим: винаги');
                } elseif ($previous === 'allow' || ($context['deadline'] ?? 0) >= time()) {
                    // Не стартираме второ изпълнение при прекъснал първи HTTP отговор.
                    self::store($token, array_merge($context, array('state' => 'deny')));
                    try {
                        $result = $this->evaluate($system, $context);
                    } catch (Throwable $e) {
                        reportException($e);
                        $result = array('state' => 'deny', 'reason' => 'Грешка при оценката');
                    }
                }
                $state = $result['state'] ?? 'deny';
                $context['state'] = in_array($state, array('allow', 'deny', 'pending'), true) ? $state : 'deny';
                $context['providerData'] = $result['providerData'] ?? ($context['providerData'] ?? array());
                $context['reason'] = $result['reason'] ?? '';
            }
            if (($context['state'] ?? '') !== $previous && in_array($context['state'] ?? '', array('allow', 'deny'), true)) {
                cal_Tasks::logInfo('Публичен отзив: ' . $context['state'] . '. ' . str::limitLen((string) ($context['reason'] ?? ''), 150), $context['taskId']);
            }
            self::store($token, $context);

            return array($context, $system);
        } finally {
            core_Locks::release($lock);
        }
    }


    /**
     * Показва потвърждението и разрешената покана на езика на изпратения сигнал.
     *
     * @return core_ET
     */
    public function act_Finish()
    {
        self::privateHeaders();
        $token = Request::get('token', 'varchar');
        list($context, $system) = $this->advance($token);
        Mode::set('lg', $context['language'] ?? 'bg');
        Mode::set('wrapper', 'cms_page_External');
        $tpl = new ET('<section class="support-review"><h2>[#THANKS#]</h2><div id="supportReviewResult">[#RESULT#]</div></section>');
        $tpl->appendOnce(' support-review-page', 'BODY_CLASS_NAME');
        $tpl->replace(tr('Благодарим Ви за мнението!||Thank you for your feedback!'), 'THANKS');
        $tpl->replace($this->renderInvitation($context, $system, $token), 'RESULT');
        $tpl->push('support/js/reviewInvitation.js', 'JS');
        $tpl->push('support/css/reviewInvitation.css', 'CSS');
        $options = array('url' => toUrl(array($this, 'status', 'token' => $token)),
            'pending' => ($context['state'] ?? '') === 'pending',
            'copy' => tr('Копирай текста||Copy text'),
            'copied' => tr('Текстът е копиран!||Text copied!'),
            'paste' => tr('Готово! Поставете текста в отзива си.||All set! Paste the text into your review.'),
            'manual' => tr('Текстът е маркиран. Копирайте го и го поставете в отзива си.||The text is selected. Copy it and paste it into your review.'));
        jquery_Jquery::run($tpl, 'supportReviewInvitation(' . json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ');', true);
        // CMS обвивката се рендира след екшъна и трябва да запази езика на потвърждението.

        return $tpl;
    }


    /**
     * Връща JSON със състоянието и HTML на поканата при фонова проверка.
     *
     * @return void
     */
    public function act_Status()
    {
        self::privateHeaders();
        $token = Request::get('token', 'varchar');
        list($context, $system) = $this->advance($token);
        core_Lg::push($context['language'] ?? 'bg');
        try {
            $html = $this->renderInvitation($context, $system, $token)->getContent();
        } finally {
            core_Lg::pop();
        }

        core_App::outputJson(array('pending' => ($context['state'] ?? '') === 'pending', 'html' => $html));
    }


    /**
     * Проверява разрешението отново и пренасочва към избраната външна форма.
     *
     * @return Redirect
     */
    public function act_Open()
    {
        self::privateHeaders();
        $token = Request::get('token', 'varchar');
        list($context) = $this->advance($token);
        expect404(($context['state'] ?? '') === 'allow');
        cal_Tasks::logInfo('Отворена връзка за публичен отзив', $context['taskId'] ?? null);

        return new Redirect($context['url']);
    }


    /**
     * Забранява кеширането, индексирането и предаването на адреса като referrer.
     *
     * @return void
     */
    protected static function privateHeaders()
    {
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
    }


    /**
     * Рендира поканата с нередактируем обикновен текст или състоянието на изчакване.
     *
     * @param array $context
     * @param stdClass|null $system
     * @param string $token
     * @return core_ET
     */
    public function renderInvitation($context, $system, $token)
    {
        if (($context['state'] ?? '') === 'pending') {
            return new ET('<div class="support-review-pending" role="status"><span class="support-review-spinner" aria-hidden="true"></span><span>[#1#]</span></div>', tr('Моля, изчакайте...||Please wait...'));
        }
        if (($context['state'] ?? '') !== 'allow') {
            return new ET('');
        }
        $en = ($context['language'] ?? 'bg') === 'en';
        $text = $en ? ($system->reviewTextEn ?? '') : ($system->reviewTextBg ?? '');
        $button = $en ? ($system->reviewButtonEn ?? '') : ($system->reviewButtonBg ?? '');
        if (!strlen(trim($text))) {
            $text = tr('Ако желаете, можете да споделите мнението си и като публичен отзив.||If you wish, you can also share your feedback as a public review.');
        }
        if (!strlen(trim($button))) {
            $button = tr('Сподели отзив||Share a review');
        }
        Mode::push('text', 'plain');
        Mode::push('ClearFormat', true);
        try {
            $plainHtml = cls::get('type_Richtext')->toHtml($context['text'] ?? '');
            // Декодираме след махането на таговете, за да запазим буквални <...> в мнението.
            $plainText = trim(html_entity_decode(type_Richtext::stripTags($plainHtml), ENT_QUOTES, 'UTF-8'));
        } finally {
            Mode::pop('ClearFormat');
            Mode::pop('text');
        }
        $tpl = getTplFromFile('support/tpl/ReviewInvitation.shtml');
        $tpl->replace(sbf('img/16/copy16.png', ''), 'COPY_ICON');
        $tpl->replace(nl2br(ET::escape(type_Varchar::escape($text))), 'INVITATION');
        $tpl->replace(ET::escape(type_Varchar::escape($plainText)), 'TEXT');
        $tpl->replace(tr('Вашето мнение||Your feedback'), 'LABEL');
        $tpl->replace(tr('Копирай текста||Copy text'), 'COPY');
        $tpl->replace(ht::createElement('a', array('href' => toUrl(array($this, 'open', 'token' => $token)),
            'target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'support-review-link'), ET::escape(type_Varchar::escape($button))), 'LINK');

        return $tpl;
    }
}
