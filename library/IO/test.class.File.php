<?
Class TestFile extends UnitTestCase{ 

   public function test_file() {
	$file = new File("/my/location/for/the/file.html");
	$this->assertEqual("/my/location/for/the/file.html", $file->getLocation(), "the default location is \"/my/location/for/the/file.html\"");
	$file->setLocation("/location/two.css");
	$this->assertEqual("/location/two.css", $file->getLocation(), "the new location is \"/location/two.css\"");
   }
};

?>