<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

Loader::includeModule($module_id);

$aTabs = array(
	array(
		"DIV" => "edit",
		"TAB" => Loc::getMessage("MODULE_OPTIONS_TAB_NAME"),
		"TITLE"   => Loc::getMessage("MODULE_OPTIONS_TAB_NAME_TITLE"),
		"OPTIONS" => array(
			Loc::getMessage("GENERAL_MODULE_OPTIONS"),
			array(
				"getToken",
				Loc::getMessage("MODULE_OPTIONS_GET_TOKEN"),
				Option::get("blinovav.extensionrest", "getToken"),
				array("text", 100)
			),
			array(
				"request",
				Loc::getMessage("MODULE_OPTIONS_REQUEST"),
				Option::get("blinovav.extensionrest", "request"),
				array("text", 100)
			),
			array(
				"requestAdd",
				Loc::getMessage("MODULE_OPTIONS_REQUEST_ADD"),
				Option::get("blinovav.extensionrest", "requestAdd"),
				array("text", 100)
			),
		)
	)
);

$tabControl = new CAdminTabControl(
	"tabControl",
	$aTabs
);

$tabControl->Begin();

?>
<form action="<? echo ($APPLICATION->GetCurPage()); ?>?mid=<? echo ($module_id); ?>&lang=<? echo (LANG); ?>" method="post">

	<?
	foreach ($aTabs as $aTab) {
		if ($aTab["OPTIONS"]) {
			$tabControl->BeginNextTab();
			__AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
		}
	}
	$tabControl->Buttons();
	?>

	<input type="submit" name="apply" value="<? echo (Loc::GetMessage("MODULE_OPTIONS_UPPLY")); ?>" class="adm-btn-save" />
	<input type="submit" name="default" value="<? echo (Loc::GetMessage("MODULE_OPTIONS_DEFAULT")); ?>" />

	<?
	echo (bitrix_sessid_post());
	?>

</form>
<?
$tabControl->End();

if ($request->isPost() && check_bitrix_sessid()) {
	$aTabs = $request->getPostList();
	foreach ($aTabs as $key => $aTab) {
		$name = $key;
		$val = $_REQUEST[$name];
		Option::set($module_id, $name, $val);
	}
	LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . $module_id . "&lang=" . LANG);
}
