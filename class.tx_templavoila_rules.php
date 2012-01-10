<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003  Robert Lemke (robert@typo3.org)
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
 * Class 'tx_templavoila_rules' for the 'templavoila' extension.
 *
 * Deprecated - not used anymore since version 1.0.0
 *
 * $Id$
 *
 * @author     Robert Lemke <robert@typo3.org>
 */

/**
 * Class 'tx_templavoila_rules' for the 'templavoila' extension.
 *
 * This library contains several functions for evaluating and output of rules
 * being defined in data structure objects.
 *
 * @author		Robert Lemke <robert@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_templavoila
 * @deprecated 1.0.0
 */
class tx_templavoila_rules {
	var $lastParsedRecord;	// Holds the last child record before an error occurs. Used for rule tracking / err messages
	var $currentFieldName;	// Used for error tracking

	/**
	 * Checks a given element if it complies with certain rules provided as a regular expression.
	 * This function prepares all the information which is needed to check rules compliance, the
	 * check itself will be carried out by checkRulesForElement () which calls itself recursively.
	 *
	 * Note that only few functionality of the POSIX standard for regular expressions is being supported,
	 * see the manual for more details.
	 *
	 * @param	string		$rules: A regular expression describing the rule. The content elements are reflected by certain tokens (i.e. uppercase and lowercase characters). These tokens are also called "ruleConstants".
	 * @param	array		$ruleConstants: An array with the mapping of tokens to content elements.
	 * @param	array		$elArray:
	 * @return	array		Array containing status information if the check was successful.
	 * @access	public
	 * @see checkRulesForElement()
	 */
	function evaluateRulesForElement ($table, $uid) {
	 	$statusArr = array();

			// Getting data structure for the template and extract information for default records to create
		$parentRecord = t3lib_BEfunc::getRecord ($table, $uid);

			// Only care about page records or flexible content elements:
		if ($table != 'tt_content' || $parentRecord['CType'] == 'templavoila_pi1') {
			$recRow = t3lib_BEfunc::getRecord ('tx_templavoila_datastructure', $parentRecord['tx_templavoila_ds']);
			$xmlContent = t3lib_div::xml2array($recRow['dataprot']);
			if (is_array($xmlContent['ROOT']) && is_array($xmlContent['ROOT']['el'])) {
				foreach ($xmlContent['ROOT']['el'] as $fieldName=>$field) {
					$ruleRegEx = trim ($field['tx_templavoila']['ruleRegEx']);
					$ruleConstants = trim ($field['tx_templavoila']['ruleConstants']);
					if ((string)$ruleRegEx != '' && ($field['tx_templavoila']['eType'] == 'ce')) {	// only check if necessary

							// Save record of parent element for error tracking
						$this->lastParsedRecord = $parentRecord;
						$this->currentFieldName = $fieldName;
							// Get child records of the parent element record
						$childRecords = array ();
						$xmlContent = t3lib_div::xml2array($parentRecord['tx_templavoila_flex']);
						$recUIDs = t3lib_div::trimExplode(',',$xmlContent['data']['sDEF']['lDEF'][$fieldName]['vDEF']);
						foreach ($recUIDs as $recUID) {
							$row = t3lib_BEfunc::getRecord('tt_content', $recUID, 'uid,CType,tx_templavoila_to');
							if ($row['CType'] == 'templavoila_pi1') {
								$row['CType'] .= ',' . $row['tx_templavoila_to'];
							}
							if (is_array ($row)) {	// only would be no array if the row has been deleted somewhere else and still remains in the flexform data
								$childRecords[] = $row;
							}
						}
							// Check the rules:
						$tmpStatusArr = $this->checkRulesForElement ($ruleRegEx, $ruleConstants, $childRecords);
						$this->statusMerge($statusArr, $tmpStatusArr, true);
					}
				}
			}
		}
#debug ($statusArr,'statusArr',__LINE__,__FILE__);
		return $statusArr;
	}

	/********************************************
	 *
	 *  Rule processing / analyzing functions
	 *
	 ********************************************/

	/**
	 * Checks the child records of an element for compliance to the element's rules.
	 *
	 * @param	string		$rules: The regular expression as a string OR the regular expression already parsed into an array (by parseRegexIntoArray)
	 * @param	string		$constants: The constants definitions being used in the regular expression divided by line breaks (eg.: a=text)
	 * @param	array		$childRecords: Array of child records (normally page and tt_content rows)
	 * @return	array		The status array containing information about errors, restrictions etc.
	 * @access	private
	 */
	function checkRulesForElement($rules, $constants, $childRecords) {
		if (!is_array ($childRecords)) { return array ('ok'=>false); }
		$statusArr = array ('ok' => true);

		if (is_string ($rules)) {	// If $rules is a regular expression, parse it into an array for easier handling:
				// Strip off the starting and ending delimiter
			if ($rules[0]=='^') { $rules = substr ($rules, 1); }
			if ($rules[strlen($rules)-1]=='$') { $rules = substr ($rules,0,-1); }
			$rules = $this->parseRegexIntoArray ($rules, $constants);
		}

		if (is_array ($rules)) {
				// Now traverse the rules
			foreach ($rules as $k => $rulePart)	{
				if ($rulePart['el']) {						// Evaluate elements
					$this->checkRulesForElement_parseEL ($rulePart, $childRecords, $statusArr);
				} elseif ($rulePart['class']) { 			// Evaluate classes of elements
					$this->checkRulesForElement_parseCLASS($rulePart, $childRecords, $statusArr);
				} elseif (is_array ($rulePart['sub'])) {	// Traverse subparts
					$this->checkRulesForElement_parseSUB($rulePart, $childRecords, $statusArr, $constants);
				} elseif (is_array ($rulePart['alt'])) {	// Traverse alternatives
					$this->checkRulesForElement_parseALT($rulePart, $childRecords, $statusArr, $constants);
				}
			}
			if (count($childRecords)) {
				$this->statusAddErr($statusArr, 'At least the next element was not expected here:', $this->lastParsedRecord, -1);
				$this->statusAddErr($statusArr, '', $this->lastParsedRecord,1);
				foreach ($childRecords as $childRecord) {
					$this->statusAddErr($statusArr, '', $childRecord,1);
				}
			}
		}
		return $statusArr;
	}

	/**
	 * Validates an element, used by checkRulesForElement()
	 *
	 * @param	array		$rulePart: part of the regular expression parsed into an array containing the 'el' branch.
	 * @param	array		$childRecords: Current array of child records of the main element which remain to be processed. Passed by reference!
	 * @param	array		$statusArr: The current status array, passed by reference
	 * @return	void		Results are returned by reference.
	 * @access	private
	 */
	function checkRulesForElement_parseEL ($rulePart, &$childRecords, &$statusArr) {
		$counter = 0;

		if (count($childRecords)) { $this->lastParsedRecord = $childRecords[0]; }
		while ($counter < $rulePart['max'] && ($childRecords[0]['CType'] == $rulePart['el'] || $rulePart['el'] == '.')) {
			$tmp = array_shift($childRecords);
			if (is_array ($tmp) && $tmp !== null) {
				$this->lastParsedRecord = $tmp;
			} else {
				break;
			}
			$counter ++;
		}
		if ($counter < $rulePart['min']) {
			$msg = 'At least '.$rulePart['min'].' element(s) of type '.$rulePart['el'].' expected, only '.$counter.' were found';
			$this->statusAddErr($statusArr, $msg, $this->lastParsedRecord, 2);
		}
	}

	/**
	 * Validates a subpart, used by checkRulesForElement()
	 *
	 * @param	array		$rulePart: part of the regular expression parsed into an array containing the 'sub' branch.
	 * @param	array		$childRecords: Current array of child records of the main element which remain to be processed. Passed by reference!
	 * @param	array		$statusArr: The current status array, passed by reference
	 * @param	string		$constants: The regular expresion's constants
	 * @param	string		$table: table for the current parent element record
	 * @param	integer		$uid: ID of the current parent element record
	 * @param	string		$field: Field name within the datastructure
	 * @return	void		Results are returned by reference.
	 * @access	private
	 * @todo				Not completely tested yet
	 */
	function checkRulesForElement_parseSUB ($rulePart, &$childRecords, &$statusArr, $constants) {
#debug ($rulePart, 'rulePart: SUB', __LINE__, __FILE__);
		if (is_array ($rulePart['sub'])) {
			for ($counter=1; $counter <= $rulePart['max']; $counter++) {
				$savedChildren = $childRecords;
				$savedStatus = $tmpStatusArr;
#debug ($savedChildren, 'SAVEDCHILDREN');
				foreach ($rulePart['sub'] as $k => $ruleSubPart) {
#debug (array ('ruleSubPart'=>$ruleSubPart,'counter'=>$counter),'ruleSubPart: SUB',__LINE__,__FILE__);
					if ($ruleSubPart['el']) {					// Evaluate elements
						$this->checkRulesForElement_parseEL ($ruleSubPart, $childRecords, $tmpStatusArr);
					} elseif ($ruleSubPart['class']) { 			// Evaluate classes of elements
						$this->checkRulesForElement_parseCLASS($ruleSubPart, $childRecords, $tmpStatusArr);
					} elseif (is_array ($ruleSubPart['sub'])) {	// Traverse subparts
						$this->checkRulesForElement_parseSUB($ruleSubPart, $childRecords, $tmpStatusArr, $constants);
					} elseif (is_array ($rulePart['alt'])) {	// Traverse alternatives
						$this->checkRulesForElement_parseALT($ruleSubPart, $childRecords, $tmpStatusArr, $constants);
					}
				}
#debug ($tmpStatusArr, 'tmpStatus before save the children',__LINE__,__FILE__);
				if ($tmpStatusArr['ok'] == false) {
					if (!is_null($tmpStatusArr['ok'])) {
						array_shift($savedChildren);
						$childRecords = $savedChildren;
						$tmpStatusArr = $savedStatus;
#debug (array ($childRecords, $tmpStatusArr),'Save the children');
					}
					break;
				}
			}
		}
#		if ($counter < $rulePart['min']) {
#			$msg = 'At least '.$rulePart['min'].' element(s) of type '.$rulePart['el'].' expected, only '.$counter.' were found';
#			$this->statusAddErr($statusArr, $msg, $lastParsedRecord ? $lastParsedRecord: $childRecords[0],2);
#		}

#debug ($tmpStatusArr, 'tmpStatusArr in parseSUB',__LINE__,__FILE__);
#debug ($childRecords,'childRecords in parseSub',__LINE__,__FILE__,10);

#		$this->statusMerge ($statusArr, $tmpStatusArr);
	}

	/**
	 * Validates an alternative part, used by checkRulesForElement()
	 *
	 * @param	array		$rulePart: part of the regular expression parsed into an array containing the 'alt' branch.
	 * @param	array		$childRecords: Current array of child records of the main element which remain to be processed. Passed by reference!
	 * @param	array		$statusArr: The current status array, passed by reference
	 * @param	string		$constants: The regular expresion's constants
	 * @param	string		$table: table for the current parent element record
	 * @param	integer		$uid: ID of the current parent element record
	 * @param	string		$field: Field name within the datastructure
	 * @return	void		Results are returned by reference.
	 * @access	private
	 */
	function checkRulesForElement_parseALT ($rulePart, &$childRecords, &$statusArr, $constants) {
		$altStatusArr = array ();
		foreach ($rulePart['alt'] as $alternativeRule) {
			$tmpStatusArr = $this->checkRulesForElement($alternativeRule, $constants, $childRecords);
			if ($tmpStatusArr['ok'] !== false) { // If one alternative is okay, the whole ALT branch is valid
				$this->statusSetOK ($altStatusArr);
				break;
			} elseif ($tmpStatusArr['ok'] === false) { // If alternative fails and no other alternative was valid yet, merge errors
				$this->statusMerge($altStatusArr, $tmpStatusArr);
			}
		}
		if ($altStatusArr['ok'] && ($statusArr['ok'])) { $statusArr['ok'] = true; }
		if (!$altStatusArr['ok']) {
			$statusArr['ok'] = false;
			$this->statusMerge ($statusArr, $altStatusArr);
		}
			// After an ALT branch no other elements will follow, so clear all remaining children
		$childRecords = null;
	}

	/**
	 * Validates a class of elements, used by checkRulesForElement()
	 *
	 * @param	array		$rulePart: part of the regular expression parsed into an array containing the 'class' branch.
	 * @param	array		$childRecords: Current array of child records of the main element which remain to be processed. Passed by reference!
	 * @param	array		$statusArr: The current status array, passed by reference
	 * @return	void		Results are returned by reference.
	 * @access	private
	 * @todo				Obviously this function does nothing yet
	 */
	function checkRulesForElement_parseCLASS ($rulePart, &$childRecords, &$statusArr) {
#debug (array ('rulePart'=>$rulePart['class']), 'rulePart: CLASS', __LINE__, __FILE__);
	}





	/********************************************
	 *
	 * Human Readable Rules Functions
	 *
	 * NOTE: This section is not working yet and
	 *       has rather an experimental character
	 *
	 ********************************************/

	/**
	 * Returns a description of a rule in human language
	 *
	 * @param	string		$rules: Regular expression containing the rule
	 * @param	array		$ruleConstants: Contains the mapping of elements to CTypes
	 * @return	string		Description of the rule
	 */
	function getHumanReadableRules ($rules,$ruleConstants)	{
		$rulesArr = $this->parseRegexIntoArray ($rules);
//		$constantsArr = $this->

#debug ($rulesArr);
		return $this->parseRulesArrayIntoDescription ($rulesArr, $constantsArr);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$rulesArr: ...
	 * @param	[type]		$constantsArr: ...
	 * @param	[type]		$level: ...
	 * @return	[type]		...
	 */
	function parseRulesArrayIntoDescription ($rulesArr, $constantsArr, $level=0) {
		if (is_array ($rulesArr)) {
			foreach ($rulesArr as $k=>$v) {
				if (is_array ($v['alt'])) {
					reset ($v['alt']);
					if (count ($v['alt'])>1) { $description .= 'either '; }
					for ($i=0; $i <= count ($v['alt']); $i++) {
						list ($k,$vAlt) = each ($v['alt']);
						$description .= $this->getHumanReadableRules ($vAlt, $ruleConstants, $level+1);
						if ($i < count ($v['alt'])) {
							$description .= 'or ';
						}
					}
					$description .= 'and then ';
				} elseif (is_array ($v['sub'])) {
					if ($description) { $description .= 'and then '; }
					$description .= $this->parseRulesArrayIntoDescription ($v['sub'], $constantsArr, $level+1);
				} elseif ($v['el']) {
					if ($description) { $description .= 'followed by '; }
					$description .= $this->getQuantifierAsDescription ($v['min'], $v['max']);
					$description .= $this->getElementNameFromConstantsMapping ($v['el'], $constantsArr);
				}
			}
		}

		return $description;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$min: ...
	 * @param	[type]		$max: ...
	 * @return	[type]		...
	 */
	function getQuantifierAsDescription ($min, $max) {
		if ($min == $max) {
			switch ($min) {
				case 1:		$description = 'one ';
							break;
				case 0:		$description = 'no ';
							break;
				case 999:	$description = 'any number of ';
							break;
				default:	$description = intval ($min).' times ';
							break;
			}
		} elseif ($min == 0) {
			switch ($max) {
				case 1:		$description = 'maybe one '; break;
				case 999:	$description = 'any number of '; break;
				default:	$description = 'up to '.intval ($max).' '; break;
			}
		} elseif ($min > 0) {
			switch ($max) {
				case 999:	$description =''; break;
			}
		}
		return $description;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$element: ...
	 * @param	[type]		$constantsArr: ...
	 * @return	[type]		...
	 */
	function getElementNameFromConstantsMapping ($element, $constantsArr) {
		switch ($element) {
			case '.' :
				$description = 'any element ';
				break;
			default:
				$description = $element.' ';
		}
		return $description;
	}





	/********************************************
	 *
	 * Helper functions
	 *
	 ********************************************/

	/**
	 * Parses a regular expression with a reduced set of functions into an array.
	 *
	 * @param	string		$regex: The regular expression
	 * @param	string		$constants: The constants definitions being used in the regular expression divided by line breaks (eg.: a=text)
	 * @return	array		Contains the cTypes with some additional information
	 */
	function parseRegexIntoArray ($regex, $constants) {
		$pos = 0;
		$outArr = array ();
			// Strip off the not wanted characters. We only support certain functions of regular expressions.
		$regex = preg_replace ('/[^a-zA-Z0-9\[\]\{\}\*\+\.\-]/','',$regex);

			// Split regular expression into alternative parts divided by '|'. If there is more then one part,
			// call this function recursively and parse each part separately.
		$altParts = $this->explodeAlternatives ($regex);
		if (count($altParts)>1) {
			foreach ($altParts as $altRegex) {
				$altArr['alt'][] = $this->parseRegexIntoArray ($altRegex, $constants);
			}
			$outArr[]=$altArr;
		} else {
				// No other alternatives, just parse it.
			while ($pos<strlen ($regex)) {
				if ($this->isElement ($regex[$pos])) {				// Element (ie. a-z A-Z and '.')
					$el = $regex[$pos];
					$min = 0;
					$max = 0;
					$this->evaluateQuantifier ($regex, $pos, $min, $max);
					$outArr[] = array (
						'el' => $this->getCTypeFromToken($el, $constants),
						'min' => $min,
						'max' => $max,
					);
				} elseif ($regex [$pos] == '(') {
					$innerBraceData = $this->extractInnerBrace($regex, $pos);
					$sub = $this->parseRegexIntoArray ($innerBraceData['content'], $constants);
					$regex = $innerBraceData['rightpart'];
					$pos = -1;
					$outArr[] = array (
						'sub' => $sub,
						'min' => $innerBraceData['min'],
						'max' => $innerBraceData['max'],
					);
				} elseif ($regex [$pos] == '[') {					// Class definition (ie. a set of elements which are allowed, enclosed in [] )
					$pos++;
						// If there is a circumflex the elements must *not* be used - set the negate flag
					if ($regex[$pos] == '^') {
						$negate = 1;
						$pos++;
					} else {
						$negate = 0;
					}
					unset ($elements);
					while ($this->isElement ($regex[$pos])) {
						$elements .= $regex[$pos];
						$pos++;
					}
					if ($elements) {
						if ($regex[$pos] == ']') {
								// Check if there is a quantifier after the closing brace and if so, evaluated it
							$this->evaluateQuantifier ($regex, $pos, $min, $max);
							$classArr = array (
								'class' => $elements,
								'min' => $min,
								'max' => $max,
							);
							if ($negate) { $classArr['negate'] = 1; }
							$outArr[] = $classArr;
						} else { debug ('Parse error: ] expected at end of class definition'); }
					} else { debug ('Parse error: At least one element expected in class definition'); }
				}
				$pos++;
			}
		}
		return $outArr;
	}

	/**
	 * Returns true if the given character is an element
	 *
	 * @param	string		$char: Character to be checked
	 * @return	boolean		true if it is an element
	 */
	function isElement ($char) {
		return ((strtoupper($char[0]) >= 'A' && strtoupper($char[0]) <= 'Z') || ($char[0]) == '.');
	}

	/**
	 * Parses a given string for braces () and returns an array which contains the inner part of theses braces
	 * as well as the remaining right after the braces. If there is a quantifier after the closing brace, it will
	 * be evaluated and returned in the result array as well.
	 *
	 * @param	string		$regex: The regular expression
	 * @param	integer		$startPos: The position within the regex string where the search should start
	 * @return	array		Array containing the results (see function)
	 * @access	private
	 * @see					parseRegexIntoArray ()
	 */
	function extractInnerBrace ($regex, $startPos) {
		for ($endPos=$startPos; $endPos<strlen ($regex); $endPos++) {
			if ($regex[$endPos]=='(') {
				$level++;
			}
			if ($regex[$endPos]==')') {
				if ($level == 1) {
						// The end of the inner part, point to one char after the closing brace
						// Get the min and max from a quantifier which might be there
					$savePos = $endPos;
					$this->evaluateQuantifier ($regex, $endPos, $min, $max);
					$stripEnd = $endPos-$savePos;
					break;
				} else {
					$level--;
				}
			}
		}
		$innerBrace = substr ($regex,$startPos+1,($endPos-$startPos-1-$stripEnd));
		$rightPart = substr ($regex,$endPos+2);
		return array ('content' => $innerBrace, 'min' => $min, 'max'=>$max, 'rightpart'=>$rightPart);
	}

	/**
	 * Explodes a regular expression into an array of alternatives which were separated by '|'.
	 * Takes braces into account.
	 *
	 * @param	string		$regex: The regular expression to be parsed
	 * @return	array		The alternative parts
	 */
	function explodeAlternatives ($regex) {
		for ($pos=0; $pos<strlen($regex); $pos++) {
			if ($regex[$pos]=='(') {
				$level++;
			}
			if ($regex[$pos]==')' && $level>0) {
				$level--;
			}
			if ($regex[$pos]=='|' && $level==0) {
				$regex[$pos]= chr(1);
			}
		}
		return explode (chr(1),$regex);
	}

	/**
	 * Looks for a quantifier and returns their minimum and maximum values. Note that the position parameter
	 * is passed by reference. It will be incremented depending on the length of the quantify expression.
	 * The results for min and max are also returned by reference!
	 *
	 * @param	string		$quantifier: The regular expression which likely contains a quantifier
	 * @param	integer		$pos: The position within the string where the quantifier should be. BY REFERENCE
	 * @param	integer		$min: Used for returning the minimum value, ie. how many times an element should be repeated at least
	 * @param	integer		$max: Used for returning the maximum value, ie. how many times an element should be repeated at maximum
	 * @return	void
	 */
	function evaluateQuantifier ($quantifier, &$pos, &$min, &$max) {
		$min=1;
		$max=1;
		if (!$quantifier[$pos+1]) { return; }
		if (strpos (' *?+{',$quantifier[$pos+1])) {
			switch ($quantifier[$pos+1]) {
				case '*':
					$min = 0;
					$max = 999;	 // Indefinately
					break;
				case '?':
					$min = 0;
					$max = 1;
					break;
				case '+':
					$min = 1;
					$max = 999; // Indefinately
					break;
				case '{':		// Quantifier enclosed in curly braces
					$pos++;
					unset ($str);
						// Parse the first value
					while ($quantifier[$pos+1] != '}' && $quantifier[$pos+1] != '-' && $pos<strlen ($quantifier)) {
						$str .= $quantifier[$pos+1];
						$pos++;
					}
					$min = intval ($str);
					$max = $min;
					if ($quantifier[$pos+1] == '-') {
						$pos++;
						if ($quantifier[$pos+1] == '}') {
								// No second value (upper range), so assume indefinately
							$max = 999;
						} else {
								// Parse the upper range value
							unset ($str);
							while ($quantifier[$pos+1] != '}' && $pos<strlen ($quantifier)) {
								$str .= $quantifier[$pos+1];
								$pos++;
							}
							$max = intval ($str);
						}
					}
					if ($quantifier[$pos+1] != '}') { debug ('Parse error! Expected \'}\' at this point'); }
					break;
				default:
					debug ('Parse error! Unexpected token: \''.$quantifier[$pos+1].'\''); $ok = 0;
			}
		}
	}

	/**
	 * Returns the CType (fx. 'text' or 'imgtext') for a given constant (fx. 'a' or 'c').
	 *
	 * @param	string		$token: The constant / token, a single character
	 * @param	string		$ruleConstants: The constants definitions being used in the regular expression divided by line breaks (eg.: a=text)
	 * @return	string		The constant's CType
	 */
	function getCTypeFromToken ($token, $ruleConstants) {
		if ($token == '.') { return $token; }

		$lines = explode (chr(10), $ruleConstants);
		if (is_array ($lines)) {
			foreach ($lines as $line) {
				if (ord ($line[0]) > 13) {	// Ignore empty lines
					$parts = t3lib_div::trimExplode('=',$line);
					$constArr[$parts[0]]=$parts[1];
				}
			}
		}
		return $constArr[$token];
	}





	/********************************************
	 *
	 * Status functions
	 *
	 ********************************************/

	/**
	 * Adds an error message to the status array. The array is passed by reference!
	 *
	 * @param	array		$$statusArr: A status array, passed by reference.
	 * @param	string		$msg: The error message
	 * @param	integer		$lastParsedRecord: Record the element causing the error or being next to it. Optional but important.
	 * @param	integer		$position: 0: element #uid causes the error, -1: an element before #uid cause the error, 1: an element after #uid, 2: all elements after #uid
	 * @return	void		Nothing returned, result is passed by reference
	 */
	function statusAddErr (&$statusArr, $msg, $lastParsedRecord=array(), $position=0) {
		$statusArr['ok'] = false;
		$statusArr['error'][] = array (
			'message' => $msg,
			'uid' => $lastParsedRecord['uid'],
			'fieldname' => $this->currentFieldName,
			'position' => $position,
			'record' => $lastParsedRecord,
		);
	}

	/**
	 * Merges two status arrays, the second array overrules the first one
	 *
	 * @param	array		$statusArr: A status array, passed by reference. Will contain the merged arrays.
	 * @param	array		$newStatusArr: The second status array overruling the first
	 * @param	boolean		$doAND: If set, the 'ok' status will be evaluated by performing an AND operation.
	 * @return	void		Nothing returned.
	 */
	function statusMerge (&$statusArr, $newStatusArr, $doAND=false) {
		if (is_array ($statusArr) && count ($statusArr)) {
#debug (array ('statusArr'=>$statusArr, 'newStatusArr' => $newStatusArr, 'doAND'=>$doAND, 'ANDed'=>($oldOK && $newStatusArr['ok'])),'statusMerge',__LINE__);
			if (is_array ($statusArr['error']) && is_array ($newStatusArr['error'])) {
				$statusArr['error'] = array_merge($statusArr['error'], $newStatusArr['error']);
			} elseif (is_array ($newStatusArr['error'])) {
				$statusArr['error'] = $newStatusArr['error'];
			}
			if ($doAND) {
				if (!isset ($statusArr['ok']) || !isset ($newStatusArr['ok'])) {
					if ($statusArr['ok'] == null) { $statusArr['ok'] = true; }
					if ($newStatusArr['ok'] == null) { $newStatusArr['ok'] = true; }
				}
				$statusArr['ok'] = $statusArr['ok'] && $newStatusArr['ok'];
			}
		} else {
			$statusArr = $newStatusArr;
		}
	}

	/**
	 * Clears any errors and sets the status to "valid". The status array is passed by reference.
	 *
	 * @param	array		&$statusArr: A status array, passed by reference.
	 * @return	void		Nothing.
	 */
	function statusSetOK (&$statusArr) {
		$statusArr['ok'] = true;
		unset ($statusArr['error']);
	}

	/**
	 * NOTE: This is not active yet
	 *
	 * Adds information about restrictions of a content element (regarding the current rules) to the status array.
	 * Restriction means, what type of CE may or may not be added after this element, if the element may be deleted etc.
	 *
	 * Options for the restrictionsArr:
	 * 'allowedElementsAfter' =>  List of CE types which are allowed after this element. If false, no element is allowed, if not set, any element is allowed
	 * 'required' => If set to true, this element is required an therefore must not be deleted
	 *
	 * @param	array		&$statusArr: A status array, passed by reference.
	 * @param	array		$restrictionsArr: Array containing restrictions which apply to this element see description of this function for valid options
	 * @param	integer		$uid: The content element uid
	 * @return	void
	 */
	function statusSetELRestrictions (&$statusArr, $uid, $restrictionsArr) {
		$statusArr['restrictions'][$uid] = t3lib_div::array_merge_recursive_overrule ($statusArr['restrictions'][$uid], $restrictionsArr, 0);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_rules.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/templavoila/class.tx_templavoila_rules.php']);
}

?>