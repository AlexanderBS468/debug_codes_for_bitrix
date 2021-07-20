<?php

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

global $USER;

if (!$USER->IsAdmin())
{
	ShowError('access denied');
}

class CheckCatalogPriceTable
{
	public const PAGE_SIZE = 35000;

	public static function getPriceTable($offset) : array
	{
		//		$offset = isset($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;

		$arItems = [];
		$pageSize = self::PAGE_SIZE;
		$localCount = 0;

		//		\Bitrix\Main\Application::getConnection()->startTracker();

		$rsRowPrice = \Bitrix\Catalog\PriceTable::GetList([
			'order' => array(
				'ID' => 'ASC'
			),
			'limit' => $pageSize,
			'offset' => $offset,
		]);
		while ($row = $rsRowPrice->fetch())
		{
			$arItems[] = $row;

			$localCount++;
		}

		//		pr($rsRowPrice->getTrackerQuery()->getSql());
		//		\Bitrix\Main\Application::getConnection()->stopTracker();

		return [
			'ITEMS' => $arItems,
			'offset' => $offset += ($localCount)
		];
	}


	protected static function printData($obj)
	{
		$maxHeight = 'max-height:150px;';
		$maxWidth = 'max-width:800px;';
		echo '<pre style="'. $maxHeight . $maxWidth .'font:normal 10pt/12pt monospace;background:#fff;color:#000;margin:10px;padding:10px;border:1px solid red;text-align:left;overflow:scroll">';
		echo htmlspecialcharsEx(print_r($obj, true));
		echo '</pre>';

		return true;
	}

	public static function outPutData($res, $type = 'default'): void
	{
		if($res)
		{
			switch ($type)
			{
				case 'uniqall_products':
					self::printData([
						'offset' => $res['offset'],
						'items' => array_unique(array_column($res['ITEMS'], 'PRODUCT_ID'))
					]);
					break;
				case 'default':
				default:
					$arOutPutData = array_combine(
						array_column($res['ITEMS'], 'ID'),
						array_column($res['ITEMS'], 'PRODUCT_ID')
					);
					self::printData([
						'offset' => $res['offset'],
						'items' => $arOutPutData
					]);
			}
		} else
		{
			self::printData('error-data');
		}
	}

	protected static function findProducts($idsProducts): array
	{
		$catalogIblockId = (defined('CATALOG_IBLOCK_ID') && CATALOG_IBLOCK_ID ? CATALOG_IBLOCK_ID : 3);
		$offerIblockId = (defined('OFFER_IBLOCK_ID') && OFFER_IBLOCK_ID ? OFFER_IBLOCK_ID : 4);

		$arElements = $arAllElements = [];
		if($idsProducts)
		{
			$rowDBelement = \Bitrix\Iblock\ElementTable::getList([
				'filter' => [
					'ID' => $idsProducts,
					'IBLOCK_ID' => [
						$catalogIblockId,
						$offerIblockId
					],
				],
				'select' => [
					'ID',
					'IBLOCK_ID'
				]
			]);

			while($item = $rowDBelement->fetch())
			{
				$arAllElements[] = $item['ID'];
			}

			$arElements = array_unique($arAllElements);
		}

		return $arElements;
	}

	protected static function removeUnnecessaryRows($arItems)
	{
		$idsProducts = array_unique(array_column($arItems, 'PRODUCT_ID'));
		$arFindProducts = self::findProducts($idsProducts);
		foreach ($idsProducts as $productFromPrice)
		{
			if(!in_array($productFromPrice, $arFindProducts))
			{
				//todo function exec
				\Bitrix\Catalog\PriceTable::deleteByProduct($productFromPrice);
				self::printData('del - ' . $productFromPrice);
				file_put_contents($_SERVER["DOCUMENT_ROOT"]."/logprices.txt", $productFromPrice . PHP_EOL, FILE_APPEND);
			}
		}
	}

	public static function run(): void
	{
		$res = self::getPriceTable(0);
		self::outPutData($res, 'uniqall_products');
		if (count($res['ITEMS']) > 0)
		{
			self::removeUnnecessaryRows($res['ITEMS']);
			global $APPLICATION;
			self::ajaxGetResponse($APPLICATION->GetCurPageParam('NEXT=Y&offset=' . $res['offset'], array('offset', 'START')));
		}
	}

	public static function nextItems($offset): void
	{
		$res = self::getPriceTable($offset);
		self::outPutData($res, 'uniqall_products');
		if (count($res['ITEMS']) > 0)
		{
			self::removeUnnecessaryRows($res['ITEMS']);
			global $APPLICATION;
			self::ajaxGetResponse($APPLICATION->GetCurPageParam('NEXT=Y&offset=' . $res['offset'], array('offset', 'START')));
		}
	}

	public static function ajaxGetResponse($url)
	{
		?><script>
		setTimeout(function() {
			getResponse('<?=$url?>')
		}, 1000);
	</script><?php
	}

	public static function ajaxScriptHtml() : void
	{
		CJSCore::Init(array("fx"));
		global $APPLICATION;
		$APPLICATION->ShowHead();
		?>
		<script>
			function getResponse(url) {
				console.log(url);
				BX.ajax({
					timeout: 60,
					method: 'POST',
					dataType: 'html',
					url: url,

					onsuccess: function (result)
					{
						let item = BX.create('div', {
							props: {
								className: 'item-row'
							},
							html: result,
						});

						BX('container_data').append(item);
					},
					onfailure : function(result)
					{
						console.log(result);
					}
				});
			}
		</script>
		<?php
	}
}

define('DELETE_UNNECESSARY', false);
define('CATALOG_IBLOCK_ID', '3');
define('OFFER_IBLOCK_ID', '4');

if ((defined(DELETE_UNNECESSARY) && DELETE_UNNECESSARY) || DELETE_UNNECESSARY === true)
{
	$queryList = \Bitrix\Main\Context::getCurrent()->getRequest()->getQueryList()->toArray();

	if ($queryList['START'] === 'Y')
	{
		echo 'RUN';
		CheckCatalogPriceTable::run();
		?>
		<head>
			<?CheckCatalogPriceTable::ajaxScriptHtml();?>
		</head>
		<div id="container_data"></div>
		<?php
	}
	else if ($queryList['NEXT'] === 'Y' && $queryList['offset'] > 0)
	{
		CheckCatalogPriceTable::nextItems($queryList['offset']);
	}
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");