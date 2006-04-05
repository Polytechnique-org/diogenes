<?php

require_once 'PHPUnit.php';

class InternalTest extends PHPUnit_TestCase
{
	function InternalTest($name)
	{
		$this->PHPUnit_TestCase($name);
	}
	
	function testInternal()
	{
		$this->assertEquals('xyz', 'xyz');
		$this->assertNull(NULL, "Aiee!  It's not null!");
		$this->assertNotNull('notnull', "Aiee! It's null!");
		$this->assertTrue(true, "Feck!  It's false!");

/*		if (!method_exists($this, 'assertSubStr'))
		{
			die("You need a version of PHPUnit which supports assertSubStr.  Apply\nassert.patch to your PHPUnit code.  Hassle Matt if this makes no sense.\n");
		}
*/
	}
}
									
