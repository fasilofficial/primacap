<?php

namespace Webmatrik\Integrations;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Service;
use Bitrix\Main\Application;

abstract class Feed
{
    protected static $entityTypeId;
    protected static $locentityTypeId;
    protected static $bayutLocentityTypeId;
    protected static $photoentityTypeId;
    protected static $videoentityTypeId;

    public function __construct()
    {
        Loader::includeModule('crm');
        static::$entityTypeId = 1036;
        static::$locentityTypeId = 1054;
        static::$bayutLocentityTypeId = 1074;
        static::$photoentityTypeId = 1040;
        static::$videoentityTypeId = 1044;
    }

    protected function retrieveDate(array $filter, string $mode = 'Pf')
    {
        $uf = static::getEnumVal();
        $enums = $uf['enum'];
        $bool = $uf['bool'];
        //print_r($enums);
        $container = Container::getInstance();

        $factory = $container->getFactory(static::$entityTypeId);
        $rellocfactory = $container->getFactory(static::$locentityTypeId);
        $bayutLocFactory = $container->getFactory(static::$bayutLocentityTypeId);
        //$relphotofactory = $container->getFactory(static::$photoentityTypeId);
        //$relvideofactory = $container->getFactory(static::$videoentityTypeId);

        if (!$factory) {
            throw new Exception('Factory not found');
        }

        $params = [
            'select' => [
                'ID',
                'TITLE',
                'BEGINDATE',
                'UPDATED_TIME',
                'PARENT_ID_' . static::$locentityTypeId,
                'PARENT_ID_' . static::$bayutLocentityTypeId,
                'CREATED_BY',
                'ASSIGNED_BY_ID',
                'UF_*'
            ], // Все поля, включая пользовательские
            'filter' => $filter,
            'order' => ['ID' => 'ASC']
        ];

        // Получаем элементы
        $items = $factory->getItems($params);

        $result = [];
        $locations = [];
        foreach ($items as $item) {
            $res = [];
            $data = $item->getData();
            //print_r($data);
            $lisid = $data['ID'];
            if ($data['PARENT_ID_' . static::$bayutLocentityTypeId] && $mode == 'bayut') {
                $locations[] = $data['PARENT_ID_' . static::$bayutLocentityTypeId];
            } else {
                $locations[] = $data['PARENT_ID_' . static::$locentityTypeId];
            }
            if ($mode == 'Pf') {
                $users[] = $data['CREATED_BY'];
            }
            $users[] = $data['ASSIGNED_BY_ID'];
            if ($mode == 'bayut') {
                $res['location'] = $data['PARENT_ID_' . static::$bayutLocentityTypeId];
            } else {
                $res['location'] = $data['PARENT_ID_' . static::$locentityTypeId];
            }
            if ($mode == 'Pf') {
                $res['createdBy'] = $data['CREATED_BY'];
            }
            $res['assignedTo'] = $data['ASSIGNED_BY_ID'];
            if ($mode == 'bayut') {
                $res['Last_Updated'] = $data['UPDATED_TIME']->format("Y-m-d H:i:s");
            }

            if ($mode == 'Pf' && $data['BEGINDATE']) {
                $res['availableFrom'] = $data['BEGINDATE']->format("Y-m-d");;
            }
            if ($data['UF_CRM_5_1755322696']) {
                if ($mode == 'bayut') {
                    $photos = self::processPhotos($data['UF_CRM_5_1755322696'], 'bayut');
                    $res['Photos'] = $photos;
                } elseif ($mode == 'Pf') {
                    $photos = self::processPhotos($data['UF_CRM_5_1755322696'], 'Pf');
                    $res['media']['images'] = $photos;
                }
            }
            if ($data['UF_CRM_5_1755322729'] || $data['UF_CRM_5_1755322753']) {
                if ($mode == 'bayut') {
                    $res['Videos'] = [];
                    if ($data['UF_CRM_5_1755322729']) {
                        $res['Videos'][] = $data['UF_CRM_5_1755322729'];
                    }
                    if ($data['UF_CRM_5_1755322753']) {
                        $res['Videos'][] = $data['UF_CRM_5_1755322753'];
                    }
                } elseif ($mode == 'Pf') {
                    if ($data['UF_CRM_5_1755322729']) {
                        $res['media']['videos']['default'] = $data['UF_CRM_5_1755322729'];
                    }
                    if ($data['UF_CRM_5_1755322753']) {
                        $res['media']['videos']['view360'] = $data['UF_CRM_5_1755322753'];
                    }
                }
            }
            // sale amount
            // get main info
            foreach (static::$mask as $key => $item) {
                if (array_key_exists($key, $data)) {
                    if (is_array($data[$key])) {
                        if (array_key_exists($key, $enums)) {
                            $arr1 = $enums[$key];
                            $arr2 = $data[$key];
                            $arr2 = array_map(function ($key) use ($arr1) {
                                return $arr1[$key] ?? $key; // Если ключа нет в $arr1, оставляем исходное значение
                            }, $arr2);

                            $val = $arr2;
                        }
                    } else {
                        if ($data[$key]) {
                            if (array_key_exists($key, $enums)) {
                                $val = $enums[$key][$data[$key]];
                            } elseif (in_array($key, $bool)) {
                                $val = $data[$key];
                            } elseif ($key == 'UF_CRM_5_1752508197') {
                                $val = $data[$key]->format('Y-m-d\TH:i:s\Z');
                            } else {
                                $val = $data[$key];
                            }
                        } else {
                            if (in_array($key, $bool)) {
                                $val = $data[$key];
                            } else {
                                $val = '';
                            }
                        }
                    }
                    $itemarr = explode(',', $item);
                    if (in_array($key, $bool)) {
                        $res = self::arrayToNestedKeys($itemarr, $res, $val);
                    } else {
                        if ($val) {
                            $res = self::arrayToNestedKeys($itemarr, $res, $val);
                        }
                    }


                    //$val = '';

                }
            }
            if ($mode == 'bayut') {
                $res['Property_Status'] = 'Live';
            }
            //print_r($res);
            $result[$lisid] = $res;
        }
        if ($result) {
            // get locations
            $locations = array_unique($locations);

            $params = [
                'select' => ['ID', 'TITLE', 'UF_CRM_9_1753773914'],
                'filter' => [
                    '@ID' => $locations
                ],
                'order' => ['ID' => 'ASC'],
            ];

            $locobj = null;
            if ($mode == 'bayut') {
                // remove UF_CRM_9_1753773914 from select for bayut
                unset($params['select'][2]);
                $locobj = $bayutLocFactory->getItems($params);
            } elseif ($mode == 'Pf') {
                $locobj = $rellocfactory->getItems($params);
            }

            $locresult = [];

            foreach ($locobj as $item) {
                $data = $item->getData();
                if ($mode == 'bayut') {
                    $titles = array_reverse(array_map('trim', explode(',', $data['TITLE'])));
                    $count = count($titles);

                    $city = $titles[0] ?? '';
                    $locality = $titles[1] ?? '';
                    $subLocality = '';
                    $tower = '';

                    if ($count === 3) {
                        // 3 parts: city, locality, tower
                        $tower = $titles[2];
                    } elseif ($count >= 4) {
                        // 4 or more parts: city, locality, sub-locality, tower
                        $subLocality = $titles[2];
                        $tower = $titles[3];
                    }

                    $locresult[$data['ID']] = [
                        'City' => $city,
                        'Locality' => $locality,
                        'Sub_Locality' => $subLocality,
                        'Tower_Name' => $tower
                    ];
                } elseif ($mode == 'Pf') {
                    $locresult[$data['ID']] = (int)$data['UF_CRM_9_1753773914'];
                }
            }

            // get users
            $users = array_unique($users);
            if ($mode == 'bayut') {
                $select = ['ID', 'NAME', 'LAST_NAME', 'WORK_PHONE', 'EMAIL'];
            } elseif ($mode == 'Pf') {
                $select = ['ID', 'UF_PFID', 'UF_PFOP', 'UF_PFID_OP'];
            }
            //print_r($users);
            $userlist = \Bitrix\Main\UserTable::getList(array(
                'filter' => array(
                    '@ID' => $users,
                ),
                'select' => $select
            ))->fetchAll();

            $userresult = [];

            foreach ($userlist as $item) {
                if ($mode == 'bayut') {
                    $userresult[$item['ID']] = [
                        'User_ID' => $item['ID'],
                        'Listing_Agent' => $item['NAME'] . ' ' . $item['LAST_NAME'],
                        'Listing_Agent_Phone' => $item['WORK_PHONE'],
                        'Listing_Agent_Email' => $item['EMAIL']
                    ];
                } elseif ($mode == 'Pf') {
                    if ($item['UF_PFOP'] == static::$offplan || isset($item['UF_PFID_OP'])) {
                        $userresult[$item['ID']] = isset($item['UF_PFID_OP']) ? (int)$item['UF_PFID_OP'] : (int)$item['UF_PFID'];
                    } else {
                        $userresult[$item['ID']] = '';
                    }
                }
            }

            //print_r($userresult);
            /* old method to get photos and videos
			$params = [
				'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
				'filter' => [
					'@PARENT_ID_'.static::$entityTypeId => array_keys($result),
				],
				'order' => ['ID' => 'ASC'],
				//'limit' => 100,
			];
	
			// Получаем элементы
			$photoobj = $relphotofactory->getItems($params);
			$photoresult = [];
			foreach ($photoobj as $key=>$item) {
				$data = $item->getData();
				if($mode=='bayut') {
					if($item['UF_CRM_6_1752590366']) {
						$photoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590366']);
						$photoresult[$item['PARENT_ID_'.static::$entityTypeId]][] =
							'https://primocapitalcrm.ae/'.$photoarr['SRC'];
					}
				} elseif($mode=='Pf') {
					if($item['UF_CRM_6_1752590335']) {
                        $photoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590335']);
                        $photoresult[$item['PARENT_ID_'.static::$entityTypeId]][$key]['large'] =
                            [
                                'url' => 'https://primocapitalcrm.ae/'.$photoarr['SRC'],
                                'width' => (int)$photoarr['WIDTH'],
                                'height' => (int)$photoarr['HEIGHT'],
                            ];
                    }
                    if($item['UF_CRM_6_1752590350']) {
                        $photoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590350']);
                        $photoresult[$item['PARENT_ID_'.static::$entityTypeId]][$key]['medium'] =
                            [
                                'url' => 'https://primocapitalcrm.ae/'.$photoarr['SRC'],
                                'width' => (int)$photoarr['WIDTH'],
                                'height' => (int)$photoarr['HEIGHT'],
                            ];
                    }
                    if($item['UF_CRM_6_1752590366']) {
                        $photoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590366']);
                        $photoresult[$item['PARENT_ID_'.static::$entityTypeId]][$key]['original'] =
                            [
                                'url' => 'https://primocapitalcrm.ae/'.$photoarr['SRC'],
                                'width' => (int)$photoarr['WIDTH'],
                                'height' => (int)$photoarr['HEIGHT'],
                            ];
                    }
                    if($item['UF_CRM_6_1752590507']) {
                        $photoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590507']);
                        $photoresult[$item['PARENT_ID_'.static::$entityTypeId]][$key]['thumbnail'] =
                            [
                                'url' => 'https://primocapitalcrm.ae/'.$photoarr['SRC'],
                                'width' => (int)$photoarr['WIDTH'],
                                'height' => (int)$photoarr['HEIGHT'],
                            ];
                    }
                    if($item['UF_CRM_6_1752590519']) {
                        $photoarr = \CFile::GetFileArray($item['UF_CRM_6_1752590519']);
                        $photoresult[$item['PARENT_ID_'.static::$entityTypeId]][$key]['watermarked'] =
                            [
                                'url' => 'https://primocapitalcrm.ae/'.$photoarr['SRC'],
                                'width' => (int)$photoarr['WIDTH'],
                                'height' => (int)$photoarr['HEIGHT'],
                            ];
                    }
				}
			}
			// get videos
			$params = [
				'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
				'filter' => [
					'@PARENT_ID_'.static::$entityTypeId => array_keys($result),
				],
				'order' => ['ID' => 'ASC'],
				//'limit' => 100,
			];
	
			// Получаем элементы
			$videoobj = $relvideofactory->getItems($params);
			$videoresult = [];
			foreach ($videoobj as $key=>$item) {
				$data = $item->getData();
				if($mode=='bayut') {
					if($item['UF_CRM_7_1752575795']) {
						$videoresult[$item['PARENT_ID_'.static::$entityTypeId]][] =
							$item['UF_CRM_7_1752575795'];
					}
				} elseif($mode=='Pf') {
					if($item['UF_CRM_7_1752575795']) {
						$videoresult[$item['PARENT_ID_'.static::$entityTypeId]]['default'] =
							$item['UF_CRM_7_1752575795'];
					}
					if($item['UF_CRM_7_1752575817']) {
						$videoresult[$item['PARENT_ID_'.static::$entityTypeId]]['view360'] =
							$item['UF_CRM_7_1752575817'];
					}
				}
			}*/
            //print_r($locresult);
            //print_r($result);

            foreach ($result as $key => &$item) {
                $item['location'] = $locresult[$item['location']];
                $item['assignedTo'] = $userresult[$item['assignedTo']];
                if ($mode == 'bayut') {
                    //$item['Photos'] = $photoresult[$key];
                    //$item['Videos'] = $videoresult[$key];                    
                } elseif ($mode == 'Pf') {
                    $item['createdBy'] = $userresult[$item['createdBy']];
                    //$item['media']['images'] = $photoresult[$key];
                    //if($videoresult[$key]) {
                    //$item['media']['videos'] = $videoresult[$key];
                    //}

                }
            }
        }

        //print_r($result);

        return $result;
    }

    function processPhotos($data, $mode = 'bayut')
    {
        $resarr = [];
        foreach ($data as $item) {
            $photoarr = \CFile::GetFileArray($item);
            $src = 'https://primocapitalcrm.ae' . $photoarr['SRC'];
            if ($mode == 'bayut') {
                $resarr[] = $src;
            } elseif ($mode == 'Pf') {
                $resarr[] = [
                    'original' => [
                        'url' => $src,
                        'width' => (int)$photoarr['WIDTH'],
                        'height' => (int)$photoarr['HEIGHT'],
                    ]
                ];
            }
        }
        return $resarr;
    }

    function arrayToNestedKeys(array $keys, &$targetArray = [], $value = null)
    {
        $current = &$targetArray;

        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        // Если передано значение, устанавливаем его в последний ключ
        if (func_num_args() > 2) {
            $current = $value;
        }

        return $targetArray;
    }

    /*public static function getUser() {

        $user = \Bitrix\Main\UserTable::getList(array(
            'filter' => array(
                '!UF_PFID' => false,
            ),

            //'limit'=>1,

            'select'=>array('*','UF_*'),

        ))->fetchAll();

        print_r($user);
    }*/


    public static function getEnumVal()
    {
        $rsUserFields = \Bitrix\Main\UserFieldTable::getList(array(
            'filter' => array('ENTITY_ID' => 'CRM_5', '@USER_TYPE_ID' => ['enumeration', 'boolean']),
        ));
        $resval = [
            'enum' => [],
            'bool' => []
        ];

        while ($arUserField = $rsUserFields->fetch()) {
            if ($arUserField['USER_TYPE_ID'] == 'enumeration') {
                $enumList = \CUserFieldEnum::getList([], [
                    'USER_FIELD_ID' => $arUserField['ID']
                ]);
                $resval['enum'][$arUserField['FIELD_NAME']] = [];
                while ($enumValue = $enumList->fetch()) {
                    $resval['enum'][$arUserField['FIELD_NAME']][$enumValue['ID']] = $enumValue['VALUE'];
                }
            } else {
                $resval['bool'][] = $arUserField['FIELD_NAME'];
            }
        }
        return $resval;
    }

    /*public static function makeFeeds() {
        Loader::includeModule("crm");
        $entityTypeId = '1036';
        // Получаем фабрику для работы с сущностью videos
        $container = Container::getInstance();
        $relationManager = $container->getRelationManager();
        $factory = $container->getFactory($entityTypeId);

        if (!$factory) {
            throw new Exception('Factory not found');
        }

        // Подготовка параметров запроса
        $params = [
            'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
            'filter' => [
                'STAGE_ID' => 'DT1036_8:SUCCESS',
            ],
            'order' => ['ID' => 'ASC'],
            //'limit' => 100,
        ];

        // Получаем элементы
        $items = $factory->getItemsFilteredByPermissions($params);

        // Обработка результатов

        foreach ($items as $item) {
            $result = [];
            $id = $item->getId();
            $result[] = [
                //'id' => $item->getId(),
                'data' => $item->getData(),
                //'userFields' => $item->getUserFields(),
            ];
            $childs = [];
            $itemIdentifier = new \Bitrix\Crm\ItemIdentifier($entityTypeId, $id);
            $childElements = $relationManager->getChildElements($itemIdentifier);
            foreach ($childElements as $child) {
                $childs[$child->getEntityTypeId()] = [
                    //'id' => $item->getId(),
                    //'entTypeId' => $child->getEntityTypeId(),
                    $child->getEntityId(),
                    //'data' => $child->toArray()
                    //'userFields' => $item->getUserFields(),
                ];
            }

            print_r($result);
            print_r($childs);
            foreach($childs as $ckey => $citem) {
                $cfactory = $container->getFactory($ckey);
                $params = [
                    'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
                    'filter' => [
                        'ID' => $citem,
                    ],
                    'order' => ['ID' => 'ASC'],
                    //'limit' => 100,
                ];
                $chresult = [];
                // Получаем элементы
                $chitems = $cfactory->getItemsFilteredByPermissions($params);
                foreach ($chitems as $chitem) {

                    $chresult[] = [
                        //'id' => $item->getId(),
                        'data' => $chitem->getData(),
                        //'userFields' => $item->getUserFields(),
                    ];
                }
                print_r($chresult);


            }


            // альтернатива
            $listingId = '1036';
            $videoId = '1044';
            $photoId = '1040';
            // Получаем фабрику для работы с сущностью videos
            $container = Container::getInstance();
            $relationManager = $container->getRelationManager();
            $factory = $container->getFactory($photoId);


            // Подготовка параметров запроса
            $params = [
                'select' => ['*', 'UF_*'], // Все поля, включая пользовательские
                'filter' => [
                    'PARENT_ID_'.$listingId => 1,
                ],
                'order' => ['ID' => 'ASC'],
                //'limit' => 100,
            ];

            // Получаем элементы
            $items = $factory->getItemsFilteredByPermissions($params);

            foreach ($items as $item) {
                $result = [];
                $id = $item->getId();
                $result[] = [
                    //'id' => $item->getId(),
                    'data' => $item->getData(),
                    //'userFields' => $item->getUserFields(),
                ];

            }

            print_r($result);

        }




    }*/
}
