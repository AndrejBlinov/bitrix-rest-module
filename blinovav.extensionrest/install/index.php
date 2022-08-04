<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

class blinovav_extensionrest extends CModule
{
	var $MODULE_ID = "blinovav.extensionrest";
	var $APP_TABLE_CODE = "blinovav_extensionrest";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $IBLOCK_TYPE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";
	var $NAME_MODULE = "BLINOVAV_EXTENSIONREST";

	function __construct()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path . "/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = Loc::getMessage($this->NAME_MODULE . "_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage($this->NAME_MODULE . "_INSTALL_DESCRIPTION");
		$this->PARTNER_NAME = Loc::getMessage($this->NAME_MODULE . "_DEV_NAME");
	}

	function DoInstall()
	{
		global $APPLICATION;

		if ($this->isVersionD7()) {
			if (!CModule::IncludeModule('rest')) {
				$this->InstalRestModule();
			}
			$this->InstallFiles();
			$this->InstallDB();
			$this->addUserForRest();
			$this->tableAppCreate($this->APP_TABLE_CODE);
			$this->optionsAdd();

			RegisterModule($this->MODULE_ID);

			$this->InstallEvents();

			Loc::getMessage($this->NAME_MODULE . "_FORM_INSTALL_TITLE");
		} else {
			$APPLICATION->ThrowException(Loc::getMessage($this->NAME_MODULE . "_INSTALL_ERROR"));
		}

		$APPLICATION->IncludeAdminFile(Loc::getMessage($this->NAME_MODULE . "_FORM_INSTALL_TITLE"), $this->GetPath() . "/install/step.php");
	}

	function InstalRestModule()
	{
		require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/rest/install/index.php");

		$obrest = new rest();

		$obrest->InstallDB(array());
		$obrest->InstallFiles(array());
		return true;
	}

	function InstallEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
			'rest',
			'OnRestServiceBuildDescription',
			$this->MODULE_ID,
			'\Blinovav\Extensionrest\ExtensionRest',
			'OnRestServiceBuildDescription'
		);

		return true;
	}

	function tableAppCreate($code) {
		$arAppsFromDB = $this->tableAppExist($code);

		if (empty($arAppsFromDB)) {
			if (!\Bitrix\Rest\OAuthService::getEngine()->isRegistered()) {
				try {
					\Bitrix\Rest\OAuthService::register();
				} catch (\Bitrix\Main\SystemException $e) {
					$arResult['ERROR'] = $e->getCode() . ': ' . $e->getMessage();
				}
			}
	
			$appFields = array(
				'ACTIVE' => \Bitrix\Rest\AppTable::ACTIVE,
				'INSTALLED' => \Bitrix\Rest\AppTable::INSTALLED,
				'URL_DEMO' => "",
				'URL_INSTALL' => '',
				'VERSION' => $this->MODULE_VERSION,
				'SCOPE' => $this->APP_TABLE_CODE,
				'STATUS' => "L",
				'CLIENT_SECRET' => '',
				'APP_NAME' => $code,
				'MOBILE' => \Bitrix\Rest\AppTable::INACTIVE,
				'USER_INSTALL' => \Bitrix\Rest\AppTable::INACTIVE,
			);
	
			$result = \Bitrix\Rest\AppTable::add($appFields);
	
			$arAppsFromDB = $result->getData();
		}
	}

	function tableAppDelete($code) {
		$arAppsFromDB = $this->tableAppExist($code);

		if (!empty($arAppsFromDB)) {
			\Bitrix\Rest\AppTable::delete($arAppsFromDB['ID']);
		}
	}

	function tableAppExist($code) {
		$dbApps = \Bitrix\Rest\AppTable::getList(array(
            'filter' => array('=APP_NAME' => $code),
            'select' => array('ID', 'APP_NAME', "CODE", "CLIENT_ID")
        ));

        $arAppsFromDB = [];
        while ($arApp = $dbApps->Fetch()) {
            $arAppsFromDB = $arApp;
        }

		return $arAppsFromDB;
	}

	function DoUninstall()
	{
		global $APPLICATION;

		$contect = Application::getinstance()->getContext();
		$request = $contect->getRequest();

		$this->UnInstallDB();
		$this->UnInstallFiles();
		$this->UnInstallEvents();
		$this->DeleteUserForRest();
		$this->tableAppDelete($this->APP_TABLE_CODE);
		$this->optionsDelete();

		\Bitrix\Main\ModuleManager::UnRegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile(GetMessage($this->NAME_MODULE . "_FORM_UNSTALL_TITLE"), $this->GetPath() . "/install/unstep.php");
	}

	function GetPath()
	{
		return $_SERVER["DOCUMENT_ROOT"] . "/local/modules/" . $this->MODULE_ID;
	}

	function UnInstallDB()
	{
		return true;
	}

	function UnInstallEvents()
	{
		\Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
			'rest',
			'OnRestServiceBuildDescription',
			$this->MODULE_ID,
			'\Blinovav\Extensionrest\ExtensionRest',
			'OnRestServiceBuildDescription'
		);

		return true;
	}

	function InstallFiles()
	{
		$path = $_SERVER["DOCUMENT_ROOT"];

		if (!file_exists($path . "/rest/")) {
			$ret = mkdir($path . "/rest/");
		}

		if (!file_exists($path . "/rest_controller_extension/")) {
			$ret = mkdir($path . "/rest_controller_extension/");
		}

		$result = CopyDirFiles($path . "/local/modules/" . $this->MODULE_ID . "/install/public/rest/", $path . "/rest/", true, true);
		$result = CopyDirFiles($path . "/local/modules/" . $this->MODULE_ID . "/install/public/rest_controller_extension/", $path . "/rest_controller_extension/", true, true);

		return $result;
	}

	function UnInstallFiles()
	{
		$path = $_SERVER["DOCUMENT_ROOT"];

		DeleteDirFilesEx("/rest_controller_extension/");

		return true;
	}

	function InstallDB()
	{
		return true;
	}

	function isVersionD7()
	{
		return true;
	}

	function addUserForRest()
	{
		$login = 'restExtension@test.test';

		$arUser = $this->getUserByLogin($login);

		if (empty($arUser['ID'])) {
			$USER = new CUser;

			$arFields = array(
				"NAME" => 'rest extension',
				"LOGIN" => $login,
				"EMAIL" => $login,
				"LID" => "s1",
				"ACTIVE" => "Y",
				"PASSWORD" => '1Pass@word',
				"CONFIRM_PASSWORD" => '1Pass@word',
			);
			$USER->Add($arFields);
		}
	}

	function deleteUserForRest() {

		$login = 'restExtension@test.test';
		$arUser = $this->getUserByLogin($login);
		
		if (!empty($arUser['ID'])) {
			$USER = new CUser;
			$USER->delete($arUser['ID']);
		}
		
	}

	private function getUserByLogin($login) {
		$USER = new CUser;

		$rsUser = $USER->GetByLogin($login);
		$arUser = $rsUser->Fetch();

		return $arUser;
	}

	private function optionsAdd () {
		Option::set($this->MODULE_ID, 'getToken', '*saite_domain*/rest_controller_extension/extension/');
		Option::set($this->MODULE_ID, 'request', '*saite_domain*/rest/blinovav_extensionrest.extension/');
		Option::set($this->MODULE_ID, 'requestAdd', '/local/module/blinovav.extensionrest/lib/ExtensionRest');
	}

	private function optionsDelete() {
		Option::delete($this->MODULE_ID);
	}
}
