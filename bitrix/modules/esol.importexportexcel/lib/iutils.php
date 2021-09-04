<?php
namespace Bitrix\KdaImportexcel;

class IUtils
{
	public static $moduleId = 'esol.importexportexcel';
	public static $moduleSubDir = 'import/';
	
	public static function GetCurUserID()
	{
		global $USER;
		if($USER && is_callable(array($USER, 'GetID'))) return $USER->GetID();
		else return 0;
	}
	
	public function Trim($str)
	{
		$str = trim($str);
		$str = preg_replace('/(^(\xC2\xA0|\s)+|(\xC2\xA0|\s)+$)/s', '', $str);
		return $str;
	}
	
	public static function Str2Url($string, $arParams=array())
	{
		if(!is_array($arParams)) $arParams = array();
		
		if(count($arParams)==0)
		{
			$arTransParams = \Bitrix\Main\Config\Option::get(static::$moduleId, 'TRANS_PARAMS', '');
			if(is_string($arTransParams) && !empty($arTransParams)) $arTransParams = unserialize($arTransParams);
			if(!is_array($arTransParams)) $arTransParams = array();
			if(!empty($arTransParams))
			{
				$arTransParams['TRANSLITERATION'] = 'Y';
				$arParams = $arTransParams;
			}
		}
		
		if($arParams['TRANSLITERATION']=='Y')
		{
			if($arParams['USE_GOOGLE']=='Y' && strlen(trim($string)) > 0 && ($apiKey = \Bitrix\Main\Config\Option::get('main', 'translate_key_yandex', '')))
			{
				$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>10, 'disableSslVerification'=>true));
				$client->setHeader('Content-Type', 'application/xml');
				$res = $client->get('https://translate.yandex.net/api/v1.5/tr.json/translate?key='.$apiKey.'&lang='.LANGUAGE_ID.'-en&text='.urlencode($string));
				$arRes = \CUtil::JSObjectToPhp($res);
				if(array_key_exists('code', $arRes) && $arRes['code']==200 && array_key_exists('text', $arRes))
				{
					$string = (is_array($arRes['text']) ? implode('', $arRes['text']) : $arRes['text']);
				}
			}

			if(isset($arParams['TRANS_LEN'])) $arParams['max_len'] = $arParams['TRANS_LEN'];
			if(isset($arParams['TRANS_CASE'])) $arParams['change_case'] = $arParams['TRANS_CASE'];
			if(isset($arParams['TRANS_SPACE'])) $arParams['replace_space'] = $arParams['TRANS_SPACE'];
			if(isset($arParams['TRANS_OTHER'])) $arParams['replace_other'] = $arParams['TRANS_OTHER'];
			if(isset($arParams['TRANS_EAT']) && $arParams['TRANS_EAT']=='N') $arParams['delete_repeat_replace'] = false;
		}
		return \CUtil::translit($string, LANGUAGE_ID, $arParams);
	}
	
	public static function DownloadTextTextByLink($val, $altVal='')
	{
		$client = new \Bitrix\Main\Web\HttpClient(array('socketTimeout'=>10, 'disableSslVerification'=>true));
		$path = (strlen(trim($altVal)) > 0 ? trim($altVal) : trim($val));
		if(strlen($path)==0) return '';
		$arUrl = parse_url($path);
		$res = trim($client->get($path));
		if($client->getStatus()==404) $res = '';
		$hct = ToLower($client->getHeaders()->get('content-type'));
		$siteEncoding = \CKDAImportUtils::getSiteEncoding();
		if(strlen($res) > 0 && class_exists('\DOMDocument') && $arUrl['fragment'])
		{
			/*Bom UTF-8*/
			if(\CUtil::DetectUTF8(substr($res, 0, 10000)) && (substr($res, 0, 3)!="\xEF\xBB\xBF"))
			{
				$res = "\xEF\xBB\xBF".$res;
			}
			/*/Bom UTF-8*/
			$doc = new \DOMDocument();
			$doc->preserveWhiteSpace = false;
			$doc->formatOutput = true;
			$doc->loadHTML($res);
			$node = $doc;
			$arParts = preg_split('/\s+/', $arUrl['fragment']);
			$i = 0;
			while(isset($arParts[$i]) && ($node instanceOf \DOMDocument || $node instanceOf \DOMElement))
			{
				$part = $arParts[$i];
				$tagName = (preg_match('/^([^#\.]+)([#\.].*$|$)/', $part, $m) ? $m[1] : '');
				$tagId = (preg_match('/^[^#]*#([^#\.]+)([#\.].*$|$)/', $part, $m) ? $m[1] : '');
				$arClasses = array_diff(explode('.', (preg_match('/^[^\.]*\.([^#]+)([#\.].*$|$)/', $part, $m) ? $m[1] : '')), array(''));
				if($tagName)
				{
					$nodes = $node->getElementsByTagName($tagName);
					if($tagId || !empty($arClasses))
					{
						$find = false;
						$key = 0;
						while(!$find && $key<$nodes->length)
						{
							$node1 = $nodes->item($key);
							$subfind = true;
							if($tagId && $node1->getAttribute('id')!=$tagId) $subfind = false;
							foreach($arClasses as $className)
							{
								if($className && !preg_match('/(^|\s)'.preg_quote($className, '/').'(\s|$)/is', $node1->getAttribute('class'))) $subfind = false;
							}
							$find = $subfind;
							if(!$find) $key++;
						}
						if($find) $node = $nodes->item($key);
						else $node = null;
					}
					else
					{
						$node = $nodes->item(0);
					}
				}
				$i++;
			}
			if($node instanceOf \DOMElement)
			{
				$innerHTML = '';
				$children = $node->childNodes;
				foreach($children as $child)
				{
					$innerHTML .= $child->ownerDocument->saveXML($child);
				}
				if(strlen($innerHTML)==0 && $node->nodeValue) $innerHTML = $node->nodeValue;
				$res = trim($innerHTML);
			}
			else
			{
				$res = '';
			}
			if($res && $siteEncoding!='utf-8')
			{
				$res = \Bitrix\Main\Text\Encoding::convertEncoding($res, 'utf-8', $siteEncoding);
			}
		}
		elseif(preg_match('/charset=(.+)(;|$)/Uis', $hct, $m))
		{
			$fileEncoding = ToLower(trim($m[1]));
			if($siteEncoding!=$fileEncoding)
			{
				$res = \Bitrix\Main\Text\Encoding::convertEncoding($res, $fileEncoding, $siteEncoding);
			}
		}
		else
		{
			if(\CUtil::DetectUTF8($res))
			{
				if($siteEncoding!='utf-8') $res = \Bitrix\Main\Text\Encoding::convertEncoding($res, 'utf-8', $siteEncoding);
			}
			elseif($siteEncoding=='utf-8') $res = \Bitrix\Main\Text\Encoding::convertEncoding($res, 'cp1251', $siteEncoding);
		}
		return $res;
	}
	
	public static function DownloadImagesFromText($val, $domain='')
	{
		$domain = trim($domain);
		$imgDir = '/upload/esol_images/';
		$arPatterns = array(
			'/<img[^>]*src=["\']([^"\']+)["\'][^>]*>/Uis',
			'/<a\s[^>]*href=["\']([^"\']+\.(jpg|jpeg|png|gif|svg|webp|bmp|pdf)(\?[^"\']*)?)["\'][^>]*>/Uis',
		);
		foreach($arPatterns as $pattern)
		{
			if(preg_match_all($pattern, $val, $m))
			{
				foreach($m[1] as $k=>$img)
				{
					if(strpos($img, '//')===0) $img = (($pos = strpos($domain, '//'))!==false ? substr($domain, 0, $pos) : 'http:').$img;
					elseif(strpos($img, '/')===0) $img = $domain.$img;
					$imgName = md5($img).'.'.preg_replace('/[#\?].*$/', '', bx_basename($img));
					$imgPathDir1 = $imgDir.substr($imgName, 0, 3).'/';
					$imgPathDir = $_SERVER['DOCUMENT_ROOT'].$imgPathDir1;
					$imgPath1 = $imgPathDir1.$imgName;
					$imgPath = $imgPathDir.$imgName;
					if(!file_exists(\Bitrix\Main\IO\Path::convertLogicalToPhysical($imgPath)))
					{
						CheckDirPath($imgPathDir);
						$ob = new \Bitrix\Main\Web\HttpClient(array('disableSslVerification'=>true, 'socketTimeout'=>15, 'streamTimeout'=>15));
						$ob->setHeader('User-Agent', \CKDAImportUtils::GetUserAgent());
						$ob->download($img, $imgPath);
					}
					$imgHtml = str_replace($m[1][$k], $imgPath1, $m[0][$k]);
					$val = str_replace($m[0][$k], $imgHtml, $val);
				}
			}
		}
		return $val;
	}
}