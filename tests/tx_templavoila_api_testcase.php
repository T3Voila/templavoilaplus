<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Robert Lemke (robert@typo3.org)
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
 * Test case for checking the TemplaVoila 1.0 API
 *
 * NOTE:    This test case assumes that you have installed TemplaVoila (of course ...).
 *          It will create pages, datastructures and template objects with certain titles
 *          (see variables below). All othe elements with the same title will be DELETED!
 * 
 * WARNING: Never ever run a unit test like this on a live site!
 *         
 *
 * @author	Robert Lemke <robert@typo3.org>
 */
 

require_once (t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');
require_once (PATH_t3lib.'class.t3lib_tcemain.php');

class tx_templavoila_api_testcase extends tx_t3unit_testcase {

	protected $apiObj;
	protected $testPageTitle = '*** t3unit templavoila testcase page ***';
	protected $testPageTitleDE = '*** t3unit templavoila testcase page DE ***';
	protected $testPageDSTitle = '*** t3unit templavoila testcase page template ds ***';
	protected $testPageTOTitle = '*** t3unit templavoila testcase page template to ***';
	protected $testTSTemplateTitle = '*** t3unit templavoila testcase template ***';
	protected $testCEHeader = '*** t3unit templavoila testcase content element ***';
	protected $testFCEDSTitle = '*** t3unit templavoila testcase FCE template ds ***';
	protected $testFCETOTitle = '*** t3unit templavoila testcase FCE template to ***';
	protected $testPageUID;
	protected $testPageDSUID;
	protected $testPageTOUID;
	
	protected $workspaceIdAtStart;
	
	public function __construct ($name) {
		global $TYPO3_DB, $BE_USER;

		parent::__construct ($name);
		$TYPO3_DB->debugOutput = TRUE;
		
		$this->apiObj = new tx_templavoila_api;
		$this->workspaceIdAtStart = $BE_USER->workspace;
		$BE_USER->setWorkspace(0);	
	}

	public function setUp() {
		global $TYPO3_DB;
		
		$TYPO3_DB->exec_DELETEquery ('tt_content', 'header LIKE "'.$this->testCEHeader.'%"');
		$TYPO3_DB->exec_DELETEquery ('pages', 'title="'.$this->testPageTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('pages_language_overlay', 'title="'.$this->testPageTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('tx_templavoila_datastructure', 'title="'.$this->testPageDSTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('tx_templavoila_tmplobj', 'title="'.$this->testPageTOTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('sys_template', 'title="'.$this->testTSTemplateTitle.'"');
	}
	
	public function tearDown () {
		global $BE_USER, $TYPO3_DB;
return;		
		$BE_USER->setWorkspace($this->workspaceIdAtStart);	

		$TYPO3_DB->exec_DELETEquery ('tt_content', 'header LIKE "'.$this->testCEHeader.'%"');
		$TYPO3_DB->exec_DELETEquery ('pages', 'title="'.$this->testPageTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('pages_language_overlay', 'title="'.$this->testPageTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('tx_templavoila_datastructure', 'title="'.$this->testPageDSTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('tx_templavoila_tmplobj', 'title="'.$this->testPageTOTitle.'"');
		$TYPO3_DB->exec_DELETEquery ('sys_template', 'title="'.$this->testTSTemplateTitle.'"');
	}





	/*********************************************************
	 *
	 * INSERT ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_insertElement_basic() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Prepare the new content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;

		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '0'
		);
				
			// run insertElement():
		$elementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($elementUid !== FALSE, 'Inserting a new element was not successful, insertElement() returned FALSE');
		
		 	// Check if the new record really exists:
		$fields = implode (',', array_keys ($row)) . ',uid';
		$fetchedRow = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUid, $fields);

		$recordsAreTheSame = count (array_intersect_assoc ($row, $fetchedRow)) == count ($row);		
		self::assertTrue ($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');
		 
		 	// Check if the new record has been inserted correctly into the references list in table "pages":
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], (string)$elementUid, 'The reference from the test page to the element created by insertElement() is not as expected!');


			// Prepare the A SECOND content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;
		$row['bodytext'] = 'SECOND content element';

		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '0'		// Before first element
		);
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');
				
			// run insertElement():
		$secondElementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($secondElementUid !== FALSE, 'Inserting the second element was not successful, insertElement() returned FALSE');
		
		 	// Check if the new record really exists:
		$fields = implode (',', array_keys ($row)) . ',uid';
		$fetchedRow = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$secondElementUid, $fields);

		$recordsAreTheSame = count (array_intersect_assoc ($row, $fetchedRow)) == count ($row);		
		self::assertTrue ($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');
		 
		 	// Check if the new record has been inserted correctly before the first one:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');

		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $secondElementUid.','.$elementUid, 'The reference list the elements created by insertElement() is not as expected!');


			// Prepare the A THIRD content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;
		$row['bodytext'] = 'THIRD content element';

		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '1',		// After first element
			'targetCheckUid' => $secondElementUid
		);
				
			// run insertElement():
		$thirdElementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($thirdElementUid !== FALSE, 'Inserting the third element was not successful, insertElement() returned FALSE');

		 	// Check if the new record really exists:
		$fields = implode (',', array_keys ($row)) . ',uid';
		$fetchedRow = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$thirdElementUid, $fields);

		$recordsAreTheSame = count (array_intersect_assoc ($row, $fetchedRow)) == count ($row);		
		self::assertTrue ($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');
		 
		 	// Check if the new record has been inserted correctly behind the second one:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $secondElementUid.','.$thirdElementUid.','.$elementUid, '(Third element) The reference list the elements created by insertElement() is not as expected!');
	}

	public function test_insertElement_basic_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (-1);
		
		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Prepare the new content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;

		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '0'
		);
				
			// run insertElement():
		$elementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($elementUid !== FALSE, 'Inserting a new element was not successful, insertElement() returned FALSE');
		
		 	// Check if the new record really exists:
		$fields = implode (',', array_keys ($row)) . ',uid';
		$fetchedRow = t3lib_beFunc::getRecordWSOL ('tt_content', $elementUid, $fields);

		$recordsAreTheSame = 
			$row['CType'] == $fetchedRow['CType'] &&
			$row['header'] == $fetchedRow['header'] &&
			$row['bodytext'] == $fetchedRow['bodytext'] &&
			$elementUid == $fetchedRow['uid'] &&
			-1 == $fetchedRow['_ORIG_pid']
		;
		self::assertTrue ($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');
		 
		 	// Check if the new record has been inserted correctly into the references list in table "pages":
		$testPageRecord = t3lib_beFunc::getRecordWSOL ('pages', $this->testPageUID, 'tx_templavoila_flex,uid,pid,t3ver_swapmode');
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");		
		self::assertEquals ((string)$xpathResArr[0], (string)$elementUid, 'The reference from the test page to the element created by insertElement() is not as expected!');


			// Prepare the A SECOND content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;
		$row['bodytext'] = 'SECOND content element';

		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '0'		// Before first element
		);
				
			// run insertElement():
		$secondElementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($secondElementUid !== FALSE, 'Inserting the second element was not successful, insertElement() returned FALSE');
		
		 	// Check if the new record really exists:
		$fields = implode (',', array_keys ($row)) . ',uid';
		$fetchedRow = t3lib_beFunc::getRecordWSOL('tt_content', $secondElementUid, $fields);
		$recordsAreTheSame = 
			$row['CType'] == $fetchedRow['CType'] &&
			$row['header'] == $fetchedRow['header'] &&
			$row['bodytext'] == $fetchedRow['bodytext'] &&
			$secondElementUid == $fetchedRow['uid'] &&
			-1 == $fetchedRow['_ORIG_pid']
		;
		self::assertTrue ($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');
		 
		 	// Check if the new record has been inserted correctly before the first one:
		$testPageRecord = t3lib_beFunc::getRecordWSOL('pages', $this->testPageUID, 'tx_templavoila_flex,uid,pid,t3ver_swapmode');
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);		
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $secondElementUid.','.$elementUid, 'The reference list the elements created by insertElement() is not as expected!');


			// Prepare the A THIRD content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;
		$row['bodytext'] = 'THIRD content element';

		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '1',		// After first element
			'targetCheckUid' => $secondElementUid
		);
				
			// run insertElement():
		$thirdElementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($thirdElementUid !== FALSE, 'Inserting the third element was not successful, insertElement() returned FALSE');

		 	// Check if the new record really exists:
		$fields = implode (',', array_keys ($row)) . ',uid';
		$fetchedRow = t3lib_beFunc::getRecordWSOL('tt_content', $thirdElementUid, $fields);

		$recordsAreTheSame = 
			$row['CType'] == $fetchedRow['CType'] &&
			$row['header'] == $fetchedRow['header'] &&
			$row['bodytext'] == $fetchedRow['bodytext'] &&
			$thirdElementUid == $fetchedRow['uid'] &&
			-1 == $fetchedRow['_ORIG_pid']
		;
		self::assertTrue ($recordsAreTheSame, 'The element created by insertElement() contains not the same data like the fixture');
		 
		 	// Check if the new record has been inserted correctly behind the second one:
		$testPageRecord = t3lib_beFunc::getRecordWSOL ('pages', $this->testPageUID, 'tx_templavoila_flex,uid,pid,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $secondElementUid.','.$thirdElementUid.','.$elementUid, '(Third element) The reference list the elements created by insertElement() is not as expected!');
		
		$BE_USER->setWorkspace(0);		
	}


	public function test_insertElement_invalidData() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Prepare the new content element:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['pid'] = $this->testPageUID;

		$destinationPointer = array(
			'table' => 'be_users',
			'uid'   => 1,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => '1'
		);
				
			// Try to insert the element with invalid parent table:
		$elementUid = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertFalse ($elementUid, 'Trying to insert a content element into invalid table did not return FALSE!');
	}

	public function test_insertElement_sortingField() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Create 3 new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'insert test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

		 	// Check if the sorting field has been set correctly:
		$elementRecords[1] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[1], 'uid,sorting');		
		$elementRecords[2] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[2], 'uid,sorting');		
		$elementRecords[3] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[3], 'uid,sorting');
		
		$orderIsCorrect = $elementRecords[1]['sorting'] < $elementRecords[2]['sorting'] && $elementRecords[2]['sorting'] < $elementRecords[3]['sorting']; 
		self::assertTrue ($orderIsCorrect, 'The sorting field has not been set correctly after inserting three CEs with insertElement()!');

			// Insert yet another element after the first:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['bodytext'] = 'insert test element #4';
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1
		);

		$elementUids[4] = $this->apiObj->insertElement ($destinationPointer, $row);

		 	// Check if the sorting field has been set correctly:
		$elementRecords[1] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[1], 'uid,sorting');		
		$elementRecords[2] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[2], 'uid,sorting');		
		$elementRecords[3] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[3], 'uid,sorting');
		$elementRecords[4] = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[4], 'uid,sorting');

		$orderIsCorrect = 
			$elementRecords[1]['sorting'] < $elementRecords[4]['sorting'] && 
			$elementRecords[4]['sorting'] < $elementRecords[2]['sorting'] && 
			$elementRecords[2]['sorting'] < $elementRecords[3]['sorting']; 
		self::assertTrue ($orderIsCorrect, 'The sorting field has not been set correctly after inserting a forth CE after the first with insertElement()!');
	}

	public function test_insertElement_oldStyleColumnNumber() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO('twocolumns');

			// Create 2 new content elements, one in the main area and one in the right bar:
		$elementUids = array();

		$row = $this->fixture_getContentElementRow_TEXT();
		$row['bodytext'] = 'oldStyleColumnNumber test #1';
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 0
		);
		$elementUids[1] = $this->apiObj->insertElement ($destinationPointer, $row);

		$row = $this->fixture_getContentElementRow_TEXT();
		$row['bodytext'] = 'oldStyleColumnNumber test #2';
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_rightbar',
			'vLang' => 'vDEF',
			'position' => 0
		);
		$elementUids[2] = $this->apiObj->insertElement ($destinationPointer, $row);	
		
		$elementRecords[1]= t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[1], 'uid,sorting,colpos');
		$elementRecords[2]= t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[2], 'uid,sorting,colpos');
			
		self::assertTrue($elementRecords[1]['colpos'] == 0 && $elementRecords[2]['colpos'] == 1, 'The column position stored in the "colpos" field is not correct after inserting two content elements!');
	}

	/**
	 * Checks a special situation while inserting CEs if elements have been deleted 
	 * before. See bug #3042
	 */
	public function test_insertElement_bug3042_part1() {
		global $TYPO3_DB, $BE_USER;

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$BE_USER->setWorkspace (-1);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Create 3 new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'insert test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);			
		}

			// Delete the second content element by calling TCEmain instead of using the TemplaVoila API.
			// We pass the UID of the CE with the content (overlayed UID), not the UID of the placeholder
			// record because that exposes the bug.
								
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$cmdMap = array (
			'tt_content' => array(
				t3lib_beFunc::wsMapId('tt_content', $elementUids[2]) => array (
					'delete' => 1
				)
			)
		);
		$tce->start(array(), $cmdMap);
		$tce->process_cmdmap();

			// Now insert an element after the second:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['bodytext'] = 'insert test element #4';
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

		$elementUids[4] = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($elementUids[4] !== FALSE, 'Bug 3042 part one - Inserting a new element was not successful, insertElement() returned FALSE');

			// Check if the new record has been inserted correctly behind the second one:
		$testPageRecord = t3lib_beFunc::getRecordWSOL ('pages', $this->testPageUID, 'tx_templavoila_flex,uid,pid,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[3].','.$elementUids[4], 'insertElement_bug3042 - The pages reference list of the elements I created and deleted is not as expected!');
	}

	/**
	 * Checks a special situation while inserting CEs if elements have been deleted 
	 * before. See bug #3042
	 */
	public function test_insertElement_bug3042_part2() {
		global $TYPO3_DB, $BE_USER;

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$BE_USER->setWorkspace (-1);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Create 3 new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'insert test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);			
		}

			//Mark the second content element as deleted directly in the database so TemplaVoila has no
			// chance to clean up the flexform XML and therefore must handle the inconsistency:

		$TYPO3_DB->exec_UPDATEquery (
			'tt_content',
			'uid='.intval($elementUids[2]),
			array('deleted' => 1)			
		);

			// Now insert an element after the second:
		$row = $this->fixture_getContentElementRow_TEXT();
		$row['bodytext'] = 'insert test element #4';
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		$elementUids[4] = $this->apiObj->insertElement ($destinationPointer, $row);
		self::assertTrue ($elementUids[4] !== FALSE, 'Bug 3042 Part two - Inserting a new element was not successful, insertElement() returned FALSE');

			// Check if the new record has been inserted correctly behind the second one:
		$testPageRecord = t3lib_beFunc::getRecordWSOL ('pages', $this->testPageUID, 'tx_templavoila_flex,uid,pid,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[3].','.$elementUids[4], 'insertElement_bug3042 - The pages reference list of the elements I created and deleted is not as expected!');
	}
	




	/*********************************************************
	 *
	 * MOVE ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_moveElement_onSamePage() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create 3 new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Cut first element and paste it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Move the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the first element has been moved correctly behind the third one:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[2].','.$elementUids[3].','.$elementUids[1], 'The reference list is not as expected after moving the first element after the third with moveElement()!');

			// Cut third element and paste it after the first:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1
		);

			// Move the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the first element has been moved correctly behind the third one:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[2].','.$elementUids[1].','.$elementUids[3], 'The reference list is not as expected after moving the third element after the first with moveElement()!');

			// Try to move the element with invalid source pointer:
		$sourcePointer['position'] = 9999;
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertFalse ($result, 'moveElement() did not return FALSE although we tried to move an element specified by an invalid source pointer!');		
	}
	
	public function test_moveElement_onSamePageWithinFCE() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

		$this->fixture_createTestFCEDSTO('2col');
		
			// Create a 2-column FCE:
		$row = $this->fixture_getContentElementRow_FCE($this->testFCEDSUID, $this->testFCETOUID);
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 0
		);
		$FCEUid = $this->apiObj->insertElement ($destinationPointer, $row);

			// Create 3+3 new content elements within the two columns of the FCE:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element left #'.$i;
			$destinationPointer = array(
				'table' => 'tt_content',
				'uid'   => $FCEUid,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_leftcolumn',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUidsLeft[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
			
			$row['bodytext'] = 'move test element right #'.$i;
			$destinationPointer = array(
				'table' => 'tt_content',
				'uid'   => $FCEUid,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_rightcolumn',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUidsRight[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Right column: cut first element and paste it after the third:
		$sourcePointer = array(
			'table' => 'tt_content',
			'uid'   => $FCEUid,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_rightcolumn',
			'vLang' => 'vDEF',
			'position' => 1
		);
		
		$destinationPointer = array(
			'table' => 'tt_content',
			'uid'   => $FCEUid,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_rightcolumn',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Move the element within the same FCE with valid source and destination pointer:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the first element has been moved correctly behind the third one:
		$testFCERecord = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$FCEUid, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testFCERecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightcolumn']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUidsRight[2].','.$elementUidsRight[3].','.$elementUidsRight[1], 'The reference list is not as expected after moving the first element after the third with moveElement()!');

			// Cut third element of the right column and paste it after the first in the left column:
		$sourcePointer = array(
			'table' => 'tt_content',
			'uid'   => $FCEUid,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_rightcolumn',
			'vLang' => 'vDEF',
			'position' => 3
		);
		
		$destinationPointer = array(
			'table' => 'tt_content',
			'uid'   => $FCEUid,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_leftcolumn',
			'vLang' => 'vDEF',
			'position' => 1
		);

			// Move the element within the same FCE with valid source and destination pointer from one column to another:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the first element has been moved correctly behind the first one in the other column:
		$testFCERecord = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$FCEUid, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testFCERecord['tx_templavoila_flex']);

		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightcolumn']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUidsRight[2].','.$elementUidsRight[3], 'The reference list in the right column is not as expected after moving the third element of the second column to after the first in the first column with moveElement()!');
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_leftcolumn']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUidsLeft[1].','.$elementUidsRight[1].','.$elementUidsLeft[2].','.$elementUidsLeft[3], 'The reference list in the left column is not as expected after moving the third element of the second column to after the first in the first column with moveElement()!');
	}
	
	public function test_moveElement_onSamePage_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (-1);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create 3 new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Cut first element and paste it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Move the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the first element has been moved correctly behind the third one:
		$testPageRecord = t3lib_beFunc::getRecordWSOL ('pages', $this->testPageUID, 'uid,pid,tx_templavoila_flex,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[2].','.$elementUids[3].','.$elementUids[1], 'The reference list is not as expected after moving the first element after the third with moveElement()!');

			// Cut third element and paste it after the first:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1
		);

			// Move the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the first element has been moved correctly behind the third one:
		$testPageRecord = t3lib_beFunc::getRecordWSOL('pages', $this->testPageUID, 'uid,pid,tx_templavoila_flex,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[2].','.$elementUids[1].','.$elementUids[3], 'The reference list is not as expected after moving the third element after the first with moveElement()!');

			// Try to move the element with invalid source pointer:
		$sourcePointer['position'] = 9999;
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertFalse ($result, 'moveElement() did not return FALSE although we tried to move an element specified by an invalid source pointer!');
		
	}


	public function test_moveElement_toOtherPage() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create a second test page:		
		$pageRow = array ('title' => $this->testPageTitle);
		$targetTestPageUID = $this->fixture_createPage ($pageRow, $this->testPageUID);

			// Create 3 new content elements on test page and on target page:
		$sourcePageElementUids = array();
		$targetPageElementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$sourcePageElementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);

			$row['bodytext'] = 'move test element (destination page) #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $targetTestPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$targetPageElementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Cut second element from source test page and paste it after the first of the target page:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $targetTestPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1,
			'targetCheckUid' => $targetPageElementUids[1]
		);

			// Move the element:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the element has been referenced correctly on the destination page:
		$targetTestPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$targetTestPageUID, 'tx_templavoila_flex,pid');		
		$flexform = simplexml_load_string ($targetTestPageRecord['tx_templavoila_flex']);
		$expectedReferences = $targetPageElementUids[1].','.$sourcePageElementUids[2].','.$targetPageElementUids[2].','.$targetPageElementUids[3];	
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $expectedReferences, 'The reference list is not as expected after moving the element from one page to another with moveElement()!');

		 	// Check if the element has the correct PID:
		$elementRecord = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$sourcePageElementUids[2], 'pid');		
		self::assertEquals ($targetTestPageUID, (integer)$elementRecord['pid'], 'The PID of the moved element has not been set to the new page uid!');
	}

	public function test_moveElement_toOtherPage_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (-1);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create a second test page:		
		$pageRow = array ('title' => $this->testPageTitle);
		$targetTestPageUID = $this->fixture_createPage ($pageRow, $this->testPageUID);

			// Create 3 new content elements on test page and on target page:
		$sourcePageElementUids = array();
		$targetPageElementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$sourcePageElementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);

			$row['bodytext'] = 'move test element (destination page) #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $targetTestPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$targetPageElementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Cut second element from source test page and paste it after the first of the target page:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $targetTestPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1,
			'targetCheckUid' => $targetPageElementUids[1]
		);

			// Move the element:		
		$result = $this->apiObj->moveElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result, 'moveElement() did not return TRUE!');
		
		 	// Check if the element has been referenced correctly on the destination page:
		$targetTestPageRecord = t3lib_beFunc::getRecordWSOL('pages', $targetTestPageUID, 'uid,pid,tx_templavoila_flex,t3ver_swapmode');		
		$flexform = simplexml_load_string ($targetTestPageRecord['tx_templavoila_flex']);
		$expectedReferences = $targetPageElementUids[1].','.$sourcePageElementUids[2].','.$targetPageElementUids[2].','.$targetPageElementUids[3];	
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $expectedReferences, 'The reference list is not as expected after moving the element from one page to another with moveElement()!');

		 	// Check if the element has the correct PID:
		$elementRecord = t3lib_beFunc::getRecordWSOL('tt_content', $sourcePageElementUids[2], 'uid,pid');		
		self::assertEquals ($targetTestPageUID, (integer)$elementRecord['pid'], 'The PID of the moved element has not been set to the new page uid!');
	}







	/*********************************************************
	 *
	 * COPY ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_copyElement_onSamePage() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();
		
			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Copy second element and paste it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Copy the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->copyElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result !== FALSE, 'copyElement()returned FALSE!');
		
		 	// Check if the element has been copied correctly:
		$elementUids[4] = $result;
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[2].','.$elementUids[3].','.$elementUids[4], 'The reference list is not as expected after copying the second element after the third with copyElement()!');
	}

	public function test_copyElement_toOtherPage() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create a second test page:		
		$pageRow = array ('title' => $this->testPageTitle);
		$targetTestPageUID = $this->fixture_createPage ($pageRow, $this->testPageUID);

		
			// Create 3 new content elements on test page and on target page:
		$sourcePageElementUids = array();
		$targetPageElementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copy test element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$sourcePageElementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);

			$row['bodytext'] = 'copy test element (destination page) #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $targetTestPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$targetPageElementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}


			// Copy first element and from one page and paste it after the second of the other page:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 1
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $targetTestPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

			// Copy the element:		
		$result = $this->apiObj->copyElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result !== FALSE, 'copyElement() to different page returned FALSE!');
		
		 	// Check if the element has been copied correctly:
		$newElementUid = $result;
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$targetTestPageUID, 'tx_templavoila_flex');		
		$expectedReferences = $targetPageElementUids[1].','.$targetPageElementUids[2].','.$newElementUid.','.$targetPageElementUids[3];
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $expectedReferences, 'The reference list is not as expected after copying the from one page to another with copyElement()!');
	}






	/*********************************************************
	 *
	 * REFERENCE ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_referenceElement() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Take second element and reference it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Reference the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->referenceElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result !== FALSE, 'referenceElement() did FALSE!');
		
		 	// Check if the element has been referenced correctly:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[2].','.$elementUids[3].','.$elementUids[2], 'The reference list is not as expected after inserting a reference of the second element after the third with referenceElement()!');
	}

	public function test_referenceElement_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (-1);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Take second element and reference it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Reference the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->referenceElement ($sourcePointer, $destinationPointer);
		self::assertTrue ($result !== FALSE, 'referenceElement() did FALSE!');
		
		 	// Check if the element has been referenced correctly:
		$testPageRecord = t3lib_beFunc::getRecordWSOL('pages', $this->testPageUID, 'uid,pid,tx_templavoila_flex,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[2].','.$elementUids[3].','.$elementUids[2], 'The reference list is not as expected after inserting a reference of the second element after the third with referenceElement()!');
	}

	public function test_referenceElementByUid() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 3
		);

			// Reference the element within the same page with valid source and destination pointer:		
		$result = $this->apiObj->referenceElementByUid ($elementUids[2], $destinationPointer);
		self::assertTrue ($result !== FALSE, 'referenceElement() returned FALSE!');
		
		 	// Check if the element has been referenced correctly:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[2].','.$elementUids[3].','.$elementUids[2], 'The reference list is not as expected after inserting a reference of the second element after the third with referenceElementByUid()!');
	}






	/*********************************************************
	 *
	 * UNLINK ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_unlinkElement() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Unlink the second element:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

		$result = $this->apiObj->unlinkElement ($sourcePointer);
		self::assertTrue ($result !== FALSE, 'unlinkElement() returned FALSE!');
		
		 	// Check if the element has been un-referenced correctly:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[3], 'The reference list is not as expected after unlinking an elemen with unlinkElement()!');
	}






	/*********************************************************
	 *
	 * DELETE ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_deleteElement() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Unlink the second element:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

		$result = $this->apiObj->deleteElement ($sourcePointer);
		self::assertTrue ($result !== FALSE, 'deleteElement() returned FALSE!');
		
		 	// Check if the element has been un-referenced correctly:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[3], 'The reference list is not as expected after deleting an element with deleteElement()!');

		 	// Check if the record really has been deleted:
		$elementRecord = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$elementUids[2], '*');		
		self::assertEquals ((integer)$elementRecord['deleted'], 1, 'The element record has not been deleted correctly after calling deleteElement()!');
	}

	public function test_deleteElement_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'copytest element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Unlink the second element:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

		$BE_USER->setWorkspace (-1);

		$result = $this->apiObj->deleteElement ($sourcePointer);
		self::assertTrue ($result !== FALSE, 'deleteElement() returned FALSE!');
		
		 	// Check if the element has been un-referenced correctly:
		$testPageRecord = t3lib_beFunc::getRecordWSOL ('pages', $this->testPageUID, 'uid,pid,tx_templavoila_flex,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[3], 'The reference list is not as expected after deleting an element with deleteElement()!');

		 	// Check if the record really has been deleted:
		$elementRecord = t3lib_beFunc::getRecordWSOL('tt_content', $elementUids[2], '*');
		self::assertEquals ((integer)$elementRecord['t3ver_state'], 2, 'The element record has not been deleted correctly after calling deleteElement()!');
	}




	/*********************************************************
	 *
	 * GET RECORD BY POINTER TESTS
	 *
	 *********************************************************/

	public function test_getRecordByPointer() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'getRecordByPointer test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

		$row['bodytext'] = 'getRecordByPointer test element #2';
		$flexformPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

			// Fetch the record:		
		$fetchedRow = $this->apiObj->flexform_getRecordByPointer ($flexformPointer);
		self::assertTrue ($fetchedRow !== FALSE, 'flexform_getRecordByPointer() returned FALSE!');
		
		$recordsAreTheSame = count (array_intersect_assoc ($row, $fetchedRow)) == count ($row);		
		self::assertTrue ($recordsAreTheSame, 'The record returned by flexform_getRecordByPointer() was not the one we expected!');
	}

	public function test_getRecordByPointer_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (1);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'getRecordByPointer test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

		$row['bodytext'] = 'getRecordByPointer test element #2';
		$flexformPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);

			// Fetch the record:		
		$fetchedRow = $this->apiObj->flexform_getRecordByPointer ($flexformPointer);
		self::assertTrue ($fetchedRow !== FALSE, 'flexform_getRecordByPointer() returned FALSE!');
		
		$recordsAreTheSame = count (array_intersect_assoc ($row, $fetchedRow)) == count ($row);		
		self::assertTrue ($recordsAreTheSame, 'The record returned by flexform_getRecordByPointer() was not the one we expected!');
	}





	/*********************************************************
	 *
	 * DATA STRUCTURE FUNCTIONS TESTS
	 *
	 *********************************************************/

	public function test_ds_getFieldNameByColumnPosition() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

		$result = $this->apiObj->ds_getFieldNameByColumnPosition($this->testPageUID, 0);
		self::assertEquals($result, 'field_content', 'ds_getFieldNameByColumnPosition did not return the expected result!');
	}






	/*********************************************************
	 *
	 * LOCALIZE ELEMENT TESTS
	 *
	 *********************************************************/

	public function test_localizeElement() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$this->fixture_createTestPage ();		
		$this->fixture_createTestPageDSTO();
		$this->fixture_createTestAlternativePageHeader ($this->testPageUID, 'DE');

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'localize test element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Copy second element and paste it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDE',
			'position' => 0
		);

		$result = $this->apiObj->localizeElement ($sourcePointer, 'DE');
		self::assertTrue ($result !== FALSE, 'localizeElement()returned FALSE!');
		
		 	// Check if the localized element has been referenced correctly:
		$localizedUid = intval($result);
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDE']");
		self::assertEquals ((string)$xpathResArr[0], (string)$localizedUid, 'The reference list is not as expected after localizing the second element to German!');

		 	// Check if the record has been modified correctly:
		$localizedRecord = t3lib_beFunc::getRecordRaw ('tt_content', 'uid='.$localizedUid, '*');
		
		$isOkay = (
			$localizedRecord['l18n_parent'] == $elementUids[2] &&
			$localizedRecord['sys_language_uid'] == $this->currentAlternativePageHeaderSysLanguageUid 
		);
						
		self::assertTrue ($isOkay, 'The localized record has not the expected content!');
	}

	public function test_localizeElement_workspaces() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (1);

		$this->fixture_createTestPage ();		
		$this->fixture_createTestPageDSTO();
		$this->fixture_createTestAlternativePageHeader ($this->testPageUID, 'DE');

			// Create new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'localize test element #'.$i;
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Copy second element and paste it after the third:
		$sourcePointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDEF',
			'position' => 2
		);
		
		$destinationPointer = array(
			'table' => 'pages',
			'uid'   => $this->testPageUID,
			'sheet' => 'sDEF',
			'sLang' => 'lDEF',
			'field' => 'field_content',
			'vLang' => 'vDE',
			'position' => 0
		);

		$result = $this->apiObj->localizeElement ($sourcePointer, 'DE');
		self::assertTrue ($result !== FALSE, 'localizeElement()returned FALSE!');
		
		 	// Check if the localized element has been referenced correctly:
		$localizedUid = intval($result);
		$testPageRecord = t3lib_beFunc::getRecordWSOL('pages', $this->testPageUID, 'uid,pid,tx_templavoila_flex,t3ver_swapmode');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDE']");
		self::assertEquals ((string)$xpathResArr[0], (string)$localizedUid, 'The reference list is not as expected after localizing the second element to German!');

		 	// Check if the record has been modified correctly:
		$localizedRecord = t3lib_beFunc::getRecordWSOL('tt_content', $localizedUid, '*');
		
		$isOkay = (
			$localizedRecord['l18n_parent'] == $elementUids[2] &&
			$localizedRecord['sys_language_uid'] == $this->currentAlternativePageHeaderSysLanguageUid 
		);
						
		self::assertTrue ($isOkay, 'The localized record has not the expected content!');
	}





	/*********************************************************
	 *
	 * TCE MAIN TESTS
	 * 
	 * These tests emulate and check actions carried
	 * out by non-TemplaVoila-aware extensions or core modules
	 * like the list module
	 *
	 *********************************************************/

	public function test_tcemain_moveUp() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO();

			// Create 3 new content elements:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Move the third element to after the first element via TCEmain:
		$cmdMap = array (
			'tt_content' => array(
				$elementUids[3] => array (
					'move' => '-'.$elementUids[1]
				)
			)
		);
		$tce->start(array(), $cmdMap);
		$tce->process_cmdmap();

		 	// Check if the third element has been moved correctly behind the first:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
		$xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		self::assertEquals ((string)$xpathResArr[0], $elementUids[1].','.$elementUids[3].','.$elementUids[2], 'The reference list is not as expected after moving the third element after the first with TCEmain()!');
	}

	public function test_tcemain_moveUp_bug2154() {
		global $TYPO3_DB, $BE_USER;

		$BE_USER->setWorkspace (0);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;

		$this->fixture_createTestPage ();
		$this->fixture_createTestPageDSTO('twocolumns');

			// Create 3 new content elements in the main area and 3 in the right bar:
		$elementUids = array();
		for ($i=0; $i<3; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_content',
				'vLang' => 'vDEF',
				'position' => $i
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			for ($i=3; $i<6; $i++) {
			$row = $this->fixture_getContentElementRow_TEXT();
			$row['bodytext'] = 'move test element (right bar) #'.($i+1);
			$destinationPointer = array(
				'table' => 'pages',
				'uid'   => $this->testPageUID,
				'sheet' => 'sDEF',
				'sLang' => 'lDEF',
				'field' => 'field_rightbar',
				'vLang' => 'vDEF',
				'position' => $i-3
			);
			$elementUids[($i+1)] = $this->apiObj->insertElement ($destinationPointer, $row);
		}

			// Main area: move the third element to after the first element via TCEmain:
		$cmdMap = array (
			'tt_content' => array(
				$elementUids[3] => array (
					'move' => '-'.$elementUids[1]
				)
			)
		);
		$tce->start(array(), $cmdMap);
		$tce->process_cmdmap();

			// ... and then move it one more up (exposes the bug 2154):
		$cmdMap = array (
			'tt_content' => array(
				$elementUids[3] => array (
					'move' => '-'.$elementUids[1]
				)
			)
		);
		$tce->start(array(), $cmdMap);
		$tce->process_cmdmap();

			 	// Check if the elements are in the right columns in the right order:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
			
		$fieldContent_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		$fieldRightBar_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightbar']/value[@index='vDEF']");

		$everythingIsFine = 
			(string)$fieldContent_xpathResArr[0] === $elementUids[3].','.$elementUids[1].','.$elementUids[2] &&
			(string)$fieldRightBar_xpathResArr[0] === $elementUids[4].','.$elementUids[5].','.$elementUids[6]
		;
		
		self::assertTrue($everythingIsFine, 'The reference list is not as expected after moving the third element up two times in the left column!');

				// ... and then move the now second element one up again, measured by the sorting field! (also exposes the bug 2154):
		$elementsBySortingFieldArr = $TYPO3_DB->exec_SELECTgetRows(
			'uid',
			'tt_content',
			'pid='.intval($this->testPageUID),
			'',
			'sorting'
		);
		$positionOfElement1 = NULL;
		foreach ($elementsBySortingFieldArr as $index => $row) {
			if ($elementUids[1]==$row['uid']) $positionOfElement1 = $index;
		}
		
		
		$cmdMap = array (
			'tt_content' => array(
				$elementUids[1] => array (
					'move' => '-'.$elementsBySortingFieldArr[$positionOfElement1-1]['uid']
				)
			)
		);
		$tce->start(array(), $cmdMap);
		$tce->process_cmdmap();

			 	// Check again if the elements are in the right columns in the right order:
		$testPageRecord = t3lib_beFunc::getRecordRaw ('pages', 'uid='.$this->testPageUID, 'tx_templavoila_flex');		
		$flexform = simplexml_load_string ($testPageRecord['tx_templavoila_flex']);
			
		$fieldContent_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_content']/value[@index='vDEF']");
		$fieldRightBar_xpathResArr = $flexform->xpath("//data/sheet[@index='sDEF']/language[@index='lDEF']/field[@index='field_rightbar']/value[@index='vDEF']");

		$everythingIsFine = 
			(string)$fieldContent_xpathResArr[0] === $elementUids[1].','.$elementUids[3].','.$elementUids[2] &&
			(string)$fieldRightBar_xpathResArr[0] === $elementUids[4].','.$elementUids[5].','.$elementUids[6]
		;
		
		self::assertTrue($everythingIsFine, 'The reference list is not as expected after moving the second element up and choosing the destination by the sorting field!');		
}





	/*********************************************************
	 *
	 * FIXTURE PREPARATION FUNCTIONS
	 *
	 *********************************************************/

	/**
	 * Deletes old and creates a new test page. The test page DS and TO are selected
	 * for all test pages
	 * @return	void
	 */
	private function fixture_createTestPage() {
		global $TYPO3_DB;
		
			// Create a new test page and save the UID:			
		$pageRow = array (
			'title' => $this->testPageTitle
		);
		$this->testPageUID = $this->fixture_createPage($pageRow, 0);
		$this->fixture_createTSTemplate($this->testPageUID);
	}

	/**
	 * Performs the neccessary steps to creates a new page
	 *
	 * @param	array		$pageArray: array containing the fields for the new page
	 * @param	integer		$positionPid: location within the page tree (parent id)
	 * @return	integer		uid of the new page record
	 */
	private function fixture_createPage($pageArray,$positionPid)	{
		$dataArr = array();
		$dataArr['pages']['NEW'] = $pageArray;
		$dataArr['pages']['NEW']['pid'] = $positionPid;
		$dataArr['pages']['NEW']['hidden'] = 0;
		unset($dataArr['pages']['NEW']['uid']);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		return $tce->substNEWwithIDs['NEW'];
	}
	
	/**
	 * Creates a TypoScript template from the currently only fixture
	 * and puts it onto the page specified by $pid.
	 * 
	 * @param	integer		$pid: Page uid where the TS template should be stored
	 * @return	void
	 * @access private
	 */
	private function fixture_createTSTemplate($pid) {
		$dataArr = array();
		$dataArr['sys_template']['NEW'] = array (
			'pid' => intval($pid),
			'hidden' => 0,
			'title' => $this->testTSTemplateTitle,
			'clear' => 3,
			'root' => 1,
			'config' => '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:templavoila/tests/fixtures/main_typoscript_template.txt">',
			'include_static_file' => 'EXT:css_styled_content/static/'
		);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		return $tce->substNEWwithIDs['NEW'];
	}

	/**
	 * Deletes old and creates a new alternative page header for the given page.
	 * @return	integer		uid of the new alternative page header record 
	 */
	private function fixture_createTestAlternativePageHeader($pid, $languageKey) {
		global $TYPO3_DB;
				
		$res = $TYPO3_DB->exec_SELECTquery (
			'sys_language.uid',
			'sys_language LEFT JOIN static_languages ON sys_language.static_lang_isocode=static_languages.uid',
			'static_languages.lg_iso_2='.$TYPO3_DB->fullQuoteStr($languageKey, 'static_languages').' AND sys_language.hidden=0'  
		);

		if (!$res) return;

		$row = $TYPO3_DB->sql_fetch_assoc ($res);
		$this->currentAlternativePageHeaderSysLanguageUid = $row['uid'];		

		$dataArr = array();		
		$dataArr['pages_language_overlay']['NEW'] = array (
			'pid' => intval($pid),
			'hidden' => 0,
			'sys_language_uid' => $row['uid'],
			'title' => $this->testPageTitle,
			'subtitle' => $languageKey,
		);

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->start($dataArr,array());
		$tce->process_datamap();
		return $tce->substNEWwithIDs['NEW'];

	} 

	/**
	 * Creates a page datastructure and template object for the
	 * test page.
	 * 
	 * @param 	string		$type: The fixture name to use for that page template (eg. "onecolumn")
	 * @return 	array		UID of the DS and UID of the TO
	 */
	private function fixture_createTestPageDSTO($type='onecolumn') {
		global $TYPO3_DB;
	
			// Create new DS:
		$row = array (
			'pid' => $this->testPageUID,
			'tstamp' => time(),
			'crdate' => time(),
			'cruser_id' => 1,
			'deleted' => 0,
			'title' => $this->testPageDSTitle,
			'dataprot' => file_get_contents (t3lib_extMgm::extPath('templavoila').'tests/fixtures/page_datastructure_'.$type.'.xml'),
			'scope' => 1
		);
		$res = $TYPO3_DB->exec_INSERTquery ('tx_templavoila_datastructure', $row);
		$this->testPageDSUID = $TYPO3_DB->sql_insert_id ($res);	

			// Create new TO:
		$filename = t3lib_extMgm::extPath('templavoila').'tests/fixtures/page_template.html';
		$row = array (
			'pid' => $this->testPageUID,
			'tstamp' => time(),
			'crdate' => time(),
			'cruser_id' => 1,
			'deleted' => 0,
			'title' => $this->testPageTOTitle,
			'description' => 'generated by T3Unit testcase', 
			'datastructure' => $this->testPageDSUID ,
			'fileref_mtime' => @filemtime ($filename),
			'fileref_md5' => (is_callable('md5_file') ? md5_file($filename) : ''),
			'fileref' => $filename,
			'templatemapping' => file_get_contents (t3lib_extMgm::extPath('templavoila').'tests/fixtures/page_templateobject_'.$type.'.dat'),
		);
		$res = $TYPO3_DB->exec_INSERTquery ('tx_templavoila_tmplobj', $row);
		$this->testPageTOUID = $TYPO3_DB->sql_insert_id ($res);	
		
			// Select this DS / TO for the test page and set General Storage Page:
		$row = array (
			'tx_templavoila_ds' => $this->testPageDSUID,
			'tx_templavoila_to' => $this->testPageTOUID,
			'storage_pid' => $this->testPageUID
		);	
		$TYPO3_DB->exec_UPDATEquery ('pages', 'title="'.$this->testPageTitle.'"', $row);
	}

	/**
	 * Creates a datastructure and template object for a test FCE (2 columns)
	 * 
	 * @param	string		$type: The fixture name to use for the FCE (eg. "2col") 
	 * @return 	array		UID of the DS and UID of the TO
	 */
	private function fixture_createTestFCEDSTO($type) {
		global $TYPO3_DB;
	
			// Create new DS:
		$row = array (
			'pid' => $this->testPageUID,
			'tstamp' => time(),
			'crdate' => time(),
			'cruser_id' => 1,
			'deleted' => 0,
			'title' => $this->testFCEDSTitle.$type,
			'dataprot' => file_get_contents (t3lib_extMgm::extPath('templavoila').'tests/fixtures/fce_'.$type.'_datastructure.xml'),
			'scope' => 2
		);
		$res = $TYPO3_DB->exec_INSERTquery ('tx_templavoila_datastructure', $row);
		$this->testFCEDSUID = $TYPO3_DB->sql_insert_id ($res);	

			// Create new TO:
		$filename = t3lib_extMgm::extPath('templavoila').'tests/fixtures/fce_'.$type.'_template.html';
		$row = array (
			'pid' => $this->testPageUID,
			'tstamp' => time(),
			'crdate' => time(),
			'cruser_id' => 1,
			'deleted' => 0,
			'title' => $this->testFCETOTitle.$type,
			'description' => 'generated by T3Unit testcase', 
			'datastructure' => $this->testFCEDSUID ,
			'fileref_mtime' => @filemtime ($filename),
			'fileref_md5' => (is_callable('md5_file') ? md5_file($filename) : ''),
			'fileref' => $filename,
			'templatemapping' => file_get_contents (t3lib_extMgm::extPath('templavoila').'tests/fixtures/fce_'.$type.'_templateobject.dat'),
		);
		$res = $TYPO3_DB->exec_INSERTquery ('tx_templavoila_tmplobj', $row);
		$this->testFCETOUID = $TYPO3_DB->sql_insert_id ($res);			
	}





	/*********************************************************
	 *
	 * FIXTURE FUNCTIONS
	 *
	 *********************************************************/

	private function fixture_getContentElementRow_TEXT() {
		return array (
			'CType' => 'text',
			'header' => $this->testCEHeader,
			'bodytext' => 'T3Unit - If you see this message it appears that T3Unit succeeded in creating a content element at the test page. But usually you will never see this message. If everything runs fine.',
		);
	}

	private function fixture_getContentElementRow_FCE($dataStructureUid, $templateObjectUid) {
		return array (
			'CType' => 'templavoila_pi1',
			'header' => $this->testCEHeader,
			'tx_templavoila_ds' => $dataStructureUid,
			'tx_templavoila_to' => $templateObjectUid,
		);
	}
}

?>