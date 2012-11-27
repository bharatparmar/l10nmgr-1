<?php
class tx_l10nmgr_domain_exporter_exportDataRepository_testcase extends tx_l10nmgr_tests_databaseTestcase {

	/**
	 * The setup method create the testdatabase and loads the basic tables into the testdatabase
	 *
	 */
	public function setUp(){
		$this->skipInWrongWorkspaceContext();

		$this->createDatabase();
		$db = $this->useTestDatabase();
		$import = array ('cms','l10nmgr');
		$optional = array('static_info_tables','templavoila', 'languagevisibility');
		foreach($optional as $ext) {
			if (t3lib_extMgm::isLoaded($ext)) {
				$import[] = $ext;
			}
		}
		$this->importExtensions($import);
	}

	/**
	 * Resets the database to the previouse state
	 *
	 */
	public function tearDown(){
		$this->cleanDatabase();
		$this->dropDatabase();
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);
	}

	/**
	 * This method is used to test that the repository can determine all elements with
	 * a given state in the history of its workflow states
	 *
	 */
	public function test_canFindExportDataWithStateInHistory(){
		$this->importDataSet('/exporter/fixtures/statehistory/canFindExportDataWithStateInHistory.xml');

		$exportDataRepository	= new tx_l10nmgr_domain_exporter_exportDataRepository();
		$exportDataCollection	= $exportDataRepository->findAllWithStateInHistory('l0nmgr_imported');

		$this->assertEquals($exportDataCollection->offsetGet(0)->getUid(), 2,'First element with state in history is wrong');
		$this->assertEquals($exportDataCollection->count(),1,'exportData Repository returns to much exportData objects with state in history');
	}

	/**
	 * This method is used to test if all exportData can be found without a given state in its history.
	 *
	 */
	public function test_canFindExportDataWithoutStateInHistory(){
		$this->importDataSet('/exporter/fixtures/statehistory/canFindExportDataWithStateInHistory.xml');

		$exportDataRepository	= new tx_l10nmgr_domain_exporter_exportDataRepository();
		$exportDataCollection	= $exportDataRepository->findAllWithoutStateInHistory('l0nmgr_imported');

		$this->assertEquals($exportDataCollection->offsetGet(0)->getUid(), 1,'First element without state in history is wrong');
		$this->assertEquals($exportDataCollection->count(),1,'exportData Repository returns to much exportData objects without state in history');
	}
}
?>