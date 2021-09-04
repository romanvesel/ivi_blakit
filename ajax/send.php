<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if(CModule::IncludeModule("iblock")){
    $el = new CIBlockElement;
    $PROP = array();
    $PROP["INPUT_NAME"] = $_POST['name'];
    $PROP["INPUT_PHONE"] = $_POST['phone'];
    $PROP["INPUT_CITY"] = $_POST['city'];
    $PROP["INPUT_CODE"] = $_POST['pass'];
    if (!empty($_POST['name']) &&
        !empty($_POST['phone']) &&
        !empty($_POST['city']) &&
        !empty($_POST['pass']) &&
        $_POST['agree'] == "on") {
        $arLoadProductArray = array(
            "IBLOCK_ID" => 5,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => $_POST['pass'],
            "ACTIVE" => "Y",
        );
    }
    else{
        echo "Заполните все поля!";
    }
    if ($qID = $el->Add($arLoadProductArray)) echo "ok";
}

?>