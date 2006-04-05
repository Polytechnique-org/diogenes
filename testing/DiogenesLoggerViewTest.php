<?php

require_once 'PHPUnit.php';
require_once 'diogenes/diogenes.logger-view.inc.php';
require_once 'diogenes/diogenes.core.globals.inc.php';
require_once 'TestHelpers.php';

class DiogenesLoggerViewTest extends PHPUnit_TestCase
{
	function setUp()
	{
		global $globals;

		$globals = new DiogenesCoreGlobals;
		
		$globals->database_error_fatal = true;
		$globals->db = new DiogenesDatabase('test', 'localhost', 'test', '');
		$globals->db->_fatal = true;
		
		if ($globals->db->err())
		{
			echo "DB err: " . $globals->db->error() . "\n";
		}
		
		$this->o = new DiogenesLoggerView();
	}
		
	function testConstructor()
	{
		global $globals;
		
		$this->assertEquals($this->o->dbh, $globals->db);
		$this->assertTrue(is_a($this->o->dbh, 'diogenesdatabase'));
	}

	function test_getAuths()
	{
		global $globals;
		$globals->table_log_sessions = 'diogenes_logsessions';

		$this->assertEquals(array(0 => __('all'), 'native' => 'native', 'external' => 'external'), $this->o->_getAuths());

		$globals->db->query("CREATE TABLE logsesh (auth enum('x', 'y', 'z', 'heh'))");
		$this->assertFalse($globals->db->err());
		
		$globals->table_log_sessions = 'logsesh';
		$this->assertEquals(array(0 => __('all'), 'x' => 'x', 'y' => 'y', 'z' => 'z', 'heh' => 'heh'), $this->o->_getAuths());
	}

	function test_getDays()
	{
		global $globals;
		
		$rows[] = "DELETE FROM diogenes_logsessions";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20040215000000)";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20040315000000)";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20040415000000)";
		BulkQueries($rows);
				
		$globals->table_log_sessions = 'diogenes_logsessions';

		$febdays[0] = __('all');
		$mardays[0] = __('all');
		$aprdays[0] = __('all');
		
		// Febdays should be 16 to 29 (leap year), mardays should be
		// all month, and aprdays should be up to and including
		// the 15th.

		for ($i = 1; $i < 16; $i++)
		{
			$aprdays[$i] = $mardays[$i] = $i;
		}

		for ($i = 15; $i < 30; $i++)
		{
			$mardays[$i] = $febdays[$i] = $i;
		}

		$mardays[30] = 30;
		$mardays[31] = 31;

		$this->assertTrue(checkdate(2,29,2004), "Checkdate giving dodgy results");
		$this->assertEquals(array(), $this->o->_getDays(2003, 1));
		$this->assertEquals(array(), $this->o->_getDays(2003, 3));
		$this->assertEquals(array(), $this->o->_getDays(2003, 6));
		$this->assertEquals(array(), $this->o->_getDays(2004, 1));
		$this->assertEquals($febdays, $this->o->_getDays(2004, 2));
		$this->assertEquals($mardays, $this->o->_getDays(2004, 3));
		$this->assertEquals($aprdays, $this->o->_getDays(2004, 4));
		$this->assertEquals(array(), $this->o->_getDays(2004, 6));
		$this->assertEquals(array(), $this->o->_getDays(2005, 1));
		$this->assertEquals(array(), $this->o->_getDays(2005, 3));
		$this->assertEquals(array(), $this->o->_getDays(2005, 8));
	}

	function test_getMonths()
	{
		global $globals;
		
		$rows[] = "DELETE FROM diogenes_logsessions";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20020401000000)";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20030401000000)";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20040401000000)";
		BulkQueries($rows);
		
		$this->assertEquals(array(), $this->o->_getMonths(2001));
		$this->assertEquals(array(0=>__('all'), 4=>4, 5=>5, 6=>6, 7=>7, 8=>8,
				9=>9, 10=>10, 11=>11, 12=>12),
				$this->o->_getMonths(2002));
		$this->assertEquals(array(0=>__('all'), 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6,
				7=>7, 8=>8, 9=>9, 10=>10, 11=>11, 12=>12),
				$this->o->_getMonths(2003));
		$this->assertEquals(array(0=>__('all'), 1=>1, 2=>2, 3=>3, 4=>4),
				$this->o->_getMonths(2004));
		$this->assertEquals(array(), $this->o->_getMonths(2005));
	}

	function test_getYears()
	{
		global $globals;
		
		$rows[] = "DELETE FROM diogenes_logsessions";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20020401000000)";
		$rows[] = "INSERT INTO diogenes_logsessions (start)
				VALUES (20040401000000)";
		BulkQueries($rows);
		
		$this->assertEquals(array(0=>__('all'), 2002=>2002, 2003=>2003, 2004=>2004),
				$this->o->_getYears());

	}
}
