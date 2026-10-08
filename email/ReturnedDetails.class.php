<?php


/**
 * Извлича диагностиката на върнато писмо, без оригиналното писмо и транспортните хедъри.
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_ReturnedDetails
{
    const TEXT_LIMIT = 8000;


    /**
     * Извлича причините от DSN частите или текста на парсирано MIME известие.
     * Ключовете идентифицират известието и диагностиката за повторно записване без дубликати.
     *
     * @param email_Mime  $mime Парсираното известие за недоставяне
     * @param string|null $date Дата на известието във формат MySQL
     *
     * @return array Диагностики по уникален ключ, с получател, кодове, текст и дата
     */
    public static function extract($mime, $date)
    {
        $details = array();
        $hasRecipients = false;
        $plain = $html = '';
        foreach ((array) ($mime->parts ?? array()) as $index => $part) {
            // Прикаченото оригинално писмо не е източник на диагностика.
            if (!self::isReportPart($mime, $index)) continue;
            $type = strtoupper(($part->type ?? '') . '/' . ($part->subType ?? ''));
            if ($type === 'MESSAGE/DELIVERY-STATUS' || $type === 'MESSAGE/GLOBAL-DELIVERY-STATUS') {
                $body = $part->data ?? ($mime->files[$part->filemanId ?? 0]->data ?? '');
                foreach (preg_split('/\n\s*\n/', str_replace("\r", '', $body)) as $block) {
                    $fields = email_Mime::parseHeaders($block);
                    $recipient = self::recipient($fields['final-recipient'][0] ?? '');
                    if (!$recipient) continue;
                    $hasRecipients = true;
                    $action = trim($fields['action'][0] ?? '');
                    if ($action && !in_array(strtolower($action), array('failed', 'delayed'), true)) continue;
                    $diagnostic = preg_replace('/\s+/', ' ', trim($fields['diagnostic-code'][0] ?? ''));
                    $details[] = array('recipient' => $recipient,
                        'originalRecipient' => self::recipient($fields['original-recipient'][0] ?? ''),
                        'status' => preg_match('/^[245]\.\d{1,3}\.\d{1,3}\b/', trim($fields['status'][0] ?? ''), $status) ? $status[0] : '', 'deliveryAction' => $action,
                        'diagnostic' => $diagnostic, 'text' => $diagnostic, 'source' => 'dsn');
                }
            } elseif ($type === 'TEXT/PLAIN' && !$plain) {
                $plain = $part->data ?? '';
            } elseif ($type === 'TEXT/HTML' && !$html) {
                $html = $part->data ?? '';
                $html = i18n_Charset::convertToUtf8($html, $part->charset ?? null, true);
            }
        }
        $needsText = !$hasRecipients;
        foreach ($details as $detail) {
            if ($detail['diagnostic'] === '') $needsText = true;
        }
        if ($needsText) {
            if (!$plain && $html) {
                // Само текст; HTML, изображения и линкове не се изпълняват/зареждат.
                $html = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $html);
                $plain = html_entity_decode(strip_tags(preg_replace('~<br\s*/?>|</(?:p|div|tr|li)>~i', "\n", $html)), ENT_QUOTES, 'UTF-8');
            }
            $textDetails = self::extractText(self::cleanText($plain, null));
            if (!$hasRecipients) {
                $details = $textDetails;
            } else {
                // Непълна DSN диагностика се допълва само от текста за същия получател.
                foreach ($details as &$detail) {
                    if ($detail['diagnostic'] !== '') continue;
                    foreach ($textDetails as $textDetail) {
                        if (strcasecmp($detail['recipient'], $textDetail['recipient'] ?? '') !== 0) continue;
                        $detail['diagnostic'] = $textDetail['diagnostic'];
                        $detail['text'] = $textDetail['text'];
                        break;
                    }
                }
                unset($detail);
            }
        }

        $reportKey = hash('sha256', $mime->getHeader('Message-ID') ?: $mime->getData());
        $result = array();
        foreach ($details as $detail) {
            $detail['diagnostic'] = self::cleanText($detail['diagnostic']);
            $detail['text'] = self::cleanText($detail['text']);
            $diagnostic = preg_replace('/\s+/', ' ', $detail['diagnostic']);
            if (empty($detail['status']) && preg_match('/\b([245]\.\d{1,3}\.\d{1,3})\b/', $diagnostic, $m)) $detail['status'] = $m[1];
            $detail['status'] = $detail['status'] ?? '';
            $detail['smtpCode'] = preg_match('/\b([245]\d{2})(?:[ -]|$)/', $diagnostic, $m) ? $m[1] : null;
            $detail['reasonCode'] = self::classify($diagnostic, $detail['status']);
            $detail['reason'] = self::reason($detail['reasonCode']);
            // Без датата: повторно обработено същото известие не добавя дубликат.
            $key = hash('sha256', $reportKey . json_encode($detail, JSON_UNESCAPED_UNICODE));
            $detail['reportedOn'] = $date;
            $result[$key] = $detail;
        }

        return $result;
    }


    /**
     * Разделя почистения текст на диагностики по получател според разпознатия формат.
     * При неразпознат формат запазва текста като обща диагностика.
     *
     * @param string $text Текст без разпознаваемото цитирано писмо и неговите хедъри
     *
     * @return array Списък с получател, диагностичен текст и източник
     */
    protected static function extractText($text)
    {
        // В Outlook примерните кодове в помощния текст не са действителната грешка.
        if (preg_match('/Your message to\h+([^\s<>]+@[^\s<>]+)\h+couldn.t be delivered\./i', $text, $to)
            && preg_match('/^\h*Error Details\h*\n(.*?)(?=^\h*(?:Notification Details|Message Hops)\h*$|\z)/mis', $text, $error)) {
            $intro = preg_split('/^\h*(?:How to Fix It|More Info for Email Admins|Original Message Details)\h*$/mi', $text, 2)[0];

            return array(array('recipient' => self::recipient($to[1]), 'diagnostic' => trim($error[1]),
                'text' => trim($intro) . "\n\n" . trim($error[1]), 'source' => 'text'));
        }
        // Искането за потвърждение не е отказ от несъществуващ адрес; подписът не е диагностика.
        if (self::isChallenge($text)) {
            return array(array('recipient' => null, 'diagnostic' => $text, 'text' => $text, 'source' => 'text'));
        }
        $diagnosticText = preg_split('/^\h*Diagnostic information for administrators:\h*$/mi', $text, 2);
        $text = $diagnosticText[1] ?? $text;
        $allowBareAddress = count($diagnosticText) > 1 || preg_match('/following (?:address|recipient)|delivery has failed|permanent fatal errors/i', $text);
        // Postfix, Exim и Sendmail. Повторен адрес в SMTP отговора остава в същия запис.
        preg_match_all('/^\h*(?:<([^<>\n]+)>(?:\h+\(expanded from <[^>\n]+>\))?:\h*|([^\s<>]+@[^\s<>]+)(?:<mailto:[^>\n]+>)?\h*(?:\n|$)|<([^<>\s]+@[^<>\s]+)>\h*(?:\n|$))/m', $text, $matches, PREG_OFFSET_CAPTURE);
        $starts = array();
        foreach ($matches[0] as $i => $match) {
            $value = $matches[1][$i][0] ?? '';
            if (!$value && !$allowBareAddress) continue;
            $value = $value ?: ($matches[2][$i][0] ?? '') ?: ($matches[3][$i][0] ?? '');
            $original = null;
            if (preg_match('/^([^\s]+@[^\s]+)\h+\(([^\s]+@[^\s]+)\)$/', $value, $alias)) {
                $original = self::recipient($alias[1]);
                $value = $alias[2];
            }
            $recipient = self::recipient($value);
            if ($recipient) $starts[] = array('recipient' => $recipient, 'originalRecipient' => $original,
                'offset' => $match[1], 'start' => $match[1] + strlen($match[0]));
        }
        $details = array();
        foreach ($starts as $i => $start) {
            $end = $starts[$i + 1]['offset'] ?? strlen($text);
            $diagnostic = trim(substr($text, $start['start'], $end - $start['start']));
            $key = strtolower($start['recipient']);
            if (isset($details[$key])) {
                $details[$key]['diagnostic'] .= "\n" . $diagnostic;
                $details[$key]['text'] .= "\n" . $diagnostic;
            } else {
                $details[$key] = array('recipient' => $start['recipient'], 'originalRecipient' => $start['originalRecipient'],
                    'diagnostic' => $diagnostic, 'text' => $diagnostic, 'source' => 'text');
            }
        }
        if (!$details) {
            $recipient = null;
            if (preg_match('/The following message to <([^<>\s]+@[^<>\s]+)> was undeliverable|^Mailbox full[^:\n]*:\h*([^\s<>]+@[^\s<>]+)/mi', $text, $to)) {
                $recipient = self::recipient($to[1] ?: ($to[2] ?? ''));
            }
            $details[] = array('recipient' => $recipient, 'diagnostic' => $text, 'text' => $text, 'source' => 'text');
        }

        return array_values($details);
    }


    /**
     * Разпознава искане на Mailinblack за потвърждение от подателя.
     *
     * @param string $text Текст на известието
     *
     * @return bool
     */
    protected static function isChallenge($text)
    {
        return stripos($text, 'mailinblack') !== false && preg_match('/confirm (?:they|you) are human|authenticate yourself/i', $text);
    }


    /**
     * Проверява MIME частта и родителите ѝ, за да изключи прикачени оригинални писма.
     * Допуска приложени DSN части, когато те съдържат самата диагностика.
     *
     * @param email_Mime $mime  Парсираното известие
     * @param string|int $index Път на частта в MIME дървото
     *
     * @return bool Дали частта може да е източник на диагностика
     */
    protected static function isReportPart($mime, $index)
    {
        $path = (string) $index;
        while ($path !== '') {
            $part = $mime->parts[$path] ?? null;
            if (!$part || strtolower($part->attachment ?? '') === 'attachment'
                && !in_array(strtoupper($part->subType ?? ''), array('DELIVERY-STATUS', 'GLOBAL-DELIVERY-STATUS'), true)) return false;
            if ($path !== (string) $index && strtoupper($part->type ?? '') !== 'MULTIPART') return false;
            if (strtoupper($part->type ?? '') === 'MESSAGE'
                && !in_array(strtoupper($part->subType ?? ''), array('DELIVERY-STATUS', 'GLOBAL-DELIVERY-STATUS'), true)) return false;
            $dot = strrpos($path, '.');
            $path = $dot === false ? '' : substr($path, 0, $dot);
        }

        return true;
    }


    /**
     * Премахва DSN префикса и ограждащите скоби и проверява имейл адреса.
     *
     * @param string $value Стойност на поле за получател или адрес от текста
     *
     * @return string|null Валиден адрес или null при невалидна стойност
     */
    protected static function recipient($value)
    {
        $value = trim(preg_replace('/^[^;]+;\s*/', '', $value), " \t\r\n<>");

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }


    /**
     * Отрязва разпознаваемото цитирано писмо/хедъри и почиства контролните символи.
     * Резултатът остава обикновен текст и изисква escaping при показване в HTML.
     *
     * @param string|null $text  Диагностичен текст
     * @param int|null    $limit Максимален брой символи преди маркера за съкращаване; null е без лимит
     *
     * @return string
     */
    public static function cleanText($text, $limit = self::TEXT_LIMIT)
    {
        $text = str_replace(array("\r\n", "\r"), "\n", $text ?? '');
        // Разпознаваемо начало на цитирано писмо или на неговите хедъри.
        $text = preg_split('/^\h*(?:[-_]{2,}\h*(?:(?:Original|Forwarded|Returned) message\b|This is a copy of the message)|Original message (?:headers|follows)\b|(?:Content-Type:\h*(?:message\/rfc822|text\/rfc822-headers))|(?:Return-Path|Received|Message-I[dD]|DKIM-Signature|ARC-Seal|ARC-Message-Signature):|(?:From:.*\n(?:[^\n]*\n){0,3}(?:To|Date|Subject):))/mi', $text, 2)[0];
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", trim($text));

        return $limit !== null && mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . "\n[…]" : $text;
    }


    /**
     * Определя кратката причина по диагностиката и разширения SMTP статус.
     *
     * @param string $text   Диагностика с нормализирани интервали
     * @param string $status Разширен статус, например 5.1.1, или празен низ
     *
     * @return string Код на причина или unknown, ако няма разпознато правило
     */
    protected static function classify($text, $status)
    {
        if (self::isChallenge($text)) return 'sender_confirmation';
        if (preg_match('/SubjectMismatch|certificate (?:verify failed|verification failed|has expired)|TLS (?:handshake|negotiation) failed/i', $text)) return 'tls_failed';
        if (preg_match('/Hop\s*count\s*exceeded|mail .*loops back to myself|mail loop detected|too many hops/i', $text)) return 'routing_loop';
        if (preg_match('/no mail-enabled subscriptions|account inbounds disabled/i', $text)) return 'mail_service_disabled';
        if (preg_match('/does not accept mail|\bnullMX\b/i', $text)) return 'domain_not_accepting_mail';
        if (preg_match('/TRANSPORT\.RULES\.RejectMessage|blocked by mail flow rule|rejected by organization policy/i', $text)) return 'policy_rejection';
        if (preg_match('/user unknown|unknown user|no such (?:user|recipient|mailbox)|recipient (?:address )?unknown|recipient(?:not| not )found|recipnotfound|user (?:does not|doesn.t) exist|recipient (?:email )?address does not exist|account .*does not exist|no mailbox by that name/i', $text)) return 'unknown_recipient';
        if (in_array($status, array('4.2.2', '5.2.2'), true) || preg_match('/mailbox (?:is |for user is )?full|(?:over|exceeded) quota|quota exceeded|out of storage space|(?:blocks|inode) limit exceeded/i', $text)) return 'mailbox_full';
        if (preg_match('/account .*is inactive|mailbox (?:is )?disabled/i', $text)) return 'mailbox_disabled';
        if (preg_match('/unverified address|unable to verify user/i', $text)) return 'recipient_unverified';
        if (preg_match('/too many errors detected from your IP|flagged for abuse/i', $text)) return 'sender_blocked';
        if (preg_match('/\bspam\b.*\b(?:reject|block|detect|suspect)|\b(?:reject|block|detect|suspect|classif|mark)\w*\b.*\bspam\b/i', $text)) return 'spam';
        if (preg_match('/(?:SPF|DKIM|DMARC).*(?:fail|reject)|not allowed to send .*SPF/i', $text)) return 'authentication_failed';
        if (preg_match('/host or domain name not found|host not found|\bNXDOMAIN\b/i', $text)) return 'host_not_found';
        if (preg_match('/connection timed out|connect to .*timed out|conversation with .*timed out/i', $text)) return 'connection_timeout';
        if (preg_match('/no route to host|connection refused|cannot connect to remote server|domain is not reachable/i', $text)) return 'connection_failed';
        if (preg_match('/relay(?:ing)? (?:denied|not permitted|access denied)|please use the smtp server of your ISP/i', $text)) return 'relay_denied';
        if (preg_match('/mailbox unavailable/i', $text)) return 'mailbox_unavailable';
        if (preg_match('/unroutable address|alias expansion error/i', $text)) return 'routing_failed';
        if (preg_match('/header error/i', $text)) return 'invalid_headers';
        if (preg_match('/internal (?:server )?error occurred/i', $text)) return 'server_error';
        if (preg_match('/recipient (?:address )?rejected|invalid[- ]recipient|address rejected|recipient.*:blocked|\S+@\S+ not found/i', $text)) return 'recipient_rejected';
        if ($status === '5.1.1') return 'unknown_recipient';
        // RFC 7505: 5.1.10 е null MX; Outlook RecipientNotFound се разпознава по текста по-горе.
        if ($status === '5.1.10') return 'domain_not_accepting_mail';
        if ($status === '4.2.1' || $status === '5.2.1') return 'mailbox_disabled';
        if ($status === '5.3.4' || preg_match('/message (?:size )?(?:too large|exceeds)|size limit exceeded/i', $text)) return 'message_too_large';
        if ($status === '4.7.1' || $status === '5.7.1') return 'policy_rejection';

        return 'unknown';
    }


    /**
     * Връща краткото описание на причина от фиксирания речник за превод при показване.
     *
     * @param string $code Код на причина
     *
     * @return string Описание на български или общото описание за неразпозната причина
     */
    public static function reason($code)
    {
        $reasons = array('unknown_recipient' => 'Несъществуващ получател', 'mailbox_full' => 'Пълна пощенска кутия',
            'spam' => 'Отхвърлено като спам', 'host_not_found' => 'Не е намерен пощенският сървър',
            'connection_timeout' => 'Изтекло време за връзка със сървъра', 'connection_failed' => 'Недостъпен пощенски сървър',
            'relay_denied' => 'Сървърът отказва препращане', 'mailbox_unavailable' => 'Недостъпна пощенска кутия',
            'sender_confirmation' => 'Изисква се потвърждение от подателя', 'tls_failed' => 'Проблем със защитената връзка',
            'routing_loop' => 'Зацикляне при препращане', 'routing_failed' => 'Грешка при насочване на писмото',
            'mail_service_disabled' => 'Приемането на поща е изключено', 'domain_not_accepting_mail' => 'Домейнът не приема имейли',
            'mailbox_disabled' => 'Неактивна пощенска кутия', 'recipient_unverified' => 'Сървърът не може да потвърди получателя',
            'sender_blocked' => 'Подателят е блокиран от сървъра', 'recipient_rejected' => 'Получателят е отхвърлен от сървъра',
            'authentication_failed' => 'Неуспешна проверка на подателя', 'policy_rejection' => 'Отказ по политика на сървъра',
            'invalid_headers' => 'Невалидни хедъри на писмото', 'server_error' => 'Вътрешна грешка на пощенския сървър',
            'message_too_large' => 'Твърде голямо писмо', 'unknown' => 'Причината не е разпозната');

        return $reasons[$code] ?? $reasons['unknown'];
    }
}
