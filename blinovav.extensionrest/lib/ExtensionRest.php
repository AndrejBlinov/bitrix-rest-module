<?

namespace Blinovav\Extensionrest;

use \Bitrix\Rest\RestException;

class ExtensionRest extends \IRestService
{

    public static function OnRestServiceBuildDescription()
    {
        $className = "blinovav_extensionrest";
        return array(
            $className => array(
                $className . '.extension' => array(
                    'callback' => array(__CLASS__, 'extension'),
                    'options' => array(),
                ),
            )
        );
    }

    public static function extension($query, $nav, \CRestServer $server)
    {
        switch ($query['action']) {
            case "test":
                return true;
        }
    }
}
