jQuery.extend({

	TableRow : function(row, view){
		/* Row Object - Setup */
		var that = this;
		var $dom = $("<tr id='row_" + row.getRowId() + "'><td><input/><span></span></td></tr>");
		$dom.find("input:first").hide();
		var editing = false;
		
		this.setEditMode = function(b){
			editing = b;
			if(b){
				$dom.find("span:first").hide();
				$dom.find("input:first").show().val(row.getText());
				that.focus();
			}else{
				$dom.find("input:first").hide();
				$dom.find("span:first").show();
			}
		}
		
		this.isEditing = function(){
			return editing;
		}
		
		this.focus = function(){
			$dom.find("input:first").focus().select();
		}
		
		this.saveChanges = function(){
			if($dom.find("input:first").val() != row.getText()){
				row.setText($dom.find("input:first").val());
				row.confirm();
				that.refresh();
			}
		}
		
		$dom.find("span:first").click(function(){
			if(!editing){
				view.selectRow(that);
			}
		});

		/* All keypress events in here! */
		$dom.find("input:first").keypress(function(e){
			if(editing){
				if(e.keyCode == 27){ // escape
					view.unselectAll();
					return false;
				}else if(e.keyCode == 13 && e.ctrlKey){ // ctrl + enter
					view.addRowAfter(that);
				}else if(e.keyCode == 13 && !e.shiftKey){ // enter
					row.setText($dom.find("input:first").val());
					row.confirm();
					that.refresh();
					view.selectNextRow(that);
				}else if(e.keyCode == 13 && e.shiftKey){ // enter
					row.setText($dom.find("input:first").val());
					row.confirm();
					that.refresh();
					view.selectPreviousRow(that);
				}else if(e.keyCode == 40){ // down
					view.selectNextRow(that);
				}else if(e.keyCode == 38){ // up
					view.selectPreviousRow(that);
				}else if(e.keyCode == 9 && !e.shiftKey){ // tab
					view.indent(that);
					return false;
				}else if(e.keyCode == 9 && e.shiftKey){ // tab
					view.outdent(that);
					return false;
				}else{
//					console.log(e.keyCode);
				}
			}
		});
		
		this.getRowId = function(){
			return row.getRowId();
		}
				
		this.getRow = function(){
			return row;
		}
		
		this.refresh = function(){
			$dom.find("span:first").text(row.getText());
		}
		
		this.updateKid = function(table_row){
			if(table_row.getRow().getPreviousId()){
				table_row.getDOM().insertAfter($dom.find("table:first #row_" + table_row.getRow().getPreviousId()));
			}else{
				table_row.getDOM().prependTo($dom.find("table:first"));
			}
		}
		
		this.getDOM = function(){
			return $dom;
		}
		
		this.refresh();
	},
	
	Listotron : function(){
		var $dom = $("<div><span>The List</span><table></table></div>");
		
		this.updateKid = function(table_row){
			if(table_row.getRow().getPreviousId()){
				table_row.getDOM().insertAfter($dom.find("table:first #row_" + table_row.getRow().getPreviousId()));
			}else{
				table_row.getDOM().prependTo($dom.find("table:first"));
			}
		}
		
		this.getDOM = function(){
			return $dom;
		}
	},

	View: function(){
	
		// this will hold the dom nodes that
		// we use to display notes in the
		// list
		var rows = new $.HashTable();
		
		// keep a reference to ourselves
		var that = this;
		
		// a list of who is listening to us
		var listeners = new Array();
	
		// get the interface
		$interface = $("#interface");
		
		// the base list
		var list = new $.Listotron();
		$interface.append(list.getDOM());
		
		/* - */
		
		var selected = null;
		
		this.unselectAll = function(){
			if(selected) selected.setEditMode(false);
		}
		
		this.selectRow = function(table_row){
			that.unselectAll();
			table_row.setEditMode(true);
			selected = table_row;
		}
		
		this.selectPreviousRow = function(table_row){
			var prev = table_row.getRow().getPrevious();
			if(prev){
				var prevli = rows.get(prev.getRowId());
				table_row.saveChanges();
				that.selectRow(prevli);
			}
		}
		
		this.selectNextRow = function(table_row){
			var next = table_row.getRow().getNext();
			if(next){
				var nextli = rows.get(next.getRowId());
				table_row.saveChanges();
				that.selectRow(nextli);
			}
		}
		
		this.indent = function(table_row){
			if(table_row.getRow().getPreviousId()){
				that.notifyIndentRow(table_row.getRow());
			}
		}
				
		this.outdent = function(table_row){
			if(table_row.getRow().getParentId()){
				that.notifyOutdentRow(table_row.getRow());
			}
		}
		
		this.addRowAfter = function(table_row){
			that.notifyAddRowAfter(table_row.getRow());
		}
		
		this.getTableRow = function(i){
			return rows.get(i);
		}
				
		/**************************************
		 *  The view is now set up,          *
		 *  so let's flesh out functionality *
		 *************************************/
	
		/**
		 * a note was loaded/updated from the
		 * cache, so let's build / update
		 * the DOM
		 */
		this.loadRow = function(model_row){
			var table_row = rows.get(model_row.getRowId());
			if(table_row){
				table_row.refresh();
			}else{
				// build a new note item
				// and put it in the list
				table_row = new $.TableRow(model_row, that);
				rows.put(table_row.getRowId(), table_row);
			}
			if(table_row.getRow().getParentId()){
				var par = rows.get(table_row.getRow().getParentId());
				par.updateKid(table_row);
			}else{
				list.updateKid(table_row);
			}
			if(table_row.isEditing()) table_row.focus();
		}
		
		/**
		 * add a listener to this view
		 */
		this.addListener = function(list){
			listeners.push(list);
		}
		
		
		/**
		 * notify that we're trying to add a new note
		 */
		this.notifyIndentRow = function(row){
			$.each(listeners, function(i){
				listeners[i].indentRow(row);
			});
		}
		
		/**
		 * notify that we're trying to delete a note
		 */
		this.notifyOutdentRow = function(row){
			$.each(listeners, function(i){
				listeners[i].outdentRow(row);
			});
		}


		/**
		 * notify that we're trying to delete a note
		 */
		this.notifyStopClicked = function(){
			$.each(listeners, function(i){
				listeners[i].stopClicked();
			});
		}
		
		/**
		 * notify that we want to add a row after
		 * the input row
		 */
		this.notifyAddRowAfter = function(row){
			$.each(listeners, function(i){
				listeners[i].addRowAfter(row);
			});
		}

        $('#stopbutton').click(function(){
        	that.notifyStopClicked();
        });

		
	},
	
	/**
	 * let people create listeners easily
	 */
	ViewListener: function(list) {
		if(!list) list = {};
		return $.extend({
			indentRow : function() { },
			outdentRow : function() { },
			addRowAfter : function() { },
			stopClicked : function() { }
		}, list);
	}
	
});
