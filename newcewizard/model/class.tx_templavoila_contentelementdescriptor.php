<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Dmitry Dulepov <dmitry@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * $Id$
 */


/**
 * This class describes a content element.
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package	TYPO3
 * @subpackage	tx_templavoila
 */

class tx_templavoila_contentElementDescriptor {

	/**
	 * Element's cType
	 *
	 * @var	string
	 */
	protected	$cType = '';

	/**
	 * Element description
	 *
	 * @var	string
	 */
	protected	$elementDescription = '';

	/**
	 * Element title
	 *
	 * @var	string
	 */
	protected	$elementTitle = '';

	/**
	 * Element image (icon)
	 *
	 * @var	string
	 */
	protected	$image = '';

	/**
	 * Parameters to pass to alt_doc.php
	 *
	 * @var	string
	 */
	protected	$parameters = '';

	/**
	 * Retrieves additional parameters to the alt_doc.php
	 *
	 * @return	string	Parameters. Must start with & and be valid URL parameters
	 */
	public function getParameters() {
		return $this->parameters;
	}

	/**
	 * Sets default values for this instance
	 *
	 * @param	string	$defaultValues	Default values
	 * @return	void
	 */
	public function setParameters($parameters) {
		$this->parameters = $parameters;
	}

	/**
	 * Retrieves element image
	 *
	 * @return	string	Image
	 */
	public function getImage() {
		return $this->image;
	}

	/**
	 * Sets image for this instance
	 *
	 * @param	string	$imageTag	Image tag
	 * @return	void
	 */
	public function setImage($image) {
		$this->image = $image;
	}

	/**
	 * Retrieves element title
	 *
	 * @return	string	Title
	 */
	public function getTitle() {
		return $this->elementTitle;
	}

	/**
	 * Sets image tag for this instance
	 *
	 * @param	string	$imageTag	Image tag
	 * @return	void
	 */
	public function setTitle($title) {
		$this->elementTitle = $title;
	}

	/**
	 * Retrieves element description
	 *
	 * @return	string	Description
	 */
	public function getDescription() {
		return $this->elementDescription;
	}

	/**
	 * Sets image tag for this instance
	 *
	 * @param	string	$imageTag	Image tag
	 * @return	void
	 */
	public function setDescription($description) {
		$this->elementDescription = $description;
	}

	public function getCType() {
		return $this->cType;
	}

	public function setCType($cType) {
		$this->cType = $cType;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/model/class.tx_templavoila_contentElementDescriptor.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/newcewizard/model/class.tx_templavoila_contentElementDescriptor.php']);
}

?>