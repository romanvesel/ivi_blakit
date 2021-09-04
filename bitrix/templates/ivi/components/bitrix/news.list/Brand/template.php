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
 <h3 class="section__title __white-color collection__title animate scroll">Бренды и коллекции</h3>
        <div class="collection-slider swiper-container animate scroll" id="collection">
            <div class="collection-slides swiper-wrapper">
               <?foreach($arResult["ITEMS"] as $arItem):?>
                <? /*echo "<pre>"; print_r($arItem['PREVIEW_PICTURE']['ALT']); echo "</pre>";*/?>
	               <div class="collection-slide swiper-slide">
	               	<? if (!empty($arItem['PROPERTIES']['PAGE_LINK']['VALUE'])){?>
	               		<a href="<?=$arItem['PROPERTIES']['PAGE_LINK']['VALUE']?>">
	               		    <img class="img--full img--center   " src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?=$arItem['PREVIEW_PICTURE']['ALT']?>" title="<?=$arItem['PREVIEW_PICTURE']['TITLE']?>" draggable="false" loading="lazy">
	                	</a>
	                <?}
	                	else{?>
	                	<img class="img--full img--center   " src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?=$arItem['PREVIEW_PICTURE']['ALT']?>" title="<?=$arItem['PREVIEW_PICTURE']['TITLE']?>" draggable="false" loading="lazy"><?
	                	}?>
	                </div>
               <?endforeach;?>
            </div>
        </div>