<?
if(!defined('NO_AGENT_CHECK')) define('NO_AGENT_CHECK', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/prolog.php");
$moduleId = 'esol.importxml';
CModule::IncludeModule('iblock');
CModule::IncludeModule($moduleId);
$bCurrency = CModule::IncludeModule("currency");
IncludeModuleLangFile(__FILE__);

$MODULE_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if($MODULE_RIGHT < "W") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

if($_POST['action']!='save') CUtil::JSPostUnescape();

$oProfile = new \Bitrix\EsolImportxml\Profile();
$oProfile->Apply($SETTINGS_DEFAULT, $SETTINGS, $_REQUEST['PROFILE_ID']);
$oProfile->ApplyExtra($PEXTRASETTINGS, $_REQUEST['PROFILE_ID']);

$IBLOCK_ID = $SETTINGS_DEFAULT['IBLOCK_ID'];

$fl = new \Bitrix\EsolImportxml\FieldList();


$arParams = array();
$arMap = array();
if(isset($_POST['MAP']))
{
	$arParams = unserialize(base64_decode($_POST['MAP']));
	if(!is_array($arParams)) $arParams = array();
	if(isset($arParams['MAP']) && is_array($arParams['MAP'])) $arMap = $arParams['MAP'];
}

if($_POST['action']=='save' && is_array($_POST['MAP']))
{
	define('PUBLIC_AJAX_MODE', 'Y');
	$APPLICATION->RestartBuffer();
	if(ob_get_contents()) ob_end_clean();
	
	$map = base64_encode(serialize($_POST['MAP']));
	echo '<script>EIXPreview.SetGroupSettings("'.htmlspecialcharsbx($map).'", "SECTION")</script>';

	die();
}

/*$xmlViewer = new \Bitrix\EsolImportxml\XMLViewer();
$availableTags=array();
$xmlViewer->GetAvailableTags($availableTags, $xpath, $arStuct);*/

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
//print_r($_POST);
$xmlViewer = new \Bitrix\EsolImportxml\XMLViewer($SETTINGS_DEFAULT['URL_DATA_FILE'], $SETTINGS_DEFAULT);
$arXmlSections = $xmlViewer->GetSectionStruct($_POST['XPATH'], $_POST['FIELDS'], $_POST['INNER_GROUPS']);

$arSections = array();
$dbRes = \CIblockSection::GetList(array('LEFT_MARGIN'=>'ASC'), array('IBLOCK_ID'=>$IBLOCK_ID), false, array('ID', 'IBLOCK_SECTION_ID', 'NAME'));
while($arr = $dbRes->Fetch())
{
	$name = $arr['NAME'];
	$parentId = $arr['IBLOCK_SECTION_ID'];
	if($parentId && array_key_exists($parentId, $arSections)) $name = $arSections[$parentId].' / '.$name;
	$arSections[$arr['ID']] = $name;
}
$sectionSelect = '<select name="section"><option value="">'.htmlspecialcharsbx(GetMessage("ESOL_IX_NOT_CHOOSE")).'</option>';
foreach($arSections as $k=>$v)
{
	$sectionSelect .= '<option value="'.htmlspecialcharsbx($k).'">'.htmlspecialcharsbx($v).'</option>';
}
$sectionSelect .= '</select>';
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<div style="display: none;">
		<?echo $sectionSelect;?>
	</div>
	
	<?
	/*echo BeginNote();
	echo GetMessage("ESOL_IX_SECTION_MAPPING_NOTE");
	echo EndNote();*/
	?>

	<table width="100%">
		<col width="50%">
		<col width="50%">
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("ESOL_IX_SECTION_LOAD_MODE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="radio" name="MAP[SECTION_LOAD_MODE]" value="" <?=(!isset($arParams['SECTION_LOAD_MODE']) || strlen($arParams['SECTION_LOAD_MODE'])==0 ? 'checked' : '')?> id="esol_slm_default"><label for="esol_slm_default"><?echo GetMessage("ESOL_IX_SECTION_LOAD_MODE_DEFAULT");?></label><br>
				<input type="radio" name="MAP[SECTION_LOAD_MODE]" value="MAPPED" <?=($arParams['SECTION_LOAD_MODE']=='MAPPED' ? 'checked' : '')?> id="esol_slm_mapped"><label for="esol_slm_mapped"><?echo GetMessage("ESOL_IX_SECTION_LOAD_MODE_MAPPED");?></label><br>
				<input type="radio" name="MAP[SECTION_LOAD_MODE]" value="MAPPED_CHILD" <?=($arParams['SECTION_LOAD_MODE']=='MAPPED_CHILD' ? 'checked' : '')?> id="esol_slm_mapped_child"><label for="esol_slm_mapped_child"><?echo GetMessage("ESOL_IX_SECTION_LOAD_MODE_MAPPED_CHILD");?></label>
			</td>
		</tr>


		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("ESOL_IX_SECTION_MAPPING_TITLE");?>
			</td>
		</tr>
		
	<tr>
	  <td colspan="2">
		<?
		if(!is_array($arXmlSections)) echo GetMessage("ESOL_IX_SECTION_NOT_CHOOSE_FIELDS");
		elseif(count($arXmlSections)==0) echo GetMessage("ESOL_IX_SECTION_NO_STRUCT");
		else
		{
		?>
		<table width="100%" border="1" cellpadding="5">
		<col width="50%">
		<col width="50%">
		<tr>
			<th><? echo GetMessage("ESOL_IX_SECTION_IN_FILE");?></th>
			<th><? echo GetMessage("ESOL_IX_SECTION_ON_SITE");?></th>
		</tr>
		<?
		$arMap2 = array();
		foreach($arMap as $k=>$v)
		{
			if(!array_key_exists($v['XML_ID'], $arMap2)) $arMap2[$v['XML_ID']] = array();
			$arMap2[$v['XML_ID']][] = $v['ID'];
		}
		$index = 0;
		foreach($arXmlSections as $xmlId=>$arXmlSection){?>
			<tr>
				<td><?echo $arXmlSection['NAME'];?></td>
				<td>
					<div class="esol-ix-select-mapping" data-xml-id="<?echo htmlspecialcharsbx(trim($xmlId))?>">
						<?
						$name = GetMessage("ESOL_IX_NOT_CHOOSE");
						if(array_key_exists($xmlId, $arMap2))
						{
							$val = current($arMap2[$xmlId]);
							if(array_key_exists($val, $arSections))
							{
								echo '<input id="esol_mapping_'.$index.'" type="hidden" name="MAP[MAP]['.$index.'][XML_ID]" value="'.htmlspecialcharsbx($xmlId).'"><input type="hidden" name="MAP[MAP]['.$index.'][ID]" value="'.htmlspecialcharsbx($val).'">';
								$name = $arSections[$val];
								$index++;
							}
						}
						?>
						<a href="javascript:void(0)" onclick="ESettings.ShowSelectMapping(this)"><?echo $name;?></a>
					</div>
				</td>
			</tr>
		<?}?>
		</table>
		<?}?>
	  </td>
	</tr>
	</table>
</form>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>