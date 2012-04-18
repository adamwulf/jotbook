<html>
	<head>
		<script src="jquery.js" type="text/javascript"></script>
		<script src="bajax.js" type="text/javascript"></script>
		<script src="model.js" type="text/javascript"></script>
		<script src="util.js" type="text/javascript"></script>
		<script src="view.js" type="text/javascript"></script>
		<script src="controller.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(function(){
				// css junk
				$("table#primary th:not(:first)").css('width', '100px');
                $("#row_2 td:first, #row_3 td:first, #row_4 td:first, #row_5 td:first, #row_6 td:first").css('padding-left', '30px');
                
                
                
                // app init.
				var model = new $.Model();
				var view = new $.View($("#primary"));
				var controller = new $.Controller(model, view);
			});
		</script>
		<style>
		li span{
			display: block;
			padding:3px;
		}
		
        table { width: 100%; border-collapse: collapse; }
        table td { border: 1px solid black; }
		table th { text-align: left;}

		</style>
	</head>
<body>
<div id="interface">
	
<table id="primary">
	<tr>
	    <th>column 1</th>
	    <th>column 2</th>
	    <th>column 3</th>
	    <th>column 4</th>
	</tr>
	<tr id="row_1">
	    <td id="cell_1"><span>one</span></td>
	    <td id="cell_2"><span>1-2</span></td>
	    <td id="cell_3"><span>1-3</span></td>
	    <td id="cell_4"><span>1-4</span></td>
	</tr>
	<tr id="row_2">
	    <td><span>two</span></td>
	    <td><span>2-2</span></td>
	    <td><span>2-3</span></td>
	    <td><span>2-4</span></td>
	</tr>
	<tr id="row_3">
	    <td><span>three</span></td>
	    <td><span>3-2</span></td>
	    <td><span>3-3</span></td>
	    <td><span>3-4</span></td>
	</tr>
	<tr id="row_4">
	    <td><span>four</span></td>
	    <td><span>3-2</span></td>
	    <td><span>3-3</span></td>
	    <td><span>3-4</span></td>
	</tr>
	<tr id="row_5">
	    <td><span>five</span></td>
	    <td><span>3-2</span></td>
	    <td><span>3-3</span></td>
	    <td><span>3-4</span></td>
	</tr>
	<tr id="row_6">
	    <td><span>six</span></td>
	    <td><span>3-2</span></td>
	    <td><span>3-3</span></td>
	    <td><span>3-4</span></td>
	</tr>

</table>

</div>
</body>
</html>