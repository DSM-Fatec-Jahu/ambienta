<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Email extends BaseConfig
{
    // Populated from .env — see MAIL_* variables
    public string $fromEmail  = '';
    public string $fromName   = '';
    public string $recipients = '';

    public function __construct()
    {
        parent::__construct();

        // Override from .env if set
        if ($host = env('MAIL_SMTP_HOST')) {
            $this->SMTPHost   = $host;
        }
        if ($user = env('MAIL_SMTP_USER')) {
            $this->SMTPUser   = $user;
        }
        if ($pass = env('MAIL_SMTP_PASS')) {
            $this->SMTPPass   = $pass;
        }
        if ($port = env('MAIL_SMTP_PORT')) {
            $this->SMTPPort   = (int) $port;
        }
        if ($crypto = env('MAIL_SMTP_CRYPTO')) {
            $this->SMTPCrypto = $crypto;
        }
        if ($from = env('MAIL_FROM_ADDRESS')) {
            $this->fromEmail  = $from;
        }
        if ($name = env('MAIL_FROM_NAME')) {
            $this->fromName   = $name;
        }
    }

    /**
     * The "user agent"
     */
    public string $userAgent = 'CodeIgniter';

    /**
     * The mail sending protocol: mail, sendmail, smtp
     */
    public string $protocol = 'smtp';

    /**
     * The server path to Sendmail.
     */
    public string $mailPath = '/usr/sbin/sendmail';

    /**
     * SMTP Server Hostname — configure via MAIL_SMTP_HOST in .env
     */
    public string $SMTPHost = '';

    /**
     * Which SMTP authentication method to use: login, plain
     */
    public string $SMTPAuthMethod = 'login';

    /**
     * SMTP Username — configure via MAIL_SMTP_USER in .env
     */
    public string $SMTPUser = '';

    /**
     * SMTP Password — configure via MAIL_SMTP_PASS in .env
     */
    public string $SMTPPass = '';

    /**
     * SMTP Port — configure via MAIL_SMTP_PORT in .env (587 = TLS, 465 = SSL, 25 = plain)
     */
    public int $SMTPPort = 587;

    /**
     * SMTP Timeout (in seconds)
     */
    public int $SMTPTimeout = 5;

    /**
     * Enable persistent SMTP connections
     */
    public bool $SMTPKeepAlive = false;

    /**
     * SMTP Encryption.
     *
     * @var string '', 'tls' or 'ssl'. 'tls' will issue a STARTTLS command
     *             to the server. 'ssl' means implicit SSL. Connection on port
     *             465 should set this to ''.
     */
    public string $SMTPCrypto = 'tls';

    /**
     * Enable word-wrap
     */
    public bool $wordWrap = true;

    /**
     * Character count to wrap at
     */
    public int $wrapChars = 76;

    /**
     * Type of mail, either 'text' or 'html'
     */
    public string $mailType = 'text';

    /**
     * Character set (utf-8, iso-8859-1, etc.)
     */
    public string $charset = 'UTF-8';

    /**
     * Whether to validate the email address
     */
    public bool $validate = false;

    /**
     * Email Priority. 1 = highest. 5 = lowest. 3 = normal
     */
    public int $priority = 3;

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $CRLF = "\r\n";

    /**
     * Newline character. (Use “\r\n” to comply with RFC 822)
     */
    public string $newline = "\r\n";

    /**
     * Enable BCC Batch Mode.
     */
    public bool $BCCBatchMode = false;

    /**
     * Number of emails in each BCC batch
     */
    public int $BCCBatchSize = 200;

    /**
     * Enable notify message from server
     */
    public bool $DSN = false;
}
