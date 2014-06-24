<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'TYPO3.' . $_EXTKEY,
	'Minishop',
	array(
		'Order' => 'showMiniBasket, showBasket, removePosition, updateBasket, checkout, submitOrder, payViaPaypal, addProduct',
		'Product' => 'show',
	),
	// non-cacheable actions
	array(
		'Order' => 'showMiniBasket, showBasket, removePosition, updateBasket, checkout, submitOrder, payViaPaypal, addProduct',
		
	)
);

?>