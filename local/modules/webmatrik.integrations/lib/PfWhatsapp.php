<?php

namespace Webmatrik\Integrations;

class PfWhatsapp extends AbstractIntegration
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
        $logFile = 'pf_whatsapp_leads.txt';
        $now  = new \DateTime('now', new \DateTimeZone('UTC'));
        $from = (clone $now)->modify('-1 hour');

        $createdAtFrom = $from->format('Y-m-d\TH:i:s\Z');
        $createdAtTo   = $now->format('Y-m-d\TH:i:s\Z');

        foreach (self::$branches as $branch) {
            try {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    "Fetching PF whatsapp leads [{$branch}] from {$createdAtFrom} to {$createdAtTo}",
                    'PfWhatsapp Start ' . date('Y-m-d H:i:s'),
                    $logFile
                );

                $offplan = $branch === 'offplan';

                new FeedPf(true, $offplan);
                $httpClient = FeedPf::getHttpClient();

                $url = 'https://atlas.propertyfinder.com/v1/leads';
                $queryParams = [
                    'channel'       => 'whatsapp',
                    'createdAtFrom' => $createdAtFrom,
                    'createdAtTo'   => $createdAtTo,
                ];

                $response = $httpClient->get($url . '?' . http_build_query($queryParams));
                $status   = $httpClient->getStatus();

                if ($status !== 200) {
                    \Bitrix\Main\Diag\Debug::writeToFile(
                        "HTTP {$status}: {$response}",
                        "PfWhatsapp API Error [{$branch}] " . date('Y-m-d H:i:s'),
                        $logFile
                    );
                    continue;
                }

                $data  = json_decode($response, true);
                $leads = $data['data'] ?? [];

                \Bitrix\Main\Diag\Debug::writeToFile(
                    $data,
                    "PfWhatsapp Response [{$branch}] " . date('Y-m-d H:i:s'),
                    $logFile
                );

                if (empty($leads)) {
                    \Bitrix\Main\Diag\Debug::writeToFile(
                        "No whatsapp leads found [{$branch}]",
                        'PfWhatsapp ' . date('Y-m-d H:i:s'),
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
                            "PfWhatsapp Deal Create Error [{$branch}] " . date('Y-m-d H:i:s'),
                            $logFile
                        );
                    }
                }
            } catch (\Throwable $e) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    $e->getMessage(),
                    "PfWhatsapp Branch Error [{$branch}] " . date('Y-m-d H:i:s'),
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

        $email = '';
        $phone = '';
        foreach ($sender['contacts'] ?? [] as $contact) {
            if ($contact['type'] === 'email' && empty($email)) {
                $email = $contact['value'];
            } elseif ($contact['type'] === 'phone' && empty($phone)) {
                $phone = $contact['value'];
            }
        }

        $this->leadid         = $lead['id'] ?? '';
        $this->name           = $sender['name'] ?: 'Unknown contact from PF';
        $this->email          = $email;
        $this->phone          = $phone;
        $this->comment        = $lead['message'] ?? '';
        $this->pfproprefufval = $listing['reference'] ?? '';
        $this->pfproplinkufval = $listing['id'] ? ("https://propertyfinder.ae/go/" . $listing['id']) : '';
        $this->pfcontactlinkval = $lead['responseLink'] ?: '';
        $this->title          = 'PfWhatsapp_' . $this->name . '_' . $this->phone;
        $this->pfagentidufval = $agentId;

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
            'PfWhatsapp Processing Lead ' . date('Y-m-d H:i:s'),
            $logFile
        );

        $this->createDeal('WA');
    }
}
