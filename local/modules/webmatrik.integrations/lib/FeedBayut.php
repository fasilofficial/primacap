<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

class FeedBayut extends Feed
{
    protected static $root;
    protected static $mask;
    protected static $furnmap;
    protected static $extraAmenities;
    protected static $userEmailCache = [];
    protected static $locationIdCache = [];
    protected static $fileArrayCache = [];

    public function __construct()
    {
        $server = Application::getInstance()->getContext()::getCurrent()->getServer();
        static::$root = $server->getDocumentRoot() . '/pub/feed';
        static::$mask = [
            'TITLE' => 'Property_Title',
            'UF_CRM_5_1752571265' => 'Property_Ref_No',
            'UF_CRM_5_1752508269' => 'Permit_Number',
            'UF_CRM_5_1752755567' => 'Property_purpose',
            'UF_CRM_5_1754561389' => 'Property_Type',
            'UF_CRM_5_1752571276' => 'Property_Size',
            'UF_CRM_5_1752755685' => 'Property_Size_Unit',
            'UF_CRM_5_1752569108' => 'plotArea',
            'UF_CRM_5_1752508051' => 'Bedrooms',
            'UF_CRM_5_1752507949' => 'Bathrooms',
            'UF_CRM_5_1754495503' => 'Features',
            'UF_CRM_5_1752571194' => 'Off_plan',
            'UF_CRM_5_1752569141' => 'Portals',
            'UF_CRM_5_1752508408' => 'Property_Description',
            'UF_CRM_5_1752571489' => 'Property_Title_AR',
            'UF_CRM_5_1752508464' => 'Property_Description_AR',
            'UF_CRM_5_1752569908' => 'Rent_Frequency',
            'UF_CRM_5_1754555234' => 'Price',
            'UF_CRM_5_1752508563' => 'Furnished',
            'UF_CRM_5_1752755788' => 'offplanDetails_saleType',
            'UF_CRM_5_1752755825' => 'offplanDetails_dldWaiver',
            'UF_CRM_5_1754555417' => 'offplanDetails_originalPrice',
            'UF_CRM_5_1754555396' => 'offplanDetails_amountPaid',
            'UF_CRM_5_1752569021' => 'Parking Spaces',
            'UF_CRM_5_1755236272' => 'View',
            'UF_CRM_5_1755238439' => 'Pet policy',
            'UF_CRM_5_1752508720' => 'Floor',
            'UF_CRM_5_1755238866' => 'Other Main Features',
            'UF_CRM_5_1755238928' => 'Other Rooms',
            'UF_CRM_5_1755238978' => 'Other Facilities',
            'UF_CRM_5_1755239127' => 'Land Area',
            'UF_CRM_5_1755239186' => 'Nearby Schools',
            'UF_CRM_5_1755239275' => 'Nearby Hospitals',
            'UF_CRM_5_1755239336' => 'Nearby Shopping Malls',
            'UF_CRM_5_1755239384' => 'Distance From Airport (kms)',
            'UF_CRM_5_1755239445' => 'Nearby Public Transport',
            'UF_CRM_5_1755239531' => 'Other Nearby Places',
            'UF_CRM_5_1755239591' => 'Total Floors',
            'UF_CRM_5_1755239671' => 'Elevators in Building',
            'UF_CRM_5_1755239741' => 'Completion Year',
            'UF_CRM_5_1755239886' => 'Flooring'
        ];

        static::$extraAmenities = [
            'Parking Spaces',
            'View',
            'Pet policy',
            'Floor',
            'Other Main Features',
            'Other Rooms',
            'Other Facilities',
            'Land Area',
            'Nearby Schools',
            'Nearby Hospitals',
            'Nearby Hospitals',
            'Nearby Shopping Malls',
            'Distance From Airport (kms)',
            'Nearby Public Transport',
            'Other Nearby Places',
            'Total Floors',
            'Elevators in Building',
            'Completion Year',
            'Flooring'
        ];

        static::$furnmap = [
            'furnished' => 'Yes',
            'semi-furnished' => 'Partly',
            'unfurnished' => 'No'
        ];
        parent::__construct();
    }

    public function readCSVWithDetection()
    {
        $filename = __DIR__ . '/dubizzleListingdetails_2025-10-2_offplan.csv';
        if (!file_exists($filename)) {
            throw new Exception("Файл не найден");
        }

        // Определяем разделитель
        $delimiter = static::detectDelimiter($filename);

        $data = [];
        if (($file = fopen($filename, 'r')) !== false) {
            // Читаем BOM (Byte Order Mark) для UTF-8
            $bom = fread($file, 3);
            if ($bom != "\xEF\xBB\xBF") {
                // Если нет BOM, возвращаемся к началу файла
                fseek($file, 0);
            }

            $headers = fgetcsv($file, 0, $delimiter);

            while (($row = fgetcsv($file, 0, $delimiter)) !== false) {
                // Обрабатываем каждое поле
                $processedRow = [];
                foreach ($row as $index => $value) {
                    $processedRow[$index] = trim($value, " \t\n\r\0\x0B\"'");
                }

                if ($headers && count($headers) == count($processedRow)) {
                    $data[] = array_combine($headers, $processedRow);
                } else {
                    $data[] = $processedRow;
                }
            }

            fclose($file);
        }
        print_r($data);
        return $data;
    }

    protected function detectDelimiter($filename)
    {
        $file = fopen($filename, 'r');
        $firstLine = fgets($file);
        fclose($file);

        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = count(str_getcsv($firstLine, $delimiter));
        }

        return array_search(max($counts), $counts);
    }

    private static function determinePortal($filename, $listingAgency = null, $projectStatus = null)
    {
        $basename   = basename($filename);
        $portalName = 'bayut';

        if (stripos($basename, 'bayut') !== false) {
            $portalName = 'bayut';
        } elseif (stripos($basename, 'dubizzle') !== false) {
            $portalName = 'dubizzle';
        }

        if (!$portalName) {
            return null;                     // unknown portal
        }

        $isSecondary = false;
        $isOffplan   = false;

        if ($listingAgency !== null) {
            $agencyLower = mb_strtolower(trim($listingAgency));
            $isSecondary = (stripos($agencyLower, 'secondary') !== false);
            $isOffplan   = !$isSecondary;               // if not secondary → off-plan
        }

        if ($listingAgency === null) {
            if (stripos($basename, 'offplan') !== false || stripos($basename, 'off_plan') !== false || stripos($projectStatus, 'off_plan') !== false) {
                $isOffplan = true;
            } elseif (stripos($basename, 'sec') !== false || stripos($basename, 'secondary') !== false || stripos($projectStatus, 'ready') !== false) {
                $isSecondary = true;
            }
        }

        if ($isSecondary) {
            $portal = $portalName . '_sec';
        } else {
            $portal = $portalName . '_offplan';
        }

        return $portal;
    }

    public function importFeed($filename = null)
    {
        if (!$filename || !file_exists(__DIR__ . '/' . $filename)) {
            throw new \Exception('CSV file not found: ' . $filename);
        }

        // Step 0: Ensure CRM module is available
        \Bitrix\Main\Loader::includeModule('crm');
        // Allow long-running import without webserver aborts
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        // Step 1: Open CSV with delimiter detection and BOM handling
        $filename = __DIR__ . '/' . $filename;
        if (!file_exists($filename)) {
            throw new \Exception('CSV file not found: ' . $filename);
        }
        $delimiter = static::detectDelimiter($filename);
        $handle = fopen($filename, 'r');
        if ($handle === false) {
            throw new \Exception('Unable to open CSV file: ' . $filename);
        }
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new \Exception('Empty CSV');
        }
        $hasBom = (strncmp($firstLine, "\xEF\xBB\xBF", 3) === 0);
        rewind($handle);
        if ($hasBom) {
            fread($handle, 3);
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Empty CSV or no headers');
        }
        $headers = array_map(function ($h) {
            return trim($h);
        }, $headers);
        \Bitrix\Main\Diag\Debug::writeToFile($headers, 'bayut_csv_headers ' . date('Y-m-d H:i:s'), 'bayut_import.log');

        // Helper to normalize header names (case/spacing-insensitive)
        $normalize = function ($s) {
            $s = (string)$s;
            $s = trim($s);
            $s = mb_strtolower($s);
            $s = preg_replace('/[^a-z0-9]+/u', '', $s);
            return $s;
        };
        $normalizedHeaderMap = [];
        foreach ($headers as $h) {
            $normalizedHeaderMap[$normalize($h)] = $h;
        }

        // Step 2: Build mapping from CSV headers to CRM UF fields (Bayut CSV schema)
        $csvToUf = [
            'property_ref_no'      => 'UF_CRM_5_1752571265',
            'permit_number'        => 'UF_CRM_5_1752508269',
            'property_purpose'     => 'UF_CRM_5_1752755567',
            'property_type'        => 'UF_CRM_5_1754561389',
            'furnished'            => 'UF_CRM_5_1752508563',
            'property_description' => 'UF_CRM_5_1752508408',
            'property_size'        => 'UF_CRM_5_1752571276',
            'property_size_unit'   => 'UF_CRM_5_1752755685',
            'bedrooms'             => 'UF_CRM_5_1752508051',
            'bathroom'             => 'UF_CRM_5_1752507949',
            'price'                => 'UF_CRM_5_1754555234',
            'features'             => 'UF_CRM_5_1754495503',
            'completion_status'    => 'UF_CRM_5_1752571194',
            'city'                 => 'UF_CRM_5_1752509816', // Used for UAE Emirate field
        ];

        $propertyFinderTypeField = 'UF_CRM_5_1752571572';
        $categoryField = 'UF_CRM_5_1752508146';

        $csvToUf['listing_agency'] = 'LISTING_AGENCY_TEMP';

        // Step 3: Prepare factories
        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(static::$entityTypeId);
        $locFactory = $container->getFactory(static::$locentityTypeId);
        $bayutLocFactory = $container->getFactory(static::$bayutLocentityTypeId);
        if (!$factory || !$locFactory || !$bayutLocFactory) {
            fclose($handle);
            throw new \Exception('Required factories not found');
        }

        // Step 4: Prepare enum/boolean metadata for UF mapping
        $ufMeta = self::getEnumVal();
        $enumValues = $ufMeta['enum']; // [FIELD_NAME => [ID=>VALUE]]
        $enumLookup = [];
        foreach ($enumValues as $fieldName => $idToValue) {
            // Build value(lowercased) => id map for fast lookup
            $enumLookup[$fieldName] = array_change_key_case(array_flip($idToValue), CASE_LOWER);
        }
        $boolFields = array_flip($ufMeta['bool']); // set of boolean UF field names

        // Optional enum normalization map: CSV -> Bitrix display value (lowercased)
        $enumNormalize = [
            // type Bayut
            'UF_CRM_5_1754561389' => [
                'apartments' => 'apartment',
                'townhouses' => 'townhouse',
                'villas' => 'villa',
                'penthouses' => 'pent house',
                'duplexes' => 'duplex',
                'offices' => 'office',
                'residential plots' => 'residential land',
                'residential floors' => 'residential floor',
                'residential buildings' => 'residential building',
                'commercial plots' => 'commercial plot',
                'commercial floors' => 'commercial floor',
                'commercial buildings' => 'commercial building',
                'hotel apartments' => 'hotel apartment',
                'warehouses' => 'warehouse',
                'shops' => 'shop',
            ],
            // type PropertyFinder
            'UF_CRM_5_1752571572' => [
                // Add your PropertyFinder type enum values here (lowercase)
                'apartment' => 'apartment',
                'townhouse' => 'townhouse',
                'villa' => 'villa',
                'pent house' => 'penthouse',
                'duplex' => 'duplex',
                'office' => 'office-space',
                'residential land' => 'land',
                'residential floor' => 'full-floor',
                // 'residential building' => 'residential building',
                'commercial land' => 'land',
                'commercial floor' => 'full-floor',
                // 'commercial building' => 'commercial building',
                'hotel apartment' => 'hotel-apartment',
                'warehouse' => 'warehouse',
                'shop' => 'shop',
            ],
            // Property_purpose
            'UF_CRM_5_1752755567' => [
                'for rent' => 'rent',
                'rent' => 'rent',
                'for sale' => 'buy',
                'sale' => 'buy',
                'buy' => 'buy',
            ],
            // furnished/furnishingType (if CSV variants differ)
            'UF_CRM_5_1752508563' => [
                'yes' => 'furnished',
                'no' => 'unfurnished',
                'partly' => 'semi-furnished',
            ],
            'UF_CRM_5_1752571194' => [
                'off_plan' => 'off_plan',
                'off plan' => 'off_plan',
                'offplan' => 'off_plan',
                'completed' => 'completed',
                'ready' => 'completed',
                'completed_primary' => 'completed',
                'off_plan_primary' => 'off_plan',
            ],
            'UF_CRM_5_1752509816' => [
                'dubai' => 'dubai',
                'abu dhabi' => 'abu_dhabi',
                'sharjah' => 'northern_emirates',
                'ajman' => 'northern_emirates',
                'umm al quwain' => 'northern_emirates',
                'ras al khaimah' => 'northern_emirates',
                'fujairah' => 'northern_emirates',
            ],
            'UF_CRM_5_1752508146' => [
                'residential' => 'residential',
                'commercial' => 'commercial',
            ]
        ];

        // Precompute portal enum normalization once (used for `UF_CRM_5_1752569141`)
        $portalField = 'UF_CRM_5_1752569141';
        if (!isset($enumNormalize[$portalField])) {
            $enumNormalize[$portalField] = [
                'bayut_offplan' => 'bayut_offplan',
                'bayut offplan' => 'bayut_offplan',
                'bayut_sec' => 'bayut_sec',
                'bayut sec' => 'bayut_sec',
                'bayut secondary' => 'bayut_sec',
                'dubizzle_offplan' => 'dubizzle_offplan',
                'dubizzle offplan' => 'dubizzle_offplan',
                'dubizzle_sec' => 'dubizzle_sec',
                'dubizzle sec' => 'dubizzle_sec',
                'dubizzle secondary' => 'dubizzle_sec',
            ];
        }

        // Build existing listings cache (reference -> {id, portals[]}) and persist to file
        $cachePath = __DIR__ . '/existing_refs_cache.json';
        $existingCache = [];
        try {
            $existingItems = $factory->getItems([
                'select' => ['ID', 'UF_CRM_5_1752571265', $portalField],
                'filter' => [],
                'order' => ['ID' => 'ASC'],
            ]);
            foreach ($existingItems as $ex) {
                $refVal = $ex->get('UF_CRM_5_1752571265');
                if (!$refVal) {
                    continue;
                }
                $portalsVal = $ex->get($portalField);
                $portalsArr = is_array($portalsVal) ? array_values(array_filter($portalsVal, 'is_numeric')) : ($portalsVal ? [(int)$portalsVal] : []);
                $existingCache[$refVal] = [
                    'id' => (int)$ex->getId(),
                    'portals' => $portalsArr,
                ];
            }
            @file_put_contents($cachePath, json_encode($existingCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            // continue without cache if listing retrieval fails
        }

        // Configurable throttling (milliseconds)
        $rowDelayMs = (int)Option::get('webmatrik.integrations', 'import_delay_ms', '100');
        $imageDelayMs = (int)Option::get('webmatrik.integrations', 'import_image_delay_ms', '0');

        $bayutToPropertyFinderType = [
            'apartment' => 'apartment',
            'townhouse' => 'townhouse',
            'villa' => 'villa',
            'pent house' => 'penthouse',
            'duplex' => 'duplex',
            'office' => 'office',
            'residential land' => 'land',
            'residential floor' => 'full-floor',
            // 'residential building' => 'residential building',
            'commercial land' => 'land',
            'commercial floor' => 'full-floor',
            // 'commercial building' => 'commercial building',
            'warehouse' => 'warehouse',
            'shop' => 'shop',
            'hotel apartment' => 'hotel-apartment',
            // Add more mappings as needed based on your PropertyFinder enum values
        ];

        $propertyFinderTypeToCategory = [
            // Residential types
            'apartment' => 'residential',
            'bulk-rent-unit' => 'residential',
            'bulk-sale-unit' => 'residential',
            'bungalow' => 'residential',
            'compound' => 'residential',
            'duplex' => 'residential',
            'full-floor' => 'residential',
            'half-floor' => 'residential',
            'hotel-apartment' => 'residential',
            'penthouse' => 'residential',
            'townhouse' => 'residential',
            'villa' => 'residential',
            'whole-building' => 'residential',
            'land' => 'residential',

            // Commercial types
            'farm' => 'commercial',
            'business-center' => 'commercial',
            'co-working-space' => 'commercial',
            'factory' => 'commercial',
            'labor-camp' => 'commercial',
            'office-space' => 'commercial',
            'retail' => 'commercial',
            'shop' => 'commercial',
            'show-room' => 'commercial',
            'staff-accommodation' => 'commercial',
            'warehouse' => 'commercial',
        ];

        // Helper: parse list-like strings (split by ; | ,)
        $parseList = function ($str) {
            $parts = preg_split('/[;|,]/', (string)$str);
            $clean = [];
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $clean[] = $p;
                }
            }
            return $clean;
        };

        // Helper: convert typical yes/no strings into boolean
        $toBool = function ($val) {
            $v = mb_strtolower(trim((string)$val));
            if ($v === '1' || $v === 'y' || $v === 'yes' || $v === 'true') {
                return 1;
            }
            if ($v === '0' || $v === 'n' || $v === 'no' || $v === 'false') {
                return 0;
            }
            return $val; // leave as-is if unknown
        };

        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) == 1 && trim((string)$row[0]) === '') {
                continue;
            }
            $assoc = array_combine($headers, $row);
            if ($assoc === false) {
                continue;
            }
            $assoc = array_map(function ($v) {
                return is_string($v) ? trim($v) : $v;
            }, $assoc);

            // Step 5: Build fields for the SPA item
            $assignedUserId = (int)\Bitrix\Main\Config\Option::get('webmatrik.integrations', '_Lead_AssignedTo') ?: 1;

            $agentEmail = $assoc['listing_agent_email'] ?? null;
            if ($agentEmail) {
                $agentEmail = trim(mb_strtolower($agentEmail));
                if (array_key_exists($agentEmail, self::$userEmailCache)) {
                    $cachedUserId = self::$userEmailCache[$agentEmail];
                    if ($cachedUserId) {
                        $assignedUserId = $cachedUserId;
                    } else {
                        // Keep behavior similar; only log once per unknown email to reduce I/O
                        \Bitrix\Main\Diag\Debug::writeToFile([
                            'email' => $agentEmail,
                            'row' => $rowNum,
                        ], 'Unmatched listing_agent_email (cached)', 'bayut_import.log');
                    }
                } else {
                    $user = \Bitrix\Main\UserTable::getList([
                        'select' => ['ID'],
                        'filter' => ['=EMAIL' => $agentEmail, '=ACTIVE' => 'Y'],
                        'limit' => 1,
                    ])->fetch();
                    if ($user && isset($user['ID'])) {
                        $assignedUserId = (int)$user['ID'];
                        self::$userEmailCache[$agentEmail] = $assignedUserId;
                    } else {
                        self::$userEmailCache[$agentEmail] = 0; // sentinel for no-match
                        \Bitrix\Main\Diag\Debug::writeToFile([
                            'email' => $agentEmail,
                            'row' => $rowNum,
                        ], 'Unmatched listing_agent_email', 'bayut_import.log');
                    }
                }
            }

            $fields = [
                'ASSIGNED_BY_ID' => $assignedUserId,
            ];

            // Always add existing listings to 'Published' stage
            $fields['STAGE_ID'] = 'DT1036_8:SUCCESS';

            if (!empty($assoc['property_title'])) {
                $fields['TITLE'] = $assoc['property_title'];
            }

            $listingAgency = $assoc['listing_agency'] ?? null;
            $projectStatus = $assoc['completion_status'] ?? null;

            $portal = self::determinePortal($filename, $listingAgency, $projectStatus);

            if ($portal) {
                // Normalize portal value for lookup
                $portalKey = mb_strtolower(trim($portal));
                if (isset($enumNormalize[$portalField][$portalKey])) {
                    $portalKey = $enumNormalize[$portalField][$portalKey];
                }
                // Look up enum ID
                if (isset($enumLookup[$portalField][$portalKey])) {
                    $fields[$portalField] = (int)$enumLookup[$portalField][$portalKey];
                }
            }

            // Step 6: location binding
            $locationId = $assoc['location_id'] ?? '';
            $matchedId = null;

            if (!empty($locationId)) {
                if (array_key_exists($locationId, self::$locationIdCache)) {
                    $matchedId = self::$locationIdCache[$locationId];
                } else {
                    $items = $bayutLocFactory->getItems([
                        'select' => ['ID'],
                        'filter' => ['=UF_CRM_13_1762325631' => $locationId],
                        'limit' => 1
                    ]);
                    $item = $items ? reset($items) : null;
                    $matchedId = $item?->getId();
                    self::$locationIdCache[$locationId] = $matchedId ?: 0;
                }
            }

            if ($matchedId && $matchedId > 0) {
                $fields['PARENT_ID_' . static::$bayutLocentityTypeId] = $matchedId;
            }

            // Step 7: Map CSV columns to UF fields (string/number/enum/boolean)
            $setCount = 0;
            $skipped = [];
            $bayutTypeValue = null;

            foreach ($csvToUf as $csvKey => $ufField) {
                // find CSV column by exact name or normalized fallback
                $src = null;
                if (array_key_exists($csvKey, $assoc)) {
                    $src = $csvKey;
                } else {
                    $nk = $normalize($csvKey);
                    if (isset($normalizedHeaderMap[$nk])) {
                        $src = $normalizedHeaderMap[$nk];
                    }
                }
                if ($src === null) {
                    $skipped[] = $csvKey;
                    continue;
                }
                $value = $assoc[$src];
                if ($value === '' || $value === null || $value === 'unknown') {
                    continue;
                }

                // boolean UF handling
                if (isset($boolFields[$ufField])) {
                    $fields[$ufField] = $toBool($value);
                    $setCount++;
                    continue;
                }
                // enum UF handling
                if (isset($enumLookup[$ufField])) {
                    $valKey = mb_strtolower(trim((string)$value));
                    // apply normalization if configured
                    if (isset($enumNormalize[$ufField][$valKey])) {
                        $valKey = $enumNormalize[$ufField][$valKey];
                    }
                    if ($valKey === '') {
                        continue;
                    }

                    // Store Bayut type value for PropertyFinder conversion
                    if ($ufField === 'UF_CRM_5_1754561389') {
                        $bayutTypeValue = $valKey;
                    }

                    // Multi-select: split and map each value
                    if (strpos($value, ';') !== false || strpos($value, '|') !== false || strpos($value, ',') !== false) {
                        $ids = [];
                        foreach ($parseList($value) as $part) {
                            $k = mb_strtolower(trim($part));
                            if (isset($enumNormalize[$ufField][$k])) {
                                $k = $enumNormalize[$ufField][$k];
                            }
                            if ($k === '') {
                                continue;
                            }
                            if (isset($enumLookup[$ufField][$k])) {
                                $ids[] = (int)$enumLookup[$ufField][$k];
                            }
                        }
                        if (!empty($ids)) {
                            $fields[$ufField] = $ids;
                            $setCount++;
                        }
                    } else {
                        if (isset($enumLookup[$ufField][$valKey])) {
                            $fields[$ufField] = (int)$enumLookup[$ufField][$valKey];
                            $setCount++;
                        }
                    }
                    continue;
                }
                // numeric vs. string UF
                if (is_numeric($value)) {
                    $fields[$ufField] = (strpos((string)$value, '.') !== false) ? (float)$value : (int)$value;
                    $setCount++;
                } else {
                    $fields[$ufField] = $value;
                    $setCount++;
                }
            }

            // Always set Compliance Type to RERA
            $complianceField = 'UF_CRM_5_1752570656';
            if (isset($enumLookup[$complianceField]['rera'])) {
                $fields[$complianceField] = (int)$enumLookup[$complianceField]['rera'];
            }

            // Always set Photoshoot Required to No
            $photoshootRequiredField = 'UF_CRM_5_1760883578';
            if (isset($enumLookup[$photoshootRequiredField]['No'])) {
                $fields[$photoshootRequiredField] = (int)$enumLookup[$photoshootRequiredField]['No'];
            }

            // If bedrooms = 0, set bedrooms enum to "studio"
            if (isset($assoc['bedrooms']) && (int)$assoc['bedrooms'] == 0) {
                $bedroomField = 'UF_CRM_5_1752508051';
                if (isset($enumLookup[$bedroomField]['studio'])) {
                    $fields[$bedroomField] = (int)$enumLookup[$bedroomField]['studio'];
                }
            }

            // If bathroom = 0, set bathroom enum to "none"
            if (isset($assoc['bathroom']) && (int)$assoc['bathroom'] == 0) {
                $bathroomField = 'UF_CRM_5_1752507949';
                if (isset($enumLookup[$bathroomField]['none'])) {
                    $fields[$bathroomField] = (int)$enumLookup[$bathroomField]['none'];
                }
            }

            // Step 7b: Convert Bayut type to PropertyFinder type and set Category
            if ($bayutTypeValue && isset($bayutToPropertyFinderType[$bayutTypeValue])) {
                $pfTypeValue = $bayutToPropertyFinderType[$bayutTypeValue];
                $pfTypeKey = mb_strtolower(trim($pfTypeValue));

                // Apply PropertyFinder normalization if exists
                if (isset($enumNormalize[$propertyFinderTypeField][$pfTypeKey])) {
                    $pfTypeKey = $enumNormalize[$propertyFinderTypeField][$pfTypeKey];
                }

                // Look up PropertyFinder enum ID
                if (isset($enumLookup[$propertyFinderTypeField][$pfTypeKey])) {
                    $fields[$propertyFinderTypeField] = (int)$enumLookup[$propertyFinderTypeField][$pfTypeKey];
                    $setCount++;

                    // Set Category based on PropertyFinder type
                    if (isset($propertyFinderTypeToCategory[$pfTypeKey])) {
                        $categoryValue = $propertyFinderTypeToCategory[$pfTypeKey];
                        $categoryKey = mb_strtolower(trim($categoryValue));

                        // Apply category normalization if exists
                        if (isset($enumNormalize[$categoryField][$categoryKey])) {
                            $categoryKey = $enumNormalize[$categoryField][$categoryKey];
                        }

                        // Look up Category enum ID
                        if (isset($enumLookup[$categoryField][$categoryKey])) {
                            $fields[$categoryField] = (int)$enumLookup[$categoryField][$categoryKey];
                            $setCount++;
                        }
                    }
                }
            }

            // Step 7c: Set Price Type (UF_CRM_5_1752569908) based on property_purpose
            $priceTypeField = 'UF_CRM_5_1752569908';
            $propertyPurposeField = 'UF_CRM_5_1752755567'; // Already mapped earlier

            if (isset($fields[$propertyPurposeField])) {
                $purposeEnumId = $fields[$propertyPurposeField];

                // Reverse lookup: enum ID → display value (from $enumValues)
                $purposeDisplayValue = $enumValues[$propertyPurposeField][$purposeEnumId] ?? '';
                $purposeKey = mb_strtolower(trim($purposeDisplayValue));

                // Normalize using existing map (rent/buy)
                $normalizedPurpose = $enumNormalize[$propertyPurposeField][$purposeKey] ?? $purposeKey;

                // Determine desired priceType value
                $desiredPriceType = ($normalizedPurpose === 'buy') ? 'sale' : 'yearly';

                // Normalize and lookup enum ID for priceType
                $priceTypeKey = mb_strtolower($desiredPriceType);

                if (isset($enumLookup[$priceTypeField][$priceTypeKey])) {
                    $fields[$priceTypeField] = (int)$enumLookup[$priceTypeField][$priceTypeKey];
                    $setCount++;
                }
            }

            // Step 8: Handle images if CSV provides image URLs (common header guesses)
            // Collect images from all possible columns (images, images_1, images_2, etc.)
            $photoHeaders = ['images', 'Images', 'Photos', 'ImageURLs', 'Image Urls', 'Photo Urls', 'PhotoURLs'];
            $photoUrls = [];

            // First, collect from standard headers
            foreach ($photoHeaders as $ph) {
                if (isset($assoc[$ph]) && $assoc[$ph] !== '') {
                    $urls = $parseList($assoc[$ph]);
                    $photoUrls = array_merge($photoUrls, $urls);
                }
            }

            // Then, collect from numbered image columns (images_1, images_2, etc.)
            // Check all headers that match the pattern "images_" followed by a number
            foreach ($headers as $header) {
                $headerTrimmed = trim($header);
                // Check if header matches pattern: images_ followed by digits (e.g., images_1, images_2, images_10)
                // Also handle variations like images1, images 1, etc.
                if (preg_match('/^images[_\s]?\d+$/i', $headerTrimmed)) {
                    if (isset($assoc[$header]) && $assoc[$header] !== '') {
                        $urls = $parseList($assoc[$header]);
                        $photoUrls = array_merge($photoUrls, $urls);
                    }
                }
            }

            // Remove duplicates and empty values
            $photoUrls = array_unique(array_filter($photoUrls, function ($url) {
                return trim($url) !== '';
            }));

            // Remove query parameters from URLs to avoid watermarks
            $cleanUrls = [];
            foreach ($photoUrls as $u) {
                $u = trim($u);
                if ($u === '') continue;

                // strip query params
                $stripped = explode('?', $u)[0];
                $cleanUrls[] = $stripped;
            }

            if (!empty($cleanUrls)) {
                $files = [];

                foreach ($cleanUrls as $url) {
                    $url = trim($url);
                    if ($url === '') continue;

                    // Extract file extension from URL, default to .jpg if missing
                    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                    if (!$ext) {
                        $ext = 'jpg';
                    }

                    // Use cached file array where possible to avoid re-downloading
                    if (isset(self::$fileArrayCache[$url])) {
                        $fileArr = self::$fileArrayCache[$url];
                    } else {
                        $fileArr = \CFile::MakeFileArray($url);
                        if (is_array($fileArr) && !empty($fileArr['size'])) {
                            $fileArr['name'] = 'image_' . uniqid() . '.' . $ext; // Assign a proper name with extension
                            self::$fileArrayCache[$url] = $fileArr;
                        }
                    }

                    if (is_array($fileArr) && !empty($fileArr['size'])) {
                        $files[] = $fileArr;
                    }

                    if ($imageDelayMs > 0) {
                        usleep($imageDelayMs * 1000);
                    }
                }

                if (!empty($files)) {
                    // UF_CRM_5_1755322696 is used in existing feed code for photos on listings SPA
                    $fields['UF_CRM_5_1755322696'] = $files;
                }
            }

            // Log first few prepared rows for debugging
            if ($rowNum < 3) {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'row' => $assoc,
                    'fieldsPrepared' => $fields,
                    'skipped' => $skipped,
                ], 'bayut_row_' . $rowNum . ' ' . date('Y-m-d H:i:s'), 'bayut_import.log');
            }
            $rowNum++;

            // Step 9: Upsert SPA item (by Property Ref No). If exists, update portals only; else create and extend cache
            try {
                $refField = 'UF_CRM_5_1752571265';
                $refValue = $fields[$refField] ?? ($assoc['property_ref_no'] ?? null);

                // Determine the portal enum ID for this row (if any)
                $newPortalId = $fields[$portalField] ?? null;

                if ($refValue && isset($existingCache[$refValue])) {
                    $cached = $existingCache[$refValue];

                    // Fetch the existing item
                    $found = $factory->getItems([
                        'select' => ['ID'],
                        'filter' => ['=ID' => $cached['id']],
                        'limit' => 1,
                    ]);

                    $existingItem = $found ? reset($found) : null;

                    if ($existingItem) {
                        // Apply all new fields to the existing item
                        foreach ($fields as $fieldName => $fieldValue) {
                            $existingItem->set($fieldName, $fieldValue);
                        }

                        // Update operation
                        $operation = $factory->getUpdateOperation($existingItem);
                        $operation->disableCheckFields()->disableBizProc()->disableCheckAccess();
                        $result = $operation->launch();

                        if ($result->isSuccess()) {
                            $updated++;
                            $existingCache[$refValue]['portals'] = $fields[$portalField]
                                ? (array)$fields[$portalField]
                                : $existingCache[$refValue]['portals'];
                        } else {
                            $failed++;
                            $errors[] = $result->getErrorMessages();
                        }
                    }
                } else {
                    $item = $factory->createItem($fields);
                    $operation = $factory->getAddOperation($item);
                    $operation->disableCheckFields()->disableBizProc()->disableCheckAccess();
                    $result = $operation->launch();
                    if ($result->isSuccess()) {
                        $created++;
                        // add to cache
                        $newId = (int)$item->getId();
                        if ($refValue && $newId) {
                            $existingCache[$refValue] = [
                                'id' => $newId,
                                'portals' => $newPortalId ? [(int)$newPortalId] : [],
                            ];
                        }
                    } else {
                        $failed++;
                        $errors[] = $result->getErrorMessages();
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = $e->getMessage();
            }

            if ($rowDelayMs > 0) {
                usleep($rowDelayMs * 1000);
            }
        }

        fclose($handle);
        // Persist updated cache for subsequent imports (across multiple files)
        @file_put_contents($cachePath, json_encode($existingCache, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "Created: $created\n";
        echo "Updated: $updated\n";
        if ($failed) {
            echo "Failed: $failed\n";
            \Bitrix\Main\Diag\Debug::writeToFile($errors, 'bayut_import_errors ' . date('Y-m-d H:i:s'), 'bayut_import.log');
        }
    }

    public function makeNewFeed()
    {
        self::cleanDir(static::$root);

        $filterOffplan = [
            'STAGE_ID' => 'DT1036_8:SUCCESS',
            '@UF_CRM_5_1752569141' => [1298, 1299], // bayut_offplan, dubizzle_offplan
        ];
        $filterSec = [
            'STAGE_ID' => 'DT1036_8:SUCCESS',
            '@UF_CRM_5_1752569141' => [1486, 1487], // bayut_sec, dubizzle_sec
        ];

        $dataOffplan = static::retrieveDate($filterOffplan, 'bayut');
        $dataSec = static::retrieveDate($filterSec, 'bayut');

        $dataOffplan = self::prepareData($dataOffplan);
        $dataSec = self::prepareData($dataSec);

        print_r("Total Offplan: " . count($dataOffplan) . "\n");
        print_r("Total Secondary: " . count($dataSec) . "\n");

        if ($dataOffplan) {
            self::packtoXML($dataOffplan, 'Yes');
        }

        if ($dataSec) {
            self::packtoXML($dataSec, 'No');
        }
    }

    protected static function prepareData($data)
    {
        foreach ($data as $key => &$item) {
            if (is_array($item['Features'])) {
                foreach (static::$extraAmenities as $kitem) {
                    if ($item[$kitem]) {
                        $item['Features'][] = $kitem . ':' . $item[$kitem];
                    }
                }
            }
            switch ($item['Off_plan']) {
                case 'off_plan':
                case 'off_plan_primary':
                    $item['Off_plan'] = 'Yes';
                    break;
                case 'completed':
                case 'completed_primary':
                    $item['Off_plan'] = 'No';
                    break;
            }
        }
        return $data;
    }

    protected static function packtoXML($data, string $offPlan = 'No')
    {
        $fileName = 'bayutdubizzlesec.xml';
        if ($offPlan == 'Yes') {
            $fileName = 'bayutdubizzleoffp.xml';
        }
        $inputUTF8 = <<<INPUT
            <?xml version="1.0" encoding="UTF-8"?>
            <Properties>
            </Properties>    
            INPUT;
        $root = simplexml_load_string($inputUTF8);
        foreach ($data as $key => $item) {
            // if ($item['Off_plan'] == $offPlan) {
            $property = $root->addChild('Property');
            $property->Property_Ref_No = $item['Property_Ref_No'];
            $property->Property_purpose = $item['Property_purpose'];
            $property->Property_Type = $item['Property_Type'];
            $property->Property_Status = $item['Property_Status'];
            $property->City = $item['location']['City'];
            $property->Locality = $item['location']['Locality'];
            $property->Sub_Locality = $item['location']['Sub_Locality'];
            $property->Tower_Name = $item['location']['Tower_Name'];
            $property->Property_Title = $item['Property_Title'];
            $property->Property_Title_AR = $item['Property_Title_AR'];
            $property->Property_Description = $item['Property_Description'];
            $property->Property_Description_AR = $item['Property_Description_AR'];
            $property->Property_Size = $item['Property_Size'];
            $property->Property_Size_Unit = $item['Property_Size_Unit'] ?
                $item['Property_Size_Unit'] : '<![CDATA[ SQFT ]]>';
            $property->Bedrooms = $item['Bedrooms'];
            $property->Bathroom = $item['Bathrooms'];
            $property->Price = $item['Price'];

            // Before writing into XML
            if (isset($item['assignedTo']['User_ID']) && (int)$item['assignedTo']['User_ID'] === 11909) {
                // Custom override for user 11909
                $item['assignedTo']['Listing_Agent'] = "Joach Ann Jabagat";
                $item['assignedTo']['Listing_Agent_Email'] = "admin@primocapital.ae";
                $item['assignedTo']['Listing_Agent_Phone'] = "97145427114";
            }

            $property->Listing_Agent = $item['assignedTo']['Listing_Agent'];
            $property->Listing_Agent_Phone = $item['assignedTo']['Listing_Agent_Phone'];
            $property->Listing_Agent_Email = $item['assignedTo']['Listing_Agent_Email'];
            $features = $property->addChild('Features');
            foreach ($item['Features'] as $key => $val) {
                $features->Feature[$key] = $val;
            }
            $images = $property->addChild('Images');
            foreach ($item['Photos'] as $key => $val) {
                $images->Image[$key] = $val;
            }
            $videos = $property->addChild('Videos');
            foreach ($item['Videos'] as $key => $val) {
                $videos->Video[$key] = $val;
            }
            $property->Last_Updated = $item['Last_Updated'];
            $property->Permit_Number = $item['Permit_Number'];
            if ($item['Property_purpose'] == 'Rent') {
                $property->Rent_Frequency = $item['Rent_Frequency'];
            }
            $property->Off_plan = $item['Off_plan'];
            if ($item['Off_plan'] == 'Yes') {
                $property->offplanDetails_saleType = $item['offplanDetails_saleType'];
                $property->offplanDetails_dldWaiver = $item['offplanDetails_dldWaiver'];
                $property->offplanDetails_originalPrice = $item['offplanDetails_originalPrice'];
                $property->offplanDetails_amountPaid = $item['offplanDetails_amountPaid'];
            }
            $property->Furnished = static::$furnmap[$item['Furnished']];
            $portals = $property->addChild('Portals');
            foreach ($item['Portals'] as $key => $val) {
                $portals->Portal[$key] = $val;
            }
            // }
        }
        $root->asXML(static::$root . "/" . $fileName);
    }

    public function moveToPublished($filename = null)
    {
        if (!$filename || !file_exists(__DIR__ . '/' . $filename)) {
            throw new \Exception('CSV file not found: ' . $filename);
        }

        \Bitrix\Main\Loader::includeModule('crm');

        $filename = __DIR__ . '/' . $filename;
        $delimiter = static::detectDelimiter($filename);
        $handle = fopen($filename, 'r');
        if ($handle === false) {
            throw new \Exception('Unable to open CSV file: ' . $filename);
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Empty CSV or no headers');
        }

        // Normalize headers for easy lookup
        $normalize = function ($s) {
            return preg_replace('/[^a-z0-9]+/i', '', mb_strtolower(trim($s)));
        };
        $normalizedHeaderMap = [];
        foreach ($headers as $h) {
            $normalizedHeaderMap[$normalize($h)] = $h;
        }

        $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(static::$entityTypeId);
        if (!$factory) {
            fclose($handle);
            throw new \Exception('Factory not found');
        }

        // ✅ Step 1: Fetch all existing items in one call
        $existingItems = [];
        try {
            $items = $factory->getItems([
                'select' => ['ID', 'UF_CRM_5_1752571265', 'STAGE_ID'],
                'filter' => [],
                'order' => ['ID' => 'ASC'],
            ]);

            foreach ($items as $item) {
                $ref = trim((string)$item->get('UF_CRM_5_1752571265'));
                if ($ref !== '') {
                    $existingItems[$ref] = [
                        'id' => (int)$item->getId(),
                        'stage' => $item->get('STAGE_ID'),
                    ];
                }
            }
        } catch (\Throwable $e) {
            fclose($handle);
            throw new \Exception('Failed to fetch existing items: ' . $e->getMessage());
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $rowNum = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (count($row) == 1 && trim((string)$row[0]) === '') {
                continue;
            }

            $assoc = array_combine($headers, $row);
            if ($assoc === false) {
                continue;
            }

            $refNo = $assoc['property_ref_no'] ?? null;
            if (!$refNo) {
                // Try normalized match if header name differs
                $normalizedRefHeader = null;
                foreach ($normalizedHeaderMap as $norm => $orig) {
                    if ($norm === 'propertyrefno') {
                        $normalizedRefHeader = $orig;
                        break;
                    }
                }
                if ($normalizedRefHeader && isset($assoc[$normalizedRefHeader])) {
                    $refNo = trim($assoc[$normalizedRefHeader]);
                }
            }

            if (!$refNo) {
                $skipped++;
                continue;
            }

            $refNo = trim($refNo);
            if (!isset($existingItems[$refNo])) {
                $skipped++;
                continue; // not found in CRM
            }

            $itemData = $existingItems[$refNo];
            if ($itemData['stage'] === 'DT1036_8:SUCCESS') {
                $skipped++;
                continue; // already in target stage
            }

            try {
                $item = $factory->getItem($itemData['id']);
                if ($item) {
                    $item->set('STAGE_ID', 'DT1036_8:SUCCESS');
                    $operation = $factory->getUpdateOperation($item);
                    $operation->disableCheckFields()->disableBizProc()->disableCheckAccess();
                    $result = $operation->launch();

                    if ($result->isSuccess()) {
                        $updated++;
                    } else {
                        $failed++;
                        $errors[] = [
                            'ref' => $refNo,
                            'errors' => $result->getErrorMessages(),
                        ];
                    }
                } else {
                    $failed++;
                    $errors[] = ['ref' => $refNo, 'error' => 'Item fetch failed'];
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'ref' => $refNo,
                    'error' => $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        echo "Updated: $updated\n";
        echo "Skipped: $skipped\n";
        echo "Failed: $failed\n";

        if ($failed) {
            \Bitrix\Main\Diag\Debug::writeToFile($errors, 'moveToPublished_errors ' . date('Y-m-d H:i:s'), 'bayut_import.log');
        }
    }

    public function updatePortalsByFilename($filename)
    {
        if (!$filename || !file_exists(__DIR__ . '/' . $filename)) {
            throw new \Exception('CSV file not found: ' . $filename);
        }

        \Bitrix\Main\Loader::includeModule('crm');

        $filename = __DIR__ . '/' . $filename;
        $isPrimary = stripos($filename, 'primary') !== false;
        $isSecondary = stripos($filename, 'secondary') !== false;

        if (!$isPrimary && !$isSecondary) {
            throw new \Exception('Filename must contain either "primary" or "secondary"');
        }

        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(static::$entityTypeId);
        if (!$factory) {
            throw new \Exception('Factory not found');
        }

        $portalField = 'UF_CRM_5_1752569141'; // Portals
        $refField = 'UF_CRM_5_1752571265';   // Property Ref No

        // Get enum values
        $ufMeta = self::getEnumVal();
        $enumValues = $ufMeta['enum'];
        $enumLookup = [];
        foreach ($enumValues as $fieldName => $idToValue) {
            $enumLookup[$fieldName] = array_change_key_case(array_flip($idToValue), CASE_LOWER);
        }

        // Portal enum IDs
        $bayutOffplanId   = $enumLookup[$portalField]['bayut_offplan']   ?? null;
        $dubizzleOffplanId = $enumLookup[$portalField]['dubizzle_offplan'] ?? null;
        $bayutSecId       = $enumLookup[$portalField]['bayut_sec']       ?? null;
        $dubizzleSecId    = $enumLookup[$portalField]['dubizzle_sec']    ?? null;

        if (!$bayutOffplanId || !$dubizzleOffplanId || !$bayutSecId || !$dubizzleSecId) {
            throw new \Exception('Missing portal enum IDs for UF_CRM_5_1752569141');
        }

        // Step 1: Read CSV
        $delimiter = static::detectDelimiter($filename);
        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new \Exception('Unable to open file');
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Empty or invalid CSV file');
        }

        // Normalize header lookup
        $normalize = fn($s) => preg_replace('/[^a-z0-9]+/i', '', mb_strtolower(trim($s)));
        $normalizedHeaderMap = [];
        foreach ($headers as $h) {
            $normalizedHeaderMap[$normalize($h)] = $h;
        }

        // Step 2: Fetch all existing items to match by reference
        $existingItems = [];
        $items = $factory->getItems([
            'select' => ['ID', $refField],
            'filter' => [],
            'order'  => ['ID' => 'ASC'],
        ]);
        foreach ($items as $item) {
            $ref = trim((string)$item->get($refField));
            if ($ref !== '') {
                $existingItems[$ref] = (int)$item->getId();
            }
        }

        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        // Step 3: Iterate CSV rows
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) == 1 && trim((string)$row[0]) === '') continue;

            $assoc = array_combine($headers, $row);
            if ($assoc === false) continue;

            // Try to find Property Ref No
            $refNo = $assoc['property_ref_no'] ?? null;
            if (!$refNo) {
                foreach ($normalizedHeaderMap as $norm => $orig) {
                    if ($norm === 'propertyrefno') {
                        $refNo = $assoc[$orig] ?? null;
                        break;
                    }
                }
            }

            if (!$refNo) {
                $skipped++;
                continue;
            }

            $refNo = trim($refNo);
            if (!isset($existingItems[$refNo])) {
                $skipped++;
                continue;
            }

            $id = $existingItems[$refNo];

            // Determine target portals
            if ($isPrimary) {
                $newPortals = [$bayutOffplanId, $dubizzleOffplanId];
            } elseif ($isSecondary) {
                $newPortals = [$bayutSecId, $dubizzleSecId];
            } else {
                continue;
            }

            try {
                $item = $factory->getItem($id);
                if ($item) {
                    $item->set($portalField, $newPortals);
                    $operation = $factory->getUpdateOperation($item);
                    $operation->disableCheckFields()->disableBizProc()->disableCheckAccess();
                    $result = $operation->launch();

                    if ($result->isSuccess()) {
                        $updated++;
                    } else {
                        $failed++;
                        $errors[] = [
                            'ref' => $refNo,
                            'errors' => $result->getErrorMessages(),
                        ];
                    }
                } else {
                    $failed++;
                    $errors[] = ['ref' => $refNo, 'error' => 'Item fetch failed'];
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['ref' => $refNo, 'error' => $e->getMessage()];
            }
        }

        fclose($handle);

        echo "✅ Updated portals: {$updated}\n";
        echo "⏭ Skipped: {$skipped}\n";
        echo "❌ Failed: {$failed}\n";

        if ($failed) {
            \Bitrix\Main\Diag\Debug::writeToFile($errors, 'updatePortalsByFilename_errors ' . date('Y-m-d H:i:s'), 'bayut_import.log');
        }
    }

    public function fixWatermarkedImages($csvFile)
    {
        if (!$csvFile || !file_exists(__DIR__ . '/' . $csvFile)) {
            throw new \Exception("CSV file not found");
        }

        \Bitrix\Main\Loader::includeModule('crm');

        // Load factories
        $container = \Bitrix\Crm\Service\Container::getInstance();
        $factory = $container->getFactory(static::$entityTypeId);
        if (!$factory) {
            throw new \Exception("SPA factory not found");
        }

        // 1. Fetch all SPA listings into memory (ref_no => ID)
        $refMap = [];
        $allItems = $factory->getItems([
            'select' => ['ID', 'UF_CRM_5_1752571265'],
            'filter' => [],
        ]);

        foreach ($allItems as $item) {
            $ref = trim((string)$item->get('UF_CRM_5_1752571265'));
            if ($ref !== '') {
                $refMap[$ref] = (int)$item->getId();
            }
        }

        // 2. Open CSV + detect delimiter
        $filename = __DIR__ . '/' . $csvFile;
        $delimiter = static::detectDelimiter($filename);

        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new \Exception("Unable to open CSV");
        }

        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            throw new \Exception("CSV has no headers");
        }
        $headers = array_map('trim', $headers);

        // Common image column names
        $imageColumns = ['images', 'Images', 'Photos', 'ImageURLs', 'Image Urls', 'Photo Urls', 'PhotoURLs'];

        $countUpdated = 0;
        $countSkipped = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $assoc = array_combine($headers, $row);
            if (!$assoc) continue;

            $refNo = trim($assoc['property_ref_no'] ?? '');
            if ($refNo === '') {
                $countSkipped++;
                continue;
            }

            if (!isset($refMap[$refNo])) {
                $countSkipped++;
                continue;
            }

            $spaId = $refMap[$refNo];

            // 3. Collect image URLs
            $urls = [];

            // Standard image headers
            foreach ($imageColumns as $col) {
                if (!empty($assoc[$col])) {
                    $urls = array_merge($urls, preg_split('/[;,|]/', $assoc[$col]));
                }
            }

            // Pattern: images_1, images_2, images 3 etc.
            foreach ($headers as $h) {
                if (preg_match('/^images[_ ]?\d+$/i', $h)) {
                    if (!empty($assoc[$h])) {
                        $urls = array_merge($urls, preg_split('/[;,|]/', $assoc[$h]));
                    }
                }
            }

            // Cleanup
            $urls = array_unique(array_filter(array_map('trim', $urls)));
            if (empty($urls)) {
                $countSkipped++;
                continue;
            }

            // 4. Remove watermark params (anything after ?)
            $cleanUrls = [];
            foreach ($urls as $u) {
                $u = trim($u);
                if ($u === '') continue;

                // strip query params
                $stripped = explode('?', $u)[0];
                $cleanUrls[] = $stripped;
            }

            // 5. Download images as Bitrix file arrays
            $files = [];
            foreach ($cleanUrls as $u) {
                $file = \CFile::MakeFileArray($u);
                if (is_array($file) && !empty($file['size'])) {
                    $ext = pathinfo(parse_url($u, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
                    $file['name'] = 'clean_' . uniqid() . '.' . $ext;
                    $files[] = $file;
                }
            }

            if (empty($files)) {
                $countSkipped++;
                continue;
            }

            // 6. Update SPA record with clean photos
            $item = $factory->getItem($spaId);
            if (!$item) {
                $countSkipped++;
                continue;
            }

            $photoField = 'UF_CRM_5_1755322696'; // your photos UF
            $item->set($photoField, $files);

            $op = $factory->getUpdateOperation($item);
            $op->disableCheckFields()->disableBizProc()->disableCheckAccess();
            $res = $op->launch();

            if ($res->isSuccess()) {
                $countUpdated++;
            } else {
                \Bitrix\Main\Diag\Debug::writeToFile([
                    'ref' => $refNo,
                    'errors' => $res->getErrorMessages(),
                ], 'watermark_fix_errors', 'bayut_images_fix.log');
            }
        }

        fclose($handle);

        echo "Images updated: {$countUpdated}\n";
        echo "Skipped: {$countSkipped}\n";
    }

    protected static function cleanDir($dir)
    {
        $files = glob($dir . "/*");
        $c = count($files);
        if (count($files) > 0) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}
