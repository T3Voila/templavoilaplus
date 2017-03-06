<?php
namespace Ppi\TemplaVoilaPlus\Form\Container;

/*
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

use TYPO3\CMS\Backend\Form\Container\AbstractContainer;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * The container handles single elements.
 *
 * This one is called by FlexFormTabsContainer, FlexFormNoTabsContainer or FlexFormContainerContainer.
 * For single fields, the code is similar to SingleFieldContainer, processing will end up in single
 * element classes depending on specific type of an element. Additionally, it determines if a
 * section is handled and hands over to FlexFormSectionContainer in this case.
 */
class FlexFormElementContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $flexFormDataStructureArray = $this->data['flexFormDataStructureArray'];
        $flexFormRowData = $this->data['flexFormRowData'];
        $flexFormFormPrefix = $this->data['flexFormFormPrefix'];
        $parameterArray = $this->data['parameterArray'];
        $metaData = $this->data['parameterArray']['fieldConf']['config']['ds']['meta'];

        $languageService = $this->getLanguageService();
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $resultArray = $this->initializeResultArray();

        if (is_array($metaData) && isset($metaData['langChildren']) && isset($metaData['languagesOnElement'])) {
            $lkeys = $metaData['languagesOnElement'];
            array_walk($lkeys, function (&$value) {
                $value = 'v' . $value;
            });
        } else {
            $lkeys = array('vDEF');
        }

        foreach ($flexFormDataStructureArray as $flexFormFieldName => $flexFormFieldArray) {
            if (// No item array found at all
                !is_array($flexFormFieldArray)
                // Not a section or container and not a list of single items
                || (!isset($flexFormFieldArray['type']) && !is_array($flexFormFieldArray['config']))
            ) {
                continue;
            }
            if ($flexFormFieldArray['type'] === 'array') {
                // Section
                if (empty($flexFormFieldArray['section'])) {
                    $resultArray['html'] = LF . 'Section expected at ' . $flexFormFieldName . ' but not found';
                    continue;
                }

                $sectionTitle = '';
                if (!empty(trim($flexFormFieldArray['title']))) {
                    $sectionTitle = $languageService->sL(trim($flexFormFieldArray['title']));
                }

                $options = $this->data;
                $options['flexFormDataStructureArray'] = $flexFormFieldArray['el'];
                $options['flexFormRowData'] = is_array($flexFormRowData[$flexFormFieldName]['el']) ? $flexFormRowData[$flexFormFieldName]['el'] : array();
                $options['flexFormSectionType'] = $flexFormFieldName;
                $options['flexFormSectionTitle'] = $sectionTitle;
                $options['renderType'] = 'flexFormSectionContainer';
                $sectionContainerResult = $this->nodeFactory->create($options)->render();
                $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $sectionContainerResult);
            } else {
                $html = [];
                foreach ($lkeys as $lkey) {
                    // Set up options for single element
                    $fakeParameterArray = array(
                        'fieldConf' => array(
                            'label' => $languageService->sL(trim($flexFormFieldArray[$lkey]['label'])),
                            'config' => $flexFormFieldArray[$lkey]['config'],
                            'children' => $flexFormFieldArray[$lkey]['children'],
                            'defaultExtras' => $flexFormFieldArray[$lkey]['defaultExtras'],
                            'onChange' => $flexFormFieldArray[$lkey]['onChange'],
                        ),
                    );

                    $alertMsgOnChange = '';
                    if ($fakeParameterArray['fieldConf']['onChange'] === 'reload'
                        || !empty($GLOBALS['TCA'][$table]['ctrl']['type']) && $GLOBALS['TCA'][$table]['ctrl']['type'] === $flexFormFieldName
                        || !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate']) && GeneralUtility::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'], $flexFormFieldName)
                    ) {
                        if ($this->getBackendUserAuthentication()->jsConfirmation(JsConfirmation::TYPE_CHANGE)) {
                            $alertMsgOnChange = 'top.TYPO3.Modal.confirm(TBE_EDITOR.labels.refreshRequired.title, TBE_EDITOR.labels.refreshRequired.content).on("button.clicked", function(e) { if (e.target.name == "ok" && TBE_EDITOR.checkSubmit(-1)) { TBE_EDITOR.submitForm() } top.TYPO3.Modal.dismiss(); });';
                        } else {
                            $alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
                        }
                    }
                    $fakeParameterArray['fieldChangeFunc'] = $parameterArray['fieldChangeFunc'];
                    if ($alertMsgOnChange) {
                        $fakeParameterArray['fieldChangeFunc']['alert'] = $alertMsgOnChange;
                    }

                    $fakeParameterArray['onFocus'] = $parameterArray['onFocus'];
                    $fakeParameterArray['label'] = $parameterArray['label'];
                    $originalFieldName = $parameterArray['itemFormElName'];
                    $fakeParameterArray['itemFormElName'] = $parameterArray['itemFormElName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][' . $lkey . ']';
                    if ($fakeParameterArray['itemFormElName'] !== $originalFieldName) {
                        // If calculated itemFormElName is different from originalFieldName
                        // change the originalFieldName in TBE_EDITOR_fieldChanged. This is
                        // especially relevant for wizards writing their content back to hidden fields
                        if (!empty($fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'])) {
                            $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = str_replace($originalFieldName, $fakeParameterArray['itemFormElName'], $fakeParameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged']);
                        }
                    }
                    $fakeParameterArray['itemFormElID'] = $fakeParameterArray['itemFormElName'];
                    if (isset($flexFormRowData[$flexFormFieldName][$lkey])) {
                        $fakeParameterArray['itemFormElValue'] = $flexFormRowData[$flexFormFieldName][$lkey];
                    } else {
                        $fakeParameterArray['itemFormElValue'] = $fakeParameterArray['fieldConf']['config']['default'];
                    }

                    $options = $this->data;
                    $options['parameterArray'] = $fakeParameterArray;
                    $options['elementBaseName'] = $this->data['elementBaseName'] . $flexFormFormPrefix . '[' . $flexFormFieldName . '][' . $lkey . ']';

                    if (!empty($flexFormFieldArray[$lkey]['config']['renderType'])) {
                        $options['renderType'] = $flexFormFieldArray[$lkey]['config']['renderType'];
                    } else {
                        // Fallback to type if no renderType is given
                        $options['renderType'] = $flexFormFieldArray[$lkey]['config']['type'];
                    }

                    // After all we may a TemplaVoila type which do not have any rendering.
                    if (empty($options['renderType'])) {
                        continue;
                    }

                    $childResult = $this->nodeFactory->create($options)->render();

                    $theTitle = htmlspecialchars($fakeParameterArray['fieldConf']['label']);
                    $defInfo = array();

                    // Possible line breaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace (?!)
                    $processedTitle = str_replace('\\n', '<br />', $theTitle);
                    // @todo: Similar to the processing within SingleElementContainer ... use it from there?!
                    $html[] = '<div class="form-group t3js-formengine-palette-field t3js-formengine-validation-marker">';
                    $html[] = '<label class="t3js-formengine-label">';
                    if (is_array($metaData) && isset($metaData['langChildren']) && $metaData['langChildren']) {
                        // Find language uid of this iso code
                        $languageUid = 0;
                        $lKeyWithoutV = substr($lkey, 1);
                        if ($lKeyWithoutV !== 'DEF') {
                            foreach ($this->data['systemLanguageRows'] as $systemLanguageRow) {
                                if ($systemLanguageRow['iso'] === strtolower($lKeyWithoutV)) {
                                    $languageUid = $systemLanguageRow['uid'];
                                    break;
                                }
                            }
                        }
                        $languageIcon = $iconFactory->getIcon($this->data['systemLanguageRows'][$languageUid]['flagIconIdentifier'], Icon::SIZE_SMALL)->render();
                        $html[] = $languageIcon;
                    }

                    $html[] = BackendUtility::wrapInHelp($parameterArray['_cshKey'], $flexFormFieldName, $processedTitle);
                    $html[] = '</label>';
                    $html[] = '<div class="t3js-formengine-field-item">';
                    $html[] = $childResult['html'];
                    $html[] = implode(LF, $defInfo);
                    $html[] = $this->renderVDEFDiff($flexFormRowData[$flexFormFieldName], $lkey);
                    $html[] = '</div>';
                    $html[] = '</div>';

                    $childResult['html'] = '';
                    $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult);
                }
                $resultArray['html'] .= '<div class="form-section">' . implode(LF, $html) . '</div>';
            }
        }

        return $resultArray;
    }

    /**
     * Renders the diff-view of vDEF fields in flex forms
     *
     * @param array $vArray Record array of the record being edited
     * @param string $vDEFkey HTML of the form field. This is what we add the content to.
     * @return string Item string returned again, possibly with the original value added to.
     */
    protected function renderVDEFDiff($vArray, $vDEFkey)
    {
        $item = null;
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] && isset($vArray[$vDEFkey . '.vDEFbase'])
            && !is_array($vArray['vDEF']) && !is_array($vArray[$vDEFkey . '.vDEFbase'])
            && $vArray[$vDEFkey . '.vDEFbase'] !== $vArray['vDEF']
        ) {
            // Create diff-result:
            $diffUtility = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\DiffUtility::class);
            $diffres = $diffUtility->makeDiffDisplay($vArray[$vDEFkey . '.vDEFbase'], $vArray['vDEF']);
            $item = '<div class="typo3-TCEforms-diffBox">' . '<div class="typo3-TCEforms-diffBox-header">'
                . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:compatibility6/Resources/Private/Language/locallang.xlf:labels.changeInOrig')) . ':</div>' . $diffres . '</div>';
        }
        return $item;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
