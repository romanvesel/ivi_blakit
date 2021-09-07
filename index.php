<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Мебельная компания");
?>
    <!-- top-section start-->
    <section class="section top-section" id="code">
        <div class="top-section__bg" style="background-image: url('<?=SITE_TEMPLATE_PATH?>/img/main-bg.jpg');"></div>
        <div class="wrapper top-section__wrapper"><a class="logo animate" href=""><img class="img--full img--center   " src="<?=SITE_TEMPLATE_PATH?>/img/icons/logo.svg" alt="Онлайн-кинотеатр ivi - фильмы, сериалы и мультфильмы смотреть онлайн бесплатно в хорошем качестве" title="Онлайн-кинотеатр ivi - фильмы, сериалы и мультфильмы смотреть онлайн бесплатно в хорошем качестве" draggable="false" loading="lazy"></a>
            <div class="top-section__title animate">
                <h1>Смотри <span style="color:#ec174f;">ivi</span> вместе с <span style="color:#32376f;">BLAKIT</span> </h1>
            </div>
            <form class="top-form animate"  method="POST">
                <label class="top-form__label label" for="top-name"> Ваше имя:</label>
                <input class="top-form__input input" id="top-name" type="text" name="name" required placeholder="Введите ваше имя">
                <label class="top-form__label label" for="top-phone">Номер телефона:</label>
                <input class="top-form__input input" id="top-phone" type="tel" name="phone" required>
                <label class="top-form__label label" for="top-city"> Город:</label>
                <input class="top-form__input input" id="top-city" type="text" name="city" required placeholder="Введите название вашего города">
                <label class="top-form__label label" for="top-password"> Код:</label><span class="top-form__input-wrap input-wrap">
              <input class="top-form__input input" id="top-password" type="password" name="pass" required placeholder="Введите код">
              <label class="show-pass" for="top-password"></label></span><span class="top-form__checkbox custom-checkbox">
              <input class="custom-checkbox__input" id="top-agree" type="checkbox" name="agree" required>
              <label class="custom-checkbox__checker top-form__checker" for="top-agree"></label>
              <label class="custom-checkbox__label top-form__label" for="top-agree">Я согласен с условиями акций и рассылок</label></span>
                <button class="top-form__btn btn" id="send_form" type="submit">Отправить</button>
            </form>
        </div>
    </section>
    <!-- top-section end-->
    <!--rule-section start-->
    <section class="section rule-section" id="rule">
    	    <?
$APPLICATION->IncludeFile(
	SITE_DIR."include/how_to_play.php",
	Array(),
	Array("MODE"=>"html")
);
?>

    </section>
    <!--rule-section end-->
    <!--prize-section start-->
    <section class="section prize-section" id="prize">
        <?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"Prize",
	array(
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "Y",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "Y",
		"FIELD_CODE" => array(
			0 => "ID",
			1 => "",
		),
		"FILE_404" => "",
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "Y",
		"IBLOCK_ID" => "6",
		"IBLOCK_TYPE" => "-",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"INCLUDE_SUBSECTIONS" => "Y",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK" => "",
		"PAGER_BASE_LINK_ENABLE" => "Y",
		"PAGER_DESC_NUMBERING" => "Y",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_PARAMS_NAME" => "arrPager",
		"PAGER_SHOW_ALL" => "Y",
		"PAGER_SHOW_ALWAYS" => "Y",
		"PAGER_TEMPLATE" => "",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => array(
			0 => "PAGE_PRIZ_LINK",
			1 => "",
			2 => "DESCRIPTION",
			3 => "",
		),
		"SET_BROWSER_TITLE" => "Y",
		"SET_LAST_MODIFIED" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_STATUS_404" => "Y",
		"SET_TITLE" => "Y",
		"SHOW_404" => "Y",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "DESC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "Prize"
	),
	false
);?>

	 <?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"Brand",
	array(
		"ACTIVE_DATE_FORMAT" => "d.m.Y",
		"ADD_SECTIONS_CHAIN" => "Y",
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "Y",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "Y",
		"FIELD_CODE" => array(
			0 => "ID",
			1 => "",
		),
		"FILE_404" => "",
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "Y",
		"IBLOCK_ID" => "7",
		"IBLOCK_TYPE" => "-",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "Y",
		"INCLUDE_SUBSECTIONS" => "Y",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "20",
		"PAGER_BASE_LINK" => "",
		"PAGER_BASE_LINK_ENABLE" => "Y",
		"PAGER_DESC_NUMBERING" => "Y",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_PARAMS_NAME" => "arrPager",
		"PAGER_SHOW_ALL" => "Y",
		"PAGER_SHOW_ALWAYS" => "Y",
		"PAGER_TEMPLATE" => "",
		"PAGER_TITLE" => "Новости",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "",
		"PROPERTY_CODE" => array(
			0 => "PAGE_LINK",
			1 => "DESCRIPTION",
			2 => "",
		),
		"SET_BROWSER_TITLE" => "Y",
		"SET_LAST_MODIFIED" => "Y",
		"SET_META_DESCRIPTION" => "Y",
		"SET_META_KEYWORDS" => "Y",
		"SET_STATUS_404" => "Y",
		"SET_TITLE" => "Y",
		"SHOW_404" => "Y",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "DESC",
		"SORT_ORDER2" => "ASC",
		"STRICT_SECTION_CHECK" => "N",
		"COMPONENT_TEMPLATE" => "Brand"
	),
	false
);?>

      </section>
    <!--prize-section end-->


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>