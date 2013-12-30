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
	 * action addProduct
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Product $product
	 * @return void
	 */
	public function addProductAction(\TYPO3\T3minishop\Domain\Model\Product $product) {
		$this->logger->info ( "addProduct action", array (
				'product' => $product->getTitle()
		));
		
		$order = $this->getOrderFromSession();
		
		$orderPos = $order->findOrCreatePositionForProduct($product);
		$orderPos->incrementQuantity();
		
		$this->setOrderToSession($order);
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