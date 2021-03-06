<?php
namespace Localizationteam\L10nmgr\Controller;

/***************************************************************
 * Copyright notice
 * (c) 2007 Kasper Skårhøj <kasperYYYY@typo3.com>
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
 * Module 'Workspace Tasks' for the 'l10nmgr' extension.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Script Class for rendering the frameset
 *
 * @authorKasper Skaarhoj <kasperYYYY@typo3.com>
 * @packageTYPO3
 * @subpackage tx_l10nmgr
 */
class Module2
{
    // Internal, static:
    /**
     * @var int
     */
    protected $defaultWidth = 300; // Default width of the navigation frame. Can be overridden from $TBE_STYLES['dims']['navFrameWidth'] (alternative default value) AND from User TSconfig
    // Internal, dynamic:
    /**
     * @var string
     */
    protected $content; // Content accumulation.

    /**
     * Creates the header and frameset for the module/submodules
     *
     * @return void
     */
    public function main()
    {
        // Setting frame width:
        $width = $this->defaultWidth;
        $this->content .= '
	<frameset cols="' . $width . ',*">
	<frame name="nav_frame" src="' . BackendUtility::getModuleUrl('LocalizationManager_TranslationTasks') . '" marginwidth="0" marginheight="0" scrolling="auto" />
	<frame name="list_frame" src="" marginwidth="0" marginheight="0" scrolling="auto" />
	</frameset>
	</html>
	';
        $this->printContent();
    }

    /**
     * Outputting the accumulated content to screen
     *
     * @return void
     */
    protected function printContent()
    {
        echo $this->content;
    }
}