<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003  Robert Lemke (rl@robertlemke.de)
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
 * @author     Robert Lemke <rl@robertlemke.de>
 */
class tx_templavoila_rules {

// some change to test CVS	
	/**
	 * Checks a given set of elements to comply with certain rules provided as a regular expression.
	 * Note that only few functionality of the POSIX standard for regular expressions is being supported.
	 * 
	 * @param	[string]	$rules: A regular expression describing the rule. The content elements are reflected by certain
	 * @param	[array]		$ruleConstants: An array with the mapping of tokens to content elements.
	 * @param	[type]		$elArray:
	 * @return	[array]		Array containing status information if the check was successful.
	 */
	function evaluateRulesOnElements ($rules, $ruleConstants, $elArray) {
//	debug(array($rules, $ruleConstants, $elArray));

			// Strip the starting and ending delimiter
		if ($rules[0]=='^') { $rules = substr ($rules, 1); }
		if ($rules[strlen($rules)-1]=='$') { $rules = substr ($rules,0,-1); }

		$rulesArray = $this->parseRegexIntoArray ($rules);
//		debug ($this->checkRulesCompliance ($rulesArray, $elArray));
		
		return array (
			'ok' => $ok,
			'ruletext' => array (
			
			)
		);		
	}

	/**
	 * Delivers the default content elements which are neccessary for a certain element.
	 * 
	 * @param	[type]		$rules: ...
	 * @param	[type]		$ruleConstants: ...
	 * @return	[type]		...
	 */
	function getDefaultElements($rules,$ruleConstants)	{
		$elArray=array();
		
		$elArray[]=array(
			'CType' => 'text'
		);
		$elArray[]=array(
			'CType' => 'templavoila_pi1',
			'tx_templavoila_ds' => 123
		);
		
		return $elArray;
	}

	/**
	 * Returns a description of a rule in human language
	 * 
	 * @param	[string]	$rules: Regular expression containing the rule
	 * @param	[array]		$ruleConstants: Contains the mapping of elements to CTypes
	 * @return	[string		Description of the rule
	 */
	function getHumanReadableRules ($rules,$ruleConstants)	{
		$rulesArr = $this->parseRegexIntoArray ($rules);
		
//		return $this->parseRulseArrayIntoDescription ($rulesArr, $rulesConstants);
	}

	function parseRulesArrayIntoDescription ($rulesArr, $rulesContants, $level=0) {
		if (is_array ($rulesArr)) {
			foreach ($rulesArr as $k=>$v) {
				if (is_array ($v['alt'])) {
					if (count ($v['alt'])>1) { $description .= 'either '; }
					foreach ($v['alt'] as $k=>$vAlt) {
						$description .= $this->getHumanReadableRules ($vAlt, $rulesConstants, $level+1);
					}
				} elseif (is_array ($v['sub'])) {
					
				} elseif ($v['el']) {
					$description .=	'';
				}
			}	
		}

		return $description;
	}

/*	function getQuantifierAsDescription ($min, $max) {
		if ($min == $max) {
			switch ($min) {
				1:		$description = 'one '; break;
				0:		$description = 'no '; break;
				999:	$description = 'any number of '; break;
				default:$description = intval ($min).' times '; break;
			}
		} elseif ($min == 0) {
			switch ($max) {
				1:		$description = 'maybe one '; break;
				999:	$description = 'any number of '; break;
				default:$description = 'up to '.intval ($max).' '; break;
			}
		} elseif ($min > 0) {
			switch ($max) {
				999:	$description ;
			}	
		}
	}
*/
	/**
	 * Parses a regular expression with a reduced set of functions into an array.
	 * 
	 * @param	[string]	$regex: The regular expression
	 * @return	[array]		Array containing the cTypes with some additional information
	 */
	function parseRegexIntoArray ($regex) {

		$pos = 0;
		$outArr = array ();
		
			// Strip off the not wanted characters. We only support certain functions of regular expressions.
		$regex = ereg_replace ('[^a-zA-Z0-9\[\]\{\}\*\+\.\-]','',$regex);

			// Split regular expression into alternative parts divided by '|'. If there is more then one part,
			// call this function recursively and parse each part separately.
		$altParts = $this->explodeAlternatives ($regex);
		if (count($altParts)>1) {
			foreach ($altParts as $altRegex) {
				$altArr['alt'][] = $this->parseRegexIntoArray ($altRegex);
			}
			$outArr[]=$altArr;
		} else {
			// No other alternatives, just parse it.
			while ($pos<strlen ($regex)) {
				if ($this->isElement ($regex[$pos])) {				// Element (ie. a-z A-Z and '.')
					$el = $regex[$pos];
					$this->evaluateQuantifier ($regex, $pos, $min, $max);
					$outArr[] = array (
						'el' => $el,
						'min' => $min,
						'max' => $max,
					);
				} elseif ($regex [$pos] == '(') {
					$innerBraceData = $this->extractInnerBrace($regex, $pos);
					$sub = $this->parseRegexIntoArray ($innerBraceData['content']);
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

	function checkRulesCompliance ($rulesArr, $elementsArr, $prevStatusArr='') {
		$statusArr = (is_array ($prevStatusArr) ? $prevStatusArr : array ('ok'=>1));
		if (is_array ($rulesArr) && is_array ($elementsArr)) {
			reset ($elementsArr);
			foreach ($rulesArr as $k => $v)	{
				if ($v['el']) {
					list ($kEl, $vEl) =  array_shift($elementsArr);
					debug (array ($vEl['CType'], $vEl['header'], $vEl['bodytext']));
				} elseif ($v['class']) {
					
				} elseif (is_array ($v['sub'])) {
					$statusArr = $this->checkRulesCompliance ($v['sub'], $elementsArr, $statusArr);					
				} elseif (is_array ($v['alt'])) {
					$statusArr = $this->checkRulesCompliance ($v['alt'], $elementsArr, $statusArr);
				}				
			}			
		}
		return $statusArr;
	}


	
	/********************************************
	 *	
	 * Helper functions
	 *
	 ********************************************/
	
	/**
	 * Checks a character is a 'element', ie. if it's a letter or a dot '.'
	 * 
	 * @param	[string]		$char: Character to be checked
	 * @return	[boolean]		true if it is an element
	 * @access	private
	 */
	function isElement ($char) {
		return ((strtoupper($char[0]) >= 'A' && strtoupper($char[0]) <= 'Z') || ($char[0]) == '.');
	}

	/**
	 * Parses a given string for braces () and returns an array which contains the inner part of theses braces
	 * as well as the remaining right after the braces. If there is a quantifier after the closing brace, it will
	 * be evaluated and returned in the result array as well.
	 * 
	 * @param	[string]	$regex: The regular expression
	 * @param	[integer]	$startPos: The position within the regex string where the search should start
	 * @return	[array]		Array containing the results (see function)
	 * @see					parseRegexIntoArray ()
	 * @access	private
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
	 * Splits a string into several parts divided by a vertical bar '|'. Takes care of braces!
	 * 
	 * @param	[string]	$regex: The regular expression to be parsed
	 * @return	[array]		The alternative parts
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
	 * @param	[string]	$quantifier: The regular expression which likely contains a quantifier
	 * @param	[integer]	$pos: The position within the string where the quantifier should be. BY REFERENCE
	 * @param	[integer]	$min: Used for returning the minimum value, ie. how many times an element should be repeated at least
	 * @param	[integer]	$max: Used for returning the maximum value, ie. how many times an element should be repeated at maximum
	 * @return	[void]		Nothing!
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
	 * [Describe function...]
	 * 
	 * @param	[type]		$token: ...
	 * @return	[type]		...
	 */
	function getCTypeFromToken ($token) {
	}

}

?>
