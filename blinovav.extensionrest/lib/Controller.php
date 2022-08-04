<?php

namespace Blinovav\Extensionrest;

abstract class Controller
{
    protected $requestParams;
    protected $method;

    function __construct()
    {
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestParams = $_REQUEST;

        if (count($_REQUEST) === 0 || count($_REQUEST) === 1) {
            $file_get_cont = file_get_contents('php://input');

            if ($file_get_cont !== '') {
                $params = json_decode($file_get_cont, true);

                if (isset($params)) {
                    $this->requestParams = $params;
                } else {
                    $this->log(['file_get_contents' => $file_get_cont, '_REQUEST' => $_REQUEST], "Can't decode JSON at __construct method Controller Class");
                }
            }
        }
    }

    public function print($arr, $isJson = false, $mustDie = false)
    {
        if ($isJson) {
            echo json_encode($arr);
        } else {
            echo '<pre>' . print_r($arr, true) . '</pre>';
        }

        if ($mustDie) die();
    }

    public function getError()
    {
        return $this->error;
    }

    public function log($data, $name)
    {
        //---------------------------LOG
        $fp = fopen('./' . date("Y-m-d") . '-Controller.log', 'a');
        $data = date("Y-m-d H:i:s") . " " . $name . "   " . print_r($data, true);
        fwrite($fp, $data);
        fwrite($fp, "\r\n");
        //---------------------------END LOG
        fclose($fp);
    }

    abstract public function actionIndex();
}
