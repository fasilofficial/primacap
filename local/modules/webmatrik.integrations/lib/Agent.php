<?php

namespace Webmatrik\Integrations;

class Agent
{
    public static function fetchLeads()
    {
        $obj = new BayutEmail();
        $obj->fetchEmailLeads();

        $obj = new BayutCalls();
        $obj->fetchPhoneLeads();

        return '\\' . __METHOD__ . '();';
    }

    public static function makeBayutXML()
    {
        $feed = new FeedBayut();

        $feed->makeNewFeed();

        return '\\' . __METHOD__ . '();';
    }

    public static function syncPfLocations()
    {
        $obj = new FeedPf();
        $cities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];

        foreach ($cities as $city) {
            $obj->syncLocations($city);
        }

        return '\\' . __METHOD__ . '();';
    }

    public static function syncPfUsers()
    {
        $offplan = new FeedPf(true, true);
        $offplan->getPfUsers();

        $secondary = new FeedPf(true, false);
        $secondary->getPfUsers();

        return '\\' . __METHOD__ . '();';
    }

    public static function fetchPfEmailLeads()
    {
        try {
            $obj = new PfEmail();
            $obj->fetchLeads();
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $e->getMessage(),
                'PfEmail Error ' . date('Y-m-d H:i:s'),
                'pf_email_leads.log'
            );
        }

        return '\\' . __METHOD__ . '();';
    }

    public static function fetchPfCallLeads()
    {
        try {
            $obj = new PfCall();
            $obj->fetchLeads();
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $e->getMessage(),
                'PfCall Error ' . date('Y-m-d H:i:s'),
                'pf_call_leads.log'
            );
        }

        return '\\' . __METHOD__ . '();';
    }

    public static function fetchPfWhatsappLeads()
    {
        try {
            $obj = new PfWhatsapp();
            $obj->fetchLeads();
        } catch (\Throwable $e) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                $e->getMessage(),
                'PfWhatsapp Error ' . date('Y-m-d H:i:s'),
                'pf_whatsapp_leads.log'
            );
        }

        return '\\' . __METHOD__ . '();';
    }
}
