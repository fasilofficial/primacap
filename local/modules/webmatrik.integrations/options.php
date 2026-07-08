<?php

$MODULE_ID = 'webmatrik.integrations';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();
Loc::loadMessages($context->getServer()->getDocumentRoot()."/bitrix/modules/main/options.php");
Loc::loadMessages(__FILE__);

global $USER, $APPLICATION;
if (!$USER->CanDoOperation($MODULE_ID . '_settings')) {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$arAllOptions = [
'main' => [
        [
            'main_Lead_AssignedTo',
            Loc::getMessage($MODULE_ID . '_Lead_AssignedTo'),
            Option::get($MODULE_ID, '_Lead_AssignedTo'),
            ['text', 15]
        ],
        [
            'main_Lead_Id_UF',
            Loc::getMessage($MODULE_ID . '_Lead_Id_UF'),
            Option::get($MODULE_ID, '_Lead_Id_UF'),
            ['text', 30]
        ],
        [
            'main_Client_Name_UF',
            Loc::getMessage($MODULE_ID . '_Client_Name_UF'),
            Option::get($MODULE_ID, '_Client_Name_UF'),
            ['text', 30]
        ],
        [
            'main_Client_Phone_UF',
            Loc::getMessage($MODULE_ID . '_Client_Phone_UF'),
            Option::get($MODULE_ID, '_Client_Phone_UF'),
            ['text', 30]
        ],
        [
            'main_Client_Email_UF',
            Loc::getMessage($MODULE_ID . '_Client_Email_UF'),
            Option::get($MODULE_ID, '_Client_Email_UF'),
            ['text', 30]
        ],
        [
            'main_Bayut_Source',
            Loc::getMessage($MODULE_ID . '_Bayut_Source'),
            Option::get($MODULE_ID, '_Bayut_Source'),
            ['text', 30]
        ],
        [
            'main_Dubizzle_Source',
            Loc::getMessage($MODULE_ID . '_Dubizzle_Source'),
            Option::get($MODULE_ID, '_Dubizzle_Source'),
            ['text', 15]
        ],
        [
            'main_Pf_Source',
            Loc::getMessage($MODULE_ID . '_Pf_Source'),
            Option::get($MODULE_ID, '_Pf_Source'),
            ['text', 15]
        ],
        [
            'main_Bayut_Property_Link_UF',
            Loc::getMessage($MODULE_ID . '_Bayut_Property_Link_UF'),
            Option::get($MODULE_ID, '_Bayut_Property_Link_UF'),
            ['text', 30]
        ],
        [
            'main_Bayut_Property_Ref_UF',
            Loc::getMessage($MODULE_ID . '_Bayut_Property_Ref_UF'),
            Option::get($MODULE_ID, '_Bayut_Property_Ref_UF'),
            ['text', 30]
        ],
        [
            'main_Bayut_Contact_Link_UF',
            Loc::getMessage($MODULE_ID . '_Bayut_Contact_Link_UF'),
            Option::get($MODULE_ID, '_Bayut_Contact_Link_UF'),
            ['text', 30]
        ],
        [
            'main_Pf_Property_Link_UF',
            Loc::getMessage($MODULE_ID . '_Pf_Property_Link_UF'),
            Option::get($MODULE_ID, '_Pf_Property_Link_UF'),
            ['text', 30]
        ],
        [
            'main_Pf_Property_Ref_UF',
            Loc::getMessage($MODULE_ID . '_Pf_Property_Ref_UF'),
            Option::get($MODULE_ID, '_Pf_Property_Ref_UF'),
            ['text', 30]
        ],
        [
            'main_Pf_Contact_Link_UF',
            Loc::getMessage($MODULE_ID . '_Pf_Contact_Link_UF'),
            Option::get($MODULE_ID, '_Pf_Contact_Link_UF'),
            ['text', 30]
        ],
        [
            'main_Pf_Agent_Id_UF',
            Loc::getMessage($MODULE_ID . '_Pf_Agent_Id_UF'),
            Option::get($MODULE_ID, '_Pf_Agent_Id_UF'),
            ['text', 30]
        ],
        [
            'main_Bayut_API_URL',
            Loc::getMessage($MODULE_ID . '_Bayut_API_URL'),
            Option::get($MODULE_ID, '_Bayut_API_URL'),
            ['text', 30]
        ],
        [
            'main_Dubizzle_API_URL',
            Loc::getMessage($MODULE_ID . '_Dubizzle_API_URL'),
            Option::get($MODULE_ID, '_Dubizzle_API_URL'),
            ['text', 30]
        ],
        [
            'main_BayutDubizzle_OFFPLAN_API_KEY',
            Loc::getMessage($MODULE_ID . '_BayutDubizzle_OFFPLAN_API_KEY'),
            Option::get($MODULE_ID, '_BayutDubizzle_OFFPLAN_API_KEY'),
            ['text', 30]
        ],
        [
            'main_BayutDubizzle_SECONDARY_API_KEY',
            Loc::getMessage($MODULE_ID . '_BayutDubizzle_SECONDARY_API_KEY'),
            Option::get($MODULE_ID, '_BayutDubizzle_SECONDARY_API_KEY'),
            ['text', 30]
        ],
        [
            'main_Bayut_Start_Deal_WF',
            Loc::getMessage($MODULE_ID . '_Bayut_Start_Deal_WF'),
            Option::get($MODULE_ID, '_Bayut_Start_Deal_WF'),
            ['checkbox']
        ],
        [
            'main_Bayut_Start_Deal_WF_ID',
            Loc::getMessage($MODULE_ID . '_Bayut_Start_Deal_WF_ID'),
            Option::get($MODULE_ID, '_Bayut_Start_Deal_WF_ID'),
            ['text', 5]
        ],
        [
            'main_Pf_Start_Deal_WF',
            Loc::getMessage($MODULE_ID . '_Pf_Start_Deal_WF'),
            Option::get($MODULE_ID, '_Pf_Start_Deal_WF'),
            ['checkbox']
        ],
        [
            'main_Pf_Start_Deal_WF_ID',
            Loc::getMessage($MODULE_ID . '_Pf_Start_Deal_WF_ID'),
            Option::get($MODULE_ID, '_Pf_Start_Deal_WF_ID'),
            ['text', 5]
        ],
    ],
];

if(isset($request["save"]) && check_bitrix_sessid()) {
    foreach ($arAllOptions as $part) {
        foreach($part as $arOption) {
            if(is_array($arOption)) {
                __AdmSettingsSaveOption($MODULE_ID, $arOption);
            }
        }
    }
}

$arTabs = [
    [
        "DIV" => "main",
        "TAB" => Loc::getMessage($MODULE_ID.'_main'),
        "ICON" => $MODULE_ID . '_settings',
        "TITLE" => Loc::getMessage($MODULE_ID.'_main'),
        'TYPE' => 'options', //options || rights || user defined
    ]
];

$tabControl = new CAdminTabControl("tabControl", $arTabs);

$tabControl->Begin();
?>
<form method="POST" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($mid) ?>&amp;lang=<?= LANG ?>"
      name="<?= $MODULE_ID ?>_settings">
    <?= bitrix_sessid_post(); ?>
    <?
    foreach ($arTabs as $tab) {
        $tabControl->BeginNextTab();
        __AdmSettingsDrawList($MODULE_ID, $arAllOptions[$tab['DIV']]);
    }?>
    <?$tabControl->Buttons();?>
    <input type="submit" class="adm-btn-save" name="save" value="<?=Loc::getMessage($MODULE_ID.'_save');?>">
    <?=bitrix_sessid_post();?>
    <? $tabControl->End(); ?>
</form>