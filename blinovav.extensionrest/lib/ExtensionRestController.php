<?

namespace Blinovav\Extensionrest;

use Exception;
use Lib\Exceptions\CmRestException as RestException;
//use \Bitrix\Rest\PlacementTable;
class ExtensionRestController extends Controller
{
    const STATUS_OK = "200 OK";
    const STATUS_CREATED = "201 Created";
    const STATUS_WRONG_REQUEST = "400 Bad Request";
    const STATUS_UNAUTHORIZED = "401 Unauthorized";
    const STATUS_PAYMENT_REQUIRED = "402 Payment Required"; // reserved for future use
    const STATUS_FORBIDDEN = "403 Forbidden";
    const STATUS_NOT_FOUND = "404 Not Found";
    const STATUS_INTERNAL = "500 Internal Server Error";

    /* @var RestException */
    protected $error;

    public function __construct()
    {
        parent::__construct();

        try {
            $this->init();
        } catch (Exception $e) {
            $this->error = $e;
            header("HTTP/1.1 " . $this->error->getStatus());
        }
    }

    private function init()
    {
        header('Content-Type: application/json');

        $this->setRestExeption();

        $this->action = $this->requestParams['action'];
    }

    private function setRestExeption()
    {
        if ($this->requestParams['action'] == "getToken") {
            if ((!isset($this->requestParams['username']) || !isset($this->requestParams['password']))) {
                throw new RestException(
                    'Missing authorization data',
                    RestException::ERROR_ARGUMENT,
                    self::STATUS_UNAUTHORIZED
                );
            }
            $this->username = $this->requestParams['username'];
            $this->password = $this->requestParams['password'];
            $this->authorize();
        }
    }

    private function authorize()
    {
        $USER = new \CUser;
        $this->user = $USER;
        $this->auth = $USER->Login($this->username, $this->password);

        return [$this->username, $this->password];
        if (isset($this->auth['TYPE']) && $this->auth['TYPE'] === 'ERROR') {
            throw new RestException(
                $this->auth['MESSAGE'],
                RestException::ERROR_OAUTH,
                self::STATUS_UNAUTHORIZED
            );
        }
    }

    function returnError()
    {
        $error = [
            'code' => $this->error->getErrorCode(),
            'message' => $this->error->getMessage(),
            'test' => 123
        ];

        return $error;
    }

    function returnSuccess($data)
    {
        return $data;
    }

    function actionIndex()
    {
        if (isset($this->error)) {
            return $this->returnError();
        }

        try {
            switch ($this->action) {
                case "getToken":
                    $result = $this->authUsers();
                    break;
                default:
                    return $this->requestParams;
            }

            return $this->returnSuccess($result);
        } catch (Exception $e) {
            $this->error = $e;
            header("HTTP/1.1 " . $this->error->getStatus());
            return $this->returnError();
        }
    }

    public function authUsers()
    {
        $user = $this->getUserByLogin($this->username);

        $code = "blinovav_extensionrest";
        $ver = 1;
        if (!empty($user)) {
            $userId = $user['ID'];
        }

        $arAppsFromDB = $this->isAppTableExist($code);

        if (empty($arAppsFromDB)) {
            $arAppsCreated = $this->addAppTable($code, $ver);
            $arAppsFromDB = $this->isAppTableExist($code);
        }

        $arResult['AUTH'] = \Bitrix\Rest\Application::getAuthProvider()->get(
            $arAppsFromDB["CODE"],
            $code,
            array(),
            $userId
        );

        $result = array(
            "access_token" => $arResult['AUTH']["access_token"],
            "refresh_token" => $arResult['AUTH']["refresh_token"],
            "id" => $arResult['AUTH']['user_id'],
        );
        return $result;
    }

    private function isAppTableExist($code)
    {
        $dbApps = \Bitrix\Rest\AppTable::getList(array(
            'filter' => array('=APP_NAME' => $code),
            'select' => array('APP_NAME', "CODE", "CLIENT_ID")
        ));

        $arAppsFromDB = array();
        while ($arApp = $dbApps->Fetch()) {
            $arAppsFromDB = $arApp;
        }

        return $arAppsFromDB;
    }

    private function addAppTable($code, $ver)
    {
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
            'VERSION' => $ver,
            'SCOPE' => $code,
            'STATUS' => "L",
            'CLIENT_SECRET' => '',
            'APP_NAME' => $code,
            'MOBILE' => \Bitrix\Rest\AppTable::INACTIVE,
            'USER_INSTALL' => \Bitrix\Rest\AppTable::INACTIVE,
        );

        $result = \Bitrix\Rest\AppTable::add($appFields);

        $arAppsFromDB = $result->getData();

        return $arAppsFromDB;
    }

    private function getUserByLogin($login)
    {
        $USER = new \CUser;
        $rsUser = $USER->GetByLogin($login);
        $arUser = $rsUser->Fetch();

        return $arUser;
    }
}
