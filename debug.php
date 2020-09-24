<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

\Bitrix\Main\Loader::includeModule('iblock');

class TestTimeFunctionDev {
	public static $IBLOCK_ID = 21;

	public function pregMatchNameProp($prop_fields, $arResult) {
		if (preg_match('/размер/', strtolower($prop_fields['NAME']), $matches)) {
			$arResult[$prop_fields['ID']] = [
				'ID' => $prop_fields['ID'],
				'NAME' => $prop_fields['NAME'],
				'CODE' => $prop_fields['CODE'],
				'PROPERTY_TYPE' => $prop_fields['PROPERTY_TYPE']
			];
		}
		return $arResult;
	}

	public static function getListPropertyOld($arg = []) {
		$arResult = [];

		$properties = \CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE" => "Y", "IBLOCK_ID" => self::$IBLOCK_ID));
		if (isset($arg['fetch']) && $arg['fetch']) {
			while ($prop_fields = $properties->fetch())
			{
				$arResult = static::pregMatchNameProp($prop_fields, $arResult);
			}
		} else {
			while ($prop_fields = $properties->GetNext(false, false))
			{
				if (preg_match('/размер/', strtolower($prop_fields['NAME']), $matches)) {
					$arResult[$prop_fields['ID']] = [
						'ID' => $prop_fields['ID'],
						'NAME' => $prop_fields['NAME'],
						'CODE' => $prop_fields['CODE'],
						'PROPERTY_TYPE' => $prop_fields['PROPERTY_TYPE']
					];
				}
			}
		}
		return $arResult;
	}
	public static function getListPropertyD7()
	{
		$arResult = [];

		$arResultD7 = \Bitrix\Iblock\PropertyTable::getList([
			'filter' => ["ACTIVE"=>"Y", "IBLOCK_ID" => self::$IBLOCK_ID],
			'select' => ['ID', 'NAME', 'CODE', 'PROPERTY_TYPE']
		])->fetchAll();

		foreach ($arResultD7 as $prop) {
			if (preg_match('/размер/', strtolower($prop['NAME']))) {
				$arResult[$prop['ID']] = $prop;
			}
		}
		return $arResult;
	}

	public static function defaultFunction()
	{
		return true;
	}

	protected static function setTimeMark()
	{
		return microtime(true);
	}

	protected static function endTimeMark($start)
	{
		return microtime(true) - $start;
	}

	public static function checkBuildCode($nameFunction = false, $arg = [])
	{
		if (!$nameFunction && false) {
			return $nameFunction;
		}

		$nameFunction = 'defaultFunction';

		$start = self::setTimeMark();


		static::$nameFunction($arg);

		//call_user_func([static::class, $nameFunction], $arg);

		return self::endTimeMark($start);
	}
}

//pr('old GetNext Time: ' . TestTimeFunctionDev::checkBuildCode('getListPropertyOld'));
//pr('old fetch Time: ' . TestTimeFunctionDev::checkBuildCode('getListPropertyOld', ['fetch' => true]));

//pr('d7 Time: ' . TestTimeFunctionDev::checkBuildCode('getListPropertyD7'));

$resProp = TestTimeFunctionDev::getListPropertyD7();
//pr(TestTimeFunctionDev::getListPropertyOld());
//pr(TestTimeFunctionDev::getListPropertyOld(['fetch' => true]));
pr($resProp);

$arProps['PROPERTY_MAP'] = [
	'PREFIX' => ['PROPERTY_'],
	'CODES' => [],
	'IDS' => [],
];

foreach ($resProp as $prop) {
	$arProps['PROPERTY_MAP']['CODES'][] = $prop["CODE"];
	$arProps['PROPERTY_MAP']['IDS'][] = $prop["ID"];
	$arProps['PROPERTY_MAP']['TYPE'][] = $prop["PROPERTY_TYPE"];
}

pr($arProps);
function addPrefixStringWalk(&$array, $key, $prefix)
{
	if (is_array($array['CODES']) && !empty($array['CODES'])) {
		foreach ($array['CODES'] as $itemArray) {
			$array["FORMATTED"][] = $prefix . $itemArray;
		}
	}
	unset($array);
}

function addPrefixStringMap($array, $prefix)
{
	$result = [];
	if (is_array($array['CODES']) && !empty($array['CODES'])) {
		foreach ($array['CODES'] as $itemArray) {
			$result[] = $prefix . $itemArray;
		}
	}
	return $result;
}

$additionalArPropSelect = [];
if (true) {
	array_walk($arProps, 'addPrefixStringWalk', $arProps['PROPERTY_MAP']['PREFIX'][0]);
	$additionalArPropSelect = $arProps['PROPERTY_MAP']['FORMATTED'];
} else {
	$additionalArPropSelect = array_map('addPrefixStringMap', $arProps, $arProps['PROPERTY_MAP']['PREFIX'])[0];
}

$arSelect = array_merge(['ID', 'IBLOCK_ID'], $additionalArPropSelect);

$rsElement = \CIBlockElement::GetList([],
	[
		'=ID' => 214010,
	],
	false,
	['nTopCount' => 1],
	$arSelect
);
if ($objElement = $rsElement->GetNextElement(false, false)) {
	$arResultElement = $objElement->GetFields();
	//	foreach ($arProps['PROPERTY_MAP']['IDS'] as $idProp) {
	//		$arResultElement['PROP'][] = $objElement->GetProperty($idProp);
	//	}
	$arResultElement['PROPERTIES'] = $objElement->GetProperties([], ['ID' => $arProps['PROPERTY_MAP']['IDS']]);

	pr($arResultElement);
	pr(count($arResultElement['PROPERTIES']));
}

?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>