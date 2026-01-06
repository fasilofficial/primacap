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
}
