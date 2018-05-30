<?php
namespace Localizationteam\L10nmgr;

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
 * Addition of an item to the clickmenu
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Context menu processing
 *
 * @authorKasper Skaarhoj <kasperYYYY@typo3.com>
 * @packageTYPO3
 * @subpackage tx_l10nmgr
 */
class ClickMenu
{
    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * Main function
     *
     * @param $backRef
     * @param $menuItems
     * @param $table
     * @param $uid
     * @return array [type]...
     * @internal param $ [type]$$backRef: ...
     * @internal param $ [type]$menuItems: ...
     * @internal param $ [type]$table: ...
     * @internal param $ [type]$uid: ...
     *
     */
    public function main(&$backRef, $menuItems, $table, $uid)
    {
        $localItems = Array();
        if (!$backRef->cmLevel) {
            // Returns directly, because the clicked item was not from the pages table
            if ($table == "tx_l10nmgr_cfg") {
                // Adds the regular item:
                $LL = $this->includeLL();
                // Repeat this (below) for as many items you want to add!
                // Remember to add entries in the localconf.php file for additional titles.
                $url = BackendUtility::getModuleUrl(
                    'ConfigurationManager_LocalizationManager',
                    array(
                        'id' => $backRef->rec['pid'],
                        'srcPID' => $backRef->rec['pid'],
                        'exportUID' => $uid,
                    )
                );
                $localItems[] = $backRef->linkItem($this->getLanguageService()->getLLL("cm1_title", $LL),
                    $backRef->excludeIcon('<img src="' . ExtensionManagementUtility::siteRelPath("l10nmgr") . 'cm1/cm_icon.gif" width="15" height="12" border="0" align="top" />'),
                    $backRef->urlRefForCM($url),
                    1 // Disables the item in the top-bar. Set this to zero if you with the item to appear in the top bar!
                );
            }
            $localItems["moreoptions_tx_l10nmgr_cm3"] = $backRef->linkItem('L10Nmgr tools', '',
                "top.loadTopMenu('" . GeneralUtility::linkThisScript() . "&cmLevel=1&subname=moreoptions_tx_l10nmgrXX_cm3');return false;",
                0, 1);
            // Simply merges the two arrays together and returns ...
            $menuItems = array_merge($menuItems, $localItems);
        } elseif (GeneralUtility::_GET('subname') == 'moreoptions_tx_l10nmgrXX_cm3') {
            $url = BackendUtility::getModuleUrl('LocalizationManager_TranslationTasks',
                array(
                    'id' => $backRef->rec['pid'],
                    'table' => $table,
                )
            );
            $localItems[] = $backRef->linkItem('Create priority', '',
                $backRef->urlRefForCM($url . '&cmd=createPriority'), 1);
            $localItems[] = $backRef->linkItem('Manage priorities', '',
                $backRef->urlRefForCM($url . '&cmd=managePriorities'), 1);
            $localItems[] = $backRef->linkItem('Update Index', '', $backRef->urlRefForCM($url . '&cmd=updateIndex'), 1);
            $localItems[] = $backRef->linkItem('Flush Translations', '',
                $backRef->urlRefForCM($url . '&cmd=flushTranslations'), 1);
            $menuItems = array_merge($menuItems, $localItems);
        }
        return $menuItems;
    }

    /**
     * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found in that file.
     *
     * @return array Local lang value.
     */
    protected function includeLL()
    {
        $LOCAL_LANG = $this->getLanguageService()->includeLLFile('EXT:l10nmgr/Resources/Private/Language/locallang.xml',
            false);
        return $LOCAL_LANG;
    }

    /**
     * setter for databaseConnection object
     *
     * @return LanguageService $languageService
     */
    protected function getLanguageService()
    {
        if (!$this->languageService instanceof LanguageService) {
            $this->languageService = GeneralUtility::makeInstance(LanguageService::class);
        }
        if ($this->getBackendUser()) {
            $this->languageService->init($this->getBackendUser()->uc['lang']);
        }
        return $this->languageService;
    }

    /**
     * Gets the current backend user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}