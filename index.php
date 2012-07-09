<?
// seed with microseconds
function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (float) $sec + ((float) $usec * 100000);
}
srand(make_seed());
$randval = rand(0, 10000) / 10000;
?>
<html>
	<head>
		<script>
		if(typeof(console) == "undefined") console = {};
		if(typeof(console.log) == "undefined") console.log = function(){}
		</script>
<!--		<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script> -->
		<script src="/includes/jquery.js" type="text/javascript"></script>
		<script src="/includes/bajax.js" type="text/javascript"></script>
		<script src="/model.js" type="text/javascript"></script>
		<script src="/util.js" type="text/javascript"></script>
		<script src="/view.js" type="text/javascript"></script>
		<script src="/controller.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(function(){
				var randomSeed = <?= $randval ?>;
				var model = new $.Model(randomSeed);
				var view = new $.View($("#interface"));
				var controller = new $.Controller(model, view);
			});
		</script>
		<style>
		li span{
			display: block;
			padding:3px;
			height: 14px;
			font-size: 10pt;
		}
		li input{
			width: 90%;
		}
		li{
			background: url(bullet.png) no-repeat 1px 6px;
			padding-left: 10px;
		}
		ul{
			list-style: none;
		}
		</style>
	</head>
<body>
<div id="interface">
</div>
<input type='button' value='stop' id='stopbutton'>
<input type='button' value='refresh' id='refreshbutton'>
</body>
</html>