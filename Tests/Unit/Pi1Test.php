<?php
namespace Ppi\TemplaVoilaPlus\Tests\Unit;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * @author Alexander Schnitzler <typo3@alexanderschnitzler.de>
 */
class Pi1Test extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function inheritValueLogsErrorIfFirstParamNotAnArray() {
		/** @var $mockObject \tx_templavoilaplus_pi1 | \PHPUnit_Framework_MockObject_MockObject  */
		$mockObject = $this->getMock('tx_templavoilaplus_pi1', array('log'));
		$mockObject->expects($this->once())->method('log');
		$mockObject->inheritValue(NULL, NULL);
	}

	/**
	 * @test
	 */
	public function inheritValueLogsErrorIfvDefIsNotAKeyOfFirstParam() {
		/** @var $mockObject \tx_templavoilaplus_pi1 | \PHPUnit_Framework_MockObject_MockObject  */
		$mockObject = $this->getMock('tx_templavoilaplus_pi1', array('log'));
		$mockObject->expects($this->once())->method('log')->with('Key "vDEF" of array "$dV" doesn\'t exist');
		$mockObject->inheritValue(array(), 'vDEF');
	}

	/**
	 * @test
	 * @dataProvider inheritValueDataProvider
	 */
	public function inheritValueResultsWithParamMatrix($data, $expected) {
		/** @var $mockObject \tx_templavoilaplus_pi1 | \PHPUnit_Framework_MockObject_MockObject  */
		$mockObject = $this->getMock('tx_templavoilaplus_pi1', array('log'));
		$mockObject->inheritValueFromDefault = TRUE;

		$this->assertSame($expected, call_user_func_array(array($mockObject, 'inheritValue'), $data));
	}

	/**
	 * @return array
	 */
	public function inheritValueDataProvider() {
		return array(
			array(
				array(
					array('vDEF' => 'en', 'foo' => 'bar'),
					'foo',
				),
				'bar',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 'bar'),
					'vDEF',
				),
				'en',
			),
			array(
				array(
					array('vDEF' => '1', 'vFR' => '2'),
					'vFR',
				),
				'2',
			),
			array(
				array(
					array('vDEF' => '1'),
					'vFR',
				),
				'1',
			),
			array(
				array(
					array(),
					'vFR',
				),
				'',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => ''),
					'foo',
					'ifFalse'
				),
				'en',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => '0'),
					'foo',
					'ifFalse'
				),
				'en',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 0),
					'foo',
					'ifFalse'
				),
				'en',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => FALSE),
					'foo',
					'ifFalse'
				),
				'en',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 'bar'),
					'foo',
					'ifFalse'
				),
				'bar',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => ''),
					'foo',
					'ifBlank'
				),
				'en',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => FALSE),
					'foo',
					'ifBlank'
				),
				'en',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => '0'),
					'foo',
					'ifBlank'
				),
				'0',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 0),
					'foo',
					'ifBlank'
				),
				'0',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 'bar'),
					'foo',
					'never'
				),
				'bar',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 'bar'),
					'foo',
					'removeIfBlank'
				),
				'',
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => ''),
					'foo',
					'removeIfBlank'
				),
				array('ERROR' => '__REMOVE'),
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => 'bar'),
					'foo',
					''
				),
				'bar'
			),
			array(
				array(
					array('vDEF' => 'en', 'foo' => ''),
					'foo',
					''
				),
				'en'
			),
		);
	}
}
