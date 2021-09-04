<?php
namespace Bitrix\EsolImportxml;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class ImporterBase {
	protected static $moduleId = 'esol.importxml';
	var $rcurrencies = array('#USD#', '#EUR#');
	var $xmlParts = array();
	var $xmlPartsValues = array();
	var $xmlSingleElems = array();
	var $arTmpImageDirs = array();
	var $arTmpImages = array();
	var $tagIblocks = array();
	var $offerParentId = null;
	
	function __construct(){}
	
	public function CheckTimeEnding($time = 0)
	{
		if($time==0) $time = $this->timeBeginImport;
		$this->ClearIblocksTagCache(true);
		return ($this->params['MAX_EXECUTION_TIME'] && (time()-$time >= $this->params['MAX_EXECUTION_TIME']));
	}
	
	public function GetFileName()
	{
		if(!file_exists($this->filename))
		{
			$oProfile = \Bitrix\EsolImportxml\Profile::getInstance();
			$oProfile->Apply(($sd=false), ($s=false), $ID);
			$fid = $oProfile->GetParam('DATA_FILE');
			if($fid)
			{
				$arFile = \CFile::GetFileArray($fid);
				$this->filename = $_SERVER['DOCUMENT_ROOT'].$arFile['SRC'];
			}
		}
		return $this->filename;
	}
	
	public function GetNextImportFile()
	{
		/*if(isset($this->stepparams['api_last_line']) && $this->stepparams['api_last_line']>=$this->stepparams['total_read_line']) return false;
		$this->stepparams['api_last_line'] = $this->stepparams['total_read_line'];*/
		if($this->stepparams['xmlCurrentRow']==0 && (!isset($this->xmlCurrentRow) || $this->xmlCurrentRow==0)) return false;
		if(!isset($this->stepparams['api_page'])) $this->stepparams['api_page'] = 1;
		$page = ++$this->stepparams['api_page'];
		//if($this->stepparams['api_page'] > 3) return false;
		if(array_key_exists('EXT_DATA_FILE', $this->params) && ($fid = \Bitrix\EsolImportxml\Utils::GetNextImportFile($this->params['EXT_DATA_FILE'], $page)))
		{
			\CFile::Delete($this->params['DATA_FILE']);
			$arFile = \CFile::GetFileArray($fid);
			$filename = $arFile['SRC'];
			$this->filename = $_SERVER['DOCUMENT_ROOT'].$filename;
			$this->params['URL_DATA_FILE'] = $filename;
			$this->params['DATA_FILE'] = $fid;
			$oProfile = \Bitrix\EsolImportxml\Profile::getInstance()->UpdatePartSettings($this->pid, array('DATA_FILE'=>$fid, 'URL_DATA_FILE'=>$filename));
			$this->stepparams['curstep'] = 'import_props';
			$this->xmlCurrentRow = $this->stepparams['xmlCurrentRow'] = 0;
			$this->xmlSectionCurrentRow = $this->stepparams['xmlSectionCurrentRow'] = 0;
			return true;
		}
		return false;
	}
	
	public function GetUVFilterParams(&$val, &$op, $key)
	{
		if($val=='{empty}'){$val = false;}
		elseif($val=='{not_empty}'){$op .= '!'; $val = false;}
		elseif(!$key){$op .= '=';}
		elseif($key=='contain'){$op .= '%';}
		elseif($key=='begin'){$val = $val.'%';}
		elseif($key=='end'){$val = '%'.$val;}
		elseif($key=='gt'){$op .= '>';}
		elseif($key=='lt'){$op .= '<';}
		
		if($op=='!!') $op = '';
		elseif($op=='!>') $op = '<';
		elseif($op=='!<') $op = '>';
	}
	
	public function CheckGroupParams($type, $xpathFrom, $xpathTo)
	{
		if(trim($this->params['GROUPS'][$type], '/')==$xpathFrom)
		{
			$xmlSectionCurrentRow = $this->xmlSectionCurrentRow;
			$xmlCurrentRow = $this->xmlCurrentRow;
			$maxStepRows = $this->maxStepRows;
			$this->maxStepRows = 2;
			$xmlElements = $this->GetXmlObject(($count=0), 0, $xpathTo);
			if(is_array($xmlElements) && count($xmlElements) > 0)
			{
				$this->params['GROUPS'][$type] = $xpathTo;
			}
			$this->xmlSectionCurrentRow = $xmlSectionCurrentRow;
			$this->xmlCurrentRow = $xmlCurrentRow;
			$this->maxStepRows = $maxStepRows;
		}
	}
	
	public function GetXmlObject(&$countRows, $beginRow, $xpath, $nolimit = false)
	{
		$xpath = trim($xpath);
		if(strlen($xpath) == 0) return;
		
		$arXpath = explode('/', trim($xpath, '/'));
		$this->xpath = '/'.$xpath;
		$countRows = 0;
		if($this->params['NOT_USE_XML_READER']=='Y' || !class_exists('\XMLReader'))
		{
			$this->xmlRowDiff = 0;
			$this->xmlObject = simplexml_load_file($this->GetFileName());
			//$rows = $this->xmlObject->xpath('/'.$xpath);
			$rows = $this->Xpath($this->xmlObject, '/'.$xpath);
			$countRows = count($rows); 
			return $rows;
		}

		$multiParent = false;
		for($i=1; $i<count($arXpath); $i++)
		{
			if(in_array(implode('/', array_slice($arXpath, 0, $i)), $this->xpathMulti))
			{
				$multiParent = true;
			}
		}
		$arXpath = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($arXpath, $this->siteEncoding, $this->fileEncoding);
		$cachedCountRowsKey = $xpath;
		$cachedCountRows = 0;
		if(isset($this->stepparams['count_rows'][$cachedCountRowsKey]))
		{
			$cachedCountRows = (int)$this->stepparams['count_rows'][$cachedCountRowsKey];
		}
		
		$xml = new \XMLReader();
		$res = $xml->open($this->GetFileName());
		
		$arObjects = array();
		$arObjectNames = array();
		$arXPaths = array();
		$curDepth = 0;
		$isRead = false;
		$countLoadedRows = 0;
		$break = false;
		$countRows = -1;
		$rootNS = '';
		while(($isRead || $xml->read()) && !$break) 
		{
			$isRead = false;
			if($xml->nodeType == \XMLReader::ELEMENT) 
			{
				$curDepth = $xml->depth;
				$arObjectNames[$curDepth] = $curName = (strlen($rootNS) > 0 && strpos($xml->name, ':')===false ? $rootNS.':' : '').$xml->name;
				$extraDepth = $curDepth + 1;
				while(isset($arObjectNames[$extraDepth]))
				{
					unset($arObjectNames[$extraDepth]);
					$extraDepth++;
				}
				
				$curXPath = implode('/', $arObjectNames);
				$curXPath = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($curXPath, $this->fileEncoding, $this->siteEncoding);
				if($multiParent)
				{
					if(strpos($xpath, $curXPath)!==0 && strpos($curXPath, $xpath)!==0) continue;
					if($xpath==$curXPath) $countRows++;
					if($countRows < $beginRow && strlen($curXPath)>=strlen($xpath)) continue;
					if($xpath==$curXPath)
					{
						$countLoadedRows++;
						if($countLoadedRows > $this->maxStepRows && !$nolimit && $cachedCountRows > 0)
						{
							$break = true;
						}
					}
				}
				else
				{
					if(strpos($xpath.'/', $curXPath.'/')!==0 && strpos($curXPath.'/', $xpath.'/')!==0)
					{
						$isRead = false;
						$nextTag = $arXpath[$curDepth];
						if(($pos = strpos($nextTag, ':'))!==false) $nextTag = substr($nextTag, $pos+1);
						while(!$isRead && $xml->next($nextTag)) $isRead = true;
						continue;
					}
					if($xpath==$curXPath)
					{
						$countRows++;
						$nextTag = $curName;
						if(($pos = strpos($nextTag, ':'))!==false) $nextTag = substr($nextTag, $pos+1);
						while($countRows < $beginRow && $xml->next($nextTag)) $countRows++;
					}
					if($countRows < $beginRow && strlen($curXPath)>=strlen($xpath)) continue;
					if($xpath==$curXPath)
					{
						$countLoadedRows++;
						if($countLoadedRows > $this->maxStepRows && !$nolimit)
						{
							if($cachedCountRows > 0)
							{
								$break = true;
							}
							else
							{
								$nextTag = $curName;
								if(($pos = strpos($nextTag, ':'))!==false) $nextTag = substr($nextTag, $pos+1);
								while($xml->next($nextTag)) $countRows++;
							}
						}
					}
				}
				if($countLoadedRows > $this->maxStepRows && !$nolimit) continue;
				
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
					if(($pos = strpos($curName, ':'))!==false) $rootNS = substr($curName, 0, $pos);
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
			}
		}
		$xml->close();
		$countRows++;
		if($cachedCountRows > 0) $countRows = $cachedCountRows;
		else $this->stepparams['count_rows'][$cachedCountRowsKey] = $countRows;
			
		if(is_object($xmlObj))
		{
			$this->xmlRowDiff = $beginRow;
			$this->xmlObject = $xmlObj;
			//return $this->xmlObject->xpath('/'.$xpath);
			return $this->Xpath($this->xmlObject, '/'.$xpath);
		}
		return false;
	}
	
	public function GetPartXmlObject($xpath, $wChild=true)
	{
		$xpath = trim(trim($xpath), '/');
		if(strlen($xpath) == 0) return;

		if(!class_exists('\XMLReader'))
		{
			$xmlObject = simplexml_load_file($this->GetFileName());
			//$rows = $xmlObject->xpath('/'.$xpath);
			$rows = $this->Xpath($xmlObject, '/'.$xpath);
			return $rows;
		}
		
		$xpath = preg_replace('/\[\d+\]/', '', $xpath);
		$arXpath = $arXpathOrig = explode('/', trim($xpath, '/'));
		
		$xml = new \XMLReader();
		$res = $xml->open($this->GetFileName());
		
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
					if(isset($arObjects[$curDepth]) && !in_array(implode('/', array_slice($arXpathOrig, 0, $curDepth+1)), $this->xpathMulti))
					{
						$break = true;
					}
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
	
	public function GetBreakParams($action = 'continue')
	{
		$this->ClearIblocksTagCache();
		$arStepParams = array(
			'params'=> array_merge($this->stepparams, array(
				'xmlCurrentRow' => intval($this->xmlCurrentRow),
				'xmlSectionCurrentRow' => intval($this->xmlSectionCurrentRow),
				'xmlIbPropCurrentRow' => intval($this->xmlIbPropCurrentRow),
				'sectionIds' => $this->sectionIds,
				'propertyIds' => $this->propertyIds,
				'sectionsTmp' => $this->sectionsTmp,
			)),
			'action' => $action,
			'errors' => $this->errors,
			'sessid' => bitrix_sessid()
		);
		
		if($action == 'continue')
		{
			file_put_contents($this->tmpfile, serialize($arStepParams['params']));
			unset($arStepParams['params']['sectionIds'], $arStepParams['params']['propertyIds']);
			if(file_exists($this->imagedir))
			{
				DeleteDirFilesEx(substr($this->imagedir, strlen($_SERVER['DOCUMENT_ROOT'])));
			}
		}
		elseif(file_exists($this->tmpdir))
		{
			DeleteDirFilesEx(substr($this->tmpdir, strlen($_SERVER['DOCUMENT_ROOT'])));
			unlink($this->procfile);
		}
		
		unset($arStepParams['params']['currentelement']);
		unset($arStepParams['params']['currentelementitem']);
		return $arStepParams;
	}
	
	public function AddTagIblock($IBLOCK_ID)
	{
		$IBLOCK_ID = (int)$IBLOCK_ID;
		if($IBLOCK_ID <= 0) return;
		$this->tagIblocks[$IBLOCK_ID] = $IBLOCK_ID;
	}
	
	public function ClearIblocksTagCache($checkTime = false)
	{
		if($this->params['REMOVE_CACHE_AFTER_IMPORT']=='Y') return;
		if($checkTime && (time() - $this->timeBeginTagCache < 60)) return;
		if(is_callable(array('\CIBlock', 'clearIblockTagCache')))
		{
			if(is_callable(array('\CIBlock', 'enableClearTagCache'))) \CIBlock::enableClearTagCache();
			foreach($this->tagIblocks as $IBLOCK_ID)
			{
				\CIBlock::clearIblockTagCache($IBLOCK_ID);
			}
			if(is_callable(array('\CIBlock', 'disableClearTagCache'))) \CIBlock::disableClearTagCache();
		}
		$this->tagIblocks = array();
		$this->timeBeginTagCache = time();
	}
	
	public function CompareUploadValue($key, $val, $needval)
	{
		if((!$key && $needval==$val)
			|| ($needval=='{empty}' && strlen($val)==0)
			|| ($needval=='{not_empty}' && strlen($val) > 0)
			|| ($key=='contain' && strpos($val, $needval)!==false)
			|| ($key=='begin' && substr($val, 0, strlen($needval))==$needval)
			|| ($key=='end' && substr($val, -strlen($needval))==$needval)
			|| ($key=='gt' && $this->GetFloatVal($val) > $this->GetFloatVal($needval))
			|| ($key=='lt' && $this->GetFloatVal($val) < $this->GetFloatVal($needval)))
		{
			return true;
		}else return false;
	}
	
	public function ExecuteFilterExpression($val, $expression, $altReturn = true, $arParams = array())
	{
		foreach($arParams as $k=>$v)
		{
			${$k} = $v;
		}
		$expression = trim($expression);
		try{				
			if(stripos($expression, 'return')===0)
			{
				return eval($expression.';');
			}
			elseif(preg_match('/\$val\s*=/', $expression))
			{
				eval($expression.';');
				return $val;
			}
			else
			{
				return eval('return '.$expression.';');
			}
		}catch(\Exception $ex){
			return $altReturn;
		}
	}
	
	public function ExecuteOnAfterSaveHandler($handler, $ID)
	{
		try{				
			eval($handler.';');
		}catch(\Exception $ex){}
	}
	
	public function GetPathAttr(&$arPath)
	{
		$attr = false;
		if(strpos($arPath[count($arPath)-1], '@')===0)
		{
			$attr = substr(array_pop($arPath), 1);
			$attr = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($attr, $this->siteEncoding, $this->fileEncoding);
		}
		return $attr;
	}
	
	public function ReplaceXpath($xpath)
	{
		if(is_array($this->xpathReplace) && isset($this->xpathReplace['FROM']) && isset($this->xpathReplace['TO']))
		{
			$xpath = str_replace($this->xpathReplace['FROM'], $this->xpathReplace['TO'], $xpath);
		}
		return $xpath;
	}
	
	public function ReplaceConditionXpath($m)
	{
		$offerXpath = substr($this->xpath, 1);
		if(strpos($m[1], $offerXpath)===0)
		{
			return '{'.substr($this->ReplaceXpath($m[1]), strlen($offerXpath) + 1).'}';
		}
		else
		{
			return '{'.$this->ReplaceXpath($m[1]).'}';
		}
	}
	
	public function ReplaceConditionXpathToValue($m)
	{
		$xpath = $this->replaceXpath;
		$simpleXmlObj = $this->replaceSimpleXmlObj;
		$simpleXmlObj2 = $this->replaceSimpleXmlObj2;
		$xpath2 = $m[1];
		if(strpos($xpath2, $xpath)===0)
		{
			$xpath2 = substr($xpath2, strlen($xpath) + 1);
			$simpleXmlObj = $simpleXmlObj2;
		}
		else
		{
			$arXpath2 = $this->GetXPathParts($xpath2);
			if(strlen($arXpath2['xpath']) > 0)
			{
				if(!isset($this->xmlParts[$arXpath2['xpath']]))
				{
					$this->xmlParts[$arXpath2['xpath']] = $this->GetPartXmlObject($arXpath2['xpath']);
				}
				$xmlPart = $this->xmlParts[$arXpath2['xpath']];
				if(!isset($this->xmlPartsValues[$xpath2]))
				{
					$arValues = array();
					foreach($xmlPart as $k=>$xmlObj)
					{
						if(strlen($arXpath2['subpath'])==0) $xmlObj2 = $xmlObj;
						else $xmlObj2 = $this->Xpath($xmlObj, $arXpath2['subpath']);
						if(!is_array($xmlObj2)) $xmlObj2 = array($xmlObj2);
						foreach($xmlObj2 as $xmlObj3)
						{
							if($arXpath2['attr']!==false && is_callable(array($xmlObj3, 'attributes')))
							{
								$val2 = (string)$xmlObj3->attributes()->{$arXpath2['attr']};
							}
							else
							{
								$val2 = (string)$xmlObj3;
							}
							//$arValues[$k] = $val2;
							$arValues[$val2] = $k;
						}
					}
					$this->xmlPartsValues[$xpath2] = $arValues;
				}
				$xmlPartsValues = $this->xmlPartsValues[$xpath2];
				
				if(is_array($xmlPart))
				{
					$valXpath = $xpath;
					$parentXpath = (isset($this->parentXpath) && strlen($this->parentXpath) > 0 ? $this->parentXpath : '');
					$parentXpathWS = trim($parentXpath, '/');
					$xpathReplaced = false;
					if($this->replaceXpathCell)
					{
						$valXpath2 = trim($this->replaceXpathCell, '{}');
						$parentXpath2 = trim($this->xpath, '/');
						if(strlen($parentXpath2) > 0 && strpos($valXpath2, $parentXpath2)===0)
						{
							$valXpath = substr($valXpath2, strlen($parentXpath2)+1);
							if(strlen($parentXpathWS) > 0 && strpos($parentXpath2, $parentXpathWS)===0)
							{
								$valXpath = substr($parentXpath2, strlen($parentXpathWS)+1).'/'.ltrim($valXpath, '/');
							}
							$xpathReplaced = true;
						}
					}
					if(strlen($parentXpath) > 0)
					{
						$valXpath = rtrim($this->parentXpath, '/').'/'.ltrim($valXpath, '/');
						if($xpathReplaced) $valXpath = $this->ReplaceXpath($valXpath);
					}
					$val = $this->GetValueByXpath($valXpath, $simpleXmlObj, true);
					$k = false;
					if(strlen($val) > 0 && isset($xmlPartsValues[$val])) $k = $xmlPartsValues[$val];

					if($k!==false)
					{
						$this->xmlPartObjects[$arXpath2['xpath']] = $xmlPart[$k];
						return $val;
					}
					else return '';
					
					/*foreach($xmlPart as $xmlObj)
					{
						if(strlen($arXpath2['subpath'])==0) $xmlObj2 = $xmlObj;
						//else $xmlObj2 = $xmlObj->xpath($arXpath2['subpath']);
						else $xmlObj2 = $this->Xpath($xmlObj, $arXpath2['subpath']);
						if(is_array($xmlObj2)) $xmlObj2 = current($xmlObj2);
						if($arXpath2['attr']!==false && is_callable(array($xmlObj2, 'attributes')))
						{
							$val2 = (string)$xmlObj2->attributes()->{$arXpath2['attr']};
						}
						else
						{
							$val2 = (string)$xmlObj2;
						}
						if($val2==$val)
						{
							$this->xmlPartObjects[$arXpath2['xpath']] = $xmlObj;
							return $val;
						}
					}*/
				}
			}
		}
		$arPath = explode('/', $xpath2);
		$attr = $this->GetPathAttr($arPath);
		if(count($arPath) > 0)
		{
			//$simpleXmlObj3 = $simpleXmlObj->xpath(implode('/', $arPath));
			$simpleXmlObj3 = $this->Xpath($simpleXmlObj, implode('/', $arPath));
			if(count($simpleXmlObj3)==1) $simpleXmlObj3 = current($simpleXmlObj3);
		}
		else $simpleXmlObj3 = $simpleXmlObj;
		
		if(is_array($simpleXmlObj3)) $simpleXmlObj3 = current($simpleXmlObj3);
		$condVal = (string)(($attr!==false && is_callable(array($simpleXmlObj3, 'attributes'))) ? $simpleXmlObj3->attributes()->{$attr} : $simpleXmlObj3);
		return $condVal;
	}
	
	public function GetXPathParts($xpath)
	{
		$arPath = explode('/', $xpath);
		$attr = $this->GetPathAttr($arPath);
		$xpath2 = implode('/', $arPath);
		$xpath3 = '';
		if(strpos($xpath2, '//')!==false && strpos($xpath2, '//') > 0)
		{
			list($xpath2, $xpath3) = explode('//', $xpath2, 2);
		}
		$xpath2 = rtrim($xpath2, '/');
		return array('xpath'=>$xpath2, 'subpath' => $xpath3, 'attr'=>$attr);
	}
	
	public function GetToXpathReplace($arPath, $lastElem, $lastKey, $key, $simpleXmlObj)
	{
		$toXpath = ltrim(implode('/', $arPath).'/'.$lastElem.'['.$lastKey.']', '/');
		if(count($this->Xpath($simpleXmlObj, $toXpath))==0)
		{
			$keyOrig = $key;
			$arPath[] = $lastElem;
			$arNewPath = array();
			while(count($arPath) > 0)
			{
				$arNewPath[] = array_shift($arPath);
				if(count($arPath) > 0)
				{
					$objs = $this->Xpath($simpleXmlObj, implode('/', $arNewPath));
					if(count($objs) > 1)
					{
						$key2 = $key;
						$k = -1;
						while($key2 >= 0 && isset($objs[++$k]))
						{
							$key2 -= count($this->Xpath($objs[$k], implode('/', $arPath)));
							if($key2 >= 0) $key = $key2;
						}
						$lastInd = count($arNewPath) - 1;
						if(!preg_match('/\[\d+\]/', $arNewPath[$lastInd]))
						{
							$arNewPath[$lastInd] = $arNewPath[$lastInd].'['.($k + 1).']';
						}
					}
				}
				else
				{
					$lastInd = count($arNewPath) - 1;
					if(!preg_match('/\[\d+\]/', $arNewPath[$lastInd]))
					{
						$arNewPath[$lastInd] = $arNewPath[$lastInd].'['.($key + 1).']';
					}
				}
			}
			if(count($this->Xpath($simpleXmlObj, implode('/', $arNewPath))) > 0)
			{
				$toXpath = ltrim(implode('/', $arNewPath), '/');
			}
		}
		return $toXpath;
	}
	
	public function CheckConditions($conditions, $xpath, $simpleXmlObj, $simpleXmlObj2, $key=false)
	{
		if(empty($conditions)) return true;
		if($key!==false)
		{
			$arPath = explode('/', $xpath);
			$attr = $this->GetPathAttr($arPath);
			if(count($arPath) > 1 && ($cnt = count($this->Xpath($simpleXmlObj, implode('/', $arPath)))) && $cnt > 1)
			{
				$arMap = array();
				$this->GetXpathMap($arMap, $simpleXmlObj, $xpath);
				if(strpos($xpath, '[')===false && isset($arMap[$key]) && strlen($arMap[$key]) > 0)
				{
					$rfrom = $xpath;
					$rto = $arMap[$key];
				}
				else
				{
					while(($lastElem = array_pop($arPath)) && (count($arPath) > 0) /*&& (count($this->Xpath($simpleXmlObj, implode('/', $arPath)))==$cnt)*/ && ($cnt2 = count($this->Xpath($simpleXmlObj, implode('/', $arPath)))) && $cnt2>=$cnt){$cnt3 = $cnt2;}
					/*Fix for missign tag*/
					$key2 = $key;
					if($cnt3 > $cnt)
					{
						$subpath = implode('/', $arPath).'/'.$lastElem;
						for($i=0; $i<min($key2+1, $cnt3); $i++)
						{
							$xpath2 = $subpath.'['.($i+1).']/'.substr($xpath, strlen($subpath) + 1);
							//if(count($simpleXmlObj->xpath($xpath2))==0) $key2++;
							if(count($this->Xpath($simpleXmlObj, $xpath2))==0) $key2++;
						}
					}
					/*/Fix for missign tag*/

					$rfrom = ltrim(implode('/', $arPath).'/'.$lastElem, '/');
					$rto = $this->GetToXpathReplace($arPath, $lastElem, ($key2+1), $key, $simpleXmlObj);
				}
				foreach($conditions as $k3=>$v3)
				{
					$conditions[$k3]['XPATH'] = str_replace($rfrom, $rto, $conditions[$k3]['XPATH']);
				}
			}
		}
		
		$k = 0;
		while(isset($conditions[$k]))
		{
			$v = $conditions[$k];
			$pattern = '/^\{(\S*)\}$/';
			if(preg_match($pattern, $v['FROM']))
			{
				$this->replaceXpath = $xpath;
				$this->replaceXpathCell = $v['CELL'];
				$this->replaceSimpleXmlObj = $simpleXmlObj;
				$this->replaceSimpleXmlObj2 = $simpleXmlObj2;
				$v['FROM'] = preg_replace_callback($pattern, array($this, 'ReplaceConditionXpathToValue'), $v['FROM']);
			}
			
			$xpath2 = $v['XPATH'];

			$generalXpath = $xpath;
			if(strpos($xpath, '@')!==false) $generalXpath = rtrim(substr($xpath, 0, strpos($xpath, '@')), '/');
			/*Attempt of relative seaarch node*/
			if(strpos($xpath2, $generalXpath)!==0 && strpos($xpath2, '[')===false && strpos($generalXpath, '[')===false)
			{
				$diffLevel = 0;
				$sharedXpath = ltrim($generalXpath, '/');
				$arSharedXpath = explode('/', $sharedXpath);
				while(count($arSharedXpath) > 0 && strpos($xpath2, $sharedXpath)!==0)
				{
					array_pop($arSharedXpath);
					$sharedXpath = implode('/', $arSharedXpath);
					$diffLevel++;
				}
				if(strlen($sharedXpath) > 0 && strpos($xpath2, $sharedXpath)===0 && $diffLevel > 0)
				{
					$simpleXmlObjArr = $simpleXmlObj2->xpath(substr(str_repeat('../', $diffLevel), 0, -1));
					if(is_array($simpleXmlObjArr) && count($simpleXmlObjArr)==1) $simpleXmlObjArr = current($simpleXmlObjArr);
					if(is_object($simpleXmlObjArr))
					{
						$simpleXmlObj2 = $simpleXmlObjArr;
						$generalXpath = $sharedXpath;
					}
				}
			}
			/*/Attempt of relative seaarch node*/
			if(strpos($xpath2, $generalXpath)===0)
			{
				//$xpath2 = substr($xpath2, strlen($xpath) + 1);
				$xpath2 = substr($xpath2, strlen($generalXpath));
				$xpath2 = ltrim(preg_replace('/^\[\d*\]/', '', $xpath2), '/');
				$simpleXmlObj = $simpleXmlObj2;
			}
			$arPath = explode('/', $xpath2);
			$attr = $this->GetPathAttr($arPath);
			if(count($arPath) > 0)
			{
				//$simpleXmlObj3 = $simpleXmlObj->xpath(implode('/', $arPath));
				$simpleXmlObj3 = $this->Xpath($simpleXmlObj, implode('/', $arPath));
				if(count($simpleXmlObj3)==1) $simpleXmlObj3 = current($simpleXmlObj3);
			}
			else $simpleXmlObj3 = $simpleXmlObj;
			
			$condVal = '';
			if(is_array($simpleXmlObj3))
			{					
				$find = false;
				foreach($simpleXmlObj3 as $k2=>$curObj)
				{
					$condVal = (string)($attr!==false ? $curObj->attributes()->{$attr} : $curObj);
					if($this->CheckCondition($condVal, $v))
					{
						$find = true;
						
						$cnt = count($simpleXmlObj3);
						if($cnt > 1)
						{
							$arPath2 = $arPath;
							$lastElem = array_pop($arPath2);
							while(($lastElem = array_pop($arPath2)) && (count($arPath) > 0) 
								//&& (count($simpleXmlObj->xpath(implode('/', $arPath2)))==$cnt)){}
								&& (count($this->Xpath($simpleXmlObj, implode('/', $arPath2)))==$cnt)){}
							$xpathReplace = $this->xpathReplace;
							$this->xpathReplace = array(
								'FROM' => implode('/', $arPath2).'/'.$lastElem,
								//'TO' => implode('/', $arPath2).'/'.$lastElem.'['.($k2+1).']'
								'TO' => $this->GetToXpathReplace($arPath2, $lastElem, ($k2+1), $key, $simpleXmlObj)
							);
							foreach($conditions as $k3=>$v3)
							{
								if($k3 <= $k) continue;
								$conditions[$k3]['XPATH'] = str_replace($this->xpathReplace['FROM'], $this->xpathReplace['TO'], $conditions[$k3]['XPATH']);
								$conditions[$k3]['FROM'] = preg_replace_callback('/^\{(\S*)\}$/', array($this, 'ReplaceConditionXpath'), $conditions[$k3]['FROM']);
							}
							$this->xpathReplace = $xpathReplace;
						}
					}
				}
				if(!$find) return false;
			}
			else
			{
				$condVal = (string)(($attr!==false && is_callable(array($simpleXmlObj3, 'attributes'))) ? $simpleXmlObj3->attributes()->{$attr} : $simpleXmlObj3);
				if(!$this->CheckCondition($condVal, $v)) return false;
			}
			$k++;
		}
		return true;
	}
	
	public function CheckCondition($condVal, $v)
	{
		$condVal = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($condVal, $this->fileEncoding, $this->siteEncoding);
		$condVal = preg_replace('/\s+/', ' ', trim($condVal));
		$v['FROM'] = preg_replace('/\s+/', ' ', trim($v['FROM']));
		if(!(($v['WHEN']=='EQ' && $condVal==$v['FROM'])
			|| ($v['WHEN']=='NEQ' && $condVal!=$v['FROM'])
			|| ($v['WHEN']=='GT' && $condVal > $v['FROM'])
			|| ($v['WHEN']=='LT' && $condVal < $v['FROM'])
			|| ($v['WHEN']=='GEQ' && $condVal >= $v['FROM'])
			|| ($v['WHEN']=='LEQ' && $condVal <= $v['FROM'])
			|| ($v['WHEN']=='CONTAIN' && strpos($condVal, $v['FROM'])!==false)
			|| ($v['WHEN']=='NOT_CONTAIN' && strpos($condVal, $v['FROM'])===false)
			|| ($v['WHEN']=='REGEXP' && preg_match('/'.preg_replace_callback('/(?<!\\\)./'.($this->siteEncoding=='utf-8' ? 'u' : ''), create_function('$m', 'return ToLower($m[0]);'), $v['FROM']).'/i', ToLower($condVal)))
			|| ($v['WHEN']=='EMPTY' && strlen($condVal)==0)
			|| ($v['WHEN']=='NOT_EMPTY' && strlen($condVal) > 0)))
		{
			return false;
		}
		return true;
	}
	
	public function ApplyConversions($val, $arConv, $arItem, $field=false, $iblockFields=array())
	{
		$arExpParams = array();
		$fieldName = $fieldKey = $fieldIndex = false;
		if(!is_array($field))
		{
			$fieldName = $field;
		}
		else
		{
			if($field['NAME']) $fieldName = $field['NAME'];
			if(strlen($field['KEY']) > 0) $fieldKey = $field['KEY'];
			if(strlen($field['INDEX']) > 0) $fieldIndex = $field['INDEX'];
			if(strlen($field['PARENT_ID']) > 0) $arExpParams['PARENT_ID'] = $field['PARENT_ID'];
		}
		$this->currentFieldKey = $fieldKey;
		$this->currentFieldIndex = $fieldIndex;
		
		if(is_array($arConv))
		{
			$execConv = false;
			$this->currentItemValues = $arItem;
			$prefixPattern = '/(\{([^\s\}]*[\'"][^\'"\}]*[\'"])*[^\s\'"\}]*\}|'.'\$\{[\'"]([^\s\}]*[\'"][^\'"\}]*[\'"])*[^\s\'"\}]*[\'"]\}|#HASH#|'.implode('|', $this->rcurrencies).')/';
			foreach($arConv as $k=>$v)
			{
				$condVal = $val;

				if(preg_match('/^\{(\S*)\}$/', $v['CELL'], $m))
				{
					$condVal = $this->GetValueByXpath($m[1]);
				}

				//if(strlen($v['FROM']) > 0) $v['FROM'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['FROM']);
				$i = 0;
				while(++$i<10 && strlen($v['FROM']) > 0 && preg_match($prefixPattern, $v['FROM']))
				{
					$v['FROM'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['FROM']);
				}
				if($v['CELL']=='ELSE') $v['WHEN'] = '';
				$condValNum = $this->GetFloatVal($condVal);
				$fromNum = $this->GetFloatVal($v['FROM']);
				if(($v['CELL']=='ELSE' && !$execConv)
					|| ($v['WHEN']=='EQ' && $condVal==$v['FROM'])
					|| ($v['WHEN']=='NEQ' && $condVal!=$v['FROM'])
					|| ($v['WHEN']=='GT' && $condValNum > $fromNum)
					|| ($v['WHEN']=='LT' && $condValNum < $fromNum)
					|| ($v['WHEN']=='GEQ' && $condValNum >= $fromNum)
					|| ($v['WHEN']=='LEQ' && $condValNum <= $fromNum)
					|| ($v['WHEN']=='CONTAIN' && strpos($condVal, $v['FROM'])!==false)
					|| ($v['WHEN']=='NOT_CONTAIN' && strpos($condVal, $v['FROM'])===false)
					|| ($v['WHEN']=='REGEXP' && preg_match('/'.ToLower($v['FROM']).'/i', ToLower($condVal)))
					|| ($v['WHEN']=='NOT_REGEXP' && !preg_match('/'.ToLower($v['FROM']).'/i', ToLower($condVal)))
					|| ($v['WHEN']=='EMPTY' && strlen($condVal)==0)
					|| ($v['WHEN']=='NOT_EMPTY' && strlen($condVal) > 0)
					|| ($v['WHEN']=='ANY'))
				{
					//if(strlen($v['TO']) > 0) $v['TO'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['TO']);
					$i = 0;
					while(++$i<10 && strlen($v['TO']) > 0 && preg_match($prefixPattern, $v['TO']))
					{
						$v['TO'] = preg_replace_callback($prefixPattern, array($this, 'ConversionReplaceValues'), $v['TO']);
					}
					if($v['THEN']=='REPLACE_TO') $val = $v['TO'];
					elseif($v['THEN']=='REMOVE_SUBSTRING' && strlen($v['TO']) > 0) $val = str_replace($v['TO'], '', $val);
					elseif($v['THEN']=='REPLACE_SUBSTRING_TO' && strlen($v['FROM']) > 0)
					{
						if($v['WHEN']=='REGEXP')
						{
							if(preg_match('/'.$v['FROM'].'/i', $val)) $val = preg_replace('/'.$v['FROM'].'/i', $v['TO'], $val);
							else $val = preg_replace('/'.ToLower($v['FROM']).'/i', $v['TO'], $val);
						}
						else $val = str_replace($v['FROM'], $v['TO'], $val);
					}
					elseif($v['THEN']=='ADD_TO_BEGIN') $val = $v['TO'].$val;
					elseif($v['THEN']=='ADD_TO_END') $val = $val.$v['TO'];
					elseif($v['THEN']=='LCASE') $val = ToLower($val);
					elseif($v['THEN']=='UCASE') $val = ToUpper($val);
					elseif($v['THEN']=='UFIRST') $val = preg_replace_callback('/^(\s*)(.*)$/', create_function('$m', 'return $m[1].ToUpper(substr($m[2], 0, 1)).ToLower(substr($m[2], 1));'), $val);
					elseif($v['THEN']=='UWORD') $val = implode(' ', array_map(create_function('$m', 'return ToUpper(substr($m, 0, 1)).ToLower(substr($m, 1));'), explode(' ', $val)));
					elseif($v['THEN']=='MATH_ROUND') $val = round($this->GetFloatVal($val));
					elseif($v['THEN']=='MATH_MULTIPLY') $val = $this->GetFloatVal($val) * $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_DIVIDE') $val = $this->GetFloatVal($val) / $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_ADD') $val = $this->GetFloatVal($val) + $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='MATH_SUBTRACT') $val = $this->GetFloatVal($val) - $this->GetFloatVal($v['TO']);
					elseif($v['THEN']=='NOT_LOAD') $val = false;
					elseif($v['THEN']=='EXPRESSION') $val = $this->ExecuteFilterExpression($val, $v['TO'], '', $arExpParams);
					elseif($v['THEN']=='STRIP_TAGS') $val = strip_tags($val);
					elseif($v['THEN']=='CLEAR_TAGS') $val = preg_replace('/<([a-z][a-z0-9:]*)[^>]*(\/?)>/i','<$1$2>', $val);
					elseif($v['THEN']=='TRANSLIT')
					{
						$arParams = array();
						if($fieldName && !empty($iblockFields))
						{
							$paramName = '';
							if($fieldName=='IE_CODE') $paramName = 'CODE';
							if(preg_match('/^ISECT\d+_CODE$/', $fieldName)) $paramName = 'SECTION_CODE';
							if($paramName && $iblockFields[$paramName]['DEFAULT_VALUE']['TRANSLITERATION']=='Y')
							{
								$arParams = $iblockFields[$paramName]['DEFAULT_VALUE'];
							}
						}
						if(strlen($v['TO']) > 0) $val = $v['TO'];
						$val = $this->Str2Url($val, $arParams);
					}
					elseif($v['THEN']=='DOWNLOAD_IMAGES')
					{
						$val = \Bitrix\EsolImportxml\Utils::DownloadImagesFromText($val, $v['TO']);
					}
					$execConv = true;
				}
			}
		}
		return $val;
	}
	
	public function GetXpathMap(&$arMap, $xmlObj, $xpath, $prefix='')
	{
		$arXpath = array_diff(array_map('trim', explode('/', $xpath)), array(''));
		$subXmlObj = $xmlObj;
		while($subpath = array_shift($arXpath))
		{
			$prefix .= (strlen($prefix) > 0 ? '/' : '').$subpath;
			$subXmlObj = $this->Xpath($subXmlObj, $subpath);
			if(is_array($subXmlObj))
			{
				if(count($subXmlObj) > 0)
				{
					foreach($subXmlObj as $k=>$subXmlObj2)
					{
						if(count($arXpath)==0) $arMap[] = $prefix.'['.($k+1).']';
						else $this->GetXpathMap($arMap, $subXmlObj2, implode('/', $arXpath), $prefix.'['.($k+1).']');
					}
					$arXpath = array();
				}
				/*elseif(count($subXmlObj)==1)
				{
					$subXmlObj = current($subXmlObj);
				}*/
				else $arXpath = array();
			}
		}
	}
	
	public function SaveStatusImport($end = false)
	{
		if($this->procfile)
		{
			$writeParams = array_merge($this->stepparams, array(
				'xmlCurrentRow' => intval($this->xmlCurrentRow),
				'xmlSectionCurrentRow' => intval($this->xmlSectionCurrentRow),
				'sectionIds' => $this->sectionIds
			));
			$writeParams['action'] = ($end ? 'finish' : 'continue');
			file_put_contents($this->procfile, \CUtil::PhpToJSObject($writeParams));
		}
	}
	
	public function SaveElementId($ID, $offer=false)
	{
		$fn = ($offer ? $this->fileOffersId : $this->fileElementsId);
		$handle = fopen($fn, 'a');
		fwrite($handle, $ID."\r\n");
		fclose($handle);
		$this->logger->SaveElementChanges($ID);
	}
	
	public function ApplyMargins($val, $fieldKey)
	{
		if(is_array($val))
		{
			foreach($val as $k=>$v)
			{
				$val[$k] = $this->ApplyMargins($v, $fieldKey);
			}
			return $val;
		}
		
		if(is_array($fieldKey)) $arParams = $fieldKey;
		else $arParams = $this->fieldSettings[$fieldKey];
		$val = $this->GetFloatVal($val);
		$sval = $val;
		$margins = $arParams['MARGINS'];
		if(is_array($margins) && count($margins) > 0)
		{
			foreach($margins as $margin)
			{
				if((strlen(trim($margin['PRICE_FROM']))==0 || $sval >= $this->GetFloatVal($margin['PRICE_FROM']))
					&& (strlen(trim($margin['PRICE_TO']))==0 || $sval <= $this->GetFloatVal($margin['PRICE_TO'])))
				{
					if($margin['PERCENT_TYPE']=='F')
						$val += ($margin['TYPE'] > 0 ? 1 : -1)*$this->GetFloatVal($margin['PERCENT']);
					else
						$val *= (1 + ($margin['TYPE'] > 0 ? 1 : -1)*$this->GetFloatVal($margin['PERCENT'])/100);
				}
			}
		}
		
		/*Rounding*/
		$roundRule = $arParams['PRICE_ROUND_RULE'];
		$roundRatio = $arParams['PRICE_ROUND_COEFFICIENT'];
		$roundRatio = str_replace(',', '.', $roundRatio);
		if(!preg_match('/^[\d\.]+$/', $roundRatio)) $roundRatio = 1;
		
		if($roundRule=='ROUND')	$val = round($val / $roundRatio) * $roundRatio;
		elseif($roundRule=='CEIL') $val = ceil($val / $roundRatio) * $roundRatio;
		elseif($roundRule=='FLOOR') $val = floor($val / $roundRatio) * $roundRatio;
		/*/Rounding*/
		
		return $val;
	}
	
	function GetFilesByExt($path, $arExt=array())
	{
		$arFiles = array();
		$arDirFiles = array_diff(scandir($path), array('.', '..'));
		foreach($arDirFiles as $file)
		{
			if(is_file($path.$file) && (empty($arExt) || preg_match('/\.('.implode('|', $arExt).')$/i', ToLower($file))))
			{
				$arFiles[] = $path.$file;
			}
		}
		foreach($arDirFiles as $file)
		{
			if(is_dir($path.$file))
			{
				$arFiles = array_merge($arFiles, $this->GetFilesByExt($path.$file.'/', $arExt));
			}
		}
		return $arFiles;
	}
	
	public function AddTmpFile($fileOrig, $file)
	{
		$this->arTmpImages[$fileOrig] = array('file'=>$file, 'size'=>filesize($file));
	}
	
	public function GetTmpFile($fileOrig)
	{
		if(array_key_exists($fileOrig, $this->arTmpImages))
		{
			/*if(filesize($this->arTmpImages[$fileOrig]['file'])==$this->arTmpImages[$fileOrig]['size']) return $this->arTmpImages[$fileOrig]['file'];
			else unset($this->arTmpImages[$fileOrig]);*/
			$fn = \Bitrix\Main\IO\Path::convertLogicalToPhysical($this->arTmpImages[$fileOrig]['file']);
			$i = 0;
			$newFn = '';
			while(($i++)==0 || (file_exists($newFn)))
			{
				if($i > 1000) return false;
				$newFn = (preg_match('/\.[^\/\.]*$/', $fn) ? preg_replace('/(\.[^\/\.]*)$/', '_'.$i.'$1', $fn) : $fn.'_'.$i);
			}
			if(copy($fn, $newFn)) return $newFn;
		}
		return false;
	}
	
	public function CreateTmpImageDir()
	{
		$tmpsubdir = $this->imagedir.($this->filecnt++).'/';
		CheckDirPath($tmpsubdir);
		$this->arTmpImageDirs[] = $tmpsubdir;
		return $tmpsubdir;
	}
	
	public function RemoveTmpImageDirs()
	{
		if(!empty($this->arTmpImageDirs))
		{
			foreach($this->arTmpImageDirs as $k=>$v)
			{
				DeleteDirFilesEx(substr($v, strlen($_SERVER['DOCUMENT_ROOT'])));
			}
			$this->arTmpImageDirs = array();
		}
		$this->arTmpImages = array();
	}
	
	public function GetFileArray($file, $arDef=array(), $arParams=array())
	{
		$bNeedImage = (bool)($arParams['FILETYPE']=='IMAGE');
		$bMultiple = (bool)($arParams['MULTIPLE']=='Y');
		$fileTypes = array();
		if($bNeedImage) $fileTypes = array('jpg', 'jpeg', 'png', 'gif', 'bmp');
		elseif($arParams['FILE_TYPE']) $fileTypes = array_diff(array_map('trim', explode(',', ToLower($arParams['FILE_TYPE']))), array(''));
		
		if(is_array($file))
		{
			if($bMultiple)
			{
				$arFiles = array();
				foreach($file as $subfile)
				{
					$arFiles[] = $this->GetFileArray($subfile, $arDef, $arParams);
				}
				return $arFiles;
			}
			else
			{
				$file = array_shift($file);
			}
		}
		
		$fileOrig = $file = $this->Trim($file);
		if($file=='-')
		{
			return array('del'=>'Y');
		}
		elseif($tmpFile = $this->GetTmpFile($fileOrig))
		{
			$file = $tmpFile;
		}
		elseif($tmpFile = $this->GetFileFromArchive($fileOrig))
		{
			$file = $tmpFile;
		}
		elseif(strpos($file, '/')===0 || (strpos($file, '://')===false && strpos($file, '/')!==false))
		{
			$file = '/'.ltrim($file, '/');
			$file = \Bitrix\Main\IO\Path::convertLogicalToPhysical($file);
			if($this->PathContainsMask($file) && !file_exists($file) && !file_exists($_SERVER['DOCUMENT_ROOT'].$file))
			{
				$arFiles = $this->GetFilesByMask($file);
				if($arParams['MULTIPLE']=='Y' && count($arFiles) > 1)
				{
					foreach($arFiles as $k=>$v)
					{
						$arFiles[$k] = self::GetFileArray($v, $arDef, $arParams);
					}
					return array('VALUES'=>$arFiles);
				}
				elseif(count($arFiles) > 0)
				{
					$tmpfile = current($arFiles);
					return self::GetFileArray($tmpfile, $arDef, $arParams);
				}
			}
			
			$tmpsubdir = $this->CreateTmpImageDir();
			$arFile = \CFile::MakeFileArray(current(explode('#', $file)));
			$file = $tmpsubdir.$arFile['name'];
			copy($arFile['tmp_name'], $file);
		}
		elseif(strpos($file, 'zip://')===0)
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			$oldfile = $file;
			$file = $tmpsubdir.basename($oldfile);
			copy($oldfile, $file);
		}
		elseif(preg_match('/ftp(s)?:\/\//', $file))
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			$arFile = $this->sftp->MakeFileArray($file, $arParams);
			if($bMultiple && array_key_exists('0', $arFile))
			{
				$arFiles = array();
				foreach($arFile as $subfile)
				{
					if(is_array($subfile)) $arFiles[] = $subfile;
					else $arFiles[] = $this->GetFileArray($subfile, $arDef, $arParams);
				}
				return $arFiles;
			}
			$file = $tmpsubdir.$arFile['name'];
			copy($arFile['tmp_name'], $file);
		}
		elseif($service = $this->cloud->GetService($file))
		{
			$tmpsubdir = $this->CreateTmpImageDir();
			if($arFile = $this->cloud->MakeFileArray($service, $file))
			{
				$file = $tmpsubdir.$arFile['name'];
				copy($arFile['tmp_name'], $file);
			}
		}
		elseif(preg_match('/http(s)?:\/\//', $file))
		{
			//$file = urldecode($file);
			$file = preg_replace_callback('/[^:\/?=&#@\+]+/', create_function('$m', 'return urldecode($m[0]);'), $file);
			$arUrl = parse_url($file);
			//Cyrillic domain
			if(preg_match('/[^A-Za-z0-9\-\.]/', $arUrl['host']))
			{
				if(!class_exists('idna_convert')) require_once(dirname(__FILE__).'/idna_convert.class.php');
				if(class_exists('idna_convert'))
				{
					$idn = new \idna_convert();
					$oldHost = $arUrl['host'];
					if(!\CUtil::DetectUTF8($oldHost)) $oldHost = \Bitrix\EsolImportxml\Utils::Win1251Utf8($oldHost);
					$file = str_replace($arUrl['host'], $idn->encode($oldHost), $file);
				}
			}
			if(class_exists('\Bitrix\Main\Web\HttpClient'))
			{
				$tmpsubdir = $this->CreateTmpImageDir();
				$basename = preg_replace('/[&=\+]/', '', preg_replace('/\?.*$/', '', bx_basename($file)));
				if(preg_match('/^[_+=!?]*\./', $basename) || strlen(trim($basename))==0) $basename = 'f'.$basename;
				$tempPath = $tmpsubdir.$basename;
				$tempPath2 = $tmpsubdir.(\Bitrix\Main\IO\Path::convertLogicalToPhysical($basename));
				$arOptions = array();
				if($this->useProxy) $arOptions = $this->proxySettings;
				$arOptions['disableSslVerification'] = true;
				$arOptions['socketTimeout'] = $arOptions['streamTimeout'] = 10;
				$ob = new \Bitrix\Main\Web\HttpClient($arOptions);
				//$ob->setHeader('User-Agent', 'BitrixSM HttpClient class');
				$ob->setHeader('User-Agent', \Bitrix\EsolImportxml\Utils::GetUserAgent());
				try{
					if(!\CUtil::DetectUTF8($file)) $file = \Bitrix\EsolImportxml\Utils::Win1251Utf8($file);
					$file = preg_replace_callback('/[^:\/?=&#@]+/', create_function('$m', 'return rawurlencode($m[0]);'), $file);
					if($ob->download($file, $tempPath) && $ob->getStatus()!=404) $file = $tempPath2;
					else return array();
				}catch(\Exception $ex){}
				
				$hcd = $ob->getHeaders()->get("content-disposition");
				if($hcd && stripos($hcd, 'filename=')!==false)
				{
					$hcdParts = preg_grep('/filename=/i', array_map('trim', explode(';', $hcd)));
					if(count($hcdParts) > 0)
					{
						$hcdParts = explode('=', current($hcdParts));
						$fn = end(explode('/', trim(end($hcdParts), '"\' ')));
						if(strlen($fn) > 0 && strpos($tempPath, $fn)===false)
						{
							$oldTempPath = $tempPath;
							$tempPath = preg_replace('/\/[^\/]+$/', '/'.$fn, $oldTempPath);
							rename($oldTempPath, $tempPath);
							$tempPath2 = \Bitrix\Main\IO\Path::convertLogicalToPhysical($tempPath);
							$file = $tempPath2;
						}
					}
				}
				
				if(strpos($ob->getHeaders()->get("content-type"), 'text/html')!==false 
					&& (in_array('jpg', $fileTypes) || in_array('jpeg', $fileTypes))
					&& ($arFile = \CFile::MakeFileArray($file))
					&& stripos($arFile['type'], 'image')===false)
				{
					$fileContent = file_get_contents($file);
					if(preg_match_all('/src=[\'"]([^\'"]*)[\'"]/is', $fileContent, $m))
					{
						if($bMultiple)
						{
							$arFiles = array();
							foreach($m[1] as $img)
							{
								$img = trim($img);
								if(preg_match('/data:image\/(.{3,4});base64,/is', $img, $m))
								{
									$subfile = $this->CreateTmpImageDir().'img.'.$m[1];
									file_put_contents($subfile, base64_decode(substr($img, strlen($m[0]))));
									$arFiles[] = $this->GetFileArray($subfile, $arDef, $arParams);
								}
							}
							if(!empty($arFiles)) return array('VALUES' => $arFiles);
						}
						else
						{
							$img = trim(current($m[1]));
							if(preg_match('/data:image\/(.{3,4});base64,/is', $img, $m))
							{
								file_put_contents($file, base64_decode(substr($img, strlen($m[0]))));
							}
						}
					}
				}
			}
		}
		$this->AddTmpFile($fileOrig, $file);
		$arFile = \CFile::MakeFileArray($file);	
		if(!file_exists($file) && !$arFile['name'] && !\CUtil::DetectUTF8($file))
		{
			$file = \Bitrix\EsolImportxml\Utils::Win1251Utf8($file);
			$arFile = \CFile::MakeFileArray($file);
		}
		
		$dirname = '';
		if(file_exists($file) && is_dir($file))
		{
			$dirname = $file;
		}
		elseif(in_array($arFile['type'], array('application/zip', 'application/x-zip-compressed')) && !empty($fileTypes) && !in_array('zip', $fileTypes))
		{
			$archiveParams = $this->GetArchiveParams($fileOrig);
			if(!$archiveParams['exists'])
			{
				CheckDirPath($archiveParams['path']);
				$zipObj = \CBXArchive::GetArchive($arFile['tmp_name'], 'ZIP');
				$zipObj->Unpack($archiveParams['path']);
				if($arFile['type']=='application/zip') \Bitrix\EsolImportxml\Utils::CorrectEncodingForExtractDir($archiveParams['path']);
			}
			$dirname = $archiveParams['file'];
		}
		if(strlen($dirname) > 0)
		{
			$arFile = array();
			if(file_exists($dirname) && is_file($dirname)) $arFiles = array($dirname);
			else $arFiles = $this->GetFilesByExt($dirname, $fileTypes);
			if($bMultiple && count($arFiles) > 1)
			{
				foreach($arFiles as $k=>$v)
				{
					$arFiles[$k] = \CFile::MakeFileArray($v);
				}
				$arFile = array('VALUES'=>$arFiles);
			}
			elseif(count($arFiles) > 0)
			{
				$tmpfile = current($arFiles);
				$arFile = \CFile::MakeFileArray($tmpfile);
			}
		}
		
		if(strpos($arFile['type'], 'image/')===0)
		{
			$ext = ToLower(str_replace('image/', '', $arFile['type']));
			if($this->IsWrongExt($arFile['name'], $ext))
			{
				if(($ext!='jpeg' || (($ext='jpg') && $this->IsWrongExt($arFile['name'], $ext)))
					&& ($ext!='svg+xml' || (($ext='svg') && $this->IsWrongExt($arFile['name'], $ext)))
				)
				{
					$arFile['name'] = $arFile['name'].'.'.$ext;
				}
			}
		}
		elseif($bNeedImage) $arFile = array();

		if(!empty($arDef) && !empty($arFile))
		{
			if(isset($arFile['VALUES']))
			{
				foreach($arFile['VALUES'] as $k=>$v)
				{
					$arFile['VALUES'][$k] = $this->PictureProcessing($v, $arDef);
				}
			}
			else
			{
				$arFile = $this->PictureProcessing($arFile, $arDef);
			}
		}
		if(!empty($arFile) && strpos($arFile['type'], 'image/')===0)
		{
			$arCacheKeys = array('width'=>$width, 'height'=>$height, 'size'=>$arFile['size']);
			if($this->params['ELEMENT_NOT_CHECK_NAME_IMAGES']!='Y') $arCacheKeys['name'] = $arFile['name'];
			list($width, $height, $type, $attr) = getimagesize($arFile['tmp_name']);
			$arFile['external_id'] = 'i_'.md5(serialize($arCacheKeys));
		}
		if(!empty($arFile) && strpos($arFile['type'], 'html')!==false)
		{
			$arFile = array();
		}
		
		return $arFile;
	}
	
	public function PictureProcessing($arFile, $arDef)
	{
		if($arDef["SCALE"] === "Y")
		{
			$arNewPicture = \CIBlock::ResizePicture($arFile, $arDef);
			if(is_array($arNewPicture))
			{
				$arFile = $arNewPicture;
			}
			/*elseif($arDef["IGNORE_ERRORS"] !== "Y")
			{
				unset($arFile);
				$strWarning .= Loc::getMessage("IBLOCK_FIELD_PREVIEW_PICTURE").": ".$arNewPicture."<br>";
			}*/
		}

		if($arDef["USE_WATERMARK_FILE"] === "Y")
		{
			\CIBLock::FilterPicture($arFile["tmp_name"], array(
				"name" => "watermark",
				"position" => $arDef["WATERMARK_FILE_POSITION"],
				"type" => "file",
				"size" => "real",
				"alpha_level" => 100 - min(max($arDef["WATERMARK_FILE_ALPHA"], 0), 100),
				"file" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_FILE"]),
			));
		}

		if($arDef["USE_WATERMARK_TEXT"] === "Y")
		{
			\CIBLock::FilterPicture($arFile["tmp_name"], array(
				"name" => "watermark",
				"position" => $arDef["WATERMARK_TEXT_POSITION"],
				"type" => "text",
				"coefficient" => $arDef["WATERMARK_TEXT_SIZE"],
				"text" => $arDef["WATERMARK_TEXT"],
				"font" => $_SERVER["DOCUMENT_ROOT"].Rel2Abs("/", $arDef["WATERMARK_TEXT_FONT"]),
				"color" => $arDef["WATERMARK_TEXT_COLOR"],
			));
		}
		return $arFile;
	}
	
	public function IsChangedImage($fileId, $arNewFile)
	{
		$fileId = (int)$fileId;
		if($this->params['ELEMENT_IMAGES_FORCE_UPDATE']=='Y' || !$fileId) return true;
		$arFile = \Bitrix\EsolImportxml\Utils::GetFileArray($fileId);
		$arNewFileVal = $arNewFile;
		if(isset($arNewFileVal['VALUE'])) $arNewFileVal = $arNewFileVal['VALUE'];
		if(isset($arNewFileVal['DESCRIPTION'])) $arNewFile['description'] = $arNewFile['DESCRIPTION'];
		list($width, $height, $type, $attr) = getimagesize($arNewFileVal['tmp_name']);
		if(($arFile['EXTERNAL_ID']==$arNewFileVal['external_id']
			|| ($arFile['FILE_SIZE']==$arNewFileVal['size'] 
				&& $arFile['ORIGINAL_NAME']==$arNewFileVal['name'] 
				&& (!$arFile['WIDTH'] || !$arFile['WIDTH'] || ($arFile['WIDTH']==$width && $arFile['HEIGHT']==$height))))
			&& file_exists($_SERVER['DOCUMENT_ROOT'].\Bitrix\Main\IO\Path::convertLogicalToPhysical($arFile['SRC']))
			&& (!isset($arNewFile['description']) || $arNewFile['description']==$arFile['DESCRIPTION']))
		{
			return false;
		}
		return true;
	}
	
	public function IsWrongExt($name, $ext)
	{
		return (bool)(substr($name, -(strlen($ext) + 1))!='.'.$ext);
	}
	
	public function PathContainsMask($path)
	{
		return (bool)((strpos($path, '*')!==false || (strpos($path, '{')!==false && strpos($path, '}')!==false)));
	}
	
	public function GetFilesByMask($mask)
	{
		$arFiles = array();
		$prefix = (strpos($mask, $_SERVER['DOCUMENT_ROOT'])===0 ? '' : $_SERVER['DOCUMENT_ROOT']);
		if(strpos($mask, '/*/')===false)
		{
			$arFiles = glob($prefix.$mask, GLOB_BRACE);
		}
		else
		{
			$i = 1;
			while(empty($arFiles) && $i<8)
			{
				$arFiles = glob($prefix.str_replace('/*/', str_repeat('/*', $i).'/', $mask), GLOB_BRACE);
				$i++;
			}
		}
		if(empty($arFiles)) return array();
		
		$arFiles = array_map(create_function('$n', 'return substr($n, strlen($_SERVER["DOCUMENT_ROOT"]));'), $arFiles);
		usort($arFiles, create_function('$a,$b', 'return strlen($a)<strlen($b) ? -1 : 1;'));
		return $arFiles;
	}
	
	public function GetArchiveParams($file)
	{
		$arUrl = parse_url($file);
		$fragment = (isset($arUrl['fragment']) ? $arUrl['fragment'] : '');
		if(strlen($fragment) > 0) $file = substr($file, 0, -strlen($fragment) - 1);
		$archivePath = $this->archivedir.md5($file).'/';
		return array(
			'path' => $archivePath, 
			'exists' => file_exists($archivePath),
			'file' => $archivePath.ltrim($fragment, '/')
		);
	}
	
	public function GetFileFromArchive($file)
	{
		$archiveParams = $this->GetArchiveParams($file);
		if(!$archiveParams['exists']) return false;
		return $archiveParams['file'];
	}
	
	public function IsEmptyPrice($arPrices)
	{
		if(is_array($arPrices))
		{
			foreach($arPrices as $arPrice)
			{
				if($arPrice['PRICE'] > 0)
				{
					return false;
				}
			}
		}
		return true;
	}
	
	public function GetHLBoolValue($val)
	{
		$res = $this->GetBoolValue($val);
		if($res=='Y') return 1;
		else return 0;
	}
	
	public function GetBoolValue($val, $numReturn = false, $defaultValue = false)
	{
		$trueVals = array_map('trim', explode(',', Loc::getMessage("ESOL_IX_FIELD_VAL_Y")));
		$falseVals = array_map('trim', explode(',', Loc::getMessage("ESOL_IX_FIELD_VAL_N")));
		if(in_array(ToLower($val), $trueVals))
		{
			return ($numReturn ? 1 : 'Y');
		}
		elseif(in_array(ToLower($val), $falseVals))
		{
			return ($numReturn ? 0 : 'N');
		}
		else
		{
			return $defaultValue;
		}
	}
	
	public function IsEmptyField($key, $arr)
	{
		if(!isset($arr[$key]) || (!is_array($arr[$key]) && strlen(trim($arr[$key]))==0) || (is_array($arr[$key]) && empty($arr[$key]))) return true;
		return false;
	}
	
	public function GenerateElementCode(&$arElement, $iblockFields)
	{
		if(($iblockFields['CODE']['IS_REQUIRED']=='Y' || $iblockFields['CODE']['DEFAULT_VALUE']['TRANSLITERATION']=='Y') && strlen($arElement['CODE'])==0 && strlen($arElement['NAME'])>0)
		{
			$arElement['CODE'] = $this->Str2Url($arElement['NAME'], $iblockFields['CODE']['DEFAULT_VALUE']);
			if($iblockFields['CODE']['DEFAULT_VALUE']['UNIQUE']=='Y')
			{
				$i = 0;
				while(($tmpCode = $arElement['CODE'].($i ? '-'.mt_rand() : '')) && \CIblockElement::GetList(array(), array('IBLOCK_ID'=>$arElement['IBLOCK_ID'], 'CODE'=>$tmpCode, 'CHECK_PERMISSIONS' => 'N'), array()) > 0 && ++$i){}
				$arElement['CODE'] = $tmpCode;
			}
		}
	}
	
	public function GetCurrencyRates()
	{
		if(!isset($this->currencyRates))
		{
			$arRates = array();
			$currFile = $this->tmpdir.'/currencies.txt';
			if(file_exists($currFile))
			{
				$arRates = unserialize(file_get_contents($currFile));
			}
			else
			{
				$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>20, 'disableSslVerification'=>true));
				$res = $client->get('http://www.cbr.ru/scripts/XML_daily.asp');
				if($res)
				{
					$xml = simplexml_load_string($res);
					if($xml->Valute)
					{
						foreach($xml->Valute as $val)
						{
							$arRates[(string)$val->CharCode] = $this->GetFloatVal((string)$val->Value);
						}
					}
				}
				file_put_contents($currFile, serialize($arRates));
			}
			$this->currencyRates = $arRates;
		}
		return $this->currencyRates;
	}
	
	public function ConversionReplaceValues($m)
	{
		if(preg_match('/^\{(([^\s\}]*[\'"][^\'"\}]*[\'"])*[^\s\}]*)\}$/', $m[0], $m2))
		{
			return $this->GetValueByXpath($m2[1]);
		}
		elseif(preg_match('/^\$\{[\'"](([^\s\}]*[\'"][^\'"\}]*[\'"])*[^\s\}]*)[\'"]\}$/', $m[0], $m2))
		{
			if(!isset($this->convParams)) $this->convParams = array();
			$this->convParams[$m2[1]] = $this->GetValueByXpath($m2[1]);
			$quot = substr(ltrim($m2[0], '${ '), 0, 1);
			return '$this->convParams['.$quot.$m2[1].$quot.']';
		}
		elseif($m[0]=='#HASH#')
		{
			$hash = md5(serialize($this->currentItemValues).serialize($this->params['FIELDS']).serialize($this->fparams));
			return $hash;
		}
		elseif(in_array($m[0], $this->rcurrencies))
		{
			$arRates = $this->GetCurrencyRates();
			$k = trim($m[0], '#');
			return (isset($arRates[$k]) ? floatval($arRates[$k]) : 1);
		}
		else return "";
	}
	
	public function GetValueByXpath($xpath, $simpleXmlObj=null, $singleVal=false)
	{
		if(preg_match('/^\d+$/', $xpath) && isset($this->currentItemValues[$xpath]))
		{
			$val = $this->currentItemValues[$xpath];
			if(is_array($val))
			{
				if($singleVal) $val = current($val);
				elseif(count(preg_grep('/\D/', array_keys($val)))==0) $val = implode($this->params['ELEMENT_MULTIPLE_SEPARATOR'], $val);
			}
			return $val;
		}
		if(preg_match('/^[\d,]*$/', $xpath))
		{
			return '{'.$xpath.'}';
		}
		
		$val = '';
		
		/*if(strlen($xpath) > 0) $arPath = explode('/', $xpath);
		else $arPath = array();
		$attr = $this->GetPathAttr($arPath);*/
		$arXPath = $this->GetXPathParts($xpath);
		$curXpath2 = $arXPath['xpath'];
		$subXpath = $arXPath['subpath'];
		$attr = $arXPath['attr'];
		$currentXmlObj = $this->currentXmlObj;
		if(isset($simpleXmlObj)) $currentXmlObj = $simpleXmlObj;
		
		if(strlen($curXpath2) > 0)
		{
			//$curXpath = '/'.ltrim($curXpath2, '/');
			$curXpath = ltrim($curXpath2, '/');
			if(strpos($curXpath, '.')!==0) $curXpath = '/'.$curXpath;
			if(substr($curXpath2, 0, 2)=='//') $curXpath = $curXpath2;
			if(isset($this->parentXpath) && strlen($this->parentXpath) > 0 && strpos($curXpath, $this->parentXpath)===0)
			{
				$tmpXpath = substr($curXpath, strlen($this->parentXpath) + 1);
				//$tmpXmlObj = $currentXmlObj->xpath($tmpXpath);
				$tmpXmlObj = $this->Xpath($currentXmlObj, $tmpXpath);
				if(!empty($tmpXmlObj))
				{
					$currentXmlObj = $tmpXmlObj;
					$curXpath = '';
				}
			}
			if(strlen($curXpath) > 0)
			{
				if(strpos($curXpath, $this->xpath)===0)
				{
					//$curXpath = $this->ReplaceXpath($curXpath);
					$curXpath = substr($curXpath, strlen($this->xpath) + 1);
				}
				elseif(isset($this->xmlPartObjects[$curXpath2]))
				{
					//$currentXmlObj = $this->xmlPartObjects[$curXpath2]->xpath($subXpath);
					$currentXmlObj = $this->Xpath($this->xmlPartObjects[$curXpath2], $subXpath);
					$curXpath = '';
				}
				elseif(substr($curXpath, 0, 2)=='//')
				{
					if(!isset($this->xmlSingleElems[$curXpath]))
					{
						$this->xmlSingleElems[$curXpath] = $this->GetPartXmlObject($curXpath, false);
					}
					$currentXmlObj = $this->xmlSingleElems[$curXpath];
					$curXpath = '';
				}
				elseif(substr($curXpath, 0, 1)=='.')
				{
					$node = $this->GetCurrentFieldNode();
					if($node!==false && ($tmpXmlObj = $this->Xpath($node, $curXpath)))
					{
						$currentXmlObj = $tmpXmlObj;
						$curXpath = '';
					}
				}
			}

			//if(strlen($curXpath) > 0) $simpleXmlObj2 = $currentXmlObj->xpath($curXpath);
			if(strlen($curXpath) > 0) $simpleXmlObj2 = $this->Xpath($currentXmlObj, ltrim($curXpath, '/'));
			else $simpleXmlObj2 = $currentXmlObj;
			if(count($simpleXmlObj2)==1) $simpleXmlObj2 = current($simpleXmlObj2);
		}
		else $simpleXmlObj2 = $currentXmlObj;
		//if(is_array($simpleXmlObj2)) $simpleXmlObj2 = current($simpleXmlObj2);
		
		if(is_array($simpleXmlObj2))
		{
			$arVals = array();
			foreach($simpleXmlObj2 as $sxml)
			{
				if($attr!==false)
				{
					if(is_callable(array($sxml, 'attributes')))
					{
						$arVals[] = (string)$sxml->attributes()->{$attr};
					}
				}
				else
				{
					$arVals[] = (string)$sxml;					
				}
			}
			if($singleVal) $val = current($arVals);
			else $val = implode($this->params['ELEMENT_MULTIPLE_SEPARATOR'], $arVals);
		}
		else
		{
			if($attr!==false)
			{
				if(is_callable(array($simpleXmlObj2, 'attributes')))
				{
					$val = (string)$simpleXmlObj2->attributes()->{$attr};
				}
			}
			else
			{
				$val = (string)$simpleXmlObj2;					
			}
		}
		
		$val = $this->GetRealXmlValue($val);		
		return $val;
	}
	
	public function GetCurrentFieldNode()
	{
		$key = $this->currentFieldKey;
		$arFields = $this->params['FIELDS'];
		if(!array_key_exists($key, $arFields)) return false;
		$field = $arFields[$key];
		list($xpath, $fieldName) = explode(';', $field, 2);
		$simpleXmlObj = $this->currentXmlObj;
		
		$conditionIndex = trim($this->fparams[$key]['INDEX_LOAD_VALUE']);
		$conditions = $this->fparams[$key]['CONDITIONS'];
		if(!is_array($conditions)) $conditions = array();
		foreach($conditions as $k2=>$v2)
		{
			if(preg_match('/^\{(\S*)\}$/', $v2['CELL'], $m))
			{
				$conditions[$k2]['XPATH'] = substr($m[1], strlen(trim($this->xpath, '/')) + 1);
			}
		}
		
		$xpath = substr($xpath, strlen(trim($this->xpath, '/')) + 1);
		$arPath = array_diff(explode('/', $xpath), array(''));
		$attr = $this->GetPathAttr($arPath);
		if(count($arPath) > 0)
		{
			$simpleXmlObj2 = $this->Xpath($simpleXmlObj, implode('/', $arPath));
			if(count($simpleXmlObj2)==1) $simpleXmlObj2 = current($simpleXmlObj2);
		}
		else $simpleXmlObj2 = $simpleXmlObj;
		
		$val = false;
		if(is_array($simpleXmlObj2))
		{
			$val = array();
			foreach($simpleXmlObj2 as $k=>$v)
			{
				if($this->CheckConditions($conditions, $xpath, $simpleXmlObj, $v, $k))
				{
					$val[] = $v;
				}
			}
			if(is_numeric($conditionIndex)) $val = $val[$conditionIndex - 1];
			elseif(count($val)==1) $val = current($val);
		}
		else
		{
			if($this->CheckConditions($conditions, $xpath, $simpleXmlObj, $simpleXmlObj2))
			{
				$val = $simpleXmlObj2;
			}
		}
	
		if(is_array($val))
		{
			if(array_key_exists($this->currentFieldIndex, $val)) $val = $val[$this->currentFieldIndex];
			else $val = current($val);
		}
		if(!($val instanceof \SimpleXMLElement)) $val = false;
		return $val;
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
		if(strlen($xpath) > 0 && $xpath!='.') return $simpleXmlObj->xpath($xpath);
		else return $simpleXmlObj;
	}
	
	public function GetOfferParentId()
	{
		return (isset($this->offerParentId) ? $this->offerParentId : false);
	}
	
	public function GetFieldSettings($key)
	{
		$fieldSettings = $this->fieldSettings[$key];
		if(!is_array($fieldSettings)) $fieldSettings = array();
		return $fieldSettings;
	}
	
	public function GetCurrentIblock()
	{
		return $this->params['IBLOCK_ID'];
	}
	
	public function GetFloatVal($val, $precision=0)
	{
		if(is_array($val)) $val = current($val);
		$val = floatval(preg_replace('/[^\d\.\-]+/', '', str_replace(',', '.', $val)));
		if($precision > 0) $val = round($val, $precision);
		return $val;
	}
	
	public function GetDateVal($val, $format = 'FULL')
	{
		$time = strtotime($val);
		if($time > 0)
		{
			return ConvertTimeStamp($time, $format);
		}
		return false;
	}
	
	public function GetSeparator($sep)
	{
		return strtr($sep, array('\r'=>"\r", '\n'=>"\n", '\t'=>"\t"));
	}
	
	public function Trim($str)
	{
		return \Bitrix\EsolImportxml\Utils::Trim($str);
	}
	
	public function Str2Url($string, $arParams=array())
	{
		return \Bitrix\EsolImportxml\Utils::Str2Url($string, $arParams);
	}
	
	public function GetRealXmlValue($val)
	{
		$val = \Bitrix\EsolImportxml\Utils::ConvertDataEncoding($val, $this->fileEncoding, $this->siteEncoding);
		if($this->params['HTML_ENTITY_DECODE']=='Y')
		{
			if(is_array($val))
			{
				foreach($val as $k=>$v)
				{
					$val[$k] = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, $this->siteEncoding);
				}
			}
			else
			{
				$val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, $this->siteEncoding);
			}
		}
		return $val;
	}
	
	public function SetLastError($error=false)
	{
		$this->lastError = $error;
	}

	public function GetLastError()
	{
		return $this->lastError;
	}
	
	public function OnShutdown()
	{
		$arError = error_get_last();
		if(!is_array($arError) || !isset($arError['type']) || !in_array($arError['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR))) return;
		
		$this->EndWithError(sprintf(Loc::getMessage("ESOL_IX_FATAL_ERROR"), $arError['type'], $arError['message'], $arError['file'], $arError['line']));
	}
	
	public function HandleError($code, $message, $file, $line)
	{
		return true;
	}
	
	public function HandleException($exception)
	{
		if(is_callable(array('\Bitrix\Main\Diag\ExceptionHandlerFormatter', 'format')))
		{
			$this->EndWithError(\Bitrix\Main\Diag\ExceptionHandlerFormatter::format($exception));
		}
		$this->EndWithError(sprintf(Loc::getMessage("ESOL_IX_FATAL_ERROR"), '', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
	}
	
	public function EndWithError($error)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		ob_end_clean();
		$this->errors[] = $error;
		$this->SaveStatusImport();
		echo '<!--module_return_data-->'.(\CUtil::PhpToJSObject($this->GetBreakParams()));
		die();
	}
}