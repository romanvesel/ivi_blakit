<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule('iblock');

$arSelect = Array("ID", "IBLOCK_ID", "NAME");
$arFilter = Array("IBLOCK_ID"=>9, "ACTIVE"=>"Y");
$res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
$cupons = array();
while($ob = $res->GetNextElement()) {
    $arFields = $ob->GetFields();
    $arProps = $ob->GetProperties();

    $cupons[$arProps["KEY"]["VALUE"]] = [
        "ID" => $arFields["ID"],
        "KEY" => $arProps["KEY"]["VALUE"],
        "CUPON" => $arProps["CUPON"]["VALUE"],
    ];
}


foreach ($cupons as $key=>$cupon) {
    $el = new CIBlockElement;
    $PROP = array();
    $PROP["INPUT_NAME"] = $_POST['name'];
    $PROP["INPUT_PHONE"] = $_POST['phone'];
    $PROP["INPUT_CITY"] = $_POST['city'];
    $PROP["INPUT_CODE"] = $_POST['pass'];
    $PROP["INPUT_CUPON"] = $cupon["CUPON"];
    if ($_POST['pass'] == $key) {
        echo 'ok';
        if (!empty($_POST['name']) &&
            !empty($_POST['phone']) &&
            !empty($_POST['city']) &&
            !empty($_POST['pass']) &&
            $_POST['pass'] == $key &&
            $_POST['agree'] == "on") {
            $arLoadProductArray = array(
                "IBLOCK_ID" => 5,
                "PROPERTY_VALUES" => $PROP,
                "NAME" => $cupon['CUPON'],
                "ACTIVE" => "Y",
            );



            $phone = preg_replace("/[^0-9]/", "", $_POST['phone']);

            $url = 'http://cp.websms.by/?r=api%2Fmsg_send&user=markprodvblakit%40gmail.com&apikey=h12QRIQdde&recipients='.$phone.'&message=Ваш%20купон:%20'.$cupon['CUPON'].'&sender=BLAKIT.BY&urgent=1';

            $ch = curl_init($url);

            curl_exec($ch);

            if (!curl_errno($ch)) {
                $info = curl_getinfo($ch);
                echo 'Прошло ', $info['total_time'], ' секунд во время запроса к ', $info['url'], "\n";
            }

            curl_close($ch);

            $arLoadProductArrays = array("ACTIVE" => "N");
            $res = $el->Update($cupon["ID"], $arLoadProductArrays);



        } else {
            echo "no";
        }

        if ($qID = $el->Add($arLoadProductArray)) echo "ok";


    }
}
?>