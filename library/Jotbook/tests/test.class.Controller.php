<?


class TestController extends UnitTestCase{


	public function setUp(){
//		echo "setting up";
	}
	
	
	protected function getDefaultList(){
		$list = array();
		
		$list["rows"] = array();
		$list["users"] = array();
		
		$row = array();
		$row["row_id"] = "1";
		$row["text"] = "one";
		$row["par"] = null;
		$row["prev"] = null;
		$list["rows"][] = $row;

		$row = array();
		$row["row_id"] = "2";
		$row["text"] = "two";
		$row["par"] = "1";
		$row["prev"] = null;
		$list["rows"][] = $row;

		$row = array();
		$row["row_id"] = "3";
		$row["text"] = "three";
		$row["par"] = "1";
		$row["prev"] = "2";
		$list["rows"][] = $row;

		$row = array();
		$row["row_id"] = "4";
		$row["text"] = "four";
		$row["par"] = "1";
		$row["prev"] = "3";
		$list["rows"][] = $row;

		$row = array();
		$row["row_id"] = "5";
		$row["text"] = "five";
		$row["par"] = "1";
		$row["prev"] = "4";
		$list["rows"][] = $row;

		$row = array();
		$row["row_id"] = "6";
		$row["text"] = "six";
		$row["par"] = "1";
		$row["prev"] = "5";
		$list["rows"][] = $row;
			
		return $list;
	}
	
	/**
	 * confirm that a new Listotron
	 * builds a good default list
	 */
	public function test_setup(){
		$listotron = new Listotron($this->getDefaultList());
		$control = new Controller($listotron);
		
		$user1 = "5ccda36503c32e2694b7e10278c73bea";
		$now = $listotron->getNOW();
		
		$data = json_decode('[{"outdent" : true,"dt" : "' . $now . '",
			"row_id" : "4","user_id" : "' . $user1 . '"}]');
		
		$out = $control->process($data);
		$rows = $out[0]["rows"];

		// check the output
		$this->assertEqual(count($rows), 3);
		$this->assertEqual($rows[0]["row_id"], 4);
		$this->assertEqual($rows[0]["par"], null);
		$this->assertEqual($rows[0]["prev"], 1);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 5);
		$this->assertEqual($rows[1]["par"], 4);
		$this->assertEqual($rows[1]["prev"], null);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);

		$this->assertEqual($rows[2]["row_id"], 6);
		$this->assertEqual($rows[2]["par"], 4);
		$this->assertEqual($rows[2]["prev"], 5);
		$this->assertEqual($rows[2]["lm"], $now);
		$this->assertEqual($rows[2]["lmb"], $user1);
		
	}


}




?>