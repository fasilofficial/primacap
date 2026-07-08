<?php

namespace Webmatrik\Integrations;

class PfCall extends AbstractIntegration
{
    private static array $branches = [
        'offplan',
        'secondary',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->source = static::getModuleOption('main_Pf_Source', '');
    }

    public function fetchLeads()
    {
        $logFile = 'pf_call_leads.txt';
        $now  = new \DateTime('now', new \DateTimeZone('UTC'));
        $from = (clone $now)->modify('-1 hour');

        $createdAtFrom = $from->format('Y-m-d\TH:i:s\Z');
        $createdAtTo   = $now->format('Y-m-d\TH:i:s\Z');

        foreach (self::$branches as $branch) {
            try {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    "Fetching PF call leads [{$branch}] from {$createdAtFrom} to {$createdAtTo}",
                    'PfCall Start ' . date('Y-m-d H:i:s'),
                    $logFile
                );

                $offplan = $branch === 'offplan';

                new FeedPf(true, $offplan);
                $httpClient = FeedPf::getHttpClient();

                $url = 'https://atlas.propertyfinder.com/v1/leads';
                $queryParams = [
                    'channel'       => 'call',
                    'createdAtFrom' => $createdAtFrom,
                    'createdAtTo'   => $createdAtTo,
                ];

                $response = $httpClient->get($url . '?' . http_build_query($queryParams));
                $status   = $httpClient->getStatus();

                if ($status !== 200) {
                    \Bitrix\Main\Diag\Debug::writeToFile(
                        "HTTP {$status}: {$response}",
                        "PfCall API Error [{$branch}] " . date('Y-m-d H:i:s'),
                        $logFile
                    );
                    continue;
                }

                $data  = json_decode($response, true);
                $leads = $data['data'] ?? [];

                \Bitrix\Main\Diag\Debug::writeToFile(
                    $data,
                    "PfCall Response [{$branch}] " . date('Y-m-d H:i:s'),
                    $logFile
                );

                if (empty($leads)) {
                    \Bitrix\Main\Diag\Debug::writeToFile(
                        "No call leads found [{$branch}]",
                        'PfCall ' . date('Y-m-d H:i:s'),
                        $logFile
                    );
                    continue;
                }

                foreach ($leads as $lead) {
                    try {
                        $this->processLead($lead, $branch, $logFile);
                    } catch (\Throwable $e) {
                        \Bitrix\Main\Diag\Debug::writeToFile(
                            ['error' => $e->getMessage(), 'lead' => $lead],
                            "PfCall Deal Create Error [{$branch}] " . date('Y-m-d H:i:s'),
                            $logFile
                        );
                    }
                }
            } catch (\Throwable $e) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    $e->getMessage(),
                    "PfCall Branch Error [{$branch}] " . date('Y-m-d H:i:s'),
                    $logFile
                );
            }
        }
    }

    private function processLead(array $lead, string $branch, string $logFile): void
    {
        $sender  = $lead['sender'] ?? [];
        $listing = $lead['listing'] ?? [];
        $agentId = $lead['publicProfile']['id'] ?? "";
        $callMeta = $lead['call'] ?? [];
        $direction = is_array($lead['tags']) && in_array('from_agent', $lead['tags']) ? 'outgoing' : 'incoming';

        $email = '';
        $phone = '';
        foreach ($sender['contacts'] ?? [] as $contact) {
            if ($contact['type'] === 'email' && empty($email)) {
                $email = $contact['value'];
            } elseif ($contact['type'] === 'phone' && empty($phone)) {
                $phone = $contact['value'];
            }
        }

        // Prepare comment with call details
        $comment = '';
        // talk time in seconds
        if ($callMeta['talkTime'] >= 0) {
            $comment .= "Talk Time: " . gmdate("H:i:s", $callMeta['talkTime']) . "\n";
        }
        // wait time in seconds
        if ($callMeta['waitTime'] >= 0) {
            $comment .= "Wait Time: " . gmdate("H:i:s", $callMeta['waitTime']) . "\n";
        }
        if (!empty($direction)) {
            $comment .= "Direction: " . ucfirst($direction) . "\n";
        }
        if (!empty($lead['status'])) {
            if ($lead['status'] === 'replied') {
                $comment .= "Call Status: Answered\n";
            } elseif ($lead['status'] === 'sent') {
                $comment .= "Call Status: Missed\n";
            } else {
                $comment .= "Call Status: " . ucfirst($lead['status']) . "\n";
            }
        }
        if (!empty($callMeta['recordFile'])) {
            $comment .= "Recording: " . $callMeta['recordFile'] . "\n";
        }

        $this->leadid         = $lead['id'] ?? '';
        $this->name           = $sender['name'] ?: 'Unknown contact from PF';
        $this->email          = $email;
        $this->phone          = $phone;
        $this->comment        = $lead['message'] ?? '';
        $this->pfproprefufval = $listing['reference'] ?? '';
        $this->pfproplinkufval = $listing['id'] ? ("https://propertyfinder.ae/go/" . $listing['id']) : '';
        $this->pfcontactlinkval = $lead['responseLink'] ?: '';
        $this->title          = 'PfCall_' . $this->name . '_' . $this->phone;
        $this->pfagentidufval = $agentId;
        $this->commentsval    = $comment;

        \Bitrix\Main\Diag\Debug::writeToFile(
            [
                'branch'    => $branch,
                'pfLeadId'  => $lead['id'] ?? '',
                'name'      => $this->name,
                'email'     => $this->email,
                'phone'     => $this->phone,
                'reference' => $this->pfproprefufval,
                'leadId'    => $this->leadid,
            ],
            'PfCall Processing Lead ' . date('Y-m-d H:i:s'),
            $logFile
        );

        $this->createDeal('pf_phone');
    }
}
