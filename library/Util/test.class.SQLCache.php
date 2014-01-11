<?
Class TestSQLCache extends UnitTestCase{ 

	public function test_SQLCache_obj(){
		$c = new SQLCache();
		
		$result = "this is a sample result";
		$o_result = "other result";
		$t_result = "third result";
		
		$sql   = "SELECT * FROM avalanche_buddy WHERE 1";
		$other = "SELECT * FROM avalanche_buddy WHERE billy = 'yourmom'";
		$third = "SELECT * FROM avalanche_third WHERE 1";
		$this->assertEqual(SQLCache::getTableName($sql), "avalanche_buddy", "the table is correct");
		$this->assertEqual(SQLCache::getTableName($other), "avalanche_buddy", "the table is correct");
		$this->assertEqual(SQLCache::getTableName($third), "avalanche_third", "the table is correct");

		$c->put($sql, $result);
		$c->put($other, $o_result);
		$c->put($third, $t_result);
		
		$this->assertEqual($c->get($sql), $result, "the result is correct");
		$this->assertEqual($c->get($other), $o_result, "the result is correct");
		$this->assertEqual($c->get($third), $t_result, "the result is correct");
		$c->clear($sql);
		$this->assertEqual($c->get($sql), false, "the result is correct");
		$this->assertEqual($c->get($other), $o_result, "the result is correct");
		$this->assertEqual($c->get($third), $t_result, "the result is correct");
	}	


	public function test_SQLCache_Update(){
		$c = new SQLCache();
		
		$result = "this is a sample result";
		$o_result = "other result";
		$t_result = "third result";
		
		$sql = "SELECT * FROM avalanche_buddy WHERE 1";
		$other = "SELECT * FROM avalanche_buddy WHERE billy = 'yourmom'";
		$third = "SELECT * FROM avalanche_third WHERE 1";
		$update = "UPDATE avalanche_buddy SET `asdf` = 'asdf'";
		$this->assertEqual(SQLCache::getTableName($sql), "avalanche_buddy", "the table is correct");
		$this->assertEqual(SQLCache::getTableName($update), "avalanche_buddy", "the table is correct");
		
		$c->put($sql, $result);
		$c->put($other, $o_result);
		$c->put($third, $t_result);
		$c->clear($update); // clears all sql with same tablename
		$this->assertEqual($c->get($sql), false, "the result is correct");
		$this->assertEqual($c->get($other), false, "the result is correct");
		$this->assertEqual($c->get($third), $t_result, "the result is correct");
	}	


	public function test_SQLCache_Insert(){
		$c = new SQLCache();
		
		$result = "this is a sample result";
		$o_result = "other result";
		$t_result = "third result";
		
		$sql = "SELECT * FROM avalanche_buddy WHERE 1";
		$other = "SELECT * FROM avalanche_buddy WHERE billy = 'yourmom'";
		$third = "SELECT * FROM avalanche_third WHERE 1";
		$insert = "INSERT INTO avalanche_buddy (`asf`) VALUES ('asdf')";
		$this->assertEqual(SQLCache::getTableName($sql), "avalanche_buddy", "the table is correct");
		$this->assertEqual(SQLCache::getTableName($insert), "avalanche_buddy", "the table is correct");
		
		$c->put($sql, $result);
		$c->put($other, $o_result);
		$c->put($third, $t_result);
		$c->clear($insert);
		$this->assertEqual($c->get($sql), false, "the result is correct");
		$this->assertEqual($c->get($other), false, "the result is correct");
		$this->assertEqual($c->get($third), $t_result, "the result is correct");
	}	
};


?>