<?php

/**
 * This class is used to test the functionallity of the l10nAccumulatedInformationsFactory class.
 *
 * @author Timo Schmidt
 * @see tx_l10nmgr:l10nAccumulatedInformationFactory
 *
 */

class tx_l10nmgr_domain_translateable_translateableField_testcase extends tx_l10nmgr_tests_databaseTestcase {

	/**
	 * The setup method create the testdatabase and loads the basic tables into the testdatabase
	 *
	 */
	public function setUp() {
		$this->skipInWrongWorkspaceContext();

		$this->createDatabase ();
		$db = $this->useTestDatabase ();
		$this->importStdDB();
			// order of extension-loading is important !!!!
		$import = array ('cms','l10nmgr');
		$optional = array('static_info_tables','templavoila');
		foreach($optional as $ext) {
			if (t3lib_extMgm::isLoaded($ext)) {
				$import[] = $ext;
			}
		}
		$this->importExtensions($import);
	}

	public function tearDown() {
		$GLOBALS ['TYPO3_DB']->sql_select_db ( TYPO3_db );
	}

	/**
	 * regular fields without specific configuration should return "plain" as transformation type
	 *
	 * @test
	 */
	public function canDetectTransformationTypePlain() {

		$tF = new tx_l10nmgr_domain_translateable_translateableField();

		$this->assertEquals('plain', $tF->getTransformationType());
	}

	/**
	 * assuming that the field is configured to require RTE transformations
	 * the required output is "text"
	 *
	 * the information is taken from t8tools
	 *
	 * @test
	 */
	public function canHandleRTEfields() {
		$tF = new tx_l10nmgr_domain_translateable_translateableField();
		$tF->setFieldType('text');
		$tF->setIsRTE(true);
		$this->assertEquals('text', $tF->getTransformationType());
	}

	/**
	 * assuming that the field is configured to contain plain HTML
	 * we suggest that "html" is returned
	 *
	 * the information is taken from t8tools
	 *
	 * @test
	 */
	public function canHandleHTMLfields() {
		$tF = new tx_l10nmgr_domain_translateable_translateableField();
		$tF->setIsHTML(true);
		$this->assertEquals('html', $tF->getTransformationType());
	}

	/**
	 * in case of wrong configuration we assume that the RTE-setting
	 * is "stronger"
	 *
	 * the information is taken from t8tools
	 *
	 * @test
	 */
	public function canHandleRTEvsHTMLfieldSetting() {
		$tF = new tx_l10nmgr_domain_translateable_translateableField();
		$tF->setFieldType('text');
		$tF->setIsRTE(true);
		$tF->setIsHTML(true);
		$this->assertEquals('text', $tF->getTransformationType());

	}
}

?>