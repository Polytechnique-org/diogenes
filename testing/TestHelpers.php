<?php

/* Send the array of query strings ($queries) to the DB connection given in
 * $db.
 */
function BulkQueries($queries)
{
	global $globals;
	
	foreach ($queries as $q)
	{
		$globals->db->query($q);
		if ($globals->db->err())
		{
			die($globals->db->error()."\n".$globals->db->errinfo()."\n");
		}
	}
}
