<?php
namespace Localizationteam\L10nmgr\View;

/***************************************************************
 * Copyright notice
 * (c) 2006 Kasper Skårhøj <kasperYYYY@typo3.com>
 * All rights reserved
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Localizationteam\L10nmgr\Model\L10nConfiguration;
use Localizationteam\L10nmgr\Model\Tools\Utf8Tools;
use Localizationteam\L10nmgr\Model\Tools\XmlTools;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * CATXMLView: Renders the XML for the use for translation agencies
 *
 * @author Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author Daniel Poetzinger <development@aoemedia.de>
 * @author Daniel Zielinski <d.zielinski@L10Ntech.de>
 * @author Fabian Seltmann <fs@marketing-factory.de>
 * @author Andreas Otto <andreas.otto@dkd.de>
 * @package TYPO3
 * @subpackage tx_l10nmgr
 */
class CatXlfView extends AbstractExportView
{
    /**
     * @var integer $forcedSourceLanguage Overwrite the default language uid with the desired language to export
     */
    protected $forcedSourceLanguage = false;
    /**
     * @var int
     */
    protected $exportType = 1;

    /**
     * CatXmlView constructor.
     * @param L10nConfiguration $l10ncfgObj
     * @param int $sysLang
     */
    public function __construct($l10ncfgObj, $sysLang)
    {
        parent::__construct($l10ncfgObj, $sysLang);
    }

    /**
     * Render the simple XML export
     *
     * @return string Filename
     */
    public function render()
    {
        $sysLang = $this->sysLang;
        $accumObj = $this->l10ncfgObj->getL10nAccumulatedInformationsObjectForLanguage($sysLang);
        if ($this->forcedSourceLanguage) {
            $accumObj->setForcedPreviewLanguage($this->forcedSourceLanguage);
        }
        $accum = $accumObj->getInfoArray();
        /** @var XmlTools $xmlTool */
        $xmlTool = GeneralUtility::makeInstance(XmlTools::class);
        $output = array();
        $targetIso = '';
        // Traverse the structure and generate XML output:

        foreach ($accum as $pId => $page) {


            foreach ($accum[$pId]['items'] as $table => $elements) {
                foreach ($elements as $elementUid => $data) {
                    $targetIso = '';
                    if (!empty($data['ISOcode'])) {
                        $targetIso = $data['ISOcode'];
                    }
                    if (is_array($data['fields'])) {
                        foreach ($data['fields'] as $key => $tData) {
                            if (is_array($tData)) {

                                $noChangeFlag = !strcmp(trim($tData['diffDefaultValue']), trim($tData['defaultValue']));
                                if (!$this->modeOnlyChanged || !$noChangeFlag) {
                                    // @DP: Why this check?
                                    if (($this->forcedSourceLanguage && isset($tData['previewLanguageValues'][$this->forcedSourceLanguage])) || $this->forcedSourceLanguage === false) {
                                        if ($this->forcedSourceLanguage) {
                                            $dataForTranslation = $tData['previewLanguageValues'][$this->forcedSourceLanguage];
                                        } else {
                                            $dataForTranslation = $tData['defaultValue'];
                                        }
                                        $translationValue = $tData['translationValue'];

                                        $_isTranformedXML = false;
                                        // Following checks are not enough! Fields that could be transformed to be XML conform are not transformed! textpic fields are not isRTE=1!!! No idea why...
                                        //DZ 2010-09-08
                                        // > if > else loop instead of ||
                                        // Test re-import of XML! RTE-Back transformation
                                        //echo $tData['fieldType'];
                                        //if (preg_match('/templavoila_flex/',$key)) { echo "1 -"; }
                                        //echo $key."\n";
                                        if ($tData['fieldType'] == 'text' && $tData['isRTE'] || (preg_match('/templavoila_flex/',
                                                $key))
                                        ) {
                                            $dataForTranslationTranformed = $xmlTool->RTE2XML($dataForTranslation);
                                            if ($dataForTranslationTranformed !== false) {
                                                $_isTranformedXML = true;
                                                $dataForTranslation = $dataForTranslationTranformed;
                                            }
                                            $translationValueTranformed = $xmlTool->RTE2XML($translationValue);
                                            if ($translationValueTranformed !== false) {
                                                $_isTranformedXML = true;
                                                $translationValue = $translationValueTranformed;
                                            }

                                        }
                                        if ($_isTranformedXML) {
                                            $output[] = "\t" . '<trans-unit id="' . $table . '_' . $elementUid . '_' . $key . '">' . "\n";
                                            $output[] = "\t\t" . '<source>'.$dataForTranslation . '</source>' . "\n";
                                            $output[] = "\t\t" . '<target>'.$translationValue . '</target>' . "\n";
                                            $output[] = "\t" . '</trans-unit>' . "\r";
                                        } else {
                                            // Substitute HTML entities with actual characters (we use UTF-8 anyway:-) but leave quotes untouched
                                            $dataForTranslation = html_entity_decode($dataForTranslation, ENT_NOQUOTES,
                                                'UTF-8');
                                            //Substitute & with &amp; in non-RTE fields
                                            $dataForTranslation = preg_replace('/&(?!(amp|nbsp|quot|apos|lt|gt);)/',
                                                '&amp;', $dataForTranslation);
                                            //Substitute > and < in non-RTE fields
                                            $dataForTranslation = str_replace(' < ', ' &lt; ', $dataForTranslation);
                                            $dataForTranslation = str_replace(' > ', ' &gt; ', $dataForTranslation);
                                            $dataForTranslation = str_replace('<br>', '<br/>', $dataForTranslation);
                                            $dataForTranslation = str_replace('<hr>', '<hr/>', $dataForTranslation);


                                            // Substitute HTML entities with actual characters (we use UTF-8 anyway:-) but leave quotes untouched
                                            $translationValue = html_entity_decode($translationValue, ENT_NOQUOTES,
                                                'UTF-8');
                                            //Substitute & with &amp; in non-RTE fields
                                            $translationValue = preg_replace('/&(?!(amp|nbsp|quot|apos|lt|gt);)/',
                                                '&amp;', $translationValue);
                                            //Substitute > and < in non-RTE fields
                                            $translationValue = str_replace(' < ', ' &lt; ', $translationValue);
                                            $translationValue = str_replace(' > ', ' &gt; ', $translationValue);
                                            $translationValue = str_replace('<br>', '<br/>', $translationValue);
                                            $translationValue = str_replace('<hr>', '<hr/>', $translationValue);
                                            $params = $this->getBackendUser()->getModuleData('l10nmgr/cm1/prefs',
                                                'prefs');
                                            if ($params['utf8'] == '1') {
                                                $dataForTranslation = Utf8Tools::utf8_bad_strip($dataForTranslation);
                                                $translationValue = Utf8Tools::utf8_bad_strip($translationValue);
                                            }
                                            if ($xmlTool->isValidXMLString($dataForTranslation)) {

                                                $output[] = "\t" . '<trans-unit id="' . $table . '_' . $elementUid . '_' . $key . '">' . "\n";
                                                $output[] = "\t\t" . '<source>'.$dataForTranslation . '</source>' . "\n";
                                                if ($xmlTool->isValidXMLString($translationValue)) {
                                                    $output[] = "\t\t" . '<target>'.$translationValue . '</target>' . "\n";
                                                }
                                                $output[] = "\t" . '</trans-unit>' . "\r";
                                            } else {
                                                if ($params['noxmlcheck'] == '1') {

                                                    $output[] = "\t" . '<trans-unit id="' . $table . '_' . $elementUid . '_' . $key . '">' . "\n";
                                                    $output[] = "\t\t" . '<source><![CDATA[' . $dataForTranslation . ']]></source>' . "\n";
                                                    $output[] = "\t\t" . '<target>'.$translationValue . '</target>' . "\n";
                                                    $output[] = "\t" . '</trans-unit>' . "\r";
                                                } else {
                                                    $this->setInternalMessage($this->getLanguageService()->getLL('export.process.error.invalid.message'),
                                                        $elementUid . '/' . $table . '/' . $key);
                                                }
                                            }
                                        }
                                    } else {
                                        $this->setInternalMessage($this->getLanguageService()->getLL('export.process.error.empty.message'),
                                            $elementUid . '/' . $table . '/' . $key);
                                    }
                                }
                            }
                        }
                    }
                }
            }

        }
        // Provide a hook for specific manipulations before building the actual XML
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['exportCatXmlPreProcess'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['exportCatXmlPreProcess'] as $classReference) {
                $processingObject = GeneralUtility::getUserObj($classReference);
                $output = $processingObject->processBeforeExportingCatXml($output, $this);
            }
        }
        // get ISO2L code for source language
        $staticLangArr = array();
        if ($this->l10ncfgObj->getData('sourceLangStaticId') && ExtensionManagementUtility::isLoaded('static_info_tables')) {
            $staticLangArr = BackendUtility::getRecord('static_languages',
                $this->l10ncfgObj->getData('sourceLangStaticId'), 'lg_iso_2');
        }
        $XML = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";
        $XML .= '<xliff version="1.0">' . "\n";
        $XML .= "\t" .'<file source-language="'.$staticLangArr['lg_iso_2'].'" target-language="' . $targetIso . '" datatype="plaintext" original="messages" date="2014-06-02T11:48:25Z" product-name="TYPO3_CMS">'."\n";
        $XML .= "\t\t" . '<header />' . "\n";
        $XML .= "\t\t" . '<body>' . "\n";
        $XML .= "\t\t" . '</body>' . "\n";
        $XML .= implode('', $output) . "\n";
        $XML .= "\t" ."</file>" . "\n";
        $XML .= "</xliff>";
        return $this->saveExportFile($XML,'xlf');
    }

    /**
     * Adds keys and values of the JSON encoded meta data field to the XML head section
     *
     * @return string The XML to add to the head section
     */
    protected function additionalHeaderData() {
        $additionalHeaderData = '';
        if (!empty($this->l10ncfgObj->getData('metadata'))) {
            $additionalHeaderDataArray = json_decode($this->l10ncfgObj->getData('metadata'));
            if (is_array($additionalHeaderDataArray) && !empty($additionalHeaderDataArray)) {
                foreach ($additionalHeaderDataArray as $key => $value) {
                    $additionalHeaderData .= "\t\t" . '<' . $key . '>' . (string)$value . '</' . $key . '>' . "\n";
                }
            }
        }
        return $additionalHeaderData;
    }

    /**
     * Renders the list of internal message as XML tags
     *
     * @return string The XML structure to output
     */
    protected function renderInternalMessage()
    {
        $messages = '';
        foreach ($this->internalMessages as $messageInformation) {
            if (!empty($messages)) {
                $messages .= "\n\t";
            }
            $messages .= "\t\t" . '<t3_skippedItem>' . "\n\t\t\t\t" . '<t3_description>' . $messageInformation['message'] . '</t3_description>' . "\n\t\t\t\t" . '<t3_key>' . $messageInformation['key'] . '</t3_key>' . "\n\t\t\t" . '</t3_skippedItem>' . "\r";
        }
        return $messages;
    }

    /**
     * Force a new source language to export the content to translate
     *
     * @param integer $id
     *
     * @access public
     * @return void
     */
    public function setForcedSourceLanguage($id)
    {
        $this->forcedSourceLanguage = $id;
    }
}