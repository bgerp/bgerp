<?php

// php email/tests/returnedDetails.php - synthetic parsed MIME fixtures; no DB or network.
if (PHP_SAPI !== 'cli') exit("CLI only.\n");
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
class core_BaseClass {}
function countR($value) { return count((array) $value); }
class i18n_Charset { public static function convertToUtf8($text, $charset = null, $html = false) { return $text; } }
require dirname(__DIR__) . '/Mime.class.php';
require dirname(__DIR__) . '/ReturnedDetails.class.php';
$checks = 0;
function check($ok, $message) { global $checks; if (!$ok) throw new RuntimeException($message); $checks++; }
class ReturnFixture {
    public $parts = array(), $files = array();
    public function getHeader($name) { return '<synthetic-report@example.test>'; }
    public function getData() { return 'synthetic report'; }
}
function fixture($text, $html = false) {
    $mime = new ReturnFixture();
    $mime->parts[1] = (object) array('type' => 'MULTIPART', 'subType' => 'REPORT');
    $mime->parts['1.1'] = (object) array('type' => 'TEXT', 'subType' => $html ? 'HTML' : 'PLAIN', 'data' => $text);
    $mime->parts['1.3'] = (object) array('type' => 'MESSAGE', 'subType' => 'RFC822', 'filemanId' => 3);
    $mime->files[3] = (object) array('data' => 'ORIGINAL_SECRET_BODY');
    $mime->parts['1.3.1'] = (object) array('type' => 'TEXT', 'subType' => 'PLAIN', 'data' => 'ORIGINAL_SECRET_BODY');
    return $mime;
}
function readReport($mime) { return email_ReturnedDetails::extract($mime, '2026-01-02 12:00:00'); }
$examples = array(
    '550 No mailbox by that name is currently available' => 'unknown_recipient',
    'Host or domain name not found. Name service error: Host not found' => 'host_not_found',
    '554 5.7.1 Spam message rejected' => 'spam',
    "550-5.1.1 The email account that you tried to reach does not exist.\n Please try again" => 'unknown_recipient',
    "connect to example.test[192.0.2.1]:25: Connection timed\n out" => 'connection_timeout',
    '550 Mailbox is full / Blocks limit exceeded / Inode limit exceeded' => 'mailbox_full',
    '550 5.7.1 relaying denied' => 'relay_denied',
    "connect to example.test[192.0.2.1]:25: No route to\n host" => 'connection_failed',
    '553 "RCPT TO" mailbox unavailable' => 'mailbox_unavailable',
    '550 5.1.1 Recipient address rejected: User unknown in virtual mailbox table' => 'unknown_recipient',
    '550 No such recipient here' => 'unknown_recipient',
    '550 Recipient unknown' => 'unknown_recipient',
    '550 Unspecified server refusal' => 'unknown',
    '452-4.2.2 The recipient\'s inbox is out of storage space' => 'mailbox_full',
    '450 4.2.2 Recipient address rejected: mailbox over quota' => 'mailbox_full',
    '550 Unknown user' => 'unknown_recipient',
    '550 5.1.2 No such mailbox' => 'unknown_recipient',
    '550 Your recipient email address does not exist on our server' => 'unknown_recipient',
    '450 4.1.1 unverified address: 550 5.1.1 User doesn\'t exist' => 'unknown_recipient',
    '450 4.1.1 unverified address: Mailbox might be disabled, full, or may not exist' => 'recipient_unverified',
    '451 unable to verify user' => 'recipient_unverified',
    '550 5.4.1 Recipient address rejected: Access denied' => 'recipient_rejected',
    '554 5.7.1 Recipient address rejected: Invalid-Recipient' => 'recipient_rejected',
    '451 4.4.4 Mail received as unauthenticated, incoming to a recipient domain configured in a hosted tenant which has no mail-enabled subscriptions' => 'mail_service_disabled',
    '451 Account inbounds disabled' => 'mail_service_disabled',
    'Domain example.test does not accept mail (nullMX)' => 'domain_not_accepting_mail',
    '550 5.2.1 The email account that you tried to reach is inactive' => 'mailbox_disabled',
    'mail for example.test loops back to myself' => 'routing_loop',
    '554 5.4.14 Hop count exceeded - possible mail loop' => 'routing_loop',
    '550 Unroutable address' => 'routing_failed',
    '451 4.6.0 Alias expansion error' => 'routing_failed',
    '451 too many errors detected from your IP' => 'sender_blocked',
    '500 This Email is flagged for abuse' => 'sender_blocked',
    'conversation with mx.example.test timed out while receiving the initial server greeting' => 'connection_timeout',
    '550 5.4.317 Message expired, cannot connect to remote server(SubjectMismatch. Expected Subject: *.example.test)' => 'tls_failed',
    '550 5.7.1 TRANSPORT.RULES.RejectMessage; the message was rejected by organization policy' => 'policy_rejection',
    '451 4.2.0 Internal error occurred. Refer to server log: retry timeout exceeded' => 'server_error',
    '550 5.1.0 Sender is not allowed to send from <example.test> per its SPF Record' => 'authentication_failed',
    '554 5.7.0 Header error' => 'invalid_headers',
    '556 5.1.10' => 'domain_not_accepting_mail',
    '550 5.1.10 RESOLVER.ADR.RecipientNotFound; Recipient not found' => 'unknown_recipient',
    '550 5.2.1' => 'mailbox_disabled',
);
foreach ($examples as $diagnostic => $expected) {
    $result = readReport(fixture("This is the mail system.\n\n<recipient@example.test>: " . $diagnostic));
    $detail = reset($result);
    check(count($result) === 1 && $detail['reasonCode'] === $expected, "Recognizes {$expected}");
    check($detail['recipient'] === 'recipient@example.test' && strpos($detail['text'], 'This is the mail system') === false, 'Keeps only the recipient diagnostic');
    check(strpos(json_encode($result), 'SECRET') === false, 'Does not include attached original');
}
$result = readReport(fixture("The following addresses failed:\n\n  full@example.test\n    LMTP error after RCPT TO:<full@example.test>:\n    552 5.2.2 Quota exceeded (mailbox for user is full)"));
check(reset($result)['reasonCode'] === 'mailbox_full', 'Exim recipient and quota');
$mime = fixture("<a@example.test>: 550 No such recipient here\n<b@example.test>: 554 Spam message rejected\n\n----- Original message -----\nFrom: other@example.test\nSubject: PRIVATE\nORIGINAL_SECRET_BODY");
$result = readReport($mime);
check(count($result) === 2 && array_column($result, 'recipient') === array('a@example.test', 'b@example.test'), 'Separates recipients');
check(strpos(json_encode($result), 'SECRET') === false, 'Cuts inline original at explicit boundary');
check(array_keys($result) === array_keys(readReport($mime)), 'Stable keys allow idempotent repeated processing');
$mime->parts['1.2'] = (object) array('type' => 'MESSAGE', 'subType' => 'DELIVERY-STATUS', 'filemanId' => 2);
$mime->files[2] = (object) array('data' => "Reporting-MTA: dns; example.test\r\n\r\nFinal-Recipient: rfc822; full@example.test\r\nOriginal-Recipient: rfc822; alias@example.test\r\nAction: failed\r\nStatus: 5.2.2\r\nDiagnostic-Code: smtp; 552 Quota exceeded\r\n (mailbox for user is full)\r\n\r\nFinal-Recipient: rfc822; absent@example.test\r\nAction: failed\r\nStatus: 5.1.1\r\nDiagnostic-Code: smtp; 550 No such user\r\n\r\nFinal-Recipient: rfc822; good@example.test\r\nAction: delivered\r\nStatus: 2.0.0");
$result = readReport($mime);
$detail = reset($result);
check(count($result) === 2 && $detail['recipient'] === 'full@example.test' && $detail['source'] === 'dsn', 'Structured per-recipient status takes precedence');
check($detail['originalRecipient'] === 'alias@example.test' && $detail['reasonCode'] === 'mailbox_full', 'Preserves original address and decoded status');
check(strpos($detail['diagnostic'], "\n") === false && $detail['smtpCode'] === '552', 'Unfolds DSN continuation lines');
check(strpos(json_encode($result), 'good@example.test') === false, 'Does not report successful recipient as failed');
$result = readReport(fixture('<p>&lt;recipient@example.test&gt;: 550 User unknown</p><script>SECRET_JS</script><img src="https://example.test/track">', true));
check(reset($result)['reasonCode'] === 'unknown_recipient' && strpos(json_encode($result), 'SECRET_JS') === false, 'HTML-only report becomes plain text');
$result = readReport(fixture("Unfamiliar report format\n\nFrom: sender@example.test\nTo: recipient@example.test\nSubject: ORIGINAL_SECRET_BODY"));
check(reset($result)['reasonCode'] === 'unknown' && reset($result)['text'] === 'Unfamiliar report format', 'Unknown report preserves text before original headers');
$result = readReport(fixture(str_repeat('Пример ', 3000)));
check(mb_strlen(reset($result)['text']) <= email_ReturnedDetails::TEXT_LIMIT + 4, 'Bounds unknown diagnostic text safely for UTF-8');
$result = readReport(fixture('<first@example.test>: 550 ' . str_repeat('Long diagnostic ', 1000) . "\n<last@example.test>: 550 User unknown"));
check(count($result) === 2 && end($result)['recipient'] === 'last@example.test', 'Bounds each diagnostic without dropping later recipients');
$result = readReport(fixture('Unspecified delivery error. Literal text: <b>diagnostic</b> [#REPORTS#]'));
check(reset($result)['reasonCode'] === 'unknown' && strpos(reset($result)['text'], '<b>') !== false, 'Unknown plain text remains source text for safe display');
$result = readReport(fixture("<a@example.test>: host mx.example.test said: 554 5.7.1\n    <a@example.test>: Recipient address rejected: Access denied\n<b@example.test>: 550 User unknown"));
check(count($result) === 2 && reset($result)['reasonCode'] === 'recipient_rejected', 'Repeated address in a wrapped SMTP reply stays in one report');
check(strpos(reset($result)['diagnostic'], '554 5.7.1') !== false, 'Preserves the SMTP status before the repeated address');
$result = readReport(fixture("This is the mail system\n<alias@example.test (target@example.test)>: Connection timed out"));
check(reset($result)['recipient'] === 'target@example.test' && reset($result)['originalRecipient'] === 'alias@example.test', 'Forwarded Postfix address is explicit');
$result = readReport(fixture("The following addresses had permanent fatal errors\n<recipient@example.test>\n (reason: 550 User unknown)"));
check(reset($result)['recipient'] === 'recipient@example.test' && reset($result)['reasonCode'] === 'unknown_recipient', 'Sendmail address without colon');
$result = readReport(fixture("Mailbox full - boite email pleine: recipient@example.test\nOriginal message follows.\nDKIM-Signature: ORIGINAL_SECRET_BODY"));
check(reset($result)['reasonCode'] === 'mailbox_full' && strpos(reset($result)['text'], 'Original message') === false, 'Inline report has a reason without quoting original headers');
$result = readReport(fixture("Mailinblack asks each new sender to confirm they are human.\n\nClick here to deliver your email.\n\nowner@example.test\n\nSignature footer"));
check(reset($result)['reasonCode'] === 'sender_confirmation' && reset($result)['recipient'] === null, 'Challenge is not mistaken for a signature recipient');
check(strpos(reset($result)['text'], 'confirm they are human') !== false, 'Keeps the challenge instructions');
$result = readReport(fixture("Unknown delivery report\n\ncontact@example.test\n\nSupport signature"));
check(reset($result)['recipient'] === null && strpos(reset($result)['text'], 'Unknown delivery report') !== false, 'Unknown signatures do not consume the report text');
$outlook = "DSN\nYour message to recipient@example.test couldn't be delivered.\n\nAddress was not found.\nHow to Fix It\nRead the help article for error code 5.1.1.\nOriginal Message Details\nSubject: ORIGINAL_SECRET_SUBJECT\nError Details\nError: 554 5.4.14 Hop count exceeded - possible mail loop\nNotification Details\nMessage Hops\nTRANSPORT_SECRET\nOriginal Message Headers\nARC-Seal: ORIGINAL_SECRET_BODY";
$result = readReport(fixture($outlook));
check(reset($result)['reasonCode'] === 'routing_loop' && reset($result)['status'] === '5.4.14', 'Uses actual Outlook error instead of help article code');
check(strpos(json_encode($result), 'SECRET') === false && strpos(reset($result)['text'], 'How to Fix It') === false, 'Outlook details omit original subject, headers, hops and generic help');
$result = readReport(fixture("Delivery has failed to these recipients or groups:\nrecipient@example.test\nGeneric help\nDiagnostic information for administrators:\nGenerating server: mx.example.test\nrecipient@example.test\nRemote Server returned '550 5.1.10 RESOLVER.ADR.RecipientNotFound; Recipient not found'\nOriginal message headers:\nARC-Seal: ORIGINAL_SECRET_BODY"));
check(count($result) === 1 && reset($result)['reasonCode'] === 'unknown_recipient' && strpos(reset($result)['text'], 'Generic help') === false, 'Classic Outlook uses the administrator diagnostic once');
$mime = fixture('<other@example.test>: 550 User unknown');
$mime->parts['1.2'] = (object) array('type' => 'MESSAGE', 'subType' => 'DELIVERY-STATUS', 'data' => "Reporting-MTA: dns; example.test\n\nFinal-Recipient: rfc822; recipient@example.test\nAction: delivered\nStatus: 2.0.0");
check(readReport($mime) === array(), 'A successful structured DSN never falls back to an unrelated failure in text');
$mime->parts['1.2']->data = "Reporting-MTA: dns; example.test\n\nFinal-Recipient: rfc822; recipient@example.test\nAction: delayed\nStatus: 4.2.2 (mailbox full)";
$result = readReport($mime);
check(reset($result)['status'] === '4.2.2' && reset($result)['reasonCode'] === 'mailbox_full' && reset($result)['deliveryAction'] === 'delayed', 'DSN comments and missing diagnostic do not hide the structured status');
$mime->parts['1.1']->data = '<recipient@example.test>: 550 Mailbox full';
$mime->parts['1.2']->data = "Reporting-MTA: dns; example.test\n\nFinal-Recipient: rfc822; recipient@example.test\nAction: failed\nStatus: 5.0.0";
$result = readReport($mime);
check(reset($result)['source'] === 'dsn' && reset($result)['status'] === '5.0.0' && reset($result)['reasonCode'] === 'mailbox_full', 'Missing DSN diagnostic uses text for exactly the same recipient');
$mime->parts['1.1']->data = '<another@example.test>: 550 User unknown';
$result = readReport($mime);
check(reset($result)['reasonCode'] === 'unknown', 'Another recipient cannot supply the missing DSN diagnostic');
echo "returnedDetails: {$checks} checks passed.\n";
