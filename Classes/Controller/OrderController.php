<?php
namespace TYPO3\T3minishop\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Pascal Alich <pascal@alichs.de>
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 *
 * @package t3minishop
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class OrderController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	protected $logger;
	
	
	/**
	 * orderRepository
	 *
	 * @var \TYPO3\T3minishop\Domain\Repository\OrderRepository
	 * @inject
	 */
	protected $orderRepository;

	/**
	 * payment handler
	 * 
	 * @var \TYPO3\T3minishop\Payment\PaymentHandler
	 */
	protected  $paymentHandler;
	
	function __construct() {
		$this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
	}
	
	/**
	 * Action for showing a minimal basket version.
	 * 
	 * @return void
	 */
	public function showMiniBasketAction() {
		$order = $this->getOrderFromSession();
		$this->view->assign('order', $order);
		$basketPageUid = isset($this->settings['basketPid']) ? intval($this->settings['basketPid']) : '';
		$this->view->assign('basketPageUid', $basketPageUid);
	}
	
	/**
	 * action showBasket
	 *
	 * @return void
	 */
	public function showBasketAction() {
		$order = $this->getOrderFromSession();
		$this->view->assign('order', $order);
	}
	
	/**
	 * 
	 */
	public function updateBasketAction() {
		$order = $this->getOrderFromSession();

		$this->logger->info("updating basket", array (
				'request args' => $this->request->getArguments()
		));
		if ($this->request->hasArgument('positions')) {
			$positions = $this->request->getArgument('positions');
			$this->logger->info("updating quantities", array (
					'positions' => $positions
			));
			
			$order->updateQuantities($positions);
			$this->setOrderToSession($order);
		}
		
		if ($this->request->hasArgument('checkout')) {
			$this->forward('checkout');
		} else {
			$this->forward('showBasket', NULL, NULL, NULL);
		}
	}

	/**
	 * action addProduct
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Product $product
	 * @return void
	 */
	public function addProductAction(\TYPO3\T3minishop\Domain\Model\Product $product) {
		$this->addProduct($product);
		$this->redirect('show', 'product', NULL, array('product' => $product));
	}
	
	/**
	 * action addProduct
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Product $product
	 * @return void
	 */
	public function addProduct(\TYPO3\T3minishop\Domain\Model\Product $product) {
		$this->logger->info("addProduct action", array (
				'product' => $product->getTitle()
		));
		
		$order = $this->getOrderFromSession();
		
		$orderPos = $order->findOrCreatePositionForProduct($product);
		$orderPos->incrementQuantity();
		
		$this->setOrderToSession($order);
	}
	
	/**
	 * Remove a position from the basket
	 * 
	 * @param integer $id
	 */
	public function removePositionAction($id) {
		$this->logger->info("removePosition action", array (
				'position id' => $id
		));
		
		$order = $this->getOrderFromSession();
		
		$position = $order->findPositionById(intval($id));
		$this->logger->info ( "found position to remove", array (
				'position' => $position != NULL ? $position->getProduct()->getTitle() : "-"
		));
		if ($position != NULL) {
			$order->removePosition($position);
			$this->setOrderToSession($order);
		}
		
		$this->logger->info ("Before redirecting to basket");
		$this->redirect('showBasket', NULL, NULL, NULL);
	}
	
	/**
	 * Initiate checkout
	 */
	public function checkoutAction() {
		$this->logger->info("Initiating checkout...");
		
		$order = $this->getOrderFromSession();
		$this->view->assign('order', $order);
	}
	
	/**
	 * Submit order
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 */
	public function submitOrderAction(\TYPO3\T3minishop\Domain\Model\Order $order) {
		
		if ($this->request->hasArgument('showBasket')) {
			$this->forward('showBasket');
		} else {
			if ($this->validateBuyer($order->getBuyer())) {
				if ($this->request->hasArgument('payViaPaypal')) {
					$this->forward('payViaPaypal', NULL, NULL, array('order' => $order));
					
				} else if ($this->request->hasArgument('payViaPrepayment')) {
					// restore positions
					$sessionOrder = $this->getOrderFromSession();
					$order->setPositions($sessionOrder->getPositions());
					$this->saveOrder($order);
					
					$this->createDownloadConfigIfRequired($order);
					$this->sendNotifications($order, 'prepayment');
					$this->resetOrder();
					$this->flashMessageContainer->add('Vielen Dank für Ihre Bestellung!');
					$this->forward('showBasket');
				}
			} else {
				// validation errors
				// restore positions
				$sessionOrder = $this->getOrderFromSession();
				$order->setPositions($sessionOrder->getPositions());
				$this->setOrderToSession($order);
				$this->forward('checkout');
			}
		}
	}
	
	/**
	 * Forward and pay via PayPal
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 * @return void
	 */
	public function payViaPaypalAction(\TYPO3\T3minishop\Domain\Model\Order $order = NULL) {
		// return from paypal
		if ($this->request->hasArgument('paypalStatus')) {
			$status = $this->request->getArgument('paypalStatus');
			if ($status === 'success') {
				$order = $this->getOrderFromSession();
				$this->createDownloadConfigIfRequired($order);
				$this->sendNotifications($order, 'paypal');
				$this->resetOrder();
				$this->flashMessageContainer->add('Vielen Dank für Ihre Bestellung!');
				$this->forward('showBasket');
			} else if ($status === 'cancel') {
				$this->flashMessageContainer->add('Bezahlung mit PayPal abgebrochen!');
				$this->forward('checkout');
			}
		} else {
			// restore positions
			$sessionOrder = $this->getOrderFromSession();
		
			$order->setPositions($sessionOrder->getPositions());
			$this->saveOrder($order);
			$this->setOrderToSession($order);
			
			$this->view->assign('order', $order);
			$orderId = $order->getUid();
			$this->view->assign('orderId', $orderId);
			$this->view->assign('orderTitle', 'Thomas Steinlein Bestellung Nr. '.$orderId);
			$this->view->assign('transactionId', "t3minishop#$orderId");
			// defaults to test
			$paypalURL = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			if ($this->settings['paypalMode'] === 'production') {
				$paypalURL = 'https://www.paypal.com/cgi-bin/webscr';
			}
			$paypalReceiver = $this->settings['paypalAccount'];
			$returnMessage = $this->settings['paypalReturnMessage'];
			
			
			$successURL = $this->controllerContext->getUriBuilder()->reset()->setArguments(array('tx_t3minishop_minishop' => array('paypalStatus' => 'success')))->setCreateAbsoluteUri(true)->setUseCacheHash(false)->uriFor($actionName='payViaPaypal');
			$cancelURL = $this->controllerContext->getUriBuilder()->reset()->setArguments(array('tx_t3minishop_minishop' => array('paypalStatus' => 'cancel')))->setCreateAbsoluteUri(true)->setUseCacheHash(false)->uriFor($actionName='payViaPaypal');
			
			$arr = array(
					'url' => $paypalURL,
					'returnUrlSuccess' => $successURL,
					'returnMessage' => $returnMessage,
					'returnUrlCancel' => $cancelURL,
					'receiver' => $paypalReceiver
			);
			$this->logger->info("paypal action", array (
					'paypal vars' => $arr
			));
			$this->view->assign('paypal', $arr);
		}
	}
	
	/**
	 * Saves or updates the order in the database
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 */
	private function saveOrder(\TYPO3\T3minishop\Domain\Model\Order $order) {
		$uid = $order->getUid();
		if(isset($uid)) {
			$this->orderRepository->update($order);
			$persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
			$persistenceManager->persistAll();
			$this->logger->info("order updated", array (
					'uid' => $order->getUid()
			));
		} else {
			$this->orderRepository->add($order);
			$persistenceManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
			$persistenceManager->persistAll();
			$this->logger->info("order saved", array (
					'uid' => $order->getUid()
			));
		}
	}
	
	/**
	 * Validates the buyer infos.
	 * 
	 * If not valid, adds flash messages and returns false.
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Contact $buyer
	 * @return boolean true if buyer is valid, false otherwise
	 */
	private function validateBuyer(\TYPO3\T3minishop\Domain\Model\Contact $buyer) {
		$valid = true;
		if ($this->isEmpty($buyer->getName())) {
			$this->flashMessageContainer->add('Bitte geben Sie Ihren Namen ein.');
			$valid = false;
		}
		if ($this->isEmpty($buyer->getAddress())) {
			$this->flashMessageContainer->add('Bitte geben Sie Ihre Straße ein.');
			$valid = false;
		}
		if ($this->isEmpty($buyer->getCity())) {
			$this->flashMessageContainer->add('Bitte geben Sie Ihre Stadt ein.');
			$valid = false;
		}
		if ($this->isEmpty($buyer->getEmail())) {
			$this->flashMessageContainer->add('Bitte geben Sie Ihre E-Mail-Adresse ein.');
			$valid = false;
		}
		if(!filter_var($buyer->getEmail(), FILTER_VALIDATE_EMAIL)) {
			$this->flashMessageContainer->add('Bitte geben eine gültige E-Mail-Adresse ein.');
			$valid = false;
		}
		if ($this->isEmpty($buyer->getTelephone())) {
			$this->flashMessageContainer->add('Bitte geben Sie Ihre Telefonnummer ein.');
			$valid = false;
		}
		return $valid;
	}
	
	private function isEmpty($str) {
		return (strlen(trim($str)) === 0);
	}
	
	/**
	 * If order contains digital products, creates a download configuration for it.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 */
	private function createDownloadConfigIfRequired(\TYPO3\T3minishop\Domain\Model\Order $order) {
		$digitalProducts = $order->findDigitalProducts();
		if (count($digitalProducts) > 0) {
			$files = array();
			foreach($digitalProducts as $product) {
				$path = $product->getFilePath();  // something like 'fileadmin/music/01 Gospel Worship Airline (SongAusschnitt).mp3'
				$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository'); // create instance to storage repository
				$storage = $storageRepository->findByUid(1);    // get file storage with uid 1 (this should by default point to your fileadmin/ directory)
				$pathInFileAdmin = substr($path, 10);
				if ($this->isDownloadabeFile($pathInFileAdmin)) {
					$file = $storage->getFile($pathInFileAdmin); // create file object for the image (the file will be indexed if necessary)
					$this->logger->info("found file to download", array (
							'file' => $file->toArray()
					));
					$files[] = $file;
				} else {
					$dir = $storage->getFolder($pathInFileAdmin);
					foreach ($dir->getFiles() as $file) {
						if ($this->isDownloadabeFile($file->getName())) {
							$this->logger->info("found file to download", array (
									'file' => $file->toArray()
							));
							$files[] = $file;
						}
					}
				}
			}
			
			if (is_object($service = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstanceService('fileService'))) {
				// 14 days
				$validDuration = 14 * 24 * 60 * 60;
				$service->createDownloadConfiguration($files, time() + $validDuration, 'ORDER-'.$order->getUid());
			}
		}
	}
	
	private function isDownloadabeFile($fileNameOrPath) {
		$fileNameLowerCase = strtolower($fileNameOrPath);
		return $this->endsWith($fileNameLowerCase, '.mp3') || $this->endsWith($fileNameLowerCase, '.pdf');
	}
	
	// http://de2.php.net/ref.strings.php
	private function endsWith( $str, $sub ) {
	    return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
	}
	
	/**
	 * Sends notifiations to admin and customer of new order.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 * @param string $paymentMode
	 */
	private function sendNotifications(\TYPO3\T3minishop\Domain\Model\Order $order, $paymentMode) {
		$this->sendCustomerMail($order, $paymentMode);
		$this->sendAdminMail($order, $paymentMode);
	}
	
	/**
	 * Sends notification to admin.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 * @param string $paymentMode
	 */
	private function sendAdminMail(\TYPO3\T3minishop\Domain\Model\Order $order, $paymentMode) {
		$email ['subject'] = 'Neue Bestellung Nr. '.$order->getUid().' (' . ($paymentMode === 'prepayment' ? 'Vorkasse' : 'PayPal') . ')';
		$template = 'adminOrderMail.txt';
		$email['toEmail']   = $this->settings['emailTo'];
		$email['fromName']  = $order->getBuyer()->getName();
		$email['fromEmail'] = $order->getBuyer()->getEmail();
		$email['body'] = $this->getMailBody($order, $paymentMode, $template);
		if ($this->settings['emailBcc']) {
			$email['bcc'] = $this->settings['emailBcc'];
		}
		$this->sendMail($email);
	}
	
	/**
	 * Sends notification to customer.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 * @param string $paymentMode
	 */
	private function sendCustomerMail(\TYPO3\T3minishop\Domain\Model\Order $order, $paymentMode) {
		$email['subject']   = 'Ihre Bestellung Nr. '.$order->getUid();
		$template = 'customerOrderMail.txt';
		$email['toName']   = $order->getBuyer()->getName();
		$email['toEmail']   = $order->getBuyer()->getEmail();
		$email['fromName']  = $this->settings['emailFromName'];
		$email['fromEmail'] = $this->settings['emailFrom'];
		$email['body'] = $this->getMailBody($order, $paymentMode, $template);
		if ($this->settings['emailBcc']) {
			$email['bcc'] = $this->settings['emailBcc'];
		}
		$this->sendMail($email);
	}
	
	/**
	 * Sends a plain text mail.
	 *
	 * @param array $email the mail data
	 */
	private function sendMail($email) {
		if ($this->settings['email']) {
			$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
			$mail->setFrom(array($email['fromEmail'] => $email['fromName']));
			$mail->setTo(array($email['toEmail'] => $email['toName']));
			if ($email['bcc']) {
				$mail->setBcc(array($email['bcc']));
			}
			$mail->setSubject($email['subject']);
			$mail->setBody($email['body']);
			$mail->send();
		}
		$this->logger->info("order email sent", array (
				'email' => $email
		));
	}
	
	/**
	 * Renders the body of the notification mail to the customer.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 * @param string $paymentMode
	 * @param string $templateFileName name of the template
	 * @return string the mail body
	 */
	private function getMailBody(\TYPO3\T3minishop\Domain\Model\Order $order, $paymentMode, $templateFileName) {
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Fluid_View_StandaloneView');
		$templateFilePath = 'typo3conf/ext/t3minishop/Resources/Private/Templates/Order/'.$templateFileName;
		$view->setTemplatePathAndFilename($templateFilePath);
		$view->assign('order', $order);
		$view->assign('paymentMode', $paymentMode);
		return $view->render();
	}
	
	private function resetOrder() {
		$this->setOrderToSession(NULL);
	}
	
	private function getOrderFromSession() {
		$order = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\T3minishop\\Domain\\Model\\Order');
		
		$sessionOrder = $GLOBALS['TSFE']->fe_user->getKey('ses', 'TYPO3\\T3minishop\\Domain\\Model\\Order');
		if (is_array($sessionOrder)) {
			$this->logger->info ("serialized order from session", $sessionOrder);
			$order->fromArray($sessionOrder);
		}
		return $order;
	}
	
	private function setOrderToSession($order) {
		$sessionOrder = $order != NULL ? $order->toArray() : NULL;
		$this->logger->info("Serialized order before storing in session", array(
			'order' => $sessionOrder
		));
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'TYPO3\\T3minishop\\Domain\\Model\\Order', $sessionOrder);
	}
}
?>