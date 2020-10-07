<?php
/**
 * Скрипт для пошагового обновления элементов и свойтсва.
 */
use Bitrix\Main;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

const IBLOCK_ID_OFFERS = 21;

$iblock = IBLOCK_ID_OFFERS;
$offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
$pageSize = 300;
$isEnd = true;
global $USER;

if (!$USER->IsAdmin())
{
	ShowError('access denied');
}
else if (!\Bitrix\Main\Loader::includeModule('iblock'))
{
	ShowError('iblock module not found');
}
else
{
	$arItems = array();
	$resUpdate = [];
	$localCount = 0;

	//Main\Application::getConnection()->startTracker();

	$query = Bitrix\Iblock\ElementTable::getList(array(
		'filter' => array(
			'ACTIVE' => 'Y',
			'=IBLOCK_ID' => IBLOCK_ID_OFFERS
		),
		'select' => array(
			'IBLOCK_ID',
			'ID',
			'NAME',
		),
		'order' => array(
			'ID' => 'DESC'
		),
		'limit' => $pageSize,
		'offset' => $offset,
	));

	//pr($query->getTrackerQuery()->getSql());

	//Main\Application::getConnection()->stopTracker();

	while ($item = $query->Fetch())
	{
		$arItems[] = $item;

		$localCount++;
	}

	if (!empty($arItems))
	{
		$el = new CIBlockElement;

		foreach ($arItems as $element) {

			$arLoadProductArray = Array(
				"MODIFIED_BY" => 1,
			);
			\CIBlockElement::SetPropertyValuesEx($element['ID'], $iblock, array("FILTER" => ''));

			$resUpdate[$element['ID']] = $el->Update($element['ID'], $arLoadProductArray);

		}
		//		pr($resUpdate);
		$isEnd = false;
	}

	$offset += ($localCount); // not found count
}

if ($isEnd)
{
	echo 'Ready';
}
else
{
	global $APPLICATION;
	$url = $APPLICATION->GetCurPageParam('offset=' . $offset, array('offset'));

	echo 'Count = ' . $offset . '<br/>';
	echo 'Progress...';

	?>
	<script>
		setTimeout(function() {
			window.location = <?= json_encode($url); ?>;
		}, 1000);
	</script>
	<?php
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");