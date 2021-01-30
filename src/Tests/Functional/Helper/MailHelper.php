<?php

namespace Momocode\ShopwareBase\Tests\Functional\Helper;

use Exception;
use ZBateson\MailMimeParser\MailMimeParser;
use ZBateson\MailMimeParser\Message;

/**
 * @author Moritz Müller <moritz@momocode.de>
 */
class MailHelper
{
    const KIND_TO = 'to';
    const KIND_FROM = 'from';
    const KIND_CONTAINING = 'containing';

    /**
     * @var string
     */
    protected $smtpUrl = '';

    /**
     * @param string $smtpUrl
     */
    public function __construct($smtpUrl)
    {
        $this->smtpUrl = $smtpUrl;
    }

    /**
     * @param string $query
     * @param string $kind
     *
     * @return Message|null
     *
     * @throws Exception
     */
    public function getEmail($query, $kind)
    {
        // It takes a second or two for the email to appear in MailHog
        sleep(2);

        $ch = curl_init(sprintf('%s/search?kind=%s&query=%s', $this->smtpUrl, $kind, urlencode($query)));

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        if ($result === false) {
            throw new Exception('Unable to access MailHog server');
        }

        $results = json_decode($result, true);

        if (!empty($results) && $results['count']) {
            $message = $results['items'][0];

            $parser = new MailMimeParser();

            $message = $parser->parse($message['Raw']['Data']);

            return $message;
        }

        return null;
    }
}
