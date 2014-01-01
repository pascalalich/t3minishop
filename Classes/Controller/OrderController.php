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
	
	function __construct() {
		$this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
	}
	
	/**
	 * orderRepository
	 *
	 * @var \TYPO3\T3minishop\Domain\Repository\OrderRepository
	 * @inject
	 */
	protected $orderRepository;

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
		}
		
		// restore positions
		$sessionOrder = $this->getOrderFromSession();
		$order->setPositions($sessionOrder->getPositions());
		
		$this->sendNotifications($order);
		$this->forward('showBasket');
	}
	
	/**
	 * Sends notifiations to admin and customer of new order.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 */
	private function sendNotifications(\TYPO3\T3minishop\Domain\Model\Order $order) {
		$this->sendCustomerMail($order);
		$this->sendAdminMail($order);
	}
	
	/**
	 * Sends notification to admin.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 */
	private function sendAdminMail(\TYPO3\T3minishop\Domain\Model\Order $order) {
		$email['subject']   = 'Neue Bestellung';
		$template = 'adminOrderMail.txt';
		$email['toEmail']   = $this->settings['emailTo'];
		$email['fromName']  = $order->getBuyer()->getName();
		$email['fromEmail'] = $order->getBuyer()->getEmail();
		$email['body'] = $this->getMailBody($order, $template);
		if ($this->settings['emailBcc']) {
			$email['bcc'] = $this->settings['emailBcc'];
		}
		$this->sendMail($email);
	}
	
	/**
	 * Sends notification to customer.
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Order $order
	 */
	private function sendCustomerMail(\TYPO3\T3minishop\Domain\Model\Order $order) {
		$email['subject']   = 'Ihre Bestellung';
		$template = 'customerOrderMail.txt';
		$email['toName']   = $order->getBuyer()->getName();
		$email['toEmail']   = $order->getBuyer()->getEmail();
		$email['fromName']  = $this->settings['emailFromName'];
		$email['fromEmail'] = $this->settings['emailFrom'];
		$email['body'] = $this->getMailBody($order, $template);
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
	 * @param string $templateFileName name of the template
	 * @return string the mail body
	 */
	private function getMailBody(\TYPO3\T3minishop\Domain\Model\Order $order, $templateFileName) {
		$view = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_Fluid_View_StandaloneView');
		$templateFilePath = 'typo3conf/ext/t3minishop/Resources/Private/Templates/Order/'.$templateFileName;
		$view->setTemplatePathAndFilename($templateFilePath);
		$view->assign('order', $order);
		return $view->render();
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
		$sessionOrder = $order->toArray();
		$this->logger->info ("serialized order before storing in session", $sessionOrder);
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'TYPO3\\T3minishop\\Domain\\Model\\Order', $sessionOrder);
	}
}
?>