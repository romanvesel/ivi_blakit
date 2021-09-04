<!DOCTYPE html>
<html lang="ru">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
      <meta http-equiv="x-ua-compatible" content="IE=edge">
      <meta name="description" content="Устройте кинотеатр у себя дома! Смотрите онлайн фильмы хорошего качества в приятной домашней обстановке и в удобное для вас время. Для вас всегда доступны бесплатные фильмы без регистрации на любой вкус: сериалы, фильмы, мультфильмы и многое другое.">
      <meta name="keywords" content="фильмы онлайн бесплатно в хорошем отличном качестве без смс кино видео смотреть без регистрации новинки кинофильмы онлайн кинотеатр 2012 2013 просмотр видеоролики">
      <meta property="og:type" content="website">
      <meta property="og:site_name" content="ivi.blakit">
      <meta property="og:title" content="Онлайн-кинотеатр ivi - фильмы, сериалы и мультфильмы смотреть онлайн бесплатно в хорошем качестве">
      <meta property="og:description" content="Устройте кинотеатр у себя дома! Смотрите онлайн фильмы хорошего качества в приятной домашней обстановке и в удобное для вас время. Для вас всегда доступны бесплатные фильмы без регистрации на любой вкус: сериалы, фильмы, мультфильмы и многое другое.">
      <meta property="og:url" content="http://ivi.betahon.by">
      <meta property="og:locale" content="ru_RU">
      <meta property="og:image" content="<?=SITE_TEMPLATE_PATH?>/img/og.png">
      <meta property="og:image:width" content="968">
      <meta property="og:image:height" content="500">
      <link rel="canonical" href="http://ivi.betahon.by">
      <link rel="apple-touch-icon" sizes="57x57" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-57x57.png">
      <link rel="apple-touch-icon" sizes="60x60" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-60x60.png">
      <link rel="apple-touch-icon" sizes="72x72" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-72x72.png">
      <link rel="apple-touch-icon" sizes="76x76" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-76x76.png">
      <link rel="apple-touch-icon" sizes="114x114" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-114x114.png">
      <link rel="apple-touch-icon" sizes="120x120" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-120x120.png">
      <link rel="apple-touch-icon" sizes="144x144" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-144x144.png">
      <link rel="apple-touch-icon" sizes="152x152" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-152x152.png">
      <link rel="apple-touch-icon" sizes="180x180" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/apple-icon-180x180.png">
      <link rel="icon" type="image/png" sizes="192x192" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/android-icon-192x192.png">
      <link rel="icon" type="image/png" sizes="32x32" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/favicon-32x32.png">
      <link rel="icon" type="image/png" sizes="96x96" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/favicon-96x96.png">
      <link rel="icon" type="image/png" sizes="16x16" href="<?=SITE_TEMPLATE_PATH?>/img/favicons/favicon-16x16.png">
      <meta name="msapplication-TileImage" content="<?=SITE_TEMPLATE_PATH?>/img/favicons/ms-icon-144x144.png">
      <meta name="msapplication-TileColor" content="#ffffff">
      <meta name="theme-color" content="#ffffff">
      <meta name="apple-mobile-web-app-title" content="Название">
      <meta name="apple-mobile-web-app-capable" content="yes">
      <meta name="format-detection" content="telephone=no">
      <meta name="format-detection" content="address=no">
   <!--   <link rel="stylesheet" href="css/main.min.css">-->
      <title>Онлайн-кинотеатр ivi - фильмы, сериалы и мультфильмы смотреть онлайн бесплатно в хорошем качестве</title>
        <?$APPLICATION->SetAdditionalCSS("/dist/css/main.min.css", true);?>
        <? $APPLICATION->ShowHead(); ?>
    </head>

  <body class="--fixed __loading">
  <div id="panel" style="width: 100%"><? $APPLICATION->ShowPanel(); ?></div>
    <div class="preloader">
      <div class="preloader__logo"><img src="<?=SITE_TEMPLATE_PATH?>/img/icons/logo.svg" alt="ОАО Брестский мясокомбинат  - производитель и поставщик колбасных изделий, мясных полуфабрикатов из Республики Беларусь" title="ОАО Брестский мясокомбинат  - производитель и поставщик колбасных изделий, мясных полуфабрикатов из Республики Беларусь" draggable="false" loading="lazy"></div>
      <progress class="preloader__progress" value="0" max="100"> </progress>
    </div>
    <header class="header" id="header">
              <?$APPLICATION->IncludeComponent("bitrix:menu","template",Array(
                      "ROOT_MENU_TYPE" => "top",
                      "MAX_LEVEL" => "1",
                      "CHILD_MENU_TYPE" => "top",
                      "USE_EXT" => "Y",
                      "DELAY" => "N",
                      "ALLOW_MULTI_SELECT" => "Y",
                      "MENU_CACHE_TYPE" => "N",
                      "MENU_CACHE_TIME" => "3600",
                      "MENU_CACHE_USE_GROUPS" => "Y",
                      "MENU_CACHE_GET_VARS" => ""
                  )
              );?>
    </header>
    <main class="main page-main mt__nan">