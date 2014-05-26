<?php

/**
 * Class tx_l10nmgr_domain_exporter_exportDataRepository_testcase
 */
class tx_l10nmgr_domain_exporter_exportDataRepository_testcase extends tx_l10nmgr_tests_databaseTestcase {

	/**
	 * The setup method create the testdatabase and loads the basic tables into the testdatabase
	 *
	 * @return void
	 */
	public function setUp() {
		$this->skipInWrongWorkspaceContext();

		$this->createDatabase();
		$this->useTestDatabase();
		$import = array ('cms', 'l10nmgr');
		$optional = array('static_info_tables', 'templavoila', 'languagevisibility');

		// Read extension dependencies from extension configuration
		$extConfigurationArray = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['l10nmgr']);
		if(isset($extConfigurationArray['ext_dependencies'])){
			$dependencyArray = explode(',', $extConfigurationArray['ext_dependencies']);
			$optional = array_merge($optional, $dependencyArray);
			$optional = array_unique($optional);
		}

		foreach ($optional as $ext) {
			if (t3lib_extMgm::isLoaded($ext)) {
				$import[] = $ext;
			}
		}
		$this->importExtensions($import);
	}

	/**
	 * Resets the database to the previous state
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
	}

	/**
	 * This method is used to test that the repository can determine all elements with
	 * a given state in the history of its workflow states
	 *
	 * @return void
	 */
	public function test_canFindExportDataWithStateInHistory() {
		$this->importDataSet('/exporter/fixtures/statehistory/canFindExportDataWithStateInHistory.xml');

		$exportDataRepository = new tx_l10nmgr_domain_exporter_exportDataRepository();
		$exportDataCollection = $exportDataRepository->findAllWithStateInHistory('l0nmgr_imported');

		$this->assertEquals($exportDataCollection->offsetGet(0)->getUid(), 2, 'First element with state in history is wrong');
		$this->assertEquals($exportDataCollection->count(), 1, 'exportData Repository returns to much exportData objects with state in history');
	}

	/**
	 * This method is used to test if all exportData can be found without a given state in its history.
	 *
	 * @return void
	 */
	public function test_canFindExportDataWithoutStateInHistory() {
		$this->importDataSet('/exporter/fixtures/statehistory/canFindExportDataWithStateInHistory.xml');

		$exportDataRepository = new tx_l10nmgr_domain_exporter_exportDataRepository();
		$exportDataCollection = $exportDataRepository->findAllWithoutStateInHistory('l0nmgr_imported');

		$this->assertEquals($exportDataCollection->offsetGet(0)->getUid(), 1, 'First element without state in history is wrong');
		$this->assertEquals($exportDataCollection->count(), 1, 'exportData Repository returns to much exportData objects without state in history');
	}
}
?>