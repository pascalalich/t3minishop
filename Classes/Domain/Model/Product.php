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
class Product extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * title
	 *
	 * @var \string
	 * @validate NotEmpty
	 */
	protected $title;

	/**
	 * Cover image
	 *
	 * @var \string
	 */
	protected $image;
	
	/**
	 * description
	 *
	 * @var \string
	 */
	protected $description;
	
	/**
	 * price
	 *
	 * @var \float
	 * @validate NotEmpty
	 */
	protected $price;
	
	/**
	 * is it a digital product for download?
	 * 
	 * @var \boolean
	 */
	protected $digital;

	/**
	 * path to file if digital product
	 * @var \string
	 */
	protected $filePath;

	/**
	 * Returns the title
	 *
	 * @return \string $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the title
	 *
	 * @param \string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * Returns the image
	 *
	 * @return \string $image
	 */
	public function getImage() {
		return $this->image;
	}
	
	/**
	 * Sets the image
	 *
	 * @param \string $image
	 * @return void
	 */
	public function setImage($image) {
		$this->image = $image;
	}
	

	/**
	 * Returns the description
	 *
	 * @return \string $description
	 */
	public function getDescription() {
		return $this->description;
	}
	
	/**
	 * Sets the description
	 *
	 * @param \string $description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Returns the price
	 *
	 * @return \float $price
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * Sets the price
	 *
	 * @param \float $price
	 * @return void
	 */
	public function setPrice($price) {
		$this->price = $price;
	}
	
	/**
	 * Is it a digital product for download?
	 * @return \boolean $digital
	 */
	public function isDigital() {
		return $this->digital;
	}
	
	/**
	 * Sets the digital property
	 * @param \boolean $digital
	 */
	public function setDigital($digital) {
		$this->digital = $digital;
	}
	
	
	/**
	 * @return \string path to file in case of a digital product
	 */
	public function getFilePath() {
		return $this->filePath;
	}
	
	/**
	 * Sets the file path for a digital product
	 * @param \string $filePath
	 */
	public function setFilePath($filePath) {
		$this->filePath = $filePath;
	}

	public function toArray() {
		$a = array();
		$a['uid'] = $this->getUid();
		$a['title'] = $this->getTitle();
		$a['price'] = $this->getPrice();
		$a['digital'] = $this->isDigital();
		$a['filePath'] = $this->getFilePath();
		return $a;
	}
	
	public function fromArray($productArr) {
		$this->uid = $productArr['uid'];
		$this->setTitle($productArr['title']);
		$this->setPrice($productArr['price']);
		$this->setDigital($productArr['digital']);
		$this->setFilePath($productArr['filePath']);
	}
}
?>