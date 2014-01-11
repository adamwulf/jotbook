<?
Class TestDateTime extends UnitTestCase{

	public function test_DateTime_obj(){
		$d = new ADateTime("2004-12-23 12:23:14");

		$this->assertEqual($d->year(), 2004, "info is correct");
		$this->assertEqual($d->month(), 12, "info is correct");
		$this->assertEqual($d->day(), 23, "info is correct");
		$this->assertEqual($d->hour(), 12, "info is correct");
		$this->assertEqual($d->minute(), 23, "info is correct");
		$this->assertEqual($d->second(), 14, "info is correct");

		$d->month(32);

		$this->assertEqual($d->month(), 32, "info is correct");
	}


	public function testTimestamp(){
		$d = new ADateTime("2004-08-04 20:06:14");

		$stamp = $d->getTimeStamp();
		
		$d2 = new ADateTime(date("Y-m-d H:i:s", $stamp));

		$d3 = new ADateTime(gmdate("Y-m-d H:i:s", $stamp));
		
		$this->assertEqual($d->toString(), $d2->toString(), "dates are the same");
		$this->assertEqual($d->toString(), $d3->toString(), "server timezone is correct");
	}

	public function testToGMT(){
		$d = new ADateTime("2004-08-04 20:06:14");

		$d->toGMT(-6);

		$this->assertEqual($d->year(), 2004, "info is correct");
		$this->assertEqual($d->month(), 8, "info is correct");
		$this->assertEqual($d->day(), 5, "info is correct");
		// should be 2 not 1!
		$this->assertEqual($d->hour(), 2, "info is correct");
		$this->assertEqual($d->minute(), 6, "info is correct");
		$this->assertEqual($d->second(), 14, "info is correct");

	}

	public function testToGMTToTimezone(){
		$d = new ADateTime("2004-08-04 20:06:14");
		$d->toGMT(-6);
		$d = new ADateTime($d->toString());
		$d->toTimezone(-6);

		$this->assertEqual($d->year(), 2004, "info is correct");
		$this->assertEqual($d->month(), 8, "info is correct");
		$this->assertEqual($d->day(), 4, "info is correct");
		$this->assertEqual($d->hour(), 20, "info is correct");
		$this->assertEqual($d->minute(), 6, "info is correct");
		$this->assertEqual($d->second(), 14, "info is correct");

	}

	public function testToHoustonTimezone(){
		// should be 01 not 02!!
		$d = new ADateTime("2004-08-05 02:06:14");
		$d->toTimezone(-6);

		$this->assertEqual($d->year(), 2004, "info is correct");
		$this->assertEqual($d->month(), 8, "info is correct");
		$this->assertEqual($d->day(), 4, "info is correct");
		$this->assertEqual($d->hour(), 20, "info is correct");
		$this->assertEqual($d->minute(), 6, "info is correct");
		$this->assertEqual($d->second(), 14, "info is correct");

	}

	public function testToBerlinTimezone(){
		// should be 01 not 02!!
		$d = new ADateTime("2004-08-05 02:06:14");
		$d->toTimezone(1);

		$this->assertEqual($d->year(), 2004, "info is correct");
		$this->assertEqual($d->month(), 8, "info is correct");
		$this->assertEqual($d->day(), 5, "info is correct");
		$this->assertEqual($d->hour(), 3, "info is correct");
		$this->assertEqual($d->minute(), 6, "info is correct");
		$this->assertEqual($d->second(), 14, "info is correct");
	}
};


?>