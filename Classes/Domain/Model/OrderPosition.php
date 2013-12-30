<?php
namespace TYPO3\T3minishop\Domain\Model;

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
class OrderPosition extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * quantity
	 *
	 * @var \integer
	 * @validate NotEmpty
	 */
	protected $quantity;

	/**
	 * product
	 *
	 * @var \TYPO3\T3minishop\Domain\Model\Product
	 */
	protected $product;

	/**
	 * Returns the quantity
	 *
	 * @return \integer $quantity
	 */
	public function getQuantity() {
		return $this->quantity;
	}

	/**
	 * Sets the quantity
	 *
	 * @param \integer $quantity
	 * @return void
	 */
	public function setQuantity($quantity) {
		$this->quantity = $quantity;
	}

	/**
	 * Returns the product
	 *
	 * @return \TYPO3\T3minishop\Domain\Model\Product $product
	 */
	public function getProduct() {
		return $this->product;
	}

	/**
	 * Sets the product
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Product $product
	 * @return void
	 */
	public function setProduct(\TYPO3\T3minishop\Domain\Model\Product $product) {
		$this->product = $product;
	}

	/**
	 * Increments the quantity by 1.
	 */
	public function incrementQuantity() {
		if (isset($this->quantity)) {
			$this->quantity++;
		} else {
			$this->quantity = 1;
		}
	}
	
}
?>