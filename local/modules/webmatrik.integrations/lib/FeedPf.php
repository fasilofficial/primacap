<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

class FeedPf extends Feed
{
    public const UF_PF_LOCATION_ID = 'UF_CRM_9_1753773914';
    public const UF_PF_LOCATION_COORDINATES = 'UF_CRM_9_1794228612';
    public const UF_PF_LOCATION_ACTIVE = 'UF_CRM_9_1788936046';
    public const LOCATION_ACTIVE = 1;
    public const LOCATION_INACTIVE = 0;

    protected static $token;
    protected static $mask;
    protected static $offplan;

    public function __construct(bool $gettoken = true, bool $offPlan = false)
    {
        static::$offplan = $offPlan;
        if ($gettoken) {
            static::$token = self::makeAuth();
        }
        static::$mask = [
            'UF_CRM_5_1752506832' => 'age',
            'UF_CRM_5_1752506857' => 'amenities',
            'UF_CRM_5_1752508051' => 'bedrooms',
            'UF_CRM_5_1752507949' => 'bathrooms',
            'UF_CRM_5_1752508146' => 'category',
            'UF_CRM_5_1752508197' => 'compliance,advertisementLicenseIssuanceDate',
            'UF_CRM_5_1752508269' => 'compliance,listingAdvertisementNumber',
            'UF_CRM_5_1752570656' => 'compliance,type',
            'UF_CRM_5_1752508386' => 'compliance,userConfirmedDataIsCorrect',
            'UF_CRM_5_1752508408' => 'description,en',
            'UF_CRM_5_1752508464' => 'description,ar',
            'UF_CRM_5_1752508545' => 'developer',
            'UF_CRM_5_1752577914' => 'finishingType',
            'UF_CRM_5_1752508563' => 'furnishingType',
            'UF_CRM_5_1752508720' => 'floorNumber',
            'UF_CRM_5_1752508637' => 'hasGarden',
            'UF_CRM_5_1752508654' => 'hasKitchen',
            'UF_CRM_5_1752578322' => 'hasParkingOnSite',
            'UF_CRM_5_1752508685' => 'landNumber',
            'UF_CRM_5_1752568955' => 'mojDeedLocationDescription',
            'UF_CRM_5_1752568971' => 'numberOfFloors',
            'UF_CRM_5_1752569001' => 'ownerName',
            'UF_CRM_5_1752569021' => 'parkingSlots',
            'UF_CRM_5_1752569049' => 'plotNumber',
            'UF_CRM_5_1752569108' => 'plotSize',
            // to be changed
            'UF_CRM_5_1754555234' => 'price,amounts,sum',
            //
            'UF_CRM_5_1754891719' => 'price,downpayment',
            'UF_CRM_5_1752569355' => 'price,minimalRentalPeriod',
            'UF_CRM_5_1752569384' => 'price,mortgage,comment',
            'UF_CRM_5_1752579812' => 'price,mortgage,enabled',
            'UF_CRM_5_1752569413' => 'price,numberOfCheques',
            'UF_CRM_5_1752569581' => 'price,numberOfMortgageYears',
            'UF_CRM_5_1752579686' => 'price,obligation,enabled',
            'UF_CRM_5_1752569649' => 'price,obligation,comment',
            'UF_CRM_5_1752569673' => 'price,onRequest',
            'UF_CRM_5_1754893298' => 'price,paymentMethods',
            'UF_CRM_5_1752569908' => 'price,type',
            'UF_CRM_5_1752569772' => 'price,utilitiesInclusive',
            'UF_CRM_5_1752570481' => 'price,valueAffected,comment',
            'UF_CRM_5_1752570503' => 'price,valueAffected,enabled',
            'UF_CRM_5_1752571194' => 'projectStatus',
            'UF_CRM_5_1752571265' => 'reference',
            'UF_CRM_5_1752571276' => 'size',
            'UF_CRM_5_1752571294' => 'street,direction',
            'UF_CRM_5_1752571434' => 'street,width',
            //'UF_CRM_5_1752571489' => 'title,ar',
            'TITLE' => 'title,en',
            'UF_CRM_5_1752571572' => 'type',
            'UF_CRM_5_1752509816' => 'uaeEmirate',
            'UF_CRM_5_1752571865' => 'unitNumber'
        ];
        parent::__construct();
    }

    public static function getHttpClient(bool $problem = false)
    {
        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);

        $httpClient->setHeader('Content-Type', 'application/json', true);
        if (!$problem) {
            $httpClient->setHeader('Accept', 'application/json', true);
        } else {
            $httpClient->setHeader('Accept', 'application/problem+json', true);
        }

        //$httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true);
        if (static::$token) {
            $httpClient->setHeader('Authorization', 'Bearer ' . static::$token, true);
        }
        return $httpClient;
    }

    protected function makeAuth()
    {
        if (static::$offplan) {
            $data = [
                'apiKey' => 'ZbtqB.S9LtCW4yuloB7HLOp9P12wr3YzponeZIaC',
                'apiSecret' => '5qWrfodfthVtL2e0YG2r9WvRPXKWAk5U'
            ];
        } else {
            $data = [
                'apiKey' => 'BlDyE.Fmy2YImN9zFqLgqEr3QTobXDxXHtGUUGPk',
                'apiSecret' => 'CoI2eARQkVfLYxz50q0b2NzVe0bULDZT'
            ];
        }
        $httpClient = self::getHttpClient();

        $response = $httpClient->post(
            'https://atlas.propertyfinder.com/v1/auth/token',
            json_encode($data)
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            //print_r($responseData);
            $token = $responseData['accessToken'];
            if (!$token) {
                throw new \Exception('no token');
            } else {
                return $token;
            }
            //echo '✅ Token: ' . $responseData['accessToken'];
        } else {
            echo "❌ HTTP Error: $status\n";
            echo "Response Body: " . $response . "\n";
            throw new \Exception('no token');
        }
    }

    private static function getCurLocations($factory, $city)
    {
        $params = [
            'select' => ['ID', 'TITLE', static::UF_PF_LOCATION_ID],
            'filter' => [
                '%TITLE' => $city
            ],
            'order' => ['ID' => 'ASC'],
        ];

        $items = $factory->getItemsFilteredByPermissions($params);

        $result = [];

        foreach ($items as $item) {
            $data = $item->getData();
            $result[$data[static::UF_PF_LOCATION_ID]][] = $data['ID'];
        }

        return $result;
    }

    private static function getAllCurLocations($factory)
    {
        $params = [
            'select' => ['ID', 'TITLE', static::UF_PF_LOCATION_ID],
            'order' => ['ID' => 'ASC'],
        ];

        $items = $factory->getItemsFilteredByPermissions($params);

        $result = [];
        foreach ($items as $item) {
            $data = $item->getData();
            $pfId = $data[static::UF_PF_LOCATION_ID];
            if ($pfId) {
                $result[$pfId][] = $data['ID'];
            }
        }

        return $result;
    }

    private static function getPfLocations($city, &$hasError = false)
    {
        $logFile = 'pf_location_sync.log';
        $httpClient = self::getHttpClient();
        $url = 'https://atlas.propertyfinder.com/v1/locations'; // Adjust endpoint as needed

        $queryParams = [
            'search' => $city,
            'page' => 1,
            'perPage' => 100
        ];

        $fullUrl = $url . '?' . http_build_query($queryParams);

        $response = $httpClient->get(
            $fullUrl
        );

        $status = $httpClient->getStatus();

        $pflocations = [];
        $hasError = false;

        if ($status == 200) {
            $responseData = json_decode($response, true);
            if (!is_array($responseData) || !isset($responseData['data'])) {
                $hasError = true;
                \Bitrix\Main\Diag\Debug::writeToFile("❌ Failed to decode PF response or missing data for {$city} page 1", '', $logFile);
                return $pflocations;
            }

            $pages = (int)($responseData['pagination']['totalPages'] ?? 1);
            foreach ($responseData['data'] as $item) {
                if (!empty($item['id'])) {
                    $pflocations[$item['id']] = [
                        'tree'        => $item['tree'] ?? [],
                        'coordinates' => $item['coordinates'] ?? null,
                    ];
                }
            }

            if ($pages > 1) {
                $startpage = 2;
                while ($startpage <= $pages) {
                    $queryParams = [
                        'search' => $city,
                        'page' => $startpage,
                        'perPage' => 100
                    ];
                    $startpage++;
                    $fullUrl = $url . '?' . http_build_query($queryParams);

                    $response = $httpClient->get(
                        $fullUrl
                    );

                    $status = $httpClient->getStatus();

                    if ($status == 200) {
                        $responseData = json_decode($response, true);
                        if (is_array($responseData) && isset($responseData['data'])) {
                            foreach ($responseData['data'] as $item) {
                                if (!empty($item['id'])) {
                                    $pflocations[$item['id']] = [
                                        'tree'        => $item['tree'] ?? [],
                                        'coordinates' => $item['coordinates'] ?? null,
                                    ];
                                }
                            }
                        } else {
                            $hasError = true;
                            \Bitrix\Main\Diag\Debug::writeToFile("❌ JSON parse error for {$city} on page " . ($startpage - 1), '', $logFile);
                            break;
                        }
                    } else {
                        $hasError = true;
                        \Bitrix\Main\Diag\Debug::writeToFile("❌ HTTP Error: {$status} on page " . ($startpage - 1) . " for {$city}", '', $logFile);
                        break;
                    }
                }
            }
        } else {
            $hasError = true;
            \Bitrix\Main\Diag\Debug::writeToFile("❌ HTTP Error: {$status} on page 1 for {$city}. Response: {$response}", '', $logFile);
        }

        return $pflocations;
    }

    public function getPfUsers()
    {
        $logFile = 'pf_user_sync.log';
        $timestamp = date('Y-m-d H:i:s');
        \Bitrix\Main\Diag\Debug::writeToFile('--- PF User Sync Started --- ' . $timestamp, '', $logFile);

        $httpClient = self::getHttpClient();
        $baseUrl = 'https://atlas.propertyfinder.com/v1/users/';

        $pfUsers = [];
        $page = 1;
        $perPage = 100;

        do {
            $queryParams = [
                'status'  => 'active',
                'page'    => $page,
                'perPage' => $perPage
            ];
            $fullUrl = $baseUrl . '?' . http_build_query($queryParams);

            \Bitrix\Main\Diag\Debug::writeToFile("Fetching PF page {$page}: {$fullUrl}", '', $logFile);

            $response = $httpClient->get($fullUrl);
            $status   = $httpClient->getStatus();

            if ($status !== 200) {
                \Bitrix\Main\Diag\Debug::writeToFile("API error on page {$page}, status: {$status}", '', $logFile);
                break;
            }

            $data = json_decode($response, true);

            if (empty($data['data'])) {
                \Bitrix\Main\Diag\Debug::writeToFile("No more data on page {$page}", '', $logFile);
                break;
            }

            foreach ($data['data'] as $item) {
                $email = mb_strtolower(trim($item['email']));
                if ($email && !empty($item['publicProfile']['id'])) {
                    $pfUsers[$email] = $item['publicProfile']['id'];
                }
            }

            $page++;
        } while (count($data['data']) === $perPage);

        \Bitrix\Main\Diag\Debug::writeToFile('Total active PF users fetched: ' . count($pfUsers), '', $logFile);
        \Bitrix\Main\Diag\Debug::writeToFile('PF users list: ' . print_r($pfUsers, true), '', $logFile);

        if (empty($pfUsers)) {
            \Bitrix\Main\Diag\Debug::writeToFile('No active users returned from PF API', '', $logFile);
            \Bitrix\Main\Diag\Debug::writeToFile('--- PF User Sync Completed (no data) --- ' . $timestamp, '', $logFile);
            return;
        }

        // === Get Bitrix users that have matching emails ===
        $bitrixUsers = \Bitrix\Main\UserTable::getList([
            'filter' => ['@EMAIL' => array_keys($pfUsers)],
            'select' => ['ID', 'EMAIL', 'UF_PFID', 'UF_PFID_OP']
        ])->fetchAll();

        \Bitrix\Main\Diag\Debug::writeToFile('Bitrix users to check: ' . count($bitrixUsers), '', $logFile);

        $userObj = new \CUser;

        // Manual fixes for wrong/old emails in Bitrix
        $manualEmailMap = [
            'pouya@primocapital.ae' => 'admin@primocapital.ae',
            'joach@primocapital.ae'  => 'jhela@primocapital.ae',
            // add more if needed
        ];

        foreach ($bitrixUsers as $user) {
            $email         = mb_strtolower($user['EMAIL']);
            $lookupEmail   = $manualEmailMap[$email] ?? $email;
            $currentPfId   = $user['UF_PFID'];
            $newPfId       = $pfUsers[$lookupEmail] ?? null;

            // If user already has a PFID → do absolutely nothing (even if it's wrong)
            if (!empty($currentPfId)) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    "Skipped {$email} (ID: {$user['ID']}) — already has PFID: {$currentPfId}",
                    '',
                    $logFile
                );
                continue;
            }

            // Only add PFID if we found a match and user has none
            if ($newPfId) {
                $userObj->Update($user['ID'], [
                    'UF_PFID' => $newPfId,
                    'UF_PFOP' => static::$offplan
                ]);

                \Bitrix\Main\Diag\Debug::writeToFile(
                    "Added PFID {$newPfId} to {$email} (Bitrix ID: {$user['ID']}) using lookup → {$lookupEmail}",
                    '',
                    $logFile
                );
            } else {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    "No active PF account found for {$email} (tried: {$lookupEmail}) → left empty",
                    '',
                    $logFile
                );
            }
        }

        \Bitrix\Main\Diag\Debug::writeToFile('--- PF User Sync Completed --- ' . $timestamp, '', $logFile);
    }

    public function syncLocations($cities = null, bool $deactivateOutdated = true)
    {
        $logFile = 'pf_location_sync.log';
        $timestamp = date('Y-m-d H:i:s');

        $defaultCities = ['Dubai', 'Abu Dhabi', 'Sharjah', 'Ajman', 'Ras Al Khaimah', 'Fujairah', 'Umm Al Quwain'];
        if ($cities === null) {
            $cities = $defaultCities;
            $isFullSync = true;
        } elseif (is_string($cities)) {
            $cities = [$cities];
            $isFullSync = false;
        } elseif (is_array($cities)) {
            $isFullSync = empty(array_diff($defaultCities, $cities));
        } else {
            $cities = $defaultCities;
            $isFullSync = true;
        }

        \Bitrix\Main\Diag\Debug::writeToFile("--- Sync started for cities: " . implode(', ', $cities) . " --- {$timestamp}", '', $logFile);

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(static::$locentityTypeId);

        if (!$factory) {
            throw new \Exception('Factory not found for entity type ID: ' . static::$locentityTypeId);
        }

        // Cache all current locations once for all calls
        static $allCurLocations = null;
        if ($allCurLocations === null) {
            $params = [
                'select' => ['ID', 'TITLE', static::UF_PF_LOCATION_ID, static::UF_PF_LOCATION_COORDINATES, static::UF_PF_LOCATION_ACTIVE],
                'order'  => ['ID' => 'ASC'],
            ];

            $items = $factory->getItems($params);

            $allCurLocations = [
                'byPf' => [],
                'byTitle' => [],
                'allItems' => []
            ];

            foreach ($items as $item) {
                $data = $item->getData();
                $titleNorm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $data['TITLE'])));
                $pfId = $data[static::UF_PF_LOCATION_ID] ?? null;
                $rawActive = $data[static::UF_PF_LOCATION_ACTIVE] ?? null;
                $geo = $data[static::UF_PF_LOCATION_COORDINATES] ?? '';
                $isYes = ($rawActive === 1 || $rawActive === '1' || $rawActive === true || $rawActive === 'Y');
                $isNo = ($rawActive === 0 || $rawActive === '0' || $rawActive === false || $rawActive === 'N');

                $locInfo = [
                    'ID' => (int)$data['ID'],
                    'TITLE' => $data['TITLE'],
                    'PF_ID' => $pfId,
                    'GEO' => $geo,
                    'IS_ACTIVE' => $isYes ? static::LOCATION_ACTIVE : static::LOCATION_INACTIVE,
                    'IS_YES' => $isYes,
                    'IS_NO' => $isNo,
                ];

                if (!empty($pfId)) {
                    $allCurLocations['byPf'][$pfId] = $locInfo;
                }
                $allCurLocations['allItems'][$data['ID']] = $locInfo;
                $allCurLocations['byTitle'][$titleNorm] = $locInfo;
            }

            \Bitrix\Main\Diag\Debug::writeToFile('Cached existing locations: ' . count($allCurLocations['byTitle']), '', $logFile);
        }

        $validPfIds = [];
        $hadFetchErrors = false;

        foreach ($cities as $city) {
            $cityTimestamp = date('Y-m-d H:i:s');
            \Bitrix\Main\Diag\Debug::writeToFile("--- Syncing city: {$city} --- {$cityTimestamp}", '', $logFile);

            // Fetch locations from Property Finder
            $cityHasError = false;
            $pfLocations = static::getPfLocations($city, $cityHasError);

            if ($cityHasError) {
                $hadFetchErrors = true;
                \Bitrix\Main\Diag\Debug::writeToFile("Fetch error occurred for {$city}. Outdated deactivation will be skipped for safety.", '', $logFile);
            }

            if (empty($pfLocations)) {
                \Bitrix\Main\Diag\Debug::writeToFile("No PF locations found for {$city}", '', $logFile);
                continue;
            }

            foreach ($pfLocations as $pfId => $locPayload) {
                $validPfIds[$pfId] = true;
                $tree = is_array($locPayload) && isset($locPayload['tree']) ? $locPayload['tree'] : (is_array($locPayload) ? $locPayload : []);
                $coordinates = is_array($locPayload) && isset($locPayload['coordinates']) ? $locPayload['coordinates'] : null;

                $geoPoint = '';
                if (!empty($coordinates)) {
                    $lat = $coordinates['lat'] ?? $coordinates['latitude'] ?? null;
                    $lng = $coordinates['lng'] ?? $coordinates['longitude'] ?? null;
                    if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
                        $geoPoint = $lat . ',' . $lng;
                    }
                }

                // 1. If PF ID already exists in Bitrix
                if (isset($allCurLocations['byPf'][$pfId])) {
                    $existingLoc = $allCurLocations['byPf'][$pfId];
                    $needsReactivate = !$existingLoc['IS_YES'];
                    $needsGeoUpdate = ($geoPoint && ($existingLoc['GEO'] ?? '') !== $geoPoint);

                    if ($needsReactivate || $needsGeoUpdate) {
                        $existingItem = $factory->getItem($existingLoc['ID']);
                        if ($existingItem) {
                            if ($needsReactivate) {
                                $existingItem->set(static::UF_PF_LOCATION_ACTIVE, static::LOCATION_ACTIVE);
                            }
                            if ($needsGeoUpdate) {
                                $existingItem->set(static::UF_PF_LOCATION_COORDINATES, $geoPoint);
                            }
                            $updateOp = $factory->getUpdateOperation($existingItem)
                                ->disableCheckFields()
                                ->disableBizProc()
                                ->disableCheckAccess();
                            $updateRes = $updateOp->launch();

                            if ($updateRes->isSuccess()) {
                                if ($needsReactivate) {
                                    \Bitrix\Main\Diag\Debug::writeToFile("Set location '{$existingLoc['TITLE']}' (ID: {$existingLoc['ID']}, PFID: {$pfId}) to Yes (ACTIVE)", '', $logFile);
                                    $allCurLocations['byPf'][$pfId]['IS_ACTIVE'] = static::LOCATION_ACTIVE;
                                    $allCurLocations['byPf'][$pfId]['IS_YES'] = true;
                                    $allCurLocations['byPf'][$pfId]['IS_NO'] = false;
                                    if (isset($allCurLocations['allItems'][$existingLoc['ID']])) {
                                        $allCurLocations['allItems'][$existingLoc['ID']]['IS_ACTIVE'] = static::LOCATION_ACTIVE;
                                        $allCurLocations['allItems'][$existingLoc['ID']]['IS_YES'] = true;
                                        $allCurLocations['allItems'][$existingLoc['ID']]['IS_NO'] = false;
                                    }
                                }
                                if ($needsGeoUpdate) {
                                    \Bitrix\Main\Diag\Debug::writeToFile("Updated location '{$existingLoc['TITLE']}' (ID: {$existingLoc['ID']}, PFID: {$pfId}) coordinates to {$geoPoint}", '', $logFile);
                                    $allCurLocations['byPf'][$pfId]['GEO'] = $geoPoint;
                                    if (isset($allCurLocations['allItems'][$existingLoc['ID']])) {
                                        $allCurLocations['allItems'][$existingLoc['ID']]['GEO'] = $geoPoint;
                                    }
                                }
                            } else {
                                $errors = implode('; ', $updateRes->getErrorMessages());
                                \Bitrix\Main\Diag\Debug::writeToFile("Failed to update location '{$existingLoc['TITLE']}' (ID: {$existingLoc['ID']}): {$errors}", '', $logFile);
                            }
                        }
                    }
                    continue;
                }

                // Build and normalize title
                $reversedTree = array_reverse($tree, true);
                $titles = [];
                foreach ($reversedTree as $locPart) {
                    $titles[] = $locPart['name'];
                }

                $title = implode(', ', $titles);
                $titleNorm = mb_strtolower(trim(preg_replace('/\s+/', ' ', $title)));

                // 2. Check if location with same title already exists
                if (isset($allCurLocations['byTitle'][$titleNorm])) {
                    $existingLoc = $allCurLocations['byTitle'][$titleNorm];
                    $existingId = $existingLoc['ID'];
                    $existingItem = $factory->getItem($existingId);

                    if ($existingItem) {
                        $existingItem->set(static::UF_PF_LOCATION_ID, $pfId);
                        $existingItem->set(static::UF_PF_LOCATION_ACTIVE, static::LOCATION_ACTIVE);
                        if ($geoPoint) {
                            $existingItem->set(static::UF_PF_LOCATION_COORDINATES, $geoPoint);
                        }
                        $updateOp = $factory->getUpdateOperation($existingItem)
                            ->disableCheckFields()
                            ->disableBizProc()
                            ->disableCheckAccess();
                        $updateRes = $updateOp->launch();

                        if ($updateRes->isSuccess()) {
                            \Bitrix\Main\Diag\Debug::writeToFile("Updated existing location '{$title}' (ID: {$existingId}) with PFID: {$pfId} (Yes)" . ($geoPoint ? " and coordinates: {$geoPoint}" : ""), '', $logFile);
                            $locInfo = [
                                'ID' => $existingId,
                                'TITLE' => $title,
                                'PF_ID' => $pfId,
                                'GEO' => $geoPoint ?: ($existingLoc['GEO'] ?? ''),
                                'IS_ACTIVE' => static::LOCATION_ACTIVE,
                                'IS_YES' => true,
                                'IS_NO' => false,
                            ];
                            $allCurLocations['byPf'][$pfId] = $locInfo;
                            $allCurLocations['allItems'][$existingId] = $locInfo;
                            $allCurLocations['byTitle'][$titleNorm] = $locInfo;
                        } else {
                            $errors = implode('; ', $updateRes->getErrorMessages());
                            \Bitrix\Main\Diag\Debug::writeToFile("Failed to update location '{$title}': {$errors}", '', $logFile);
                        }
                    }
                    continue;
                }

                // 3. Create new location if not found by PF ID or Title
                $newItem = $factory->createItem([
                    'TITLE' => $title,
                    'ASSIGNED_BY_ID' => 1013,
                    static::UF_PF_LOCATION_ID => $pfId,
                    static::UF_PF_LOCATION_COORDINATES => $geoPoint ?: '',
                    static::UF_PF_LOCATION_ACTIVE => static::LOCATION_ACTIVE,
                ]);

                $operation = $factory->getAddOperation($newItem)
                    ->disableCheckFields()
                    ->disableBizProc()
                    ->disableCheckAccess();

                $addResult = $operation->launch();

                if ($addResult->isSuccess()) {
                    $newId = $newItem->getId();
                    \Bitrix\Main\Diag\Debug::writeToFile("Added new location '{$title}' (ID: {$newId}, PFID: {$pfId}, GEO: {$geoPoint}, Yes)", '', $logFile);

                    $locInfo = [
                        'ID' => $newId,
                        'TITLE' => $title,
                        'PF_ID' => $pfId,
                        'GEO' => $geoPoint ?: '',
                        'IS_ACTIVE' => static::LOCATION_ACTIVE,
                        'IS_YES' => true,
                        'IS_NO' => false,
                    ];
                    $allCurLocations['byPf'][$pfId] = $locInfo;
                    $allCurLocations['allItems'][$newId] = $locInfo;
                    $allCurLocations['byTitle'][$titleNorm] = $locInfo;
                } else {
                    $errors = implode('; ', $addResult->getErrorMessages());
                    \Bitrix\Main\Diag\Debug::writeToFile("Failed to add location '{$title}': {$errors}", '', $logFile);
                }
            }

            \Bitrix\Main\Diag\Debug::writeToFile("--- City completed: {$city} ---", '', $logFile);
        }

        // --- Set Outdated / Inactive Locations to No ---
        if ($deactivateOutdated && !$hadFetchErrors && !empty($validPfIds)) {
            \Bitrix\Main\Diag\Debug::writeToFile("Checking for outdated/inactive locations against " . count($validPfIds) . " valid PF IDs...", '', $logFile);

            $deactivatedCount = 0;

            foreach ($allCurLocations['allItems'] as $locId => $locData) {
                $pfId = $locData['PF_ID'];

                // If this location's PF ID is valid in PF, skip (it is active)
                if (!empty($pfId) && isset($validPfIds[$pfId])) {
                    continue;
                }

                // If not a full sync, only deactivate locations belonging to the synced cities
                if (!$isFullSync) {
                    $belongsToSyncedCity = false;
                    foreach ($cities as $city) {
                        if (stripos($locData['TITLE'], $city) !== false) {
                            $belongsToSyncedCity = true;
                            break;
                        }
                    }
                    if (!$belongsToSyncedCity) {
                        continue;
                    }
                }

                // If NOT already set to No (i.e. currently Yes or null/empty), set to No (0)
                if (!$locData['IS_NO']) {
                    $item = $factory->getItem($locId);
                    if ($item) {
                        $item->set(static::UF_PF_LOCATION_ACTIVE, static::LOCATION_INACTIVE);
                        $updateOp = $factory->getUpdateOperation($item)
                            ->disableCheckFields()
                            ->disableBizProc()
                            ->disableCheckAccess();
                        $updateRes = $updateOp->launch();

                        if ($updateRes->isSuccess()) {
                            $deactivatedCount++;
                            \Bitrix\Main\Diag\Debug::writeToFile("Set location '{$locData['TITLE']}' (ID: {$locId}, PFID: " . ($pfId ?: 'NONE') . ") to No (INACTIVE)", '', $logFile);
                            $allCurLocations['allItems'][$locId]['IS_ACTIVE'] = static::LOCATION_INACTIVE;
                            $allCurLocations['allItems'][$locId]['IS_YES'] = false;
                            $allCurLocations['allItems'][$locId]['IS_NO'] = true;
                            if (!empty($pfId) && isset($allCurLocations['byPf'][$pfId])) {
                                $allCurLocations['byPf'][$pfId]['IS_ACTIVE'] = static::LOCATION_INACTIVE;
                                $allCurLocations['byPf'][$pfId]['IS_YES'] = false;
                                $allCurLocations['byPf'][$pfId]['IS_NO'] = true;
                            }
                        } else {
                            $errors = implode('; ', $updateRes->getErrorMessages());
                            \Bitrix\Main\Diag\Debug::writeToFile("Failed to mark location '{$locData['TITLE']}' (ID: {$locId}) as No: {$errors}", '', $logFile);
                        }
                    }
                }
            }

            \Bitrix\Main\Diag\Debug::writeToFile("Outdated locations check completed. Set {$deactivatedCount} locations to No (Inactive).", '', $logFile);
        }

        $endTimestamp = date('Y-m-d H:i:s');
        \Bitrix\Main\Diag\Debug::writeToFile("--- PF Location Sync finished --- {$endTimestamp}", '', $logFile);
    }

    /**
     * One-time or bulk utility to set all currently unassigned or inactive locations in Bitrix SPA to No (0).
     *
     * @return int Number of updated locations
     */
    public static function setAllInactiveLocationsToNo()
    {
        $logFile = 'pf_location_sync.log';
        $timestamp = date('Y-m-d H:i:s');
        \Bitrix\Main\Diag\Debug::writeToFile("--- Bulk setting inactive locations to No started --- {$timestamp}", '', $logFile);

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(static::$locentityTypeId);
        if (!$factory) {
            throw new \Exception('Factory not found for entity type ID: ' . static::$locentityTypeId);
        }

        $params = [
            'select' => ['ID', 'TITLE', static::UF_PF_LOCATION_ID, static::UF_PF_LOCATION_ACTIVE],
            'order'  => ['ID' => 'ASC'],
        ];

        $items = $factory->getItems($params);
        $updatedCount = 0;

        foreach ($items as $item) {
            $data = $item->getData();
            $val = $data[static::UF_PF_LOCATION_ACTIVE] ?? null;

            // If already Yes, skip
            if ($val === 1 || $val === '1' || $val === true || $val === 'Y') {
                continue;
            }

            // If already No, skip
            if ($val === 0 || $val === '0' || $val === false || $val === 'N') {
                continue;
            }

            // Otherwise (null or empty), set to No (0)
            $item->set(static::UF_PF_LOCATION_ACTIVE, static::LOCATION_INACTIVE);
            $updateOp = $factory->getUpdateOperation($item)
                ->disableCheckFields()
                ->disableBizProc()
                ->disableCheckAccess();
            $updateRes = $updateOp->launch();

            if ($updateRes->isSuccess()) {
                $updatedCount++;
                \Bitrix\Main\Diag\Debug::writeToFile("Set location '{$data['TITLE']}' (ID: {$data['ID']}) to No (Inactive)", '', $logFile);
            } else {
                $errors = implode('; ', $updateRes->getErrorMessages());
                \Bitrix\Main\Diag\Debug::writeToFile("Failed to set location '{$data['TITLE']}' (ID: {$data['ID']}) to No: {$errors}", '', $logFile);
            }
        }

        \Bitrix\Main\Diag\Debug::writeToFile("Bulk setting inactive locations to No completed. Total updated: {$updatedCount}", '', $logFile);

        return $updatedCount;
    }

    private static function processLocations($data, $factory)
    {
        foreach ($data as $key => $item) {
            if ($key == 99) {
                print_r($item);
                $locid = $item['id'];
                $newtree = array_reverse($item['tree'], true);
                $titles = [];
                foreach ($newtree as $item) {
                    $titles[] = $item['name'];
                }
                $title = implode(',', $titles);
                $item = $factory->createItem([
                    'TITLE' => $title,
                    'ASSIGNED_BY_ID' => 1013,
                    'UF_CRM_9_1753773914' => $locid
                ]);
                $operation = $factory->getAddOperation($item);
                $operation
                    ->disableCheckFields()
                    ->disableBizProc()
                    ->disableCheckAccess()
                ;
                $addResult = $operation->launch();

                $errorMessages = $addResult->getErrorMessages();

                if ($addResult->isSuccess()) {
                    // получаем ID новой записи СП
                    $newId = $item->getId();
                    echo $newId;
                } else {
                    echo "fail";
                }
            }
        }
    }

    public static function delDupl()
    {
        $logFile = 'pf_location_cleanup.log';
        $entityTypeId = static::$locentityTypeId;
        $container = Container::getInstance();
        $factory = $container->getFactory($entityTypeId);

        if (!$factory) {
            throw new \Exception('Factory not found for entity type ID: ' . $entityTypeId);
        }

        $limit = 2000; // process 2k records at a time
        $offset = 0;
        $deletedCount = 0;

        $pfGroups = []; // we'll group PF IDs across batches

        do {
            $params = [
                'select' => ['ID', 'TITLE', static::UF_PF_LOCATION_ID],
                'filter' => [],
                'order'  => ['ID' => 'ASC'],
                'limit'  => $limit,
                'offset' => $offset,
            ];

            $items = $factory->getItems($params);
            $count = count($items);
            if ($count === 0) break;

            foreach ($items as $item) {
                $data = $item->getData();
                $pfId = $data[static::UF_PF_LOCATION_ID] ?: 'NO_PFID';
                $pfGroups[$pfId][] = $data;
            }

            $offset += $limit;
            unset($items);
            gc_collect_cycles(); // free memory manually

        } while ($count === $limit);

        // Now that we have all PF IDs grouped, delete duplicates per group
        foreach ($pfGroups as $pfId => $group) {
            if ($pfId === 'NO_PFID' || count($group) <= 1) continue;

            usort($group, fn($a, $b) => $a['ID'] <=> $b['ID']);
            array_shift($group); // keep the first one

            foreach ($group as $dup) {
                $fit = $factory->getItem($dup['ID']);
                if ($fit) {
                    $operation = $factory->getDeleteOperation($fit)
                        ->disableCheckFields()
                        ->disableBizProc()
                        ->disableCheckAccess();

                    $res = $operation->launch();
                    if ($res->isSuccess()) {
                        $deletedCount++;
                        \Bitrix\Main\Diag\Debug::writeToFile(
                            "Deleted duplicate '{$dup['TITLE']}' (ID: {$dup['ID']}, PFID: {$pfId})",
                            '',
                            $logFile
                        );
                    } else {
                        $errors = implode('; ', $res->getErrorMessages());
                        \Bitrix\Main\Diag\Debug::writeToFile(
                            "Failed to delete '{$dup['TITLE']}' (ID: {$dup['ID']}): {$errors}",
                            '',
                            $logFile
                        );
                    }
                }
            }

            // Free memory for this group
            unset($pfGroups[$pfId]);
            gc_collect_cycles();
        }

        \Bitrix\Main\Diag\Debug::writeToFile("✅ Cleanup completed. Deleted {$deletedCount} duplicates.", '', $logFile);
    }

    public function sendListingDraft($lisId)
    {
        $filter = [
            'ID' => $lisId,
            '@UF_CRM_5_1752569141' => [1297, 1485]
        ];
        $data = static::retrieveDate($filter, 'Pf');
        if (!$data) {
            $data = static::retrieveDate(['ID' => $lisId], 'Pf');
        }
        if (!$data) {
            throw new \Exception('No data for export. Please check portals field');
        } else {
            $data = self::prepareListing(current($data));
            //print_r($data);
            $lisid = self::deliverListing($data);
            return $lisid;
        }
    }

    private $allowedAmenities = [
        'commercial' => [
            'farm' => [],
            'land' => [],
            'bulk-rent-unit' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'bulk-sale-unit' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'business-center' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'co-working-space' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'factory' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'full-floor' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'half-floor' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'labor-camp' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'office-space' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'retail' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'shop' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'show-room' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'staff-accommodation' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'villa' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'warehouse' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
            'whole-building' => ['shared-gym', 'covered-parking', 'networked', 'shared-pool', 'dining-in-building', 'conference-room', 'lobby-in-building', 'vastu-compliant'],
        ],

        'residential' => [
            'land' => [],
            'apartment' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'bulk-rent-unit' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'bulk-sale-unit' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'bungalow' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'compound' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'duplex' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'full-floor' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'half-floor' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'hotel-apartment' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'penthouse' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'townhouse' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'villa' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
            'whole-building' => ['central-ac', 'built-in-wardrobes', 'kitchen-appliances', 'security', 'concierge', 'maid-service', 'balcony', 'private-gym', 'shared-gym', 'private-jacuzzi', 'shared-spa', 'covered-parking', 'maids-room', 'study', 'childrens-play-area', 'pets-allowed', 'barbecue-area', 'shared-pool', 'childrens-pool', 'private-garden', 'private-pool', 'view-of-water', 'view-of-landmark', 'walk-in-closet', 'lobby-in-building', 'vastu-compliant'],
        ],
    ];

    protected function prepareListing(array $data)
    {
        file_put_contents(__DIR__ . '/listing.json', json_encode($data));
        $reserr = [];
        $resdescr = [];
        if (!$data['price']['amounts']['sum']) {
            $reserr[] = 'price in AED';
        } else {
            $sum = $data['price']['amounts']['sum'];
            unset($data['price']['amounts']['sum']);
            switch ($data['price']['type']) {
                case 'sale':
                    $data['price']['amounts']['sale'] = $sum;
                    break;
                case 'daily':
                    $data['price']['amounts']['daily'] = $sum;
                    break;
                case 'monthly':
                    $data['price']['amounts']['monthly'] = $sum;
                    break;
                case 'weekly':
                    $data['price']['amounts']['weekly'] = $sum;
                    break;
                case 'yearly':
                    $data['price']['amounts']['yearly'] = $sum;
                    break;
            }
        }
        if ($data['price']['amounts']['type'] == 'sale') {
            if (!$data['price']['downpayment']) {
                $reserr[] = 'downpayment';
            }
        }

        if (!$data['uaeEmirate']) {
            $reserr[] = 'uaeEmirate';
        } else {
            $emirate = $data['uaeEmirate'];
            $validTypes = [];

            if ($emirate === 'dubai') {
                $validTypes = ['rera', 'dtcm'];
            } elseif ($emirate === 'abu_dhabi') {
                $validTypes = ['adrec'];
            } elseif ($emirate === 'northen_emirates') {
                $validTypes = [];
            }

            if (!empty($validTypes)) {
                if (empty($data['compliance']['listingAdvertisementNumber'])) {
                    $reserr[] = 'listingAdvertisementNumber (Permit No.)';
                }

                $compType = $data['compliance']['type'] ?? null;

                if (!$compType) {
                    $reserr[] = 'compliance type';
                } elseif (!in_array($compType, $validTypes)) {
                    unset($data['compliance']);
                }
            } else {
                unset($data['compliance']);
            }
        }

        if (!$data['type']) {
            $reserr[] = 'type';
        } else {
            if ($data['type'] != 'farm' || $data['type'] != 'land') {
                // if (!$data['bedrooms']) {
                //     $reserr[] = 'bedrooms';
                // }
                if (!$data['bathrooms']) {
                    $reserr[] = 'bathrooms';
                }
            }
            if ($data['type'] == 'co-working-space') {
                if (!$data['hasParkingOnSite']) {
                    $reserr[] = 'hasParkingOnSite';
                }
            }
        }
        if ($data['location']) {
            $loc = $data['location'];
            unset($data['location']);
            $data['location']['id'] = is_array($loc) ? (int)($loc['id'] ?? 0) : (int)$loc;
        } else {
            $reserr[] = 'location';
        }
        if ($data['assignedTo']) {
            $assgn = $data['assignedTo'];
            unset($data['assignedTo']);
            $data['assignedTo']['id'] = $assgn;
            if (!$data['createdBy']) {
                unset($data['createdBy']);
                $data['createdBy']['id'] = $assgn;
            } else {
                $cre = $data['createdBy'];
                unset($data['createdBy']);
                $data['createdBy']['id'] = $cre;
            }
        } else {
            $reserr[] = 'assignedTo';
            $resdescr[] = 'User not present at PropertyFinder account';
        }
        if (!empty($reserr)) {
            throw new \Exception('Errors in fields ' . implode(",", $reserr) .
                '!' . implode(".", $resdescr));
        } else {
            ksort($data);

            // Filter invalid amenities
            if (!empty($data['amenities']) && !empty($data['category']) && !empty($data['type'])) {
                $category = $data['category'];
                $type     = $data['type'];

                if (isset($this->allowedAmenities[$category][$type])) {
                    $allowed = $this->allowedAmenities[$category][$type];
                    $data['amenities'] = array_values(array_intersect($data['amenities'], $allowed));
                } else {
                    // No amenities allowed for this type
                    $data['amenities'] = [];
                }
            }

            // add missing compliance license number
            if (
                isset($data['compliance']) &&
                (
                    !isset($data['compliance']['issuingClientLicenseNumber']) ||
                    empty($data['compliance']['issuingClientLicenseNumber'])
                )
            ) {
                $data['compliance']['issuingClientLicenseNumber'] = '1011138';
            }

            return $data;
        }
    }

    protected function deliverListing(array $data)
    {
        $httpClient = self::getHttpClient();
        //print_r($data);

        //echo __DIR__;

        //$jsonFile = __DIR__.'/UpdatedJSON.json';

        // Check if file exists
        /*if (!file_exists($jsonFile)) {
			die("JSON file not found: $jsonFile");
		}
		
		// Read file contents
		$dataj = file_get_contents($jsonFile);
		if ($jsonData === false) {
			die("Failed to read JSON file");
		}*/

        //$dataj = file_get_contents('UpdatedJSON.json');
        //print_r($dataj);
        //print_r('xxxx');

        $dataj =  json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        file_put_contents(__DIR__ . '/data.json', $dataj);
        $response = $httpClient->post(
            'https://atlas.propertyfinder.com/v1/listings',
            $dataj
        );

        $status = $httpClient->getStatus();

        if ($status == 200 || $status == 201) {
            $responseData = json_decode($response, true);
            print_r($responseData);
            \Bitrix\Main\Diag\Debug::writeToFile($responseData, "success exp " . date('Y-m-d H:i:s'), "pfexport.log");
            return $responseData['id'];
        } else {
            //$contentType = $httpClient->getHeaders();
            //print_r($contentType);
            echo "❌ HTTP Error: $status\n";
            echo "Response Body: " . $response . "\n";
            \Bitrix\Main\Diag\Debug::writeToFile($response, "error exp " . date('Y-m-d H:i:s'), "pfexport.log");
            $err = json_decode($response, true);
            print_r($err);
            $parsedError = self::parseErrorMessage($response);
            if (str_contains($response, 'reference already exists')) {
                throw new \Exception('Error in creating listing - reference is not unique. Details: ' . $parsedError);
            } elseif (str_contains($response, 'does not match authenticated user')) {
                throw new \Exception('Error in creating listing - assigned agent not registered for Pf account. Details: ' . $parsedError);
            } else {
                throw new \Exception('Error in creating listing. HTTP ' . $status . ': ' . $parsedError);
            }
        }
    }

    public function searchLocation($search)
    {
        $httpClient = self::getHttpClient();

        $url = 'https://atlas.propertyfinder.com/v1/locations'; // Adjust endpoint as needed

        $queryParams = [
            'search' => $search,
            'page' => 1, // Example additional parameter,
            'perPage' => 100
        ];

        $fullUrl = $url . '?' . http_build_query($queryParams);

        $response = $httpClient->get(
            $fullUrl
        );

        $status = $httpClient->getStatus();

        $pflocations = [];

        if ($status == 200) {
            $responseData = json_decode($response, true);
            print_r($responseData);
        }
    }

    public function getListing($id)
    {
        $httpClient = self::getHttpClient();

        $url = 'https://atlas.propertyfinder.com/v1/listings'; // Adjust endpoint as needed

        $queryParams = [
            'filter' => [
                'ids' => $id
            ]
        ];

        $fullUrl = $url . '?' . http_build_query($queryParams);

        $response = $httpClient->get(
            $fullUrl
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            print_r($responseData);
        }
    }

    private function normalizePriceAmounts(array &$payload)
    {
        if (
            empty($payload['price']['type']) ||
            empty($payload['price']['amounts']) ||
            !is_array($payload['price']['amounts'])
        ) {
            return;
        }

        $type = $payload['price']['type'];

        // Map PF price types to amounts key
        $map = [
            'sale'    => 'sale',
            'yearly'  => 'yearly',
            'monthly' => 'monthly',
            'weekly'  => 'weekly',
            'daily'   => 'daily',
        ];

        if (!isset($map[$type])) {
            return;
        }

        $key = $map[$type];

        if (!isset($payload['price']['amounts'][$key])) {
            return;
        }

        // Keep ONLY the correct amount
        $payload['price']['amounts'] = [
            $key => $payload['price']['amounts'][$key]
        ];
    }

    public function updateListing($pfListingId, $bitrixListingId)
    {
        if (!$pfListingId || !$bitrixListingId) {
            throw new \Exception('PF Listing ID and Bitrix Listing ID are required for update');
        }

        // Step 1: Load data from Bitrix
        $filter = [
            'ID' => $bitrixListingId,
            '@UF_CRM_5_1752569141' => [1297, 1485]
        ];
        $data = static::retrieveDate($filter, 'Pf');
        if (!$data) {
            throw new \Exception("No data found for Bitrix listing ID {$bitrixListingId}");
        }
        $crmPayload = self::prepareListing(current($data));

        // Step 2: Fetch existing PF listing payload (try LIVE first)
        $httpClient = self::getHttpClient();

        // 2A. Try LIVE listing
        $existingJson = $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
            'filter' => ['ids' => $pfListingId]
        ]));
        $status = $httpClient->getStatus();

        $pfPayload = null;

        if ($status === 200) {
            $decoded = json_decode($existingJson, true);

            if (!empty($decoded['results'][0])) {
                $pfPayload = $decoded['results'][0];
            }
        }

        // 2B. If not found in LIVE, try DRAFT
        if (!$pfPayload) {

            $draftUrl = "https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                'draft' => 'true',
                'filter' => ['ids' => $pfListingId]
            ]);

            $draftJson = $httpClient->get($draftUrl);
            $draftStatus = $httpClient->getStatus();

            if ($draftStatus === 200) {
                $draftDecoded = json_decode($draftJson, true);

                // PF returns draft listings under "results"
                if (!empty($draftDecoded['results'][0])) {
                    $pfPayload = $draftDecoded['results'][0];
                }
            }
        }

        // If STILL empty → fail
        if (!$pfPayload) {
            throw new \Exception("Unable to retrieve PF listing {$pfListingId} in live or draft");
        }

        // Step 3: Merge CRM payload into PF payload
        $mergedPayload = $this->deepMerge($pfPayload, $crmPayload);

        // 🔑 Fix price.amounts issue
        $this->normalizePriceAmounts($mergedPayload);

        // Clean invalid structures expected as objects
        $this->cleanPfPayload($mergedPayload);

        // Step 4: Send update (PUT)
        $jsonPayload = json_encode($mergedPayload, JSON_UNESCAPED_SLASHES);

        file_put_contents(__DIR__ . '/pf_existing.json', json_encode($pfPayload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        file_put_contents(__DIR__ . '/crm_payload.json', json_encode($crmPayload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        file_put_contents(__DIR__ . '/update_data.json', $jsonPayload);

        $response = $httpClient->query(
            \Bitrix\Main\Web\HttpClient::HTTP_PUT,
            "https://atlas.propertyfinder.com/v1/listings/{$pfListingId}",
            $jsonPayload
        );

        $status = $httpClient->getStatus();
        $responseBody = $httpClient->getResult();

        if ($status == 200 || $status == 204 || $status == 201) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['pfListingId' => $pfListingId, 'bitrixId' => $bitrixListingId],
                "PF Update SUCCESS " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            return true;
        }

        $parsedError = self::parseErrorMessage($responseBody);
        throw new \Exception("Failed to update listing. HTTP {$status}: {$parsedError}");
    }

    public function submitListingVerification($pfListingId)
    {
        if (!$pfListingId) {
            throw new \Exception('PF Listing is required for verification submission');
        }

        // Step 1: Fetch existing PF listing payload (try LIVE first)
        $httpClient = self::getHttpClient();

        // 1A. Try LIVE listing
        $existingJson = $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
            'filter' => ['ids' => $pfListingId]
        ]));
        $status = $httpClient->getStatus();

        $pfPayload = null;

        if ($status === 200) {
            $decoded = json_decode($existingJson, true);

            if (!empty($decoded['results'][0])) {
                $pfPayload = $decoded['results'][0];
            }
        }

        // 1B. If not found in LIVE, try DRAFT
        if (!$pfPayload) {

            $draftUrl = "https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                'draft' => 'true',
                'filter' => ['ids' => $pfListingId]
            ]);

            $draftJson = $httpClient->get($draftUrl);
            $draftStatus = $httpClient->getStatus();

            if ($draftStatus === 200) {
                $draftDecoded = json_decode($draftJson, true);

                // PF returns draft listings under "results"
                if (!empty($draftDecoded['results'][0])) {
                    $pfPayload = $draftDecoded['results'][0];
                }
            }
        }

        // If STILL empty → fail
        if (!$pfPayload) {
            throw new \Exception("Unable to retrieve PF listing {$pfListingId} in live or draft");
        }

        $publicProfileId = $pfPayload['assignedTo']['id'] ?? null;

        if (!$publicProfileId) {
            throw new \Exception("Unable to retrieve public profile ID for listing {$pfListingId}");
        }

        // Step 2: Prepare verification payload
        $verificationPayload = [
            'listingId' => $pfListingId,
            'publicProfileId' => $publicProfileId,
            // 'documents' => []
        ];
        $jsonPayload = json_encode($verificationPayload, JSON_UNESCAPED_SLASHES);

        $response = $httpClient->query(
            \Bitrix\Main\Web\HttpClient::HTTP_POST,
            "https://atlas.propertyfinder.com/v1/listing-verifications",
            $jsonPayload
        );

        $status = $httpClient->getStatus();
        $responseBody = $httpClient->getResult();

        if ($status == 200 || $status == 204 || $status == 201) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['pfListingId' => $pfListingId],
                "PF verification submission SUCCESS " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            $response = json_decode($responseBody, true);
            return $response;
        }

        $parsedError = self::parseErrorMessage($responseBody);
        throw new \Exception("Failed to submit listing for verification. HTTP {$status}: {$parsedError}");
    }

    public function getCreditBalance()
    {
        $httpClient = self::getHttpClient();

        $url = 'https://atlas.propertyfinder.com/v1/credits/balance';

        $response = $httpClient->get(
            $url
        );

        $status = $httpClient->getStatus();

        if ($status == 200) {
            $responseData = json_decode($response, true);
            return $responseData;
        }
    }

    public function publishListing($listingId)
    {
        $httpClient = self::getHttpClient();

        $url = "https://atlas.propertyfinder.com/v1/listings/{$listingId}/publish";

        $response = $httpClient->post($url, ''); // Body can be empty

        $status = $httpClient->getStatus();

        if ($status === 200 || $status === 204) {
            $responseData = $response ? json_decode($response, true) : [];
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'publish', 'response' => $responseData],
                "PF Publish Success " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            return true;
        } else {
            $error = json_decode($response, true);
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'publish', 'error' => $error, 'status' => $status],
                "PF Publish Failed " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            $parsedError = self::parseErrorMessage($response);
            throw new \Exception("Failed to publish listing {$listingId}. HTTP {$status}: {$parsedError}");
        }
    }

    public function unpublishListing($listingId)
    {
        $httpClient = self::getHttpClient();

        $url = "https://atlas.propertyfinder.com/v1/listings/{$listingId}/unpublish";

        $response = $httpClient->post($url, ''); // Empty body

        $status = $httpClient->getStatus();

        if ($status === 200 || $status === 204) {
            $responseData = $response ? json_decode($response, true) : [];
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'unpublish', 'response' => $responseData],
                "PF Unpublish Success " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            return true;
        } else {
            $error = json_decode($response, true);
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'unpublish', 'error' => $error, 'status' => $status],
                "PF Unpublish Failed " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            $parsedError = self::parseErrorMessage($response);
            throw new \Exception("Failed to unpublish listing {$listingId}. HTTP {$status}: {$parsedError}");
        }
    }

    public function deleteListing($listingId)
    {
        $httpClient = self::getHttpClient();

        $url = "https://atlas.propertyfinder.com/v1/listings/{$listingId}";

        $httpClient->query(
            \Bitrix\Main\Web\HttpClient::HTTP_DELETE,
            $url
        );

        $status = $httpClient->getStatus();
        $responseBody = $httpClient->getResult();

        if ($status === 204 || $status === 200 || $status === 404) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'delete', 'status' => $status],
                "PF Delete Success " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            return true;
        }

        // If direct delete returned an error (e.g. 400/409 because listing was published/live), attempt unpublish then retry delete
        try {
            $this->unpublishListing($listingId);
        } catch (\Throwable $e) {
            // Ignore unpublish error
        }

        $httpClient = self::getHttpClient();
        $httpClient->query(
            \Bitrix\Main\Web\HttpClient::HTTP_DELETE,
            $url
        );

        $status = $httpClient->getStatus();
        $responseBody = $httpClient->getResult();

        if ($status === 204 || $status === 200 || $status === 404) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'delete', 'status' => $status],
                "PF Delete Success (after unpublish) " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            return true;
        } else {
            $error = json_decode($responseBody, true);
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['listingId' => $listingId, 'action' => 'delete', 'error' => $error, 'status' => $status],
                "PF Delete Failed " . date('Y-m-d H:i:s'),
                "pfexport.log"
            );
            $parsedError = self::parseErrorMessage($responseBody);
            throw new \Exception("Failed to delete listing {$listingId}. HTTP {$status}: {$parsedError}");
        }
    }

    public function addPfId()
    {
        $logFile = 'pf_add_pfid.log';
        \Bitrix\Main\Diag\Debug::writeToFile('--- PFID Backfill Started ---', '', $logFile);

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(static::$entityTypeId);
        if (!$factory) {
            throw new \Exception('Factory not found for listing entity');
        }

        // ---------------------------------------------
        // 1. Get all Bitrix listings (ID + reference + PFID)
        // ---------------------------------------------
        $items = $factory->getItems([
            'select' => ['ID', 'UF_CRM_5_1752571265', 'UF_CRM_5_1754838287'], // reference + PFID
            'order'  => ['ID' => 'ASC']
        ]);

        $bitrixListings = [];
        foreach ($items as $item) {
            $data = $item->getData();
            if (empty($data['UF_CRM_5_1754838287'])) {
                $bitrixListings[$data['ID']] = [
                    'ID'        => $data['ID'],
                    'REFERENCE' => $data['UF_CRM_5_1752571265']
                ];
            }
        }

        \Bitrix\Main\Diag\Debug::writeToFile('Bitrix listings without PFID: ' . count($bitrixListings), '', $logFile);

        if (empty($bitrixListings)) {
            \Bitrix\Main\Diag\Debug::writeToFile('No listings to process.', '', $logFile);
            return;
        }

        // ---------------------------------------------
        // 2. Pull PF live and draft listings (paginated)
        // ---------------------------------------------
        $pfLive  = $this->fetchPfListings(false); // published
        $pfDraft = $this->fetchPfListings(true);  // draft

        // Index PF listings by reference
        $pfLiveByRef  = [];
        foreach ($pfLive as $l) {
            $pfLiveByRef[$l['reference']] = $l['id'];
        }

        $pfDraftByRef = [];
        foreach ($pfDraft as $l) {
            $pfDraftByRef[$l['reference']] = $l['id'];
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            'PF Live: ' . count($pfLive) . ' | PF Draft: ' . count($pfDraft),
            '',
            $logFile
        );


        // ---------------------------------------------
        // 3. Loop Bitrix and update matches
        // ---------------------------------------------
        foreach ($bitrixListings as $row) {

            $bitrixId  = $row['ID'];
            $reference = $row['REFERENCE'];

            if (!$reference) {
                \Bitrix\Main\Diag\Debug::writeToFile("Skipping $bitrixId – no reference", '', $logFile);
                continue;
            }

            $pfId = $pfLiveByRef[$reference] ?? $pfDraftByRef[$reference] ?? null;

            if (!$pfId) {
                \Bitrix\Main\Diag\Debug::writeToFile("Not found at PF: $reference", '', $logFile);
                continue;
            }

            // Update Bitrix
            $item = $factory->getItem($bitrixId);
            $item->set('UF_CRM_5_1754838287', $pfId);

            $op = $factory->getUpdateOperation($item)
                ->disableCheckFields()
                ->disableBizProc()
                ->disableCheckAccess();

            $res = $op->launch();

            if ($res->isSuccess()) {
                \Bitrix\Main\Diag\Debug::writeToFile("Updated Bitrix#$bitrixId with PFID $pfId", '', $logFile);
            } else {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    "Failed updating $bitrixId: " . implode('; ', $res->getErrorMessages()),
                    '',
                    $logFile
                );
            }
        }

        \Bitrix\Main\Diag\Debug::writeToFile('--- PFID Backfill Completed ---', '', $logFile);
    }

    private function fetchPfListings($draft = false)
    {
        $httpClient = self::getHttpClient();

        $url = "https://atlas.propertyfinder.com/v1/listings";

        $list = [];
        $page = 1;

        do {
            $query = http_build_query([
                'draft' => $draft ? 'true' : 'false',
                'page'  => $page,
                'perPage' => 100,
            ]);

            $response = $httpClient->get($url . '?' . $query);
            $status = $httpClient->getStatus();

            if ($status !== 200) {
                break;
            }

            $json = json_decode($response, true);

            if (!empty($json['results'])) {
                foreach ($json['results'] as $item) {
                    if (!empty($item['reference'])) {
                        $list[] = [
                            'id'        => $item['id'],
                            'reference' => $item['reference']
                        ];
                    }
                }
            }

            // check pagination
            $next = $json['pagination']['nextPage'] ?? null;
            $page = $next ? $next : null;
        } while ($page);

        return $list;
    }

    private function deepMerge(array $pfPayload, array $crmPayload)
    {
        foreach ($crmPayload as $key => $value) {
            if (is_array($value) && isset($pfPayload[$key]) && is_array($pfPayload[$key])) {
                $pfPayload[$key] = $this->deepMerge($pfPayload[$key], $value);
            } else {
                // CRM overrides PF
                $pfPayload[$key] = $value;
            }
        }
        return $pfPayload;
    }

    private function cleanPfPayload(array &$payload)
    {
        if (isset($payload['media']['videos']) && is_array($payload['media']['videos']) && empty($payload['media']['videos'])) {
            unset($payload['media']['videos']);
        }
    }

    public function getListingId($reference)
    {
        // First search live listings
        $httpClient = self::getHttpClient();
        $url = "https://atlas.propertyfinder.com/v1/listings";
        $query = http_build_query([
            'filter' => [
                'reference' => $reference
            ]
        ]);
        $response = $httpClient->get($url . '?' . $query);
        $status = $httpClient->getStatus();
        if ($status === 200) {
            $json = json_decode($response, true);
            if (!empty($json['results'][0]['id'])) {
                echo "Found live: {$json['results'][0]['id']}\n";
                return $json['results'][0]['id'];
            }
        }

        // Then search draft listings
        $url = "https://atlas.propertyfinder.com/v1/listings";
        $query = http_build_query([
            'draft' => true,
            'filter' => [
                'reference' => $reference
            ]
        ]);
        $response = $httpClient->get($url . '?' . $query);
        $status = $httpClient->getStatus();
        if ($status === 200) {
            $json = json_decode($response, true);
            if (!empty($json['results'][0]['id'])) {
                echo "Found draft: {$json['results'][0]['id']}\n";
                return $json['results'][0]['id'];
            }
        }

        echo "Not found for reference: $reference\n";
        return null;
    }

    public function refreshListing($pfId, $reference = null)
    {
        if (!$pfId && !$reference) {
            throw new \Exception('PF Listing ID or Reference is required for refresh');
        }

        $httpClient = self::getHttpClient();
        $logFile = 'pfexport.log';

        // -----------------------------------------------------------------
        // Step 1: Find the listing using the PF listing ID
        // -----------------------------------------------------------------
        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(static::$entityTypeId);
        if (!$factory) {
            throw new \Exception('Factory not found for listing entity');
        }

        $items = [];
        $filterOr = [];
        if ($pfId) {
            $filterOr[] = ['=UF_CRM_5_1754838287' => $pfId];
            $filterOr[] = ['=UF_CRM_5_1752571265' => $pfId];
        }
        if ($reference) {
            $filterOr[] = ['=UF_CRM_5_1754838287' => $reference];
            $filterOr[] = ['=UF_CRM_5_1752571265' => $reference];
        }

        if (!empty($filterOr)) {
            $items = $factory->getItems([
                'select' => ['ID', 'UF_CRM_5_1752571265', 'UF_CRM_5_1754838287'],
                'filter' => count($filterOr) === 1 ? current($filterOr) : array_merge(['LOGIC' => 'OR'], $filterOr),
                'limit'  => 1,
            ]);
        }

        // If not found in Bitrix by direct field match, fetch the listing from PF to get its reference
        if (empty($items) && $pfId) {
            // Check draft first
            $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                'draft'  => 'true',
                'filter' => ['ids' => $pfId]
            ]));
            $pfJson = ($httpClient->getStatus() === 200) ? json_decode($httpClient->getResult(), true) : null;
            $pfRef = $pfJson['results'][0]['reference'] ?? null;

            if (!$pfRef) {
                // Check live
                $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                    'filter' => ['ids' => $pfId]
                ]));
                $pfJson = ($httpClient->getStatus() === 200) ? json_decode($httpClient->getResult(), true) : null;
                $pfRef = $pfJson['results'][0]['reference'] ?? null;
            }

            if ($pfRef) {
                $items = $factory->getItems([
                    'select' => ['ID', 'UF_CRM_5_1752571265', 'UF_CRM_5_1754838287'],
                    'filter' => ['=UF_CRM_5_1752571265' => $pfRef],
                    'limit'  => 1,
                ]);
            }
        }

        if (empty($items)) {
            throw new \Exception("No Bitrix listing found for PF ID: '{$pfId}'" . ($reference ? " or reference: '{$reference}'" : ''));
        }

        $bitrixItem = current($items);
        $bitrixId   = $bitrixItem->getId();

        \Bitrix\Main\Diag\Debug::writeToFile(
            ['bitrixId' => $bitrixId, 'pfId' => $pfId, 'reference' => $reference],
            "PF Refresh Start " . date('Y-m-d H:i:s'),
            $logFile
        );

        // -----------------------------------------------------------------
        // Step 2: Delete the listing, no matter it is live or not
        // -----------------------------------------------------------------
        $this->deleteListing($pfId);

        // Make sure the old listing is deleted (not returned in live or draft)
        $isDeleted = false;
        $maxRetries = 5;
        $retryDelay = 2; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Check live listings
            $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                'filter' => ['ids' => $pfId]
            ]));
            $foundInLive = false;
            if ($httpClient->getStatus() === 200) {
                $liveJson = json_decode($httpClient->getResult(), true);
                if (!empty($liveJson['results'])) {
                    foreach ($liveJson['results'] as $resItem) {
                        if ((string)($resItem['id'] ?? '') === (string)$pfId) {
                            $foundInLive = true;
                            break;
                        }
                    }
                }
            }

            // Check draft listings
            $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                'draft'  => 'true',
                'filter' => ['ids' => $pfId]
            ]));
            $foundInDraft = false;
            if ($httpClient->getStatus() === 200) {
                $draftJson = json_decode($httpClient->getResult(), true);
                if (!empty($draftJson['results'])) {
                    foreach ($draftJson['results'] as $resItem) {
                        if ((string)($resItem['id'] ?? '') === (string)$pfId) {
                            $foundInDraft = true;
                            break;
                        }
                    }
                }
            }

            if (!$foundInLive && !$foundInDraft) {
                $isDeleted = true;
                break;
            }

            sleep($retryDelay);
        }

        if (!$isDeleted) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['pfId' => $pfId, 'status' => 'deletion_not_confirmed'],
                "PF Refresh Delete Verification Failed " . date('Y-m-d H:i:s'),
                $logFile
            );
            throw new \Exception("Failed to confirm deletion of old PF listing ID {$pfId}. Listing is still active or draft on Property Finder.");
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            ['pfId' => $pfId, 'action' => 'confirm_delete', 'status' => 'confirmed'],
            "PF Refresh Delete Confirmed " . date('Y-m-d H:i:s'),
            $logFile
        );

        // -----------------------------------------------------------------
        // Step 3: Create new listing and add the new PF ID to the SPA item field
        // -----------------------------------------------------------------
        $newPfId = $this->sendListingDraft($bitrixId);
        if (!$newPfId) {
            throw new \Exception("Failed to create new listing draft for Bitrix listing #{$bitrixId}");
        }

        // Add the new PF ID to the SPA item field (UF_CRM_5_1752571265 and UF_CRM_5_1754838287)
        $itemToUpdate = $factory->getItem($bitrixId);
        if ($itemToUpdate) {
            $itemToUpdate->set('UF_CRM_5_1752571265', $newPfId);
            $itemToUpdate->set('UF_CRM_5_1754838287', $newPfId);
            $op = $factory->getUpdateOperation($itemToUpdate)
                ->disableCheckFields()
                ->disableBizProc()
                ->disableCheckAccess();
            $res = $op->launch();
            if (!$res->isSuccess()) {
                \Bitrix\Main\Diag\Debug::writeToFile(
                    "Failed updating Bitrix #{$bitrixId} with new PFID {$newPfId}: " . implode('; ', $res->getErrorMessages()),
                    "PF Refresh Warning " . date('Y-m-d H:i:s'),
                    $logFile
                );
            }
        }

        // -----------------------------------------------------------------
        // Step 4: Make it live and make sure the new listing is live / created
        // -----------------------------------------------------------------
        $this->publishListing($newPfId);

        $isLiveConfirmed = false;
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $httpClient->get("https://atlas.propertyfinder.com/v1/listings?" . http_build_query([
                'filter' => ['ids' => $newPfId]
            ]));

            if ($httpClient->getStatus() === 200) {
                $decoded = json_decode($httpClient->getResult(), true);
                if (!empty($decoded['results'])) {
                    foreach ($decoded['results'] as $resItem) {
                        if ((string)($resItem['id'] ?? '') === (string)$newPfId) {
                            $isLiveConfirmed = true;
                            break 2;
                        }
                    }
                }
            }

            sleep($retryDelay);
        }

        if (!$isLiveConfirmed) {
            \Bitrix\Main\Diag\Debug::writeToFile(
                ['newPfId' => $newPfId, 'action' => 'verify_live', 'status' => 'failed'],
                "PF Refresh Verify Live FAILED " . date('Y-m-d H:i:s'),
                $logFile
            );
            throw new \Exception("Listing {$newPfId} was published, but could not be confirmed as live on Property Finder.");
        }

        \Bitrix\Main\Diag\Debug::writeToFile(
            [
                'oldPfId'    => $pfId,
                'newPfId'    => $newPfId,
                'bitrixId'   => $bitrixId,
                'status'     => 'success_recreated_and_live'
            ],
            "PF Refresh SUCCESS " . date('Y-m-d H:i:s'),
            $logFile
        );

        return true;
    }

    /*public function setLocations()
    {
        //$token = self::makeAuth();
        $factory = Service\Container::getInstance()->getFactory(1054);

        $httpClient = new HttpClient([
            "socketTimeout" => 10,
            "streamTimeout" => 15
        ]);
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $httpClient->setHeader('Accept', 'application/json', true);
        $httpClient->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/114.0 Safari/537.36', true); // mimic real browser
        $url = 'https://atlas.propertyfinder.com/v1/locations'; // Adjust endpoint as needed

        $startpage = 2;
        while($startpage<=86) {
            $queryParams = [
                'search' => 'Dubai',
                'page' => $startpage, // Example additional parameter,
                'perPage' => 100
            ];
            $startpage++;
            $fullUrl = $url . '?' . http_build_query($queryParams);
            $httpClient->setHeader('Authorization', 'Bearer '.self::makeAuth(), true);

            $response = $httpClient->get(
                $fullUrl
            );
            $status = $httpClient->getStatus();

            if ($status == 200) {
                $responseData = json_decode($response, true);
                //print_r($responseData);
                self::processLocations($responseData['data'], $factory);
                //return $responseData['accessToken'];
                //echo '✅ Token: ' . $responseData['accessToken'];
            } else {
                echo "❌ HTTP Error: $status\n";
                echo "Response Body: " . $response . "\n";
            }
        }
    }*/

    private static function parseErrorMessage($response)
    {
        if (empty($response)) {
            return 'empty response';
        }

        // Try to decode as JSON
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            return $response;
        }

        $messages = [];

        // Check if there are errors array
        if (!empty($data['errors']) && is_array($data['errors'])) {
            foreach ($data['errors'] as $err) {
                if (is_array($err)) {
                    $detail = $err['detail'] ?? $err['en'] ?? $err['message'] ?? $err['msg'] ?? '';
                    if (!empty($detail)) {
                        // The detail itself might contain a stringified JSON
                        if (preg_match('/request failed:\s*(\{.*\})/i', $detail, $matches)) {
                            $innerJson = $matches[1];
                            $innerMsg = self::parseErrorMessage($innerJson);
                            if ($innerMsg) {
                                $messages[] = $innerMsg;
                                continue;
                            }
                        }
                        
                        // Or if there is a pointer like /bedrooms, let's include it
                        $pointerInfo = '';
                        if (!empty($err['pointer'])) {
                            $pointerInfo = ' (' . ltrim($err['pointer'], '/') . ')';
                        }
                        
                        $messages[] = $detail . $pointerInfo;
                    }
                } elseif (is_string($err)) {
                    $messages[] = $err;
                }
            }
        }

        // Check for top-level detail
        if (!empty($data['detail']) && is_string($data['detail'])) {
            $detail = $data['detail'];
            if (preg_match('/request failed:\s*(\{.*\})/i', $detail, $matches)) {
                $innerJson = $matches[1];
                $innerMsg = self::parseErrorMessage($innerJson);
                if ($innerMsg) {
                    $messages[] = $innerMsg;
                }
            } else {
                $messages[] = $detail;
            }
        }

        // If we found specific messages, join them
        if (!empty($messages)) {
            // Remove duplicates and empty messages
            $messages = array_unique(array_filter(array_map('trim', $messages)));
            return implode('; ', $messages);
        }

        // If no details, but there's a title/type
        if (!empty($data['title'])) {
            return $data['title'] . (!empty($data['type']) ? ' (' . $data['type'] . ')' : '');
        }

        return $response;
    }
}
