<?php
namespace Ppi\TemplaVoilaPlus\Controller\Update;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

use Ppi\TemplaVoilaPlus\Utility\TemplaVoilaUtility;
use Ppi\TemplaVoilaPlus\Utility\DataStructureUtility;

/**
 * Class for userFuncs within the Extension Manager.
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 */
class StaticDataUpdateController
{
    /**
     * Step for the wizard. Can be manipulated by internal function
     *
     * @var integer
     */
    protected $step = 0;

    /**
     * Static DS wizard
     *
     * @return string The HTML to be shown.
     */
    public function run()
    {
        $this->iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);

        $this->step = GeneralUtility::_GP('dsWizardDoIt') ? (int)GeneralUtility::_GP('dsWizardStep') : 0;
        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoilaplus']);

        $title = TemplaVoilaUtility::getLanguageService()->sL(
            'LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.title.' . $this->step,
            true
        );
        $description = TemplaVoilaUtility::getLanguageService()->sL(
            'LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.description.' . $this->step
        );
        $out = '<h2>' . $title . '</h2>';

        $controls = '';

        switch ($this->step) {
            case 1:
                $ok = array(true, true);
                if (GeneralUtility::_GP('dsWizardDoIt')) {
                    if (!isset($conf['staticDS.']['path_fce']) || !strlen($conf['staticDS.']['path_fce'])) {
                        $ok[0] = false;
                        $description .= sprintf('||' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.dircheck.notset'), 'staticDS.path_fce');
                    } else {
                        $ok[0] = $this->checkDirectory($conf['staticDS.']['path_fce']);
                        if ($ok[0]) {
                            $description .= sprintf('||' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.dircheck.ok'), htmlspecialchars($conf['staticDS.']['path_fce']));
                        } else {
                            $description .= sprintf('||' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.dircheck.notok'), htmlspecialchars($conf['staticDS.']['path_fce']));
                        }
                    }

                    if (!isset($conf['staticDS.']['path_page']) || !strlen($conf['staticDS.']['path_page'])) {
                        $ok[0] = false;
                        $description .= sprintf('||' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.dircheck.notset'), 'staticDS.path_page');
                    } else {
                        $ok[1] = $this->checkDirectory($conf['staticDS.']['path_page']);
                        if ($ok[1]) {
                            $description .= sprintf('|' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.dircheck.ok'), htmlspecialchars($conf['staticDS.']['path_page']));
                        } else {
                            $description .= sprintf('|' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.dircheck.notok'), htmlspecialchars($conf['staticDS.']['path_page']));
                        }
                    }
                    if ($ok == array(true, true)) {
                        $controls .= $this->getDsRecords($conf['staticDS.']);
                    }
                }
                if ($ok == array(true, true) && $this->step < 3) {
                    $submitText = $conf['staticDS.']['enable']
                        ? TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.submit3')
                        : TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.submit2');
                    $controls .= '<br /><input type="hidden" name="dsWizardStep" value="1" />
                    <input type="submit" name="dsWizardDoIt" value="' . $submitText . '" />';
                }
                break;
            default:
                $controls .= '<input type="hidden" name="dsWizardStep" value="1" />
                <input type="submit" name="dsWizardDoIt" value="' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.submit1') . '" />';
                break;
        }

        $out .= '<p style="margin-bottom: 10px;">' . str_replace('|', '<br />', $description) . '</p>' .
            '<p style="margin-top: 5px;">' . $controls . '</p>';

        return '<form action="#" method="POST"><input type="hidden" name="update" value="StaticData" />' . $out . '</form>';
    }

    /**
     * Check directory
     *
     * @param string $path
     * @return boolean true if directory exists and is writable or could be created
     */
    protected function checkDirectory($path)
    {
        $status = false;
        $path = rtrim($path, '/') . '/';
        $absolutePath = GeneralUtility::getFileAbsFileName($path);
        if (!empty($absolutePath)) {
            if (@is_writable($absolutePath)) {
                $status = true;
            }
            if (!is_dir($absolutePath)) {
                try {
                    $errors = GeneralUtility::mkdir_deep(PATH_site, $path);
                    if ($errors === null) {
                        $status = true;
                    }
                } catch (\RuntimeException $e) {
                }
            }
        }

        return $status;
    }

    /**
     * Get DS records
     *
     * @param array $conf
     * @return string
     */
    protected function getDsRecords($conf)
    {
        $updateMessage = '';
        $writeDsIds = array();
        $writeIds = GeneralUtility::_GP('staticDSwizard');
        $options = GeneralUtility::_GP('staticDSwizardoptions');
        $checkAll = GeneralUtility::_GP('sdw-checkall');

        if (count($writeIds)) {
            $writeDsIds = array_keys($writeIds);
        }
        $rows = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'tx_templavoilaplus_datastructure',
            'deleted=0 AND pid > 0',
            '',
            'scope, title'
        );
        $out = '<table class="table table-hover"><thead>
            <tr>
                <th>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.uid') . '</th>
                <th>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.pid') . '</th>
                <th class="col-title">' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.title') . '</th>
                <th>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.scope') . '</th>
                <th>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.path') . '</th>
                <th>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.usage') . '</th>
                <th class="col-checkbox">
                    <label for="sdw-checkall">
                    <input type="checkbox" id="sdw-checkall" name="sdw-checkall" onclick="$(\'.staticDScheck\').prop(\'checked\', $(\'#sdw-checkall\')[0].checked)" value="1" ' .
                    ($checkAll ? 'checked="checked"' : '') . ' />&nbsp;
                    ' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.selectall') . '</label>
                    </label>
                </th>
            </tr></thead><tbody>';
        foreach ($rows as $row) {
            $dirPath = GeneralUtility::getFileAbsFileName($row['scope'] == 2 ? $conf['path_fce'] : $conf['path_page']);
            $dirPath = $dirPath . (substr($dirPath, -1) == '/' ? '' : '/');
            $title = $this->makeCleanFileName($row['title']);
            $pathAndFilename = $dirPath . $title . '.xml';
            $outPath = substr($pathAndFilename, strlen(PATH_site));

            $usage = TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTgetRows(
                'count(*)',
                'tx_templavoilaplus_tmplobj',
                'datastructure=' . (int) $row['uid'] . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_templavoilaplus_tmplobj')
            );
            if (count($writeDsIds) && in_array($row['uid'], $writeDsIds)) {
                $this->writeXmlWithTitle($pathAndFilename, $row['dataprot'], $row['title']);
                if ($row['previewicon']) {
                    copy(
                        GeneralUtility::getFileAbsFileName('uploads/tx_templavoilaplus/' . $row['previewicon']),
                        $dirPath . $title . '.gif'
                    );
                }
                if ($options['updateRecords']) {
                    // Update TOs belayout if ds belayout was set and TO not.
                    if ($row['belayout']) {
                        TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                            'tx_templavoilaplus_tmplobj',
                            'datastructure="' . $row['uid'] . '" AND belayout=""',
                            array('belayout' => $row['belayout'])
                        );
                    }
                    // update TO records
                    TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                        'tx_templavoilaplus_tmplobj',
                        'datastructure="' . $row['uid'] . '"',
                        array('datastructure' => $outPath)
                    );
                    // update page records
                    TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                        'pages',
                        'tx_templavoilaplus_ds="' . $row['uid'] . '"',
                        array('tx_templavoilaplus_ds' => $outPath)
                    );
                    TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                        'pages',
                        'tx_templavoilaplus_next_ds="' . $row['uid'] . '"',
                        array('tx_templavoilaplus_next_ds' => $outPath)
                    );
                    // update tt_content records
                    TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                        'tt_content',
                        'tx_templavoilaplus_ds="' . $row['uid'] . '"',
                        array('tx_templavoilaplus_ds' => $outPath)
                    );
                    // delete DS records
                    TemplaVoilaUtility::getDatabaseConnection()->exec_UPDATEquery(
                        'tx_templavoilaplus_datastructure',
                        'uid="' . $row['uid'] . '"',
                        array('deleted' => 1)
                    );
                    $updateMessage = TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.updated');
                    $this->step = 3;
                }
            }
            $out .= '<tr class="' . ($row['scope'] == 1 ? '' : 'active') . '">
            <td class="text-right" nowrap="nowrap">' . $row['uid'] . '</td>
            <td class="text-right" nowrap="nowrap">' . $row['pid'] . '</td>
            <td nowrap="nowrap">' . htmlspecialchars($row['title']) . '</td>
            <td nowrap="nowrap">' . ($row['scope'] == 1 ? 'Page' : 'FCE') . '</td>
            <td nowrap="nowrap">' . $outPath . '</td>
            <td class="text-right" nowrap="nowrap">' . $usage[0]['count(*)'] . '</td>';
            if (count($writeDsIds) && in_array($row['uid'], $writeDsIds)) {
                $out .= '<td nowrap="nowrap">'
                    . $this->iconFactory->getIcon('status-dialog-ok', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render()
                    . '</td>';
            } else {
                $out .= '<td class="col-checkbox" nowrap="nowrap"><input type="checkbox" class="staticDScheck" name="staticDSwizard[' . $row['uid'] . ']" value="1" /></td>';
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>';

        if ($conf['enable']) {
            if ($updateMessage) {
                $out .= '<div class="alert alert-info">'
                    . '<p>' . $updateMessage . '</p><p><strong>' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.clearcache') . '</strong></p>'
                    . '</div>';
            } else {
                $out .= '<div class="alert alert-danger">'
                    . '<h4 class="alert-title">' . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.description2.1') . '</h4>'
                    . '<p class="checkbox">
                        <label for="sdw-updateRecords">
                            <input type="checkbox" name="staticDSwizardoptions[updateRecords]" id="sdw-updateRecords" value="1" />'
                            . TemplaVoilaUtility::getLanguageService()->sL('LLL:EXT:templavoilaplus/Resources/Private/Language/template_conf.xlf:staticDS.wizard.updaterecords')
                    . '</label></p>'
                    . '</div>';
            }
        }

        return $out;
    }

    /**
     * Adds title, if not already set, into dsStructure XML and writes this into given file.
     *
     * @param string $pathAndFilename Filename with path to write
     * @param string $dsXml The dsStructure XML to write as string
     * @param string $title The tiotle to set for this dsStructure
     * @return void
     */
    protected function writeXmlWithTitle($pathAndFilename, $dsXml, $title)
    {
        $dataStructure = GeneralUtility::xml2array($dsXml);
        if (empty($dataStructure['ROOT']['tx_templavoilaplus']['title'])
            || $dataStructure['ROOT']['tx_templavoilaplus']['title'] === 'ROOT'
            && (empty($dataStructure['meta']['title'])
                || $dataStructure['meta']['title'] === 'ROOT'
            )
        ) {
            $dataStructure['meta']['title'] = $title;
        }
        $dsXml = DataStructureUtility::array2xml($dataStructure);
        GeneralUtility::writeFile($pathAndFilename, $dsXml);
    }

    /**
     * Get datastructure count
     *
     * @return integer
     */
    protected function datastructureDbCount()
    {
        return TemplaVoilaUtility::getDatabaseConnection()->exec_SELECTcountRows(
            '*',
            'tx_templavoilaplus_datastructure',
            'deleted=0 AND pid > 0'
        );
    }

    protected function makeCleanFileName($fileName)
    {
        // Take sanitizer from local driver
        $localdriver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Driver\LocalDriver::class);

        // After sanitizing remove double underscores and trim underscore
        return trim(
            preg_replace('/__/', '_', $localdriver->sanitizeFileName($fileName)),
            '_'
        );
    }
}
