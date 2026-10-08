<?php

// php email/tests/returnedMime.php /path/to/webroot/index.php [public-eml-directory]
// Uses the real framework MIME/charset/HTML parsers without saving mail or sending messages.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");

/**
 * Проверки на диагностика от цели MIME писма.
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_tests_ReturnedMime
{
    public static $publicDirectory = '';
    private $checks = 0;

    /**
     * Приема стандартната инициализация от cls без допълнителни зависимости.
     *
     * @param array $params Параметри на framework инициализацията
     *
     * @return void
     */
    public function init($params = array()) {}

    /**
     * Отчита успешна проверка или прекъсва теста при неуспех.
     *
     * @param bool   $condition Проверявано условие
     * @param string $message   Описание на проверката
     *
     * @return void
     * @throws RuntimeException
     */
    private function check($condition, $message)
    {
        if (!$condition) throw new RuntimeException($message);
        $this->checks++;
    }

    /**
     * Парсира цяло тестово MIME писмо с реалния парсер и извлича диагностиките.
     *
     * @param string $raw Сурово MIME съдържание
     *
     * @return array Диагностики, индексирани последователно
     */
    private function read($raw)
    {
        $mime = cls::get('email_Mime');
        $mime->parseAll($raw);

        return array_values(email_ReturnedDetails::extract($mime, '2026-01-02 12:00:00'));
    }

    /**
     * Сглобява синтетично multipart/report писмо с нормализирани CRLF разделители.
     *
     * @param array $parts MIME части, включително техните хедъри
     *
     * @return string
     */
    private function message($parts)
    {
        $raw = "From: Mailer-Daemon@example.test\nTo: sender@example.test\nSubject: Synthetic delivery report\n"
            . "Message-ID: <mime-test@example.test>\nMIME-Version: 1.0\n"
            . "Content-Type: multipart/report; report-type=delivery-status; boundary=report-boundary\n\n";
        foreach ($parts as $part) $raw .= "--report-boundary\n" . $part . "\n";

        return str_replace("\n", "\r\n", str_replace(array("\r\n", "\r"), "\n", $raw . "--report-boundary--\n"));
    }

    /**
     * Проверява синтетичните писма и, при подадена директория, публичните MIME примери.
     * Не запазва и не изпраща имейли.
     *
     * @return void
     * @throws RuntimeException При неуспешна проверка
     */
    public function cli_Run()
    {
        $human = "Content-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: quoted-printable\n\n"
            . quoted_printable_encode("<wrong@example.test>: 550 User unknown");
        $original = "Content-Type: message/rfc822\n\nFrom: private@example.test\nTo: hidden@example.test\n"
            . "Subject: ORIGINAL_SECRET_SUBJECT\nContent-Type: text/plain\n\nORIGINAL_SECRET_BODY";
        $dsn = "Reporting-MTA: dns; mx.example.test\n\nFinal-Recipient: rfc822; full@example.test\n"
            . "Original-Recipient: rfc822; alias@example.test\nAction: delayed\nStatus: 4.2.2 (mailbox full)\n"
            . "Diagnostic-Code: smtp; 452 4.2.2 The recipient's inbox is out of\n storage space\n\n"
            . "Final-Recipient: rfc822; denied@example.test\nAction: failed\nStatus: 5.4.1\n"
            . "Diagnostic-Code: smtp; 550 5.4.1 Recipient address rejected: Access denied\n\n"
            . "Final-Recipient: rfc822; good@example.test\nAction: delivered\nStatus: 2.0.0";
        foreach (array('7bit', 'base64', 'quoted-printable') as $encoding) {
            $data = $encoding === 'base64' ? chunk_split(base64_encode($dsn), 76, "\n")
                : ($encoding === 'quoted-printable' ? quoted_printable_encode($dsn) : $dsn);
            $raw = $this->message(array($human, "Content-Type: message/delivery-status\nContent-Transfer-Encoding: {$encoding}\n\n{$data}", $original));
            $result = $this->read($raw);
            $this->check(array_column($result, 'reasonCode') === array('mailbox_full', 'recipient_rejected'), "Decoded DSN: {$encoding}");
            $this->check(array_column($result, 'recipient') === array('full@example.test', 'denied@example.test'), 'Only failed/delayed recipients');
            $this->check($result[0]['originalRecipient'] === 'alias@example.test' && $result[0]['deliveryAction'] === 'delayed', 'Original recipient and delayed action');
            $this->check(strpos(json_encode($result), 'SECRET') === false && strpos(json_encode($result), 'wrong@') === false, 'DSN takes precedence over text and original');
        }
        $raw = $this->message(array($human, "Content-Type: message/delivery-status\n\nReporting-MTA: dns; mx.example.test\n\nFinal-Recipient: rfc822; good@example.test\nAction: delivered\nStatus: 2.0.0", $original));
        $this->check($this->read($raw) === array(), 'Successful DSN has no failure details');
        $raw = $this->message(array($human, "Content-Type: message/delivery-status\n\nMalformed status without recipient", $original));
        $this->check($this->read($raw)[0]['source'] === 'text', 'Malformed DSN falls back to human part');
        $attachment = "Content-Type: text/plain\nContent-Disposition: attachment; filename=original.txt\n\n<hidden@example.test>: ORIGINAL_SECRET_BODY";
        $html = '<p>&lt;recipient@example.test&gt;: 550 User unknown</p><script>SECRET_JS</script>';
        $raw = $this->message(array($attachment, "Content-Type: text/html; charset=UTF-8\nContent-Transfer-Encoding: base64\n\n" . base64_encode($html), $original));
        $result = $this->read($raw);
        $this->check($result[0]['reasonCode'] === 'unknown_recipient' && $result[0]['recipient'] === 'recipient@example.test', 'HTML-only base64 report');
        $this->check(strpos(json_encode($result), 'SECRET') === false, 'Plain attachment and script excluded');
        $latin = iconv('UTF-8', 'ISO-8859-1', '<recipient@example.test>: 550 Mailbox full - boîte pleine');
        $raw = $this->message(array("Content-Type: text/plain; charset=iso-8859-1\nContent-Transfer-Encoding: quoted-printable\n\n" . quoted_printable_encode($latin), $original));
        $result = $this->read($raw);
        $this->check($result[0]['reasonCode'] === 'mailbox_full' && strpos($result[0]['text'], 'boîte pleine') !== false, 'Charset converted to UTF-8');
        $nested = "Content-Type: multipart/alternative; boundary=human-parts\n\n--human-parts\nContent-Type: text/plain; charset=UTF-8\n\n<recipient@example.test>: 550 Mailbox full\n--human-parts\nContent-Type: text/html; charset=UTF-8\n\n<p>Mailbox full</p>\n--human-parts--";
        $this->check($this->read($this->message(array($nested, $original)))[0]['reasonCode'] === 'mailbox_full', 'Nested alternative report');
        $this->check(email_Mime::decodeHeader(null) === '', 'Absent MIME header is safe on PHP 8.2');

        // Public samples: https://github.com/sisimai/set-of-emails/tree/1e2230cf71fb6fe8935d4a8036e125e64baaf7c0/maildir/bsd
        $public = array('exim-01' => 'relay_denied', 'exim-02' => 'unknown_recipient,unknown_recipient', 'exim-03' => 'invalid_headers',
            'gmail-01' => 'unknown_recipient', 'gmail-03' => 'invalid_headers', 'office365-01' => 'unknown_recipient',
            'office365-02' => 'unknown_recipient', 'office365-03' => 'authentication_failed', 'outlook-01' => 'mailbox_full',
            'outlook-02' => 'unknown_recipient', 'outlook-03' => 'connection_failed', 'postfix-01' => 'unknown_recipient',
            'postfix-02' => 'unknown_recipient,unknown_recipient', 'postfix-03' => 'unknown_recipient', 'sendmail-01' => 'unknown_recipient',
            'sendmail-02' => 'unknown_recipient,unknown_recipient', 'sendmail-03' => 'unknown_recipient');
        if (self::$publicDirectory !== '') {
            foreach ($public as $name => $expected) {
                $file = self::$publicDirectory . '/lhost-' . $name . '.eml';
                $this->check(is_file($file), "Missing public sample: {$name}");
                $result = $this->read(file_get_contents($file));
                $this->check(implode(',', array_column($result, 'reasonCode')) === $expected, "Public MIME sample: {$name}");
                foreach ($result as $detail) {
                    $this->check(!preg_match('/Original message headers|^\h*(?:Received|Return-Path|DKIM-Signature|ARC-Seal):/mi', $detail['text']), "Original headers excluded: {$name}");
                    $this->check(!empty($detail['recipient']), "Recipient extracted: {$name}");
                }
            }
        }
        echo "returnedMime: {$this->checks} checks passed; public samples: " . (self::$publicDirectory !== '' ? count($public) : 0) . ".\n";
    }
}

$bootstrap = $argv[1] ?? '';
if (!is_file($bootstrap)) exit("Provide the installation's webroot/index.php as the first argument.\n");
email_tests_ReturnedMime::$publicDirectory = $argv[2] ?? '';
$argv = array(__FILE__, getenv('BGERP_TEST_APP') ?: 'bgerp', 'email_tests_ReturnedMime', 'Run');
require $bootstrap;
