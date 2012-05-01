<?

include "../include.php";

require_once('unit_tester.php');
require_once('reporter.php');

$test = new TestSuite('All file tests');


$c = new ClassLoader();
$c->addToClasspath(dirname(__FILE__) . "/../simpletest/");
$c->addToClasspath(dirname(__FILE__) . "/../includes/tests/");
$c->loadTestFiles($test);

if (TextReporter::inCli()) {
	exit($test->run(new TextReporter()) ? 0 : 1);
}
// $test->run(new VerboseReporter());
$test->run(new HtmlReporter());




?>
