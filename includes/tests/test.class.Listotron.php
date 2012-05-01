<?
class TestListotron extends UnitTestCase{ 

	public function setUp(){
//		echo "setting up";
	}
	
	/**
	 * confirm that a new Listotron
	 * builds a good default list
	 */
	public function test_setup(){
		$listotron = new Listotron();

		$row = $listotron->getRow(4);
		$this->assertEqual($row["row_id"], 4);
		$this->assertEqual($row["par"], 1);
		$this->assertEqual($row["prev"], 3);
		
		$row = $listotron->getRow(1);
		$this->assertEqual($row["row_id"], 1);
		$this->assertEqual($row["par"], null);
		$this->assertEqual($row["prev"], null);
		
		$row = $listotron->getRow(2);
		$this->assertEqual($row["row_id"], 2);
		$this->assertEqual($row["par"], 1);
		$this->assertEqual($row["prev"], null);
		
		$row = $listotron->getRow(6);
		$this->assertEqual($row["row_id"], 6);
		$this->assertEqual($row["par"], 1);
		$this->assertEqual($row["prev"], 5);
		
	}
	
	/**
	 * test that our validation method works
	 *
	 */
	public function test_setup_validate(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();
		
		// make sure it's valid
		$this->assertTrue($listotron->isValidHuh());

		// modify the List
		$rows = $listotron->indent(4, $user1);
		
		// make sure it's still valid
		$this->assertTrue($listotron->isValidHuh());
	}



	/**
	 * a test case that returns nothing
	 *
	 */
	public function test_fix_empty_response(){
		$listotron = new Listotron(
			array("rows" => array(
								array(
				                    "row_id" => 1,
				                    "text" => "one",
				                    "par" => null,
				                    "prev" => null
				                ),

								array(
				                    "row_id" => 2,
				                    "text" => "two",
				                    "par" => 1,
				                    "prev" => null
				                ),
								array(
				                    "row_id" => 3,
				                    "text" => "three",
				                    "par" => 1,
				                    "prev" => 2
				                ),
								array(
				                    "row_id" => 4,
				                    "text" => "four",
				                    "par" => 1,
				                    "prev" => 3
				                ),
								array(
				                    "row_id" => 5,
				                    "text" => "five",
				                    "par" => 1,
				                    "prev" => 4
				                ),
								array(
				                    "row_id" => 6,
				                    "text" => "six",
				                    "par" => 1,
				                    "prev" => 5
				                ),
								array(
				                    "row_id" => 7,
				                    "text" => "hopefully",
				                    "par" => 1,
				                    "prev" => 6,
				                    "lmb" => "bf908bd9b1e9c59bee9966326700b8ec",
                				    "lm" => "2009-06-18 00:01:470.62909200"
				                ),
								array(
				                    "row_id" => 8,
				                    "text" => "",
				                    "par" => 1,
				                    "prev" => 7,
				                    "lmb" => "bf908bd9b1e9c59bee9966326700b8ec",
                				    "lm" => "2009-06-18 00:01:470.62909200"
				                )
			), "users" => array(
								array(
		        	        	    "user_id" => "bf908bd9b1e9c59bee9966326700b8ec",
    		    	        	    "stamp" => "2009-06-18 00:01:470.78491400",
	        	    	        	"row_id" => 8
								)
		)));


		$control = new Controller($listotron);

		$user1 = "bf908bd9b1e9c59bee9966326700b8ec";
		$now = $listotron->getNOW();
		
		// make sure it's valid
		$this->assertTrue($listotron->isValidHuh());

		try{
			$json = json_decode("[{\"edit\" : true,\"dt\" : \"2009-06-18 00:01:480.91946500\",\"row_id\" : 8,\"text\" : \"i\'ll \",\"user_id\" : \"bf908bd9b1e9c59bee9966326700b8ec\"},{\"insert_after\" : true,\"dt\" : \"2009-06-18 00:01:480.91946500\",\"row_id\" : 8,\"user_id\" : \"bf908bd9b1e9c59bee9966326700b8ec\"}]");
			$ret = $control->process($json);
		}catch(NullJSONException $e){
			$this->assertTrue(true);
			return;
		}
		$this->fail("should have failed on null input");
	}

	/**
	 * test that an indent does what
	 * we think it does.
	 *
	 * 1			1
	 *  2			 2
	 *  3			 3
	 *  4	  =>	  4
	 *  5			 5
	 *  6			 6
	 *
	 */
	public function test_indent(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();
		
		// modify the List
		$rows = $listotron->indent(4, $user1);
		
		// check the output
		$this->assertEqual(count($rows), 2);
		$this->assertEqual($rows[0]["row_id"], 4);
		$this->assertEqual($rows[0]["par"], 3);
		$this->assertEqual($rows[0]["prev"], null);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 5);
		$this->assertEqual($rows[1]["par"], 1);
		$this->assertEqual($rows[1]["prev"], 3);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);
	}


	/**
	 * test that an outdent does what
	 * we think it does.
	 *
	 * 1			1
	 *  2			 2
	 *  3			 3
	 *  4	  =>	4
	 *  5			 5
	 *  6			 6
	 *
	 */
	public function test_outdent(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();
		
		// modify the List
		$rows = $listotron->outdent(4, $user1);
		
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



	/**
	 * test that an outdent does what
	 * we think it does.
	 *
	 * 1			1
	 *  2			 2
	 *  3			 3
	 * 4	  =>	 4
	 *  5			  5
	 *  6			  6
	 *
	 */
	public function test_indent_with_kids(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();
		
		// modify the List
		$rows = $listotron->outdent(4, $user1);
		$rows = $listotron->indent(4, $user1);
		
		// check the output
		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]["row_id"], 4);
		$this->assertEqual($rows[0]["par"], 1);
		$this->assertEqual($rows[0]["prev"], 3);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
	}


	/**
	 * test that an outdent does what
	 * we think it does.
	 *
	 * 1			1
	 *  2			 2
	 *  3			 3
	 *  4	  =>	4
	 *   5			 5
	 *   6			 6
	 *
	 */
	public function test_outdent_with_kids(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();
		
		// modify the List
		$rows = $listotron->outdent(4, $user1);
		$rows = $listotron->indent(4, $user1);
		$rows = $listotron->outdent(4, $user1);
		
		// check the output
		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]["row_id"], 4);
		$this->assertEqual($rows[0]["par"], null);
		$this->assertEqual($rows[0]["prev"], 1);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
	}


	/**
	 * test now vs then
	 */
	public function test_now_vs_then(){
	
		$listotron = new Listotron();
		$now = $listotron->getNOW();
		usleep(300);
		$listotron->updateNOW();
		$then = $listotron->getNOW();

		$this->assertNotEqual($now, $then);
	}

	/**
	 * test returning the last edited rows
	 * this test assumes that the user
	 * is asking for edited rows and it
	 * already knows about the user who
	 * edited them, just the time is old
	 */
	public function test_get_rows_since(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$user2 = md5(rand() . md5(rand()));
		$then = $listotron->getNOW();
		usleep(500);
		$listotron->updateNOW();
		$lmb = json_decode("{ \"$user1\" : \"$then\" }");
		$now = $listotron->getNOW();
		
		// modify the List
		$rows = $listotron->outdent(4, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$rows = $listotron->getAllRowsSince($lmb, $user2);
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



	/**
	 * test returning the last edited rows
	 * this test assumes that the user
	 * is asking for edited rows and it
	 * /does not/ know about the user
	 * who edited them
	 */
	public function test_get_rows_since2(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$user2 = md5(rand() . md5(rand()));
		$user3 = md5(rand() . md5(rand()));
		$then = $listotron->getNOW();
		usleep(500);
		$listotron->updateNOW();
		$lmb = json_decode("{ \"$user3\" : \"$then\" }");
		$now = $listotron->getNOW();
		
		// modify the List
		$rows = $listotron->outdent(4, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$rows = $listotron->getAllRowsSince($lmb, $user2);
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

	/**
	 * test inserting a new row before
	 * a row in the middle of the list,
	 * beginning of the list, and end
	 * of the list
	 */
	public function test_insert_row_before(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();

		// modify the middle of List
		$rows = $listotron->insertRowBefore(4, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$this->assertEqual(count($rows), 2);
		
		$this->assertEqual($rows[0]["row_id"], 7);
		$this->assertEqual($rows[0]["par"], 1);
		$this->assertEqual($rows[0]["prev"], 3);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 4);
		$this->assertEqual($rows[1]["par"], 1);
		$this->assertEqual($rows[1]["prev"], 7);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);

		// modify the beginning of List
		$rows = $listotron->insertRowBefore(1, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$this->assertEqual(count($rows), 2);
		
		$this->assertEqual($rows[0]["row_id"], 8);
		$this->assertEqual($rows[0]["par"], null);
		$this->assertEqual($rows[0]["prev"], null);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 1);
		$this->assertEqual($rows[1]["par"], null);
		$this->assertEqual($rows[1]["prev"], 8);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);


		// modify the end of List
		$rows = $listotron->insertRowBefore(6, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$this->assertEqual(count($rows), 2);
		
		$this->assertEqual($rows[0]["row_id"], 9);
		$this->assertEqual($rows[0]["par"], 1);
		$this->assertEqual($rows[0]["prev"], 5);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 6);
		$this->assertEqual($rows[1]["par"], 1);
		$this->assertEqual($rows[1]["prev"], 9);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);
	}

	/**
	 * test inserting a new row after
	 * a row in the middle of the list,
	 * beginning of the list, and end
	 * of the list
	 */
	public function test_insert_row_after(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$now = $listotron->getNOW();

		// modify the middle of List
		$rows = $listotron->insertRowAfter(4, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$this->assertEqual(count($rows), 2);
		
		$this->assertEqual($rows[0]["row_id"], 7);
		$this->assertEqual($rows[0]["par"], 1);
		$this->assertEqual($rows[0]["prev"], 4);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 5);
		$this->assertEqual($rows[1]["par"], 1);
		$this->assertEqual($rows[1]["prev"], 7);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);

		// modify the beginning of List
		$rows = $listotron->insertRowAfter(1, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$this->assertEqual(count($rows), 7);
		
		$this->assertEqual($rows[0]["row_id"], 8);
		$this->assertEqual($rows[0]["par"], null);
		$this->assertEqual($rows[0]["prev"], 1);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
		$this->assertEqual($rows[1]["row_id"], 2);
		$this->assertEqual($rows[1]["par"], 8);
		$this->assertEqual($rows[1]["prev"], null);
		$this->assertEqual($rows[1]["lm"], $now);
		$this->assertEqual($rows[1]["lmb"], $user1);
		$this->assertEqual($rows[2]["row_id"], 3);
		$this->assertEqual($rows[2]["par"], 8);
		$this->assertEqual($rows[3]["row_id"], 4);
		$this->assertEqual($rows[3]["par"], 8);


		// modify the end of List
		$rows = $listotron->insertRowAfter(6, $user1);

		// now get the rows changed by not me
		// since before NOW()
		$this->assertEqual(count($rows), 1);
		
		$this->assertEqual($rows[0]["row_id"], 9);
		$this->assertEqual($rows[0]["par"], 8);
		$this->assertEqual($rows[0]["prev"], 6);
		$this->assertEqual($rows[0]["lm"], $now);
		$this->assertEqual($rows[0]["lmb"], $user1);
		
	}

	public function test_track_users(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));
		$user2 = md5(rand() . md5(rand()));
		$user3 = md5(rand() . md5(rand()));

		$beforethen = $listotron->getNOW();
		usleep(500);
		$listotron->updateNOW();
		
		$then = $listotron->getNOW();
		$listotron->trackUser($user1, 4);
		$listotron->trackUser($user2, 3);
		
		$changes = $listotron->getMovementSince($then);
		$this->assertEqual(count($changes), 2);
		$this->assertEqual($changes[0]["user_id"], $user1);
		$this->assertEqual($changes[0]["row_id"], 4);
		$this->assertEqual($changes[1]["user_id"], $user2);
		$this->assertEqual($changes[1]["row_id"], 3);
		
		$changes = $listotron->getMovementSince($beforethen);
		$this->assertEqual(count($changes), 2);
		$this->assertEqual($changes[0]["user_id"], $user1);
		$this->assertEqual($changes[0]["row_id"], 4);
		$this->assertEqual($changes[1]["user_id"], $user2);
		$this->assertEqual($changes[1]["row_id"], 3);
		
		usleep(500);
		$listotron->updateNOW();
		$now = $listotron->getNOW();

		$changes = $listotron->getMovementSince($now);
		$this->assertEqual(count($changes), 0);

		$listotron->trackUser($user2, 7);
		$listotron->trackUser($user3, 6);

		$changes = $listotron->getMovementSince($now);
		$this->assertEqual(count($changes), 2);
		$this->assertEqual($changes[0]["user_id"], $user2);
		$this->assertEqual($changes[0]["row_id"], 7);
		$this->assertEqual($changes[1]["user_id"], $user3);
		$this->assertEqual($changes[1]["row_id"], 6);
		
	}

	public function test_delete_row_with_only_kids(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));

		$listotron->indent(3, $user1);
		$listotron->indent(4, $user1);
		$listotron->indent(5, $user1);
		$listotron->indent(6, $user1);
		$listotron->indent(2, $user1);

		$rows = $listotron->delete(2, $user1);
		
		$this->assertEqual(count($rows), 5);
		$this->assertEqual($rows[0]["row_id"], 3);
		$this->assertEqual($rows[1]["row_id"], 4);
		$this->assertEqual($rows[1]["par"], 1);
		$this->assertEqual($rows[2]["row_id"], 5);
		$this->assertEqual($rows[2]["par"], 1);
		$this->assertEqual($rows[3]["row_id"], 6);
		$this->assertEqual($rows[3]["par"], 1);
		$this->assertEqual($rows[4]["row_id"], 2);
		$this->assertEqual($rows[4]["del"], $listotron->getNOW());
	}

	public function test_delete_row_with_only_siblings(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));

		$rows = $listotron->delete(2, $user1);

		$this->assertEqual(count($rows), 2);
		$this->assertEqual($rows[0]["row_id"], 3);
		$this->assertEqual($rows[1]["row_id"], 2);
	}

	public function test_delete_row_with_no_sibs_or_kids(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));

		$listotron->indent(2, $user1);
		$listotron->outdent(3, $user1);
		$listotron->outdent(4, $user1);
		$listotron->outdent(5, $user1);
		$listotron->outdent(6, $user1);
		
		$rows = $listotron->delete(2, $user1);

		$this->assertEqual(count($rows), 1);
		$this->assertEqual($rows[0]["row_id"], 2);
	}


	public function test_delete_row_move_kids_to_prev(){
		$listotron = new Listotron();

		$user1 = md5(rand() . md5(rand()));

		$listotron->outdent(2, $user1);
		
		$rows = $listotron->delete(2, $user1);

		$this->assertEqual(count($rows), 5);
		$this->assertEqual($rows[0]["row_id"], 3);
		$this->assertEqual($rows[1]["row_id"], 4);
		$this->assertEqual($rows[1]["par"], 1);
		$this->assertEqual($rows[2]["row_id"], 5);
		$this->assertEqual($rows[2]["par"], 1);
		$this->assertEqual($rows[3]["row_id"], 6);
		$this->assertEqual($rows[3]["par"], 1);
		$this->assertEqual($rows[4]["row_id"], 2);
		$this->assertEqual($rows[4]["del"], $listotron->getNOW());
	}



	/**
	 * test deleting row. this test used
	 * to create an invalid list after the
	 * delete operation
	 *
	 *
	 * BEFORE
	 * root
	 *   - node1
	 *      - kid1
	 *      - kid2
	 *   - node2      <= delete this one
	 *      - kid3
	 *      - kid4
	 *
	 *
	 * AFTER
	 * root
	 *   - node1
	 *      - kid1
	 *      - kid2
	 *      - kid3
	 *      - kid4
	 */
	public function test_error_1334729757(){
		$listotron = new Listotron(array (
		  'rows' => 
		  array (
		    0 => 
		    array (
		      'row_id' => '1',
		      'text' => 'one',
		      'par' => NULL,
		      'prev' => NULL,
		    ),
		    1 => 
		    array (
		      'row_id' => '2',
		      'text' => 'two',
		      'par' => '1',
		      'prev' => NULL,
		    ),
		    2 => 
		    array (
		      'row_id' => '3',
		      'text' => 'three',
		      'par' => NULL,
		      'prev' => '1',
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:190.15526800',
		    ),
		    3 => 
		    array (
		      'row_id' => 7,
		      'text' => '',
		      'par' => NULL,
		      'prev' => '3',
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.80870000',
		      'del' => '2012-04-18 06:15:410.78905200',
		    ),
		    4 => 
		    array (
		      'row_id' => 8,
		      'text' => 'what',
		      'par' => '3',
		      'prev' => NULL,
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.78905200',
		    ),
		    5 => 
		    array (
		      'row_id' => 9,
		      'text' => '',
		      'par' => '3',
		      'prev' => 8,
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:500.55838600',
		      'del' => '2012-04-18 06:15:500.53828000',
		    ),
		    6 => 
		    array (
		      'row_id' => 10,
		      'text' => 'the',
		      'par' => '3',
		      'prev' => 8,
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:500.53828000',
		    ),
		    7 => 
		    array (
		      'row_id' => 11,
		      'text' => 'deal',
		      'par' => NULL,
		      'prev' => '3',
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.78905200',
		    ),
		    8 => 
		    array (
		      'row_id' => 13,
		      'text' => '',
		      'par' => 11,
		      'prev' => NULL,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:300.29728000',
		      'del' => '2012-04-18 05:34:300.29728000',
		    ),
		    9 => 
		    array (
		      'row_id' => 12,
		      'text' => '',
		      'par' => NULL,
		      'prev' => 11,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:270.35453600',
		      'del' => '2012-04-18 05:34:270.35453600',
		    ),
		    10 => 
		    array (
		      'row_id' => '4',
		      'text' => 'amazing',
		      'par' => NULL,
		      'prev' => 11,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:290.69889600',
		    ),
		    11 => 
		    array (
		      'row_id' => '5',
		      'text' => 'five',
		      'par' => '4',
		      'prev' => NULL,
		      'lmb' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'lm' => '2009-06-18 02:09:310.20566600',
		    ),
		    12 => 
		    array (
		      'row_id' => '6',
		      'text' => 'six',
		      'par' => '4',
		      'prev' => '5',
		      'lmb' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'lm' => '2009-06-18 02:09:310.20566600',
		    ),
		  ),
		  'users' => 
		  array (
		    0 => 
		    array (
		      'user_id' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'stamp' => '2009-06-18 02:09:330.87787700',
		      'row_id' => '3',
		    ),
		    1 => 
		    array (
		      'user_id' => '0b957ab1ede427c9c2608e1e6d19b683',
		      'stamp' => '2009-06-18 05:00:000.53217300',
		      'row_id' => '3',
		    ),
		    2 => 
		    array (
		      'user_id' => '9813a8c6bd63eeb33b3573573636de6d',
		      'stamp' => '2012-04-18 05:34:300.39666000',
		      'row_id' => 11,
		    ),
		    3 => 
		    array (
		      'user_id' => '11def8df83d98b5642dc664cce4bfaa0',
		      'stamp' => '2012-04-18 06:15:520.57614200',
		      'row_id' => '3',
		    ),
		  ),
		));
		
		$this->assertTrue($listotron->isValidHuh(), "list should be valid");

		
		$json = '[{ "delete_row" : true,
				"dt" : "2012-04-18 06:15:570.33323900",
				"row_id" : "3",
				"user_id" : "11def8df83d98b5642dc664cce4bfaa0" }]';
		$data = json_decode($json);
		
		
		$row_id_to_delete = 3;
		
		//
		// make sure input json is parsed correctly
		$this->assertEqual($data[0]->row_id, $row_id_to_delete);
		$this->assertEqual($data[0]->user_id, "11def8df83d98b5642dc664cce4bfaa0");

		// ignore deleted rows
		$this->assertEqual(count($listotron->getAllRows()), 9);


		//
		// validate some of the logic
		// that's inside the delete function
		//
		// we'll reproduce pieces here, as well as test
		// the actual delete function

		$row = $listotron->getRow($row_id_to_delete);
		$kids = $listotron->getAllKids($row);
		$lastkid = $listotron->getLastKid($row);
		
		$this->assertNotNull($row);
		$this->assertEqual($row["row_id"], 3);
		$this->assertTrue(is_array($kids));
		$this->assertEqual(count($kids), 2);
		$this->assertTrue($row["prev"] != null);
		
		//
		// ok, now delete the actual row
		// and validate the structure afterwards
				
		$out = $listotron->delete($data[0]->row_id, $data[0]->user_id);
		
		$this->assertTrue($listotron->isValidHuh(), "list should be valid");
		
		foreach($kids as $kid){
			$kid = $listotron->getRow($kid["row_id"]);
			$this->assertEqual($kid["par"], $row["prev"]);
		}
		
		
		$row2 = $listotron->getRow(2);
		$row8 = $listotron->getRow(8);
		$this->assertTrue($row2["par"] != $row8["par"] || $row2["prev"] != $row8["prev"]);
		$this->assertEqual($row2["par"], $row8["par"]);
		$this->assertEqual($row2["row_id"], $row8["prev"]);
	}


	/**
	 * this test is when a node with children, having
	 * a previous sibling without children, is deleted
	 *
	 * the sibling should inherit all the kids
	 *
	 *
	 * BEFORE
	 * root
	 *   - node1
	 *   - node2      <= delete this one
	 *      - kid1
	 *      - kid2
	 *
	 *
	 * AFTER
	 * root
	 *   - node1
	 *      - kid1
	 *      - kid2
	 */
	public function test_delete_node_with_prev_sibling(){
		$listotron = new Listotron(array (
		  'rows' => 
		  array (
		    0 => 
		    array (
		      'row_id' => '1',
		      'text' => 'one',
		      'par' => NULL,
		      'prev' => NULL,
		    ),
		    1 => 
		    array (
		      'row_id' => '2',
		      'text' => 'two',
		      'par' => '1',
		      'prev' => NULL,
		      'del' => '2012-04-18 06:15:410.78905200',
		    ),
		    2 => 
		    array (
		      'row_id' => '3',
		      'text' => 'three',
		      'par' => NULL,
		      'prev' => '1',
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:190.15526800',
		    ),
		    3 => 
		    array (
		      'row_id' => 7,
		      'text' => '',
		      'par' => NULL,
		      'prev' => '3',
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.80870000',
		      'del' => '2012-04-18 06:15:410.78905200',
		    ),
		    4 => 
		    array (
		      'row_id' => 8,
		      'text' => 'what',
		      'par' => '3',
		      'prev' => NULL,
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.78905200',
		    ),
		    5 => 
		    array (
		      'row_id' => 9,
		      'text' => '',
		      'par' => '3',
		      'prev' => 8,
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:500.55838600',
		      'del' => '2012-04-18 06:15:500.53828000',
		    ),
		    6 => 
		    array (
		      'row_id' => 10,
		      'text' => 'the',
		      'par' => '3',
		      'prev' => 8,
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:500.53828000',
		    ),
		    7 => 
		    array (
		      'row_id' => 11,
		      'text' => 'deal',
		      'par' => NULL,
		      'prev' => '3',
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.78905200',
		    ),
		    8 => 
		    array (
		      'row_id' => 13,
		      'text' => '',
		      'par' => 11,
		      'prev' => NULL,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:300.29728000',
		      'del' => '2012-04-18 05:34:300.29728000',
		    ),
		    9 => 
		    array (
		      'row_id' => 12,
		      'text' => '',
		      'par' => NULL,
		      'prev' => 11,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:270.35453600',
		      'del' => '2012-04-18 05:34:270.35453600',
		    ),
		    10 => 
		    array (
		      'row_id' => '4',
		      'text' => 'amazing',
		      'par' => NULL,
		      'prev' => 11,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:290.69889600',
		    ),
		    11 => 
		    array (
		      'row_id' => '5',
		      'text' => 'five',
		      'par' => '4',
		      'prev' => NULL,
		      'lmb' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'lm' => '2009-06-18 02:09:310.20566600',
		    ),
		    12 => 
		    array (
		      'row_id' => '6',
		      'text' => 'six',
		      'par' => '4',
		      'prev' => '5',
		      'lmb' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'lm' => '2009-06-18 02:09:310.20566600',
		    ),
		  ),
		  'users' => 
		  array (
		    0 => 
		    array (
		      'user_id' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'stamp' => '2009-06-18 02:09:330.87787700',
		      'row_id' => '3',
		    ),
		    1 => 
		    array (
		      'user_id' => '0b957ab1ede427c9c2608e1e6d19b683',
		      'stamp' => '2009-06-18 05:00:000.53217300',
		      'row_id' => '3',
		    ),
		    2 => 
		    array (
		      'user_id' => '9813a8c6bd63eeb33b3573573636de6d',
		      'stamp' => '2012-04-18 05:34:300.39666000',
		      'row_id' => 11,
		    ),
		    3 => 
		    array (
		      'user_id' => '11def8df83d98b5642dc664cce4bfaa0',
		      'stamp' => '2012-04-18 06:15:520.57614200',
		      'row_id' => '3',
		    ),
		  ),
		));
		
		$this->assertTrue($listotron->isValidHuh(), "list should be valid");

		
		$json = '[{ "delete_row" : true,
				"dt" : "2012-04-18 06:15:570.33323900",
				"row_id" : "3",
				"user_id" : "11def8df83d98b5642dc664cce4bfaa0" }]';
		$data = json_decode($json);
		
		
		$row_id_to_delete = 3;
		
		//
		// make sure input json is parsed correctly
		$this->assertEqual($data[0]->row_id, $row_id_to_delete);
		$this->assertEqual($data[0]->user_id, "11def8df83d98b5642dc664cce4bfaa0");

		// ignore deleted rows
		$this->assertEqual(count($listotron->getAllRows()), 8);


		//
		// validate some of the logic
		// that's inside the delete function
		//
		// we'll reproduce pieces here, as well as test
		// the actual delete function

		$row = $listotron->getRow($row_id_to_delete);
		$kids = $listotron->getAllKids($row);
		$lastkid = $listotron->getLastKid($row);
		
		$this->assertNotNull($row);
		$this->assertEqual($row["row_id"], 3);
		$this->assertTrue(is_array($kids));
		$this->assertEqual(count($kids), 2);
		$this->assertTrue($row["prev"] != null);
		
		//
		// ok, now delete the actual row
		// and validate the structure afterwards
		$out = $listotron->delete($data[0]->row_id, $data[0]->user_id);
		
		$txt_after = $listotron->getHumanReadable();
		
		$this->assertTrue($listotron->isValidHuh(), "list should be valid");
		
		foreach($kids as $kid){
			$kid = $listotron->getRow($kid["row_id"]);
			$this->assertEqual($kid["par"], $row["prev"]);
		}
		
		
		$row8 = $listotron->getRow(8);
		$this->assertNull($row8["prev"]);
	}



	/**
	 * this test is when a node with children, having
	 * a no siblings, is deleted
	 *
	 * all of the kids should be inherited by the parent
	 *
	 * BEFORE
	 *
	 * root
	 *   - node       <= delete this guy
	 *      - kid1
	 *      - kid2
	 *      - kid3
	 *
	 *
	 * AFTER
	 * root
	 *   - kid1
	 *   - kid2
	 *   - kid3
	 */
	public function test_delete_node_with_no_siblings_with_kids(){
		$listotron = new Listotron(array (
		  'rows' => 
		  array (
		    0 => 
		    array (
		      'row_id' => '1',
		      'text' => 'one',
		      'par' => NULL,
		      'prev' => NULL,
		    ),
		    1 => 
		    array (
		      'row_id' => '2',
		      'text' => 'two',
		      'par' => '1',
		      'prev' => NULL,
		    ),
		    2 => 
		    array (
		      'row_id' => '3',
		      'text' => 'three',
		      'par' => '2',
		      'prev' => NULL,
		      'lmb' => '9813a8c6bd63eeb33b3573573636de6d',
		      'lm' => '2012-04-18 05:34:190.15526800',
		    ),
		    3 => 
		    array (
		      'row_id' => 7,
		      'text' => '',
		      'par' => '2',
		      'prev' => '3',
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.80870000',
		    ),
		    4 => 
		    array (
		      'row_id' => 8,
		      'text' => 'what',
		      'par' => '2',
		      'prev' => '7',
		      'lmb' => '11def8df83d98b5642dc664cce4bfaa0',
		      'lm' => '2012-04-18 06:15:410.78905200',
		    )
		  ),
		  'users' => 
		  array (
		    0 => 
		    array (
		      'user_id' => 'ae8b5fae8c4313e4ff6a7407caa8b34e',
		      'stamp' => '2009-06-18 02:09:330.87787700',
		      'row_id' => '3',
		    ),
		    1 => 
		    array (
		      'user_id' => '0b957ab1ede427c9c2608e1e6d19b683',
		      'stamp' => '2009-06-18 05:00:000.53217300',
		      'row_id' => '3',
		    ),
		    2 => 
		    array (
		      'user_id' => '9813a8c6bd63eeb33b3573573636de6d',
		      'stamp' => '2012-04-18 05:34:300.39666000',
		      'row_id' => 11,
		    ),
		    3 => 
		    array (
		      'user_id' => '11def8df83d98b5642dc664cce4bfaa0',
		      'stamp' => '2012-04-18 06:15:520.57614200',
		      'row_id' => '3',
		    ),
		  ),
		));
		
		$this->assertTrue($listotron->isValidHuh(), "list should be valid");

		
		$json = '[{ "delete_row" : true,
				"dt" : "2012-04-18 06:15:570.33323900",
				"row_id" : "2",
				"user_id" : "11def8df83d98b5642dc664cce4bfaa0" }]';
		$data = json_decode($json);
		
		
		$row_id_to_delete = 2;
		
		//
		// make sure input json is parsed correctly
		$this->assertEqual($data[0]->row_id, $row_id_to_delete);
		$this->assertEqual($data[0]->user_id, "11def8df83d98b5642dc664cce4bfaa0");

		// ignore deleted rows
		$this->assertEqual(count($listotron->getAllRows()), 5);


		//
		// validate some of the logic
		// that's inside the delete function
		//
		// we'll reproduce pieces here, as well as test
		// the actual delete function

		$row = $listotron->getRow($row_id_to_delete);
		$kids = $listotron->getAllKids($row);
		$lastkid = $listotron->getLastKid($row);
		
		$this->assertNotNull($row);
		$this->assertEqual($row["row_id"], $row_id_to_delete);
		$this->assertTrue(is_array($kids));
		$this->assertEqual(count($kids), 3);
		$this->assertNull($row["prev"]);
		
		//
		// ok, now delete the actual row
		// and validate the structure afterwards
		$out = $listotron->delete($data[0]->row_id, $data[0]->user_id);
		
		$this->assertTrue($listotron->isValidHuh(), "list should be valid");
		
		foreach($kids as $kid){
			$kid = $listotron->getRow($kid["row_id"]);
			$this->assertEqual($kid["par"], $row["par"]);
		}
		
		
		$row7 = $listotron->getRow(7);
		$this->assertNotNull($row7["par"]);
	}


	public function test_somehow_invalid(){
		//
		// look at the somehow.invalid.data file
		//
		// when it loads JS gets stuck on a node
		$this->assertTrue(false);
	}

};


?>