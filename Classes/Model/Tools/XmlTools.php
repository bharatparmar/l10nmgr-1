<?php
namespace Localizationteam\L10nmgr\Model\Tools;

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
/**
 * Contains xml tools
 * $Id$
 *
 * @author Daniel Pötzinger <development@aoemedia.de>
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class XmlTools
{
    /**
     * @var RteHtmlParser
     */
    protected $parseHTML;

    public function __construct()
    {
        $this->parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
    }

    /**
     * Transforms a RTE Field to valid XML
     *
     * @param string $content HTML String which should be transformed
     *
     * @param int $withStripBadUTF8
     * @return mixed false if transformation failed, string with XML if all fine
     */
    public function RTE2XML($content, $withStripBadUTF8 = 0)
    {
        //function RTE2XML($content,$withStripBadUTF8=$this->getBackendUser()->getModuleData('l10nmgr/cm1/checkUTF8', '')) {
        //if (!$withStripBadUTF8) {
        //	$withStripBadUTF8 = $this->getBackendUser()->getModuleData('l10nmgr/cm1/checkUTF8', '');
        //}
        //echo '###'.$withStripBadUTF8;
        // First call special transformations (registered using hooks)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['transformation'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['transformation'] as $classReference) {
                $processingObject = GeneralUtility::getUserObj($classReference);
                $content = $processingObject->transform_rte($content, $this->parseHTML);
            }
        }
        $content = str_replace(CR, '', $content);
        $pageTsConf = BackendUtility::getPagesTSconfig(0);
        $rteConfiguration = $pageTsConf['RTE.']['default.'];
        $content = $this->parseHTML->RTE_transform($content, array(), 'rte', $rteConfiguration);
        //substitute & with &amp;
        //$content=str_replace('&','&amp;',$content); Changed by DZ 2011-05-11
        $content = str_replace('<hr>', '<hr />', $content);
        $content = str_replace('<br>', '<br />', $content);
        $content = preg_replace('/&amp;([#[:alnum:]]*;)/', '&\\1', $content);
        if ($withStripBadUTF8 == 1) {
            $content = Utf8Tools::utf8_bad_strip($content);
        }
        if ($this->isValidXMLString($content)) {
            return $content;
        } else {
            return false;
        }
    }

    /**
     * @param string $xmlString
     * @return bool
     */
    public function isValidXMLString($xmlString)
    {
        return $this->isValidXML('<!DOCTYPE dummy [ <!ENTITY nbsp " "> ]><dummy>' . $xmlString . '</dummy>');
    }

    /**
     * @param string $xml
     * @return bool
     */
    protected function isValidXML($xml)
    {
        $parser = xml_parser_create();
        $vals = array();
        $index = array();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 0);
        xml_parse_into_struct($parser, $xml, $vals, $index);
        if (xml_get_error_code($parser)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Transforms a XML back to RTE / reverse function of RTE2XML
     *
     * @param string $xmlstring XMLString which should be transformed
     *
     * @return string string with HTML
     */
    public function XML2RTE($xmlstring)
    {
        //fixed setting of Parser (TO-DO set it via typoscript)
        //Added because import failed
        $xmlstring = str_replace('<br/>', '<br>', $xmlstring);
        $xmlstring = str_replace('<br />', '<br>', $xmlstring);
        $xmlstring = str_replace('<hr/>', '<hr>', $xmlstring);
        $xmlstring = str_replace('<hr />', '<hr>', $xmlstring);
        $xmlstring = str_replace("\xc2\xa0", '&nbsp;', $xmlstring);
        //Writes debug information for CLI import to syslog if $TYPO3_CONF_VARS['SYS']['enable_DLOG'] is set.
        if (TYPO3_DLOG) {
            GeneralUtility::sysLog(__FILE__ . ': Before RTE transformation:' . LF . $xmlstring . LF, 'l10nmgr');
        }
        $pageTsConf = BackendUtility::getPagesTSconfig(0);
        $rteConf = $pageTsConf['RTE.']['default.'];
        $content = $this->parseHTML->RTE_transform($xmlstring, array(), 'db', $rteConf);
        // Last call special transformations (registered using hooks)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['transformation'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['l10nmgr']['transformation'] as $classReference) {
                $processingObject = GeneralUtility::getUserObj($classReference);
                $content = $processingObject->transform_db($content, $this->parseHTML);
            }
        }
        //substitute URL in <link> for CLI import
        $content = preg_replace('/<link http(s)?:\/\/[\w\.\/]*\?id=/', '<link ', $content);
        //Writes debug information for CLI import to syslog if $TYPO3_CONF_VARS['SYS']['enable_DLOG'] is set.
        if (TYPO3_DLOG) {
            GeneralUtility::sysLog(__FILE__ . ': After RTE transformation:' . LF . $content . LF, 'l10nmgr');
        }
        return $content;
    }
}