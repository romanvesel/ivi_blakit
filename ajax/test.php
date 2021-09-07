<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Highloadblock\HighloadBlockTable as HLBT;
CModule::IncludeModule('highloadblock');
CModule::IncludeModule('iblock');

$highblock_id = 1;
$hl_block = HLBT::getById($highblock_id)->fetch();

// Получение имени класса
$entity = HLBT::compileEntity($hl_block);
$entity_data_class = $entity->getDataClass();

// Вывод элементов Highload-блока
$rs_data = $entity_data_class::getList(array(
    'select' => array('*')
));
$cupons = array();
while ($el = $rs_data->fetch()){

    $cupons[$el["UF_CODE"]] = [
        "ID" => $el["ID"],
        "KEY" => $el["UF_CODE"],
        "CUPON" => $el["UF_CUPONS"],
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

           /* $arLoadProductArrays = array("ACTIVE" => "N");
            $res = $el->Update($cupon["ID"], $arLoadProductArrays);*/



        } else {
            echo "no";
        }

        if ($qID = $el->Add($arLoadProductArray)) echo "ok";


    }
}
