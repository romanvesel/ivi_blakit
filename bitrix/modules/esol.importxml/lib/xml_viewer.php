<?php
namespace Bitrix\EsolImportxml;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class XMLViewer 
{
	protected $arXPathsMulti = array();
	
	public function __construct($DATA_FILE_NAME='', $SETTINGS_DEFAULT=array())
	{
		$this->filename = $_SERVER['DOCUMENT_ROOT'].$DATA_FILE_NAME;
		$this->params = $SETTINGS_DEFAULT;
		$this->fileEncoding = 'utf-8';
		$this->siteEncoding = \Bitrix\EsolImportxml\Utils::getSiteEncoding();
		//$this->fl = new \Bitrix\EsolImportxml\FieldList($SETTINGS_DEFAULT);
	}
	
	public function GetXPathsMulti()
	{
		return $this->arXPathsMulti;
	}
	
	public function GetFileStructure()
	{
		$this->arXPathsMulti = array();
		$file = $this->filename;
		//$arXml = simplexml_load_file($file);
		$arXml = $this->getLigthSimpleXml($file);
		$arStruct = array();
		$this->GetStructureFromSimpleXML($arStruct, $arXml);
		
		if($this->siteEncoding!=$this->fileEncoding)
		{
			$arStruct = \Bitrix\Main\Text\Encoding::convertEncodingArray($arStruct, $this->fileEncoding, $this->siteEncoding);
		}
		
		return $arStruct;
	}
	
	public function getLigthSimpleXml($fn)
	{
		if(!file_exists($fn))
		{
			return new \SimpleXMLElement('<d></d>');
		}

		if(!class_exists('\XMLReader'))
		{
			return simplexml_load_file($fn);
		}
		
		$xml = new \XMLReader();
		$res = $xml->open($fn);

		$arObjects = array();
		$arObjectNames = array();
		$arXPaths = array();
		$arValues = array();
		$arXPathsMulti = array();
		$curDepth = 0;
		$isRead = false;
		$maxTime = 10;
		$beginTime = time();
		while(($isRead || $xml->read()) && $endTime-$beginTime < $maxTime) 
		{
			$isRead = false;
			if($xml->nodeType == \XMLReader::ELEMENT) 
			{
				$curDepth = $xml->depth;
				$arObjectNames[$curDepth] = $xml->name;
				$extraDepth = $curDepth + 1;
				while(isset($arObjectNames[$extraDepth]))
				{
					unset($arObjectNames[$extraDepth]);
					$extraDepth++;
				}
				$xPath = implode('/', $arObjectNames);
				
				$arAttributes = array();
				if($xml->moveToFirstAttribute())
				{
					if(!isset($arXPaths[$xPath.'/@'.$xml->name]))
					{
						$arXPaths[$xPath.'/@'.$xml->name] = $xPath.'/@'.$xml->name;
						$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
					}
					while($xml->moveToNextAttribute ())
					{
						if(!isset($arXPaths[$xPath.'/@'.$xml->name]))
						{
							$arXPaths[$xPath.'/@'.$xml->name] = $xPath.'/@'.$xml->name;
							$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
						}
					}
				}
				$xml->moveToElement();
				$xmlName = $xml->name;
				$xmlNamespaceURI = $xml->namespaceURI;
				$xmlValue = null;
				$isSubRead = false;
				while(($xml->read() && ($isSubRead = true)) && ($xml->nodeType == \XMLReader::SIGNIFICANT_WHITESPACE)){}
				if($xml->nodeType == \XMLReader::TEXT || $xml->nodeType == \XMLReader::CDATA)
				{
					$xmlValue = $xml->value;
				}
				else
				{
					$isRead = $isSubRead;
				}
				
				$setObj = false;
				if(!isset($arXPaths[$xPath]) || (isset($xmlValue) && !isset($arValues[$xPath])))
				{
					$setObj = true;
					$arXPaths[$xPath] = $xPath;
					$curName = $xmlName;
					$curValue = null;
					$curNamespace = null;
					$nsPrefix = '';
					if($xmlNamespaceURI && strpos($curName, ':')!==false)
					{
						$curNamespace = $xmlNamespaceURI;
						$nsPrefix = substr($curName, 0, strpos($curName, ':'));
					}
					if(isset($xmlValue))
					{
						$curValue = $xmlValue;
						if(strlen(trim($curValue)) > 0) $arValues[$xPath] = true;
					}

					if($curDepth == 0)
					{
						if(strlen($nsPrefix) > 0)
							$xmlObj = new \SimpleXMLElement('<'.$nsPrefix.':'.$curName.'></'.$nsPrefix.':'.$curName.'>');
						else
							$xmlObj = new \SimpleXMLElement('<'.$curName.'></'.$curName.'>');
						$arObjects[$curDepth] = &$xmlObj;
					}
					else
					{
						$parentXPath = implode('/', array_slice(explode('/', $xPath), 0, -1));
						$parentDepth = $curDepth - 1;
						/*$arObjects[$parentDepth] = $xmlObj->xpath('/'.$parentXPath);
						if(is_array($arObjects[$parentDepth])) $arObjects[$parentDepth] = current($arObjects[$parentDepth]);*/
						if($curNamespace) $xmlObj->registerXPathNamespace($nsPrefix, $curNamespace);
						$arParentObject = $xmlObj->xpath('/'.$parentXPath);
						if(is_array($arParentObject) && !empty($arParentObject))
						{
							$arObjects[$parentDepth] = current($arParentObject);
						}
						/*else
						{
							$arParentPath = explode('/', $parentXPath);
							array_shift($arParentPath);
							$subObj = $xmlObj;
							while((count($arParentPath) > 0) && ($subPath = array_shift($arParentPath)) && isset($subObj->{$subPath}))
							{
								$subObj = $subObj->{$subPath};
							}
							if(empty($arParentPath) && is_object($subObj) && !empty($subObj))
							{
								$arObjects[$parentDepth] = $subObj;
							}
						}*/
						
						$curValue = str_replace('&', '&amp;', $curValue);
						$arObjects[$curDepth] = $arObjects[$parentDepth]->addChild($curName, $curValue, $curNamespace);
					}
				}
				elseif(!isset($arXPathsMulti[$xPath]))
				{
					$arXPathsMulti[$xPath] = true;
				}

				if(!empty($arAttributes))
				{
					if(!$setObj)
					{
						$arObjects[$curDepth] = $xmlObj->xpath('/'.$xPath);
						if(is_array($arObjects[$curDepth])) $arObjects[$curDepth] = current($arObjects[$curDepth]);
					}
					foreach($arAttributes as $arAttr)
					{
						if(!is_object($arObjects[$curDepth])) continue;
						if(strpos($arAttr['name'], ':')!==false && $arAttr['namespaceURI']) $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value'], $arAttr['namespaceURI']);
						else $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value']);
					}
				}
				$endTime = time();
			}
		}
		$xml->close();
		$this->arXPathsMulti = array_keys($arXPathsMulti);
		return $xmlObj;
	}
	
	public function GetStructureFromSimpleXML(&$arStruct, $simpleXML, $level = 0, $nsKey = false)
	{
		if(!($simpleXML instanceof \SimpleXMLElement)) return;
		if($level==0)
		{
			$k = $simpleXML->getName();
			while(count(explode(':', $k)) > 2) $k = substr($k, strpos($k, ':') + 1);
			$arStruct[$k] = array();
			$attrs = $simpleXML->attributes();
			if(!empty($attrs) && $attrs instanceof \Traversable)
			{
				$arStruct[$k]['@attributes'] = array();
				foreach($attrs as $k2=>$v2)
				{
					$arStruct[$k]['@attributes'][$k2] = (string)$v2;
				}
			}
			$this->GetStructureFromSimpleXML($arStruct[$k], $simpleXML, ($level + 1));
			return;
		}
		
		$nss = $simpleXML->getNamespaces(true);
		if($nsKey!==false && isset($nss[$nsKey])) $nss = array($nsKey => $nss[$nsKey]);
		foreach($nss as $key=>$ns)
		{
			foreach($simpleXML->children($ns) as $k=>$v)
			{
				$k = $key.':'.$k;
				
				if(!isset($arStruct[$k]))
				{
					$arStruct[$k] = array();
				}
				$attrs = $v->attributes();
				if(!empty($attrs) && $attrs instanceof \Traversable)
				{
					if(!isset($arStruct[$k]['@attributes']))
					{
						$arStruct[$k]['@attributes'] = array();
					}
					foreach($attrs as $k2=>$v2)
					{
						if(!isset($arStruct[$k]['@attributes'][$k2]))
						{
							$arStruct[$k]['@attributes'][$k2] = (string)$v2;
						}
					}
				}
				if(strlen((string)$v) > 0 && !isset($arStruct[$k]['@value']))
				{
					$arStruct[$k]['@value'] = trim((string)$v);
				}
				if($v instanceof \Traversable)
				{
					$this->GetStructureFromSimpleXML($arStruct[$k], $v, ($level + 1), $key);
				}
			}
		}
		
		//$arCounts = array();
		if($nsKey===false)
		{
			foreach($simpleXML as $k=>$v)
			{
				/*if(!isset($arCounts[$k])) $arCounts[$k] = 0;
				$arCounts[$k]++;*/
				
				if(!isset($arStruct[$k]))
				{
					$arStruct[$k] = array();
				}
				$attrs = $v->attributes();
				if(!empty($attrs) && $attrs instanceof \Traversable)
				{
					if(!isset($arStruct[$k]['@attributes']))
					{
						$arStruct[$k]['@attributes'] = array();
					}
					foreach($attrs as $k2=>$v2)
					{
						if(!isset($arStruct[$k]['@attributes'][$k2]))
						{
							$arStruct[$k]['@attributes'][$k2] = (string)$v2;
						}
					}
				}
				if(strlen((string)$v) > 0 && !isset($arStruct[$k]['@value']))
				{
					$arStruct[$k]['@value'] = trim((string)$v);
				}
				if($v instanceof \Traversable)
				{
					$this->GetStructureFromSimpleXML($arStruct[$k], $v, ($level + 1));
				}
			}
		}
		
		/*foreach($arCounts as $k=>$cnt)
		{
			if(!isset($arStruct[$k]['@count']) || $cnt > $arStruct[$k]['@count'])
			{
				$arStruct[$k]['@count'] = $cnt;
			}
		}*/
		return $arStruct;
	}
	
	public function ShowXmlTag($arStruct)
	{
		foreach($arStruct as $k=>$v)
		{
			echo '<div class="esol_ix_xml_struct_item" data-name="'.htmlspecialcharsex($k).'">';
			echo '&lt;<a href="javascript:void(0)" onclick="EIXPreview.ShowBaseElements(this)" class="esol_ix_open_tag">'.$k.'</a>';
			if(is_array($v) && !empty($v['@attributes']))
			{
				foreach($v['@attributes'] as $k2=>$v2)
				{
					echo ' '.$k2.'="<span class="esol_ix_str_value" data-attr="'.htmlspecialcharsex($k2).'"><span class="esol_ix_str_value_val" title="'.htmlspecialcharsex($v2).'">'.$this->GetShowVal($v2).'</span></span>"';
				}
				unset($v['@attributes']);
			}
			echo '&gt;';
			/*if(is_array($v) && isset($v['@value']))
			{
				echo '<span class="esol_ix_str_value"><span class="esol_ix_str_value_val">'.$this->GetShowVal($v['@value']).'</span></span>';
				unset($v['@value']);
			}*/
			if((is_array($v) && isset($v['@value'])) || empty($v))
			{
				$val = ((is_array($v) && isset($v['@value'])) ? $v['@value'] : '');
				echo '<span class="esol_ix_str_value"><span class="esol_ix_str_value_val" title="'.htmlspecialcharsex($val).'">'.$this->GetShowVal($val).'</span></span>';
			}
			if(is_array($v) && isset($v['@value'])) 
			{
				unset($v['@value']);
			}
			
			if(is_array($v) && !empty($v))
			{
				$this->ShowXmlTagChoose();
				foreach($v as $k2=>$v2)
				{
					if(substr($k2, 0, 1)!='@')
					{
						$this->ShowXmlTag(array($k2=>$v2));
					}
				}
				echo '&lt;/'.$k.'&gt;';
			}
			else
			{
				echo '&lt;/'.$k.'&gt;';
				$this->ShowXmlTagChoose();
			}
			echo '</div>';
		}
	}
	
	public function GetShowVal($v)
	{
		if(strlen(trim($v)) > 50) $v = substr($v, 0, 50).'...';
		elseif(strlen(trim($v)) == 0) $v = '...';
		if($this->params['HTML_ENTITY_DECODE']=='Y')
		{
			$v = html_entity_decode($v);
		}
		$v = htmlspecialcharsex($v);
		return $v;
	}
	
	public function ShowXmlTagChoose()
	{
		//echo '<a href="javascript:void(0)" onclick="" class="esol_ix_dropdown_btn"></a>';
		echo '<span class="esol_ix_group_value"></span>';
	}
	
	public function GetAvailableTags(&$arTags, $path, $arStruct)
	{
		$arTags[$path] = Loc::getMessage("ESOL_IX_VALUE").' '.$path;
		foreach($arStruct as $k=>$v)
		{
			if($k == '@attributes')
			{
				foreach($v as $k2=>$v2)
				{
					$arTags[$path.'/@'.$k2] = Loc::getMessage("ESOL_IX_ATTRIBUTE").' '.$path.'/@'.$k2;
				}
				continue;
			}
			
			if(substr($k, 0, 1)=='@')
			{
				continue;
			}
			
			$this->GetAvailableTags($arTags, $path.'/'.$k, $arStruct[$k]);
		}
	}
	
	public function GetXpathVals($xpath)
	{
		$rows = $this->GetXpathRows($xpath);
		$arVals = array();
		if(is_array($rows))
		{
			$attr = false;
			$arPath = explode('/', $xpath);
			if(strpos($arPath[count($arPath)-1], '@')===0)
			{
				$attr = substr(array_pop($arPath), 1);
			}
			foreach($rows as $row)
			{
				$val = $row;
				if($attr!==false && is_callable(array($val, 'attributes'))) $val = $val->attributes()->{$attr};
				$val = substr((string)$val, 0, 1000);
				if(strlen($val) > 0 && !in_array($val, $arVals))
				{
					$arVals[] = $val;
					if(count($arVals) >= 10000) break;
				}
			}
		}
		elseif($rows!==false)
		{
			$arVals[] = (string)$rows;
		}
		return $arVals;
	}
	
	public function GetXpathRows($xpath, $wChild=false)
	{
		$xpath = trim(trim($xpath), '/');
		if(strlen($xpath) == 0) return;

		if(!class_exists('\XMLReader'))
		{
			$xmlObject = simplexml_load_file($this->filename);
			$rows = $this->Xpath($xmlObject, '/'.$xpath);
			return $rows;
		}
		
		$xpath = preg_replace('/\[\d+\]/', '', $xpath);
		$arXpath = $arXpathOrig = explode('/', trim($xpath, '/'));
		
		$xml = new \XMLReader();
		$res = $xml->open($this->filename);
		
		$arObjects = array();
		$arObjectNames = array();
		$arXPaths = array();
		$curDepth = 0;
		$isRead = false;
		$break = false;
		while(($isRead || $xml->read()) && !$break) 
		{
			$isRead = false;
			if($xml->nodeType == \XMLReader::ELEMENT) 
			{
				$curDepth = $xml->depth;
				$arObjectNames[$curDepth] = $xml->name;
				$extraDepth = $curDepth + 1;
				while(isset($arObjectNames[$extraDepth]))
				{
					unset($arObjectNames[$extraDepth]);
					$extraDepth++;
				}
				
				$curXPath = implode('/', $arObjectNames);
				$curXPath = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($curXPath, $this->fileEncoding, $this->siteEncoding);
				if(strpos($xpath.'/', $curXPath.'/')!==0 && strpos($curXPath.'/', $xpath.'/')!==0)
				{
					/*if(isset($arObjects[$curDepth]) && !in_array(implode('/', array_slice($arXpathOrig, 0, $curDepth+1)), $this->xpathMulti))
					{
						$break = true;
					}*/
					continue;
				}
				if(strlen($curXPath)>strlen($xpath) && !$wChild) continue;
				
				$arAttributes = array();
				if($xml->moveToFirstAttribute())
				{
					$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
					while($xml->moveToNextAttribute ())
					{
						$arAttributes[] = array('name'=>$xml->name, 'value'=>$xml->value, 'namespaceURI'=>$xml->namespaceURI);
					}
				}
				$xml->moveToElement();
				

				$curName = $xml->name;
				$curValue = null;
				//$curNamespace = ($xml->namespaceURI ? $xml->namespaceURI : null);
				$curNamespace = null;
				if($xml->namespaceURI && strpos($curName, ':')!==false)
				{
					$curNamespace = $xml->namespaceURI;
				}

				$isSubRead = false;
				while(($xml->read() && ($isSubRead = true)) && ($xml->nodeType == \XMLReader::SIGNIFICANT_WHITESPACE)){}
				if($xml->nodeType == \XMLReader::TEXT || $xml->nodeType == \XMLReader::CDATA)
				{
					$curValue = $xml->value;
				}
				else
				{
					$isRead = $isSubRead;
				}

				if($curDepth == 0)
				{
					//$xmlObj = new \SimpleXMLElement('<'.$curName.'></'.$curName.'>');
					if(($pos = strpos($curName, ':'))!==false)
					{
						$rootNS = substr($curName, 0, $pos);
						$curName = substr($curName, strlen($rootNS) + 1);
					}
					$xmlObj = new \SimpleXMLElement('<'.$curName.'></'.$curName.'>', 0, false, $rootNS, true);
					$arObjects[$curDepth] = &$xmlObj;
				}
				else
				{
					$curValue = str_replace('&', '&amp;', $curValue);
					$arObjects[$curDepth] = $arObjects[$curDepth - 1]->addChild($curName, $curValue, $curNamespace);
				}			

				foreach($arAttributes as $arAttr)
				{
					if(strpos($arAttr['name'], ':')!==false && $arAttr['namespaceURI']) $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value'], $arAttr['namespaceURI']);
					else $arObjects[$curDepth]->addAttribute($arAttr['name'], $arAttr['value']);
				}
				
				//if(strlen($xpath)==strlen($curXPath) && !$wChild) $break = true;
			}
		}
		$xml->close();

		if(is_object($xmlObj))
		{
			//return $xmlObj->xpath('/'.$xpath);
			return $this->Xpath($xmlObj, '/'.$xpath);
		}
		return false;
	}
	
	public function GetSectionStruct($xpath, $arFields, $innerGroups=array())
	{
		$arXpaths = array();
		$arSubXpaths = array();
		foreach($arFields as $k=>$v)
		{
			list($fieldXpath, $fieldName) = explode(';', $v);
			if(in_array($fieldName, array('ISECT_TMP_ID', 'ISECT_PARENT_TMP_ID', 'ISECT_NAME')))
			{
				$fieldName = substr($fieldName, 6);
				$arXpaths[$fieldName] = trim(substr($fieldXpath, strlen($xpath)), '/');
			}
			if(in_array($fieldName, array('ISUBSECT_TMP_ID', 'ISUBSECT_NAME')))
			{
				$fieldName = substr($fieldName, 9);
				$arSubXpaths[$fieldName] = trim(substr($fieldXpath, strlen($xpath)), '/');
			}
		}
		if(!array_key_exists('TMP_ID', $arXpaths) || !array_key_exists('NAME', $arXpaths))
		{
			return false;
		}
		
		$subsectionXpath = (array_key_exists('TMP_ID', $arSubXpaths) || !array_key_exists('NAME', $arSubXpaths) && array_key_exists('SUBSECTION', $innerGroups) && strlen($innerGroups['SUBSECTION']) > 0 && strpos($xpath, $innerGroups['SUBSECTION'])===0 ? trim(substr($innerGroups['SUBSECTION'], strlen($xpath)), '/') : '');
		if(strlen($subsectionXpath) > 0)
		{
			foreach($arSubXpaths as $k=>$v)
			{
				$arSubXpaths[$k] = trim(substr($v, strlen($subsectionXpath)), '/');
			}
		}
		$isParents = (bool)array_key_exists('PARENT_TMP_ID', $arXpaths);
		$arSections = array();
		$rows = $this->GetXpathRows($xpath, true);
		if(!is_array($rows)) return false;
		foreach($rows as $row)
		{
			$name = $this->GetStringByXpath($row, $arXpaths['NAME']);
			$tmpId = $this->GetStringByXpath($row, $arXpaths['TMP_ID']);
			$parentTmpId = ($isParents ? $this->GetStringByXpath($row, $arXpaths['PARENT_TMP_ID']) : false);
			$arSections[$tmpId] = array(
				'NAME' => $name,
				'PARENT_ID' => $parentTmpId,
				'ROOT_PARENT_ID' => $tmpId,
				'LEVEL' => 1
			);
			if(strlen($subsectionXpath) > 0)
			{
				$this->AddSubSectionStruct($arSections, $row, $arSubXpaths, $subsectionXpath, $tmpId, 2);
			}
		}
		
		if($isParents || strlen($subsectionXpath) > 0)
		{
			foreach($arSections as $k=>$v)
			{
				$parentId = $v['PARENT_ID'];
				while($parentId!==false && strlen($parentId) > 0 && array_key_exists($parentId, $arSections))
				{
					$arSections[$k]['LEVEL']++;
					$arSections[$k]['NAME'] = $arSections[$parentId]['NAME'].' / '.$arSections[$k]['NAME'];
					$arSections[$k]['ROOT_PARENT_ID'] = $parentId;
					$parentId = $arSections[$parentId]['PARENT_ID'];
				}
			}
		}
		return $arSections;
	}
	
	public function AddSubSectionStruct(&$arSections, $parentRow, $arXpaths, $subsectionXpath, $parentTmpId, $level)
	{
		$rows = $this->Xpath($parentRow, $subsectionXpath);
		if(!is_array($rows)) return false;
		foreach($rows as $row)
		{
			$name = $this->GetStringByXpath($row, $arXpaths['NAME']);
			$tmpId = $this->GetStringByXpath($row, $arXpaths['TMP_ID']);
			$arSections[$tmpId] = array(
				'NAME' => $name,
				'PARENT_ID' => $parentTmpId,
				'ROOT_PARENT_ID' => $tmpId,
				'LEVEL' => $level
			);
			if(strlen($subsectionXpath) > 0)
			{
				$this->AddSubSectionStruct($arSections, $row, $arXpaths, $subsectionXpath, $tmpId, $level+1);
			}
		}
	}
	
	public function GetPropertyList($xpath, $arFields)
	{
		$rows = $this->GetXpathRows($xpath, true);
		$arXpaths = array();
		foreach($arFields as $k=>$v)
		{
			list($fieldXpath, $fieldName) = explode(';', $v);
			if(in_array($fieldName, array('PROPERTY_NAME')))
			{
				$fieldName = substr($fieldName, 9);
				$arXpaths[$fieldName] = trim(substr($fieldXpath, strlen($xpath)), '/');
			}
		}
		if(!array_key_exists('NAME', $arXpaths))
		{
			return false;
		}
		
		$arProperties = array();
		foreach($rows as $row)
		{
			$name = $this->GetStringByXpath($row, $arXpaths['NAME']);
			$arProperties[$name] = array(
				'NAME' => $name
			);
		}
		return $arProperties;
	}
	
	public function GetStringByXpath($simpleXmlObj, $xpath)
	{
		$val = $this->Xpath($simpleXmlObj, $xpath);
		while(is_array($val)) $val = current($val);
		return (string)$val;
	}
	
	public function Xpath($simpleXmlObj, $xpath)
	{
		$xpath = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($xpath, $this->siteEncoding, $this->fileEncoding);
		if(preg_match('/((^|\/)[^\/]+):/', $xpath, $m))
		{
			if(strpos($m[1], '/')===0) $xpath = '/'.substr($xpath, strlen($m[1]) + 1);
			$nss = $simpleXmlObj->getNamespaces(true);
			$nsKey = trim($m[1], '/');
			if(isset($nss[$nsKey]))
			{
				$simpleXmlObj->registerXPathNamespace($nsKey, $nss[$nsKey]);
			}
		}
		$xpath = trim($xpath);
		
		$arPath = explode('/', $xpath);
		$attr = false;
		if(strpos($arPath[count($arPath)-1], '@')===0)
		{
			$attr = substr(array_pop($arPath), 1);
			$xpath = implode('/', $arPath);
		}
		if(strlen($xpath) > 0 && $xpath!='.') $simpleXmlObj = $simpleXmlObj->xpath($xpath);
		if($attr!==false && is_callable(array($simpleXmlObj, 'attributes'))) return $simpleXmlObj->attributes()->{$attr};
		else return $simpleXmlObj;
	}
}