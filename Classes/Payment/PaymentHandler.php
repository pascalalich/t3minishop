<?php

namespace TYPO3\T3minishop\Payment;

/**
 * *************************************************************
 * Copyright notice
 *
 * (c) 2013 Pascal Alich <pascal@alichs.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 * *************************************************************
 */
require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath ( 'transactor' ) . 'lib/class.tx_transactor_api.php';
// \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance ( 'TYPO3\\CMS\\Core\\Log\\LogManager' )->getLogger ( 'requireonce' )->info ( 'transactor loaded', array (
// 'path' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath ( 'transactor' ) . 'lib/tx_transactor_api.php'
// ) );

/**
 *
 * @package t3minishop
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *         
 */
class PaymentHandler {
	protected $logger;
	function __construct() {
		$this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance ( 'TYPO3\\CMS\\Core\\Log\\LogManager' )->getLogger ( __CLASS__ );
	}
	
	/**
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order        	
	 */
	public function initPayment(\TYPO3\T3minishop\Domain\Model\Order $order) {
		$this->logger->info ( "initializing payment", array (
				'total' => $order->getTotal () 
		) );
		
		$handleLib = 'transactor';
		
		$config = array (
				'gatewaymode' => 'form', 
				'extName' => 'transactor_paypal',
				'paymentMethod' => 'paypal_webpayment_standard',
				'returnPID' => 78,
				'currency' => 'EUR'
		);
		
		$bFinalize = FALSE;
		$bFinalVerify = FALSE;
		$markerArray;
		$templateFilename;
		$localTemplateCode;
		$errorMessage = '';
		
		$paymentActivity = 'finalize';
		$deliveryNote = 'Delivery note';
		
		$itemArray = array(
			'' => array(
				0 => array(
					'rec' => array(	
						'itemnumber' => '7',
						'title' => 'Test Title',
						'totalNoTax' => 7.5
					),
					'count' => 5,
					'priceNoTax' => 1.5,
					'priceTax' => 1.5,
					'totalTax' => 0.0,
					'handling' => 0.0
				),
			)
		);
		$calculatedArray = array(
			'priceNoTax' => array(
				'goodstotal' => 7.7,
				'shipping' => 10.0,
				'total' => 17.7,
				'vouchertotal' => 17.7
			),
			'priceTax' => array(
				'goodstotal' => 7.7,
				'shipping' => 10.0,
				'total' => 17.7,
				'vouchertotal' => 17.7
			),
				
		);
		$buyer = $order->getBuyer();
		$address = array(
			'name' => $buyer->getName(),
			'address' => $buyer->getAddress(),
			'zip' => $this->getZip($buyer),
			'city' => $this->getCity($buyer),
			'telephone' => $buyer->getTelephone(),
			'email' => $buyer->getEmail(),
			'country' => 'Deutschland'
		);
		$infoArray = array(
			'billing' => $address,
			'delivery' => $address
		);
		
		\tx_transactor_api::init(NULL, NULL, array());
		
		$result = \tx_transactor_api::includeHandleLib ( $handleLib, $config, 't3minishop', $itemArray, $calculatedArray, $deliveryNote, $paymentActivity, $currentPaymentActivity, $infoArray, $pidArray, $linkParams, 'TRACKINGCODE', 'ORDERID', $cardRow, $bFinalize, $bFinalVerify, $markerArray, $templateFilename, $localTemplateCode, $errorMessage);
		
		
		
		$this->logger->info ("after payment lib called", array(
			'result' => $result,
			'error' => $errorMessage,
			'marker' => $markerArray,
			'template file' => $templateFilename,
			'template code' => $localTemplateCode	
		));
		
		return $markerArray;
	}
	
	/**
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Contact $buyer
	 */
	private function getZip(\TYPO3\T3minishop\Domain\Model\Contact $buyer) {
		$pos = strpos($buyer->getCity(), " ");
		if ($pos === false) {
			return "";
		} else {
			return substr($buyer->getCity(), 0, $pos);
		}
	}
	/**
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Contact $buyer
	 */
	private function getCity(\TYPO3\T3minishop\Domain\Model\Contact $buyer) {
		$pos = strpos($buyer->getCity(), " ");
		if ($pos === false) {
			return "";
		} else {
			if (strlen($buyer->getCity()) > $pos+1) {
				return substr($buyer->getCity(), $pos+1);
			} else {
				return "";
			}
		}
	}
	
}
?>