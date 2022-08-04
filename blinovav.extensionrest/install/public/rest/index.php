<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>

<?$APPLICATION->IncludeComponent(
  "bitrix:rest.provider",
  "",
  Array(
     "CLASS" => "",
     "SEF_FOLDER" => "/",
     "SEF_MODE" => "Y",
     "SEF_URL_TEMPLATES" => Array(
        "path" => "#method#"
     )
  )
);?> 