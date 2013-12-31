<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	'TYPO3.' . $_EXTKEY,
	'Minishop',
	array(
		'Order' => 'showBasket', 'removePosition',
		
	),
	// non-cacheable actions
	array(
		'Order' => 'showBasket', 'removePosition',
		
	)
);

?>