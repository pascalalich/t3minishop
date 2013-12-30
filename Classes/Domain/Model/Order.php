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
class Order extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * positions
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\T3minishop\Domain\Model\OrderPosition>
	 */
	protected $positions;

	/**
	 * __construct
	 *
	 * @return Order
	 */
	public function __construct() {
		//Do not remove the next line: It would break the functionality
		$this->initStorageObjects();
	}

	/**
	 * Initializes all ObjectStorage properties.
	 *
	 * @return void
	 */
	protected function initStorageObjects() {
		/**
		 * Do not modify this method!
		 * It will be rewritten on each save in the extension builder
		 * You may modify the constructor of this class instead
		 */
		$this->positions = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Adds a OrderPosition
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\OrderPosition $position
	 * @return void
	 */
	public function addPosition(\TYPO3\T3minishop\Domain\Model\OrderPosition $position) {
		$this->positions->attach($position);
	}

	/**
	 * Removes a OrderPosition
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\OrderPosition $positionToRemove The OrderPosition to be removed
	 * @return void
	 */
	public function removePosition(\TYPO3\T3minishop\Domain\Model\OrderPosition $positionToRemove) {
		$this->positions->detach($positionToRemove);
	}

	/**
	 * Returns the positions
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\T3minishop\Domain\Model\OrderPosition> $positions
	 */
	public function getPositions() {
		return $this->positions;
	}

	/**
	 * Sets the positions
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\TYPO3\T3minishop\Domain\Model\OrderPosition> $positions
	 * @return void
	 */
	public function setPositions(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $positions) {
		$this->positions = $positions;
	}
	
	/**
	 * Calculates and returns the total price.
	 * @return float total price
	 */
	public function getTotal() {
		$total = 0.0;
		$this->positions->rewind();
		while ($this->positions->valid()) {
			$position = $this->positions->current();
			$total += $position->getPrice();
			$this->positions->next();
		}
		return $total;
	}

	/**
	 * 
	 * @param \TYPO3\T3minishop\Domain\Model\Product $product
	 */
	public function findOrCreatePositionForProduct(\TYPO3\T3minishop\Domain\Model\Product $product) {
		$position = $this->findPositionForProduct($product);
		
		if ($position === NULL) {
			$position = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\T3minishop\\Domain\\Model\\OrderPosition');
			$position->setProduct($product);
			$this->addPosition($position);
		}
		
		return $position;
	}
	
	/**
	 *
	 * @param \TYPO3\T3minishop\Domain\Model\Product $product
	 */
	private function findPositionForProduct(\TYPO3\T3minishop\Domain\Model\Product $product) {
		// assuming the title is unique
		$foundPosition = NULL;
		
		$this->positions->rewind();
		while ($this->positions->valid()) {
			$position = $this->positions->current();
			if ($position->getProduct()->getTitle() === $product->getTitle()) {
				$foundPosition = $position;
				break;
			}
			$this->positions->next();
		}
		
		return $foundPosition;
	}
	
	public function toArray() {
		$a = array();
		$a['positions'] = $this->getPositionsAsArray();
		return $a;
	}
	
	private function getPositionsAsArray() {
		$a = array();
		$this->positions->rewind();
		while ($this->positions->valid()) {
			$position = $this->positions->current();
			
			$a[$position->getProduct()->getTitle()] = $position->toArray();
			
			$this->positions->next();
		}
		return $a;
	}
	
	public function fromArray($orderArr) {
		$positionsArr = $orderArr['positions'];
		
		foreach ($positionsArr as $productTitle => $positionArr) {
			$position = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\T3minishop\\Domain\\Model\\OrderPosition');
			$position->fromArray($positionArr);
			$this->addPosition($position);
		}
	}
}
?>