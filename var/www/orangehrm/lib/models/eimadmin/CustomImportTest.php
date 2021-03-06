<?php
// Call CustomImportTest::main() if this source file is executed directly.
if (!defined("PHPUnit_MAIN_METHOD")) {
    define("PHPUnit_MAIN_METHOD", "CustomImportTest::main");
}

require_once "PHPUnit/Framework/TestCase.php";
require_once "PHPUnit/Framework/TestSuite.php";

require_once ROOT_PATH . '/lib/models/eimadmin/CustomImport.php';
require_once "testConf.php";

/**
 * Test class for CustomImport.
 * Generated by PHPUnit_Util_Skeleton on 2008-01-26 at 19:19:52.
 */
class CustomImportTest extends PHPUnit_Framework_TestCase {
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main() {
        require_once "PHPUnit/TextUI/TestRunner.php";

        $suite  = new PHPUnit_Framework_TestSuite("CustomImportTest");
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp() {

   		$conf = new Conf();
    	$this->connection = mysql_connect($conf->dbhost.":".$conf->dbport, $conf->dbuser, $conf->dbpass);
        mysql_select_db($conf->dbname);
    	$this->_runQuery("TRUNCATE TABLE hs_hr_custom_import");

		// insert some test data
		$this->_runQuery("INSERT INTO hs_hr_custom_import(import_id, name, fields, has_heading) VALUES (1, 'Import 1', 'empId,lastName,firstName,middleName,street1,street2,city', 0)");
		$this->_runQuery("INSERT INTO hs_hr_custom_import(import_id, name, fields, has_heading) VALUES (2, 'Import 2', 'empId,lastName,firstName,city', 1)");
		$this->_runQuery("INSERT INTO hs_hr_custom_import(import_id, name, fields, has_heading) VALUES (3, 'Import 3', 'empId,firstName,lastName,street1,street2,city', 1)");

		$this->_runQuery("TRUNCATE TABLE hs_hr_custom_fields");
		$this->_runQuery("INSERT INTO hs_hr_custom_fields(field_num, name, type, extra_data) VALUES ('1', 'Blood Group', '0', '')");

		UniqueIDGenerator::getInstance()->resetIDs();
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown() {
    	$this->_runQuery("TRUNCATE TABLE hs_hr_custom_import");
		$this->_runQuery("TRUNCATE TABLE hs_hr_custom_fields");

    	UniqueIDGenerator::getInstance()->resetIDs();
    }

    /**
     * Implement getCustomImport
     */
    public function testGetCustomImport() {

    	// non existent id
    	$this->assertNull(CustomImport::getCustomImport(10));

    	// invalid id
    	try {
    		$import = CustomImport::getCustomImport('X1');
    		$this->fail("Should throw exception on invalid parameter");
	    } catch (CustomImportException $e) {
	    	$this->assertEquals(CustomImportException::INVALID_PARAMETERS, $e->getCode());
	    }

	    // valid id
    	$import = CustomImport::getCustomImport(1);
    	$this->assertEquals(1, $import->getId());
    	$this->assertEquals('Import 1', $import->getName());

    	$assignedFields = $import->getAssignedFields();
    	$expected = array('empId','lastName','firstName','middleName','street1','street2','city');
    	$this->assertTrue(is_array($assignedFields));
    	$this->assertEquals(count($expected), count($assignedFields));
		$diff = array_diff_assoc($expected, $assignedFields);
		$this->assertEquals(0, count($diff), "Assigned fields not correct");

    	$this->assertFalse($import->getContainsHeader());

    	$import = CustomImport::getCustomImport(2);
    	$this->assertEquals(2, $import->getId());
    	$this->assertEquals('Import 2', $import->getName());

    	$assignedFields = $import->getAssignedFields();
    	$expected = array('empId','lastName','firstName','city');
    	$this->assertTrue(is_array($assignedFields));
    	$this->assertEquals(count($expected), count($assignedFields));
		$diff = array_diff_assoc($expected, $assignedFields);
		$this->assertEquals(0, count($diff), "Assigned fields not correct");

    	$this->assertTrue($import->getContainsHeader());

    }

    /**
     * Test the getAllFields method
     */
    public function testGetAllFields() {

    	$allFields = CustomImport::getAllFields();

    	$this->assertTrue(!empty($allFields));
    	$this->assertTrue(is_array($allFields));

		// compare arrays considering order
		$expected = array("empId", "lastName",  "firstName", "middleName",
						  'HomePhone','MobilePhone', 'WorkPhone', 'WorkEmail','OtherEmail', 'DrivingLic',
						  "street1", "street2", "city",
                           "state", "zip", "gender", "birthDate", "ssn", "joinedDate", "workStation", "custom1",
                           "workState",
		                   "FITWStatus", "FITWExemptions", "SITWState", "SITWStatus", "SITWExemptions",
                           "SUIState", "DD1Routing", "DD1Account", "DD1Amount",
                           "DD1AmountCode", "DD1Checking", "DD2Routing",
		                   "DD2Account", "DD2Amount", "DD2AmountCode", "DD2Checking");

		$diff = array_diff_assoc($expected, $allFields);
		$this->assertEquals(0, count($diff), "Incorrect fields returned");

		// verify that there are no duplicates
		$unique = array_unique($allFields);
		$this->assertEquals(count($unique), count($allFields), "Duplicate field names found!");

		// verify that none of the fields have a comma in them
		foreach ($allFields as $field) {
			$this->assertTrue((strpos($field, ",") === false), "Field name contains comma");
		}
    }

    /**
     * Test method for getCustomImportList().
     */
    public function testGetCustomImportList() {
    	$list = CustomImport::getCustomImportList();
    	$this->assertTrue(is_array($list));
    	$this->assertEquals(3, count($list));

		$expected = array(1, 2, 3);
		foreach ($list as $import) {
			$key = array_search($import->getId(), $expected);
			$this->assertTrue($key !== false);
			unset($expected[$key]);
		}
		$this->assertTrue(empty($expected));

    	$this->_runQuery("DELETE FROM hs_hr_custom_import");
    	$list = CustomImport::getCustomImportList();
    	$this->assertTrue(is_array($list));
    	$this->assertEquals(0, count($list));
    }

    /**
     * Test for getCustomImportListForView().
     */
    public function testGetCustomImportListForView() {
    	$list = CustomImport::getCustomImportListForView(1,"","");
    	$this->assertTrue(is_array($list));
    	$this->assertEquals(3, count($list));

		$expected = array(1=>'Import 1', 2=>'Import 2', 3=>'Import 3');
		foreach ($list as $import) {
			$id = $import[0];
			$name = $import[1];

			$this->assertTrue(array_key_exists($id, $expected));
			$this->assertEquals($expected[$id], $name);
			unset($expected[$id]);
		}
		$this->assertTrue(empty($expected));

    	$this->_runQuery("DELETE FROM hs_hr_custom_import");
    	$list = CustomImport::getCustomImportListForView(1,"","");
    	$this->assertNull($list);
    }

    /**
     * Test the getAvailableFields() method
     */
    public function testGetAvailableFields() {

    	$allFields = CustomImport::getAllFields();
    	$allCount = count($allFields);

    	$import = new CustomImport();
		$import->setName("NewImport12");

		// Assign everything
		$import->setAssignedFields($allFields);
		$available = $import->getAvailableFields();
		$this->assertTrue(is_array($available));
		$this->assertEquals(0, count($available));

		// Assign 3 fields
		$assign = array("empId", "firstName","gender");
		$import->setAssignedFields($assign);
		$available = $import->getAvailableFields();
		$this->assertTrue(is_array($available));
		$this->assertEquals($allCount - 3, count($available));

		$expected = $allFields;
		unset($expected[array_search("empId", $expected)]);
		unset($expected[array_search("firstName", $expected)]);
		unset($expected[array_search("gender", $expected)]);

		// Verify arrays equal
		$diff = array_diff($expected, $available);
		$this->assertEquals(0, count($diff), "Arrays should be equal");
    }

    /**
     * Test deleteImports() method
     */
    public function testDeleteImports() {

		$countBefore = $this->_count();

		// invalid id parameter
		try {
			$deleted = CustomImport::deleteImports(1);
			$this->fail("Should throw an exception on invalid parameter");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::INVALID_PARAMETERS, $e->getCode());
		}

		try {
			$deleted = CustomImport::deleteImports(array(1, "xyz"));
			$this->fail("Should throw an exception on invalid parameter");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::INVALID_PARAMETERS, $e->getCode());
		}


		// empty array
		$ids = array();
		$deleted = CustomImport::deleteImports($ids);
		$this->assertEquals(0, $deleted);

		$count = $this->_count();
		$this->assertEquals($countBefore, $count);

		// one id
		$ids = array(1);
		$deleted = CustomImport::deleteImports($ids);
		$this->assertEquals(1, $deleted);

		$count = $this->_count();
		$this->assertEquals($countBefore - 1, $count);

		// two id's
		$ids = array(2, 3);
		$deleted = CustomImport::deleteImports($ids);
		$this->assertEquals(2, $deleted);

		$count = $this->_count();
		$this->assertEquals($countBefore - 3, $count);

    }

    /**
     * Test case for save() method for new custom import definition
     */
    public function testSaveNew() {

		$countBefore = $this->_count();

		// save with duplicate name should throw exception
		$import = new CustomImport();
		$import->setName("Import 1");
		$import->setAssignedFields(array("empId", "lastName", "firstName", "street1", "gender"));
		try {
			$import->save();
			$this->fail("Exception should be thrown on duplicate name");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::DUPLICATE_IMPORT_NAME, $e->getCode(), $e->getMessage());
		}

		// Exception should be thrown on empty name
		$import = new CustomImport();
		$import->setName("");
		$import->setAssignedFields(array("empId", "street1", "gender", "lastName", "firstName"));
		try {
			$import->save();
			$this->fail("Exception should be thrown on empty name");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::EMPTY_IMPORT_NAME, $e->getCode());
		}

		// save with empty fields should throw exception
		$import->setName("New Import 1");
		$import->setAssignedFields(array());
		try {
			$import->save();
			$this->fail("Exception should be thrown on empty assigned fields");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::NO_ASSIGNED_FIELDS, $e->getCode());
		}

		$import->setName("New Import 1");
		$import->setAssignedFields(null);
		try {
			$import->save();
			$this->fail("Exception should be thrown on empty assigned fields");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::NO_ASSIGNED_FIELDS, $e->getCode());
		}

		// save with field not in field list should throw exception
		$import->setName("New Import 1");
		$import->setAssignedFields(array("firstName", "lastName", "EmployeeId"));
		try {
			$import->save();
			$this->fail("Exception should be thrown on invalid field");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::INVALID_FIELD_NAME, $e->getCode());
		}

		// save with compulsary field missing should throw exception.
		$import->setAssignedFields(array("firstName", "empId"));
		try {
			$import->save();
			$this->fail("Exception should be thrown when compulsary field is missing");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::COMPULSARY_FIELDS_NOT_ASSIGNED, $e->getCode());
		}

		// valid save, verify data saved
		$import->setName("New Import 1");
		$import->setAssignedFields(array("empId", "street1", "gender", "lastName", "firstName"));
		$import->setContainsHeader(true);
		$import->save();

		$id = $import->getId();

		// verify id set
		$this->assertTrue(!empty($id));
		$this->assertEquals(4, $id);

		// verify saved
		$name = $import->getName();
		$fields = implode(",", $import->getAssignedFields());
		$hasHeader = CustomImport::HAS_HEADING;

		$countAfter = $this->_count();
		$this->assertEquals(1, $countAfter - $countBefore);

		$count = $this->_count("import_id={$id} AND name='{$name}' AND fields='{$fields}' AND has_heading={$hasHeader}");
		$this->assertEquals(1, $count, "Not inserted");

		// should be able to save without setting containsHeader
		$import2 = new CustomImport();
		$import2->setName("New Import 2");
		$import2->setAssignedFields(array("empId", "street1", "gender", "lastName", "firstName"));
		$import2->save();

		$id = $import2->getId();

		// verify id set
		$this->assertTrue(!empty($id));
		$this->assertEquals(5, $id);

		// verify saved
		$name = $import2->getName();
		$fields = implode(",", $import2->getAssignedFields());
		$hasHeader = CustomImport::NO_HEADING;;

		$countAfter = $this->_count();
		$this->assertEquals(2, $countAfter - $countBefore);

		$count = $this->_count("import_id={$id} AND name='{$name}' AND fields='{$fields}' AND has_heading={$hasHeader}");
		$this->assertEquals(1, $count, "Not inserted");
    }

    /**
     * Test case for save() method for existing custom import definition
     */
    public function testSaveUpdate() {

		$countBefore = $this->_count();

		// save with duplicate name should throw exception
		$import = new CustomImport();

		// we set id = 2, so save should update the record with id=2
		$import->setId(2);

		$import->setName("Import 1");
		$import->setAssignedFields(array("empId", "street1", "gender", "firstName", "lastName"));
		try {
			$import->save();
			$this->fail("Exception should be thrown on duplicate name");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::DUPLICATE_IMPORT_NAME, $e->getCode());
		}

		// save with empty fields should throw exception
		$import->setName("New Import 1");
		$import->setAssignedFields(array());
		try {
			$import->save();
			$this->fail("Exception should be thrown on empty assigned fields");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::NO_ASSIGNED_FIELDS, $e->getCode());
		}

		$import->setName("New Import 1");
		$import->setAssignedFields(null);
		try {
			$import->save();
			$this->fail("Exception should be thrown on empty assigned fields");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::NO_ASSIGNED_FIELDS, $e->getCode());
		}

		// save with field not in field list should throw exception
		$import->setName("New Import 1");
		$import->setAssignedFields(array("firstName", "lastName", "EmployeeId"));
		try {
			$import->save();
			$this->fail("Exception should be thrown on invalid field");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::INVALID_FIELD_NAME, $e->getCode());
		}

		// save with compulsary field missing should throw exception.
		$import->setName("New Import 1");
		$import->setAssignedFields(array("firstName", "empId"));
		try {
			$import->save();
			$this->fail("Exception should be thrown when compulsary field is missing");
		} catch (CustomImportException $e) {
			$this->assertEquals(CustomImportException::COMPULSARY_FIELDS_NOT_ASSIGNED, $e->getCode());
		}

		// valid save, verify data saved
		$import->setName("New Import 1");
		$import->setAssignedFields(array("empId", "street1", "firstName", "lastName", "gender"));
		$import->setContainsHeader(true);
		$import->save();

		$id = $import->getId();

		// verify id not changed
		$this->assertTrue(!empty($id));
		$this->assertEquals(2, $id);

		// verify saved
		$name = $import->getName();
		$fields = implode(",", $import->getAssignedFields());
		$hasHeader = CustomImport::HAS_HEADING;

		$countAfter = $this->_count();
		$this->assertEquals($countAfter, $countBefore);

		$count = $this->_count("import_id={$id} AND name='{$name}' AND fields='{$fields}' AND has_heading='{$hasHeader}'");
		$this->assertEquals(1, $count, "Not Updated");

		// Save without changing anything
		$import->save();

		$id = $import->getId();

		// verify id not changed
		$this->assertTrue(!empty($id));
		$this->assertEquals(2, $id);
		$countAfter = $this->_count();
		$this->assertEquals($countAfter, $countBefore);

		$count = $this->_count("import_id={$id} AND name='{$name}' AND fields='{$fields}' AND has_heading='{$hasHeader}'");
		$this->assertEquals(1, $count, "Not Updated");

    }

	private function _runQuery($sql) {
		$this->assertTrue(mysql_query($sql), mysql_error());
	}

	/**
	 * Count the number of rows in the database with the give condition
	 *
	 * @param string $where Optional where condition
	 * @return int Number of matching rows in database
	 */
    private function _count($where = null) {

    	$sql = "SELECT COUNT(*) FROM hs_hr_custom_import";
    	if (!empty($where)) {
    		$sql .= " WHERE " . $where;
    	}
		$result = mysql_query($sql);
		$row = mysql_fetch_array($result, MYSQL_NUM);
        $count = $row[0];
		return $count;
    }

}

// Call CustomImportTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == "CustomImportTest::main") {
    CustomImportTest::main();
}
?>
