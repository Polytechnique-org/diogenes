<?php

require_once 'PHPUnit.php';

require_once 'DB.php';
require_once 'diogenes/diogenes.database.inc.php';

class DBInitialTest extends PHPUnit_TestCase
{
	var $db = NULL;

	function DBInitialTest($name)
	{
		parent::PHPUnit_TestCase($name);
		
    		if (!extension_loaded('mysql') && !dl('mysql.so'))
		{
			die("The MySQL module is not your PHP configuration and could not be loaded, fix your PHP setup!");
		}

		$conn = mysql_connect('localhost', 'test');
		
		if (!$conn)
		{
			die("You need to have a MySQL database running on the local host, with a user\ncalled 'test' with no password.");
		}
		
		$rv = mysql_query("DROP DATABASE test");
		if (!$rv)
		{
			die("Database drop failed.  The user 'test' must have full privileges to the\n'test' database.");
		}

		mysql_query("CREATE DATABASE test");
		
		system("mysql -u test test < config/db/diogenes.sql");
	}

	function testTables()
	{
		$tables = array('diogenes_auth', 'diogenes_logactions',
				'diogenes_logevents',
				'diogenes_logsessions', 'diogenes_option',
				'diogenes_perm', 'diogenes_site');
		
		$DB = DB::Connect('mysql://test@localhost/test');
		if (DB::isError($DB))
		{
			die($DB->getMessage()."\n".$DB->getUserInfo()."\n");
		}

		$this->assertEquals($tables, $DB->getCol('show tables'));
	}
}

class DiogenesDatabaseTest extends PHPUnit_TestCase
{
	function testConstructor()
	{
		$DB = new DiogenesDatabase('localhost', 'test', 'test', '');
		
		$this->assertTrue(is_object($DB));
	}

	function testErrors()
	{
		$db = new DiogenesDatabase('tst', 'localhost', 'test', '');
		
		$this->assertTrue($db->err());
		$this->assertEquals(1049, $db->errno());
		$this->assertEquals("Unknown database 'tst'", $db->error());

		$db->ResetError();
		$this->assertFalse($db->err());
		$this->assertEquals(0, $db->errno());
		$this->assertEquals('', $db->error());

	}

	function testMoreErrors()
	{
		$db = new DiogenesDatabase('test', 'localhost', 'test', '');
		
		$this->assertFalse($db->err(), "Should be no error");
		
		$db->query("SLECT");
		
		$this->assertTrue($db->err(), "Should be error");
		$this->assertEquals(1064, $db->errno());
		$this->assertEquals("You have an error in your SQL syntax.  Check the manual that corresponds to your MySQL server version for the right syntax to use near 'SLECT' at line 1", $db->error());
		$this->assertEquals("SLECT", $db->errinfo());

		$db->ResetError();
		$this->assertEquals('', $db->errinfo());
	}
}
