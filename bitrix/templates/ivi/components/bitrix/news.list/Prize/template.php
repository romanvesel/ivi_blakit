<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<div class="prize-section__bg" style="background-image: url('<?=SITE_TEMPLATE_PATH?>/img/main-bg.jpg');"></div>
<h2 class="section__title prize-section__title animate scroll">Призы</h2>
<div class="prize-slider swiper-container animate scroll">
    <div class="prize-slides swiper-wrapper">
        <?foreach($arResult["ITEMS"] as $arItem):?>
        <a class="prize-slide swiper-slide" href="<?=$arItem['PROPERTIES']['PAGE_PRIZ_LINK']['VALUE']?>">
            <img class="img--full img--center" src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?=$arItem['PREVIEW_PICTURE']['ALT']?>" title="<?=$arItem['PREVIEW_PICTURE']['TITLE']?>" draggable="false" loading="lazy">
        </a>
        <?endforeach;?>
    </div>
</div>

