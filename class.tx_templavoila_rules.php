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

	
	/**
	 * Checks a given element if it complies with certain rules provided as a regular expression.
	 * 
	 * @param	[string]		$rules: A regular expression describing the rule. The content elements are reflected by certain 
	 *								tokens (i.e. uppercase and lowercase characters). These tokens are also called "ruleConstants".
	 *								Note that only few functionality of the POSIX standard for regular expressions is being supported.
	 * @param	[array]		$ruleConstants: An array with the mapping of tokens to content elements.
	 * @return	[array]		Array containing status information if the check was successful.
	 */
	function evaluateRulesOnElements ($rules, $ruleConstants, $elArray) {
//		debug(array($rules, $ruleConstants, $elArray));

		$rules = '^ab(cde)*f(gh){21}.?[^IJ]*l+(mn)+$';		
//		debug ($this->parseRegexIntoArray ($rules));

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
	 * Parses a regular expression with a reduced set of functions into an array.
	 * 
	 * @param	[string]		$regex: The regular expression
	 * @return	[array]		Array containing the cTypes with some additional information
	 */
	function parseRegexIntoArray ($regex) {
		$regex = ereg_replace ('[^a-zA-Z0-9\[\]\{\}\*\+\.\-]','',$regex);

		$ok = 1;
		while ($pos<strlen ($regex)) {
				// It's a single element
			if ($this->isElement ($regex[$pos])) {
					// Is there a quantifier following, which defines the number of repetitions?
				if (strpos ('*?+{',$regex[$pos+1])) {
					$pos++;
					$this->evaluateQuantifier ($regex, $pos, $min, $max);
					$outArr[] = $this->getElementsArray ($regex[$pos-1], $min, $max);
				} else {
					$outArr[] = $this->getElementsArray ($regex[$pos], 1, 1);
				}
			} elseif ($regex[$pos] == '[') {
				$pos++;
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
				if (!$elements) { debug ('Parse error! Character expected'); $ok = 0; }
				if ($regex[$pos] != ']') { debug ('Parse error! Expected \']\' instead of '.$regex[$pos]); $ok = 0; }

				$pos++;
					// The characters must be the quantifier!
				$this->evaluateQuantifier ($regex, $pos, $min, $max);
				$outArr[] = $this->getElementsArray ($elements, $min, $max,'list',$negate);
			} elseif ($regex[$pos] == '(') {
				$pos++;
				unset ($elements);
				while ($this->isElement ($regex[$pos])) {
					$elements .= $regex[$pos];
					$pos++;
				}				
				if (!$elements) { debug ('Parse error! Character expected after \'(\''); $ok = 0; }
				if ($regex[$pos] != ')') { debug ('Parse error! Expected \')\' at this point'); $ok = 0; }

				$pos++;
				$this->evaluateQuantifier ($regex, $pos, $min, $max);
				$outArr[] = $this->getElementsArray ($elements, $min, $max);
			} else {
				debug ('Parse error! Unexpected token \''.$regex[$pos].'\'');	
				$ok=0;
			}
			$pos++;			
		}
		
		return $outArr;			
	}

	/**
	 * Delivers an array which holds certain information of how a cType is used within the rule.
	 * 
	 * @param	[array]		$elements: array of cTypes
	 * @param	[integer]	$min: Minimum number of occurrences
	 * @param	[integer]	$max: Maximum number of occurrences (-1 => infinite)
	 * @param	[string]		$type: 'simple' or 'list'
	 * @param	[type]		$negate: If set to '1' it means "dont allow these elements"
	 * @return	[array]		
	 */
	function getElementsArray ($elements, $min=1, $max=1, $type='simple', $negate=0) {
		$pos=0;
		while ($pos<strlen($elements)) {
			$elArr [] = $elements[$pos];
			$pos++;
		}			
		return array (
			'elements' => $elArr,
			'min' => $min,
			'max' => $max,
			'type' => $type,
			'negate' => $negate,
		);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$char: ...
	 * @return	[type]		...
	 */
	function isElement ($char) {
		return ((strtoupper($char) >= 'A' && strtoupper($char) <= 'Z') || $char =='.');
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$quantifier: ...
	 * @param	[type]		$pos: ...
	 * @param	[type]		$min: ...
	 * @param	[type]		$max: ...
	 * @return	[type]		...
	 */
	function evaluateQuantifier ($quantifier, &$pos, &$min, &$max) {
		$min=1;
		$max=1;
		switch ($quantifier[$pos]) {
			case '*':	
				$min = 0; 
				$max = 100;
				break;
			case '?':	
				$min = 0;
				$max = 1;
				break;
			case '+':	
				$min = 1;
				$max = -1;
				break;
			case '{':
				$pos++;
				unset ($str);
				while ($quantifier[$pos] != '}' && $pos<strlen ($quantifier)) {
					$str .= $quantifier[$pos];
					$pos++;
				}
				$min = intval ($str);					
				if ($quantifier[$pos] == '-') {
					$pos++;
					unset ($str);
					while ($quantifier[$pos] != '}' && $pos<strlen ($quantifier)) {
						$str .= $quantifier[$pos];
						$pos++;
					}
					$max = intval ($str);
				}
				if ($quantifier[$pos] != '}') { debug ('Parse error! Expected \'}\' at this point'); }
				break;
			default:
				debug ('Parse error! Unexpected token: \''.$quantifier[$pos].'\''); $ok = 0;
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
