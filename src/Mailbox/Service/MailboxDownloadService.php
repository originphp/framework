<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2020 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
declare(strict_types=1);
namespace Origin\Mailbox\Service;

use Origin\Service\Result;
use Origin\Mailbox\Mailbox;
use Origin\Service\Service;
use Origin\Mailbox\MailFetcher;
use Origin\Core\Exception\Exception;
use Origin\Mailbox\Model\ImapMessage;
use Origin\Mailbox\Model\InboundEmail;

class MailboxDownloadService extends Service
{
    /**
     * @var \Origin\Mailbox\Model\InboundEmail
     */
    protected $InboundEmail;
    
    /**
     * @var \Origin\Mailbox\Model\ImapMessage
     */
    protected $Imap;

    /**
     * Undocumented function
     *
     * @param \Origin\Mailbox\Model\InboundEmail $InboundEmail
     * @param \Origin\Mailbox\Model\ImapMessage $Imap
     * @return void
     */
    protected function initialize(InboundEmail $InboundEmail, ImapMessage $Imap) : void
    {
        $this->InboundEmail = $InboundEmail;
        $this->Imap = $Imap;

        # Set memory limit to prevent issues with large emails
        ini_set('memory_limit', '256M');
    }

    /**
     * If this is IMAP
     *
     * @return boolean
     */
    private function isIMAP() : bool
    {
        return $this->config['protocol'] === 'imap';
    }

    /**
     * Wrapped to make it easier to test
     *
     * @param array $options
     * @return void
     */
    protected function download(array $options)
    {
        return (new MailFetcher($this->config))->download($options);
    }

    /**
     * Executes the service object
     *
     * @param string $account
     * @param array $downloadOptions Options keys supported are
     *   - limit: max number of emails to download
     * @return \Origin\Service\Result|null
     */
    protected function execute(string $account, array $downloadOptions = []) : ?Result
    {
        $downloadOptions += ['limit' => null,'messageId' => null];
        $this->config = Mailbox::account($account);
        
        if ($this->isIMAP()) {
            $lastImapMessage = $this->Imap->findByAccount($account);
            if ($lastImapMessage && ! $downloadOptions['messageId']) {
                $downloadOptions['messageId'] = $lastImapMessage->message_id;
            }

            if (! $lastImapMessage) {
                $lastImapMessage = $this->Imap->new(['account' => $account]);
            }
        }

        $messages = $this->download($downloadOptions);
       
        $messageIds = [];

        # Save downloaded messages to database
        foreach ($messages as $message) {
            $inboundEmail = $this->InboundEmail->fromMessage($message);

            if ($this->InboundEmail->existsInDb($inboundEmail)) {
                continue;
            }

            if (! $this->InboundEmail->save($inboundEmail)) {
                throw new Exception('Error saving to database');
            }

            $messageIds[] = $inboundEmail->message_id;

            if ($this->isIMAP()) {
                $lastImapMessage->message_id = $inboundEmail->message_id;
                if (! $this->Imap->save($lastImapMessage)) {
                    throw new Exception('Error updating IMAP table');
                }
            }
        }

        return $this->result([
            'success' => true,
            'data' => $messageIds
        ]);
    }
}
