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

$fl = new \Bitrix\EsolImportxml\FieldList($SETTINGS_DEFAULT);

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
	echo '<script>EIXPreview.SetGroupSettings("'.htmlspecialcharsbx($map).'", "PROPERTY")</script>';

	die();
}

/*$xmlViewer = new \Bitrix\EsolImportxml\XMLViewer();
$availableTags=array();
$xmlViewer->GetAvailableTags($availableTags, $xpath, $arStuct);*/

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_popup_admin.php");
//print_r($_POST);
$xmlViewer = new \Bitrix\EsolImportxml\XMLViewer($SETTINGS_DEFAULT['URL_DATA_FILE'], $SETTINGS_DEFAULT);
$arXmlProps = $xmlViewer->GetPropertyList($_POST['XPATH'], $_POST['FIELDS']);

$arGroupFields = $fl->GetFieldsForPropMapping($IBLOCK_ID);
?>
<form action="" method="post" enctype="multipart/form-data" name="field_settings">
	<input type="hidden" name="action" value="save">
	<div style="display: none;">
		<select name="section">
			<option value=""><?echo GetMessage("ESOL_IX_NOT_CHOOSE");?></option><?
			foreach($arGroupFields as $k2=>$v2)
			{
				?><optgroup label="<?echo $v2['title']?>"><?
				foreach($v2['items'] as $k=>$v)
				{
					$arFields[$k] = ($v2['title'] ? $v2['title'].' - ' : '').$v;
					?><option value="<?echo $k; ?>" <?if($k==$value){echo 'selected';}?>><?echo htmlspecialcharsbx($v); ?></option><?
				}
				?></optgroup><?
			}
			?>
		</select>
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
			<td class="adm-detail-content-cell-l"><?echo GetMessage("ESOL_IX_PROPERTY_NOT_CREATE");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="MAP[PROPERTY_NOT_CREATE]" value="Y" <?=($arParams['PROPERTY_NOT_CREATE']=='Y' ? 'checked' : '')?>>
			</td>
		</tr>
		
		<tr>
			<td class="adm-detail-content-cell-l"><?echo GetMessage("ESOL_IX_PROPERTY_NOT_LOAD_WO_MAPPED");?>:</td>
			<td class="adm-detail-content-cell-r">
				<input type="checkbox" name="MAP[NOT_LOAD_WO_MAPPED]" value="Y" <?=($arParams['NOT_LOAD_WO_MAPPED']=='Y' ? 'checked' : '')?>>
			</td>
		</tr>

		<tr class="heading">
			<td colspan="2">
				<?echo GetMessage("ESOL_IX_PROPERTY_MAPPING_TITLE");?>
			</td>
		</tr>
		
	<tr>
	  <td colspan="2">
		<?
		if(!is_array($arXmlProps)) echo GetMessage("ESOL_IX_PROPERTY_NOT_CHOOSE_FIELDS");
		elseif(count($arXmlProps)==0) echo GetMessage("ESOL_IX_PROPERTY_NO_STRUCT");
		else
		{
		?>
		<table width="100%" border="1" cellpadding="5">
		<col width="50%">
		<col width="50%">
		<tr>
			<th><? echo GetMessage("ESOL_IX_PROPERTY_IN_FILE");?></th>
			<th><? echo GetMessage("ESOL_IX_PROPERTY_ON_SITE");?></th>
		</tr>
		<?
		$arMap2 = array();
		foreach($arMap as $k=>$v)
		{
			if(!array_key_exists($v['XML_ID'], $arMap2)) $arMap2[$v['XML_ID']] = array();
			$arMap2[$v['XML_ID']][] = $v['ID'];
		}
		$index = 0;
		foreach($arXmlProps as $xmlId=>$arXmlProp){?>
			<tr>
				<td><?echo $arXmlProp['NAME'];?></td>
				<td>
					<div class="esol-ix-select-mapping" data-xml-id="<?echo htmlspecialcharsbx(trim($xmlId))?>">
						<?
						$name = GetMessage("ESOL_IX_NOT_CHOOSE");
						if(array_key_exists($xmlId, $arMap2))
						{
							$val = current($arMap2[$xmlId]);
							if(array_key_exists($val, $arFields))
							{
								echo '<input id="esol_mapping_'.$index.'" type="hidden" name="MAP[MAP]['.$index.'][XML_ID]" value="'.htmlspecialcharsbx($xmlId).'"><input type="hidden" name="MAP[MAP]['.$index.'][ID]" value="'.htmlspecialcharsbx($val).'">';
								$name = $arFields[$val];
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