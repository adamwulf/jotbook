jQuery.extend({

	RowLI : function(row, view){
		var that = this;
		var $dom = $("<li id='row_" + row.getRowId() + "'><input style='display:none;'/><span></span><ul></ul></li>");
		var editing = false;
		var needsFocus = true;
		
		this.setEditMode = function(b){
			if(b){
				$dom.find("span:first").hide();
				$dom.find("input:first").show().val(row.getText());
				that.focus();
			}else{
				$dom.find("input:first").hide();
				$dom.find("span:first").show();
				if(that.isEditing()) that.saveChanges();
			}
			editing = b;
		}
		
		this.isEditing = function(){
			return editing;
		}
		
		this.focus = function(){
			if(needsFocus) $dom.find("input:first").focus().select();
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
		

		$dom.find("input:first").keydown(function(e){
			if(editing){
				needsFocus = true;
				if($dom.find("input:first").val() == "" && e.keyCode == 8){ // delete
					view.deleteRow(that);
					return false;
				}else if(e.keyCode == 27){ // escape
					view.unselectAll();
					return false;
				}else if(e.keyCode == 13 && !e.shiftKey){ // enter
					row.setText($dom.find("input:first").val());
					row.confirm();
					that.refresh();
					view.addRowAfter(that);
				}else if(e.keyCode == 13 && e.shiftKey){ // shift enter
					row.setText($dom.find("input:first").val());
					row.confirm();
					that.refresh();
					view.addRowBefore(that);
				}else if(e.keyCode == 40){ // down
					row.setText($dom.find("input:first").val());
					row.confirm();
					that.refresh();
					view.selectNextRow(that);
				}else if(e.keyCode == 38){ // up
					row.setText($dom.find("input:first").val());
					row.confirm();
					that.refresh();
					view.selectPreviousRow(that);
				}else if(e.keyCode == 9 && !e.shiftKey){ // tab
					row.setText($dom.find("input:first").val());
					row.confirm();
					view.indent(that);
					return false;
				}else if(e.keyCode == 9 && e.shiftKey){ // tab
					row.setText($dom.find("input:first").val());
					row.confirm();
					view.outdent(that);
					return false;
				}else{
					row.setText($dom.find("input:first").val());
					row.confirm();
					needsFocus = false;
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
		
		/**
		 * the parameter rowli must be one of my children
		 * otehrwise ??!
		 */
		this.updateKid = function(rowli){
			if(rowli.getRow().getPreviousId()){;
				/* Find parent, insert me after the sibling (under that parent) */
				rowli.getDOM().insertAfter($dom.find("ul:first #row_" + rowli.getRow().getPreviousId()));
			}else{
				/* Find parent, make me its first child */
				rowli.getDOM().prependTo($dom.find("ul:first"));
			}
			/* for tables */
			/*
				get all my kids
				updateKid(each kid)
			*/
		}
		
		this.addColor = function(c){
			$dom.find("span:first").css("border", "1px solid " + c);
		}
		
		this.removeColor = function(c){
			$dom.find("span:first").css("border", "0px");
		}
		
		this.getDOM = function(){
			return $dom;
		}
		
		this.refresh();
	},
	
	Listotron : function(){
		var $dom = $("<div><span>The List</span><ul></ul></div>");
		
		this.updateKid = function(rowli){
			if(rowli.getRow().getPreviousId()){
				rowli.getDOM().insertAfter($dom.find("ul:first #row_" + rowli.getRow().getPreviousId()));
			}else{
				rowli.getDOM().prependTo($dom.find("ul:first"));
			}
		}
		
		this.getDOM = function(){
			return $dom;
		}
	},

	View: function(){
	
		var control = null;
		
		
		if (document.getElementsByTagName){
			var e, i = 0
			while (e = document.getElementsByTagName ('*')[i++]) {
				$(e).focus(function () {window.activeElement = this});
			}
		}

		
		
		this.setController = function(c){
			control = c;
		}
	
		// this will hold the dom nodes that
		// we use to display notes in the
		// list
		var rows = new $.HashTable();
		
		var locations = new $.HashTable();

		this.getLocations = function(){
			return locations;
		}

		// keep a reference to ourselves
		var that = this;
		
		// a list of who is listening to us
		var listeners = new Array();
	
		// get the interface
		$interface = $("#interface");
		
		// the base list
		var list = new $.Listotron();
		$interface.append(list.getDOM());
		
		
		var user2color = new $.HashTable();
		
		var colors = new Array();
		colors.push("#FF0000");
		colors.push("#00FF00");
		colors.push("#0000FF");
		colors.push("#FFFF00");
		colors.push("#FF00FF");
		colors.push("#00FFFF");
		var nextColor = 0;
		function getColor(user_id){
			if(!user2color.get(user_id)){
				user2color.put(user_id, colors[nextColor]);
				nextColor = (nextColor+1) % colors.length;
			}
			return user2color.get(user_id);
		}
		
		/**/
		
		var selected = null;
		
		this.unselectAll = function(){
			if(selected) selected.setEditMode(false);
		}
		
		this.selectRow = function(rowli){
			that.unselectAll();
			rowli.setEditMode(true);
			selected = rowli;
			that.notifyLocationChanged(rowli.getRow());
		}
		
		this.selectPreviousRow = function(rowli){
			var prev = rowli.getRow().getPrevious();
			if(prev){
				var prevli = rows.get(prev.getRowId());
				rowli.saveChanges();
				that.selectRow(prevli);
				that.notifyLocationChanged(prev);
			}
		}
		
		this.selectNextRow = function(rowli){
			var next = rowli.getRow().getNext();
			if(next){
				var nextli = rows.get(next.getRowId());
				rowli.saveChanges();
				that.selectRow(nextli);
				that.notifyLocationChanged(next);
			}
		}
		
		this.indent = function(rowli){
			if(rowli.getRow().getPreviousId()){
				that.notifyIndentRow(rowli.getRow());
			}
		}
		
		this.outdent = function(rowli){
			if(rowli.getRow().getParentId()){
				that.notifyOutdentRow(rowli.getRow());
			}
		}
		
		this.deleteRow = function(rowli){
			if(!rowli.getRow().getParentId() &&
			   !rowli.getRow().getPreviousId() &&
			   !rowli.getRow().getNext()){
			   console.log("last row");
			}else{
				that.notifyDeleteRow(rowli.getRow());
			}
		}
		
		this.addRowAfter = function(rowli){
			that.notifyAddRowAfter(rowli.getRow());
		}
		
		this.addRowBefore = function(rowli){
			that.notifyAddRowBefore(rowli.getRow());
		}
		
		this.getRowLI = function(i){
			return rows.get(i);
		}
				
		this.loadUserPosition = function(user_id, row_id){
			var old = locations.get(user_id);
			if(old){
				if(rows.get(old)){
					rows.get(old).removeColor(getColor(user_id));
				}
			}
			if(rows.get(row_id)) rows.get(row_id).addColor(getColor(user_id));
			locations.put(user_id, row_id);
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
			var selectIt = false;
			var rowli = rows.get(model_row.getRowId());
			if(rowli){
				rowli.refresh();
			}else{
				// build a new note item
				// and put it in the list
				rowli = new $.RowLI(model_row, that);
				rows.put(rowli.getRowId(), rowli);
				selectIt = model_row.lastModifiedBy() == control.getUserId();
			}
			
			if(model_row.isDeletedHuh()){
				// it's deleted, remove it
				if(rowli.isEditing()){
					var prev = rowli.getRow().getPrevious();
					that.selectPreviousRow(rowli);
				}
			}			
			
			if(rowli.getRow().getParentId()){
				var par = rows.get(rowli.getRow().getParentId());
				if(!par){
					alert("can't find parent of " + rowli.getRowId() + " id: " + rowli.getRow().getParentId());
				}
				par.updateKid(rowli);
			}else{
				list.updateKid(rowli);
			}
			
			if(model_row.isDeletedHuh()){
				// it's deleted, remove it
				rowli.getDOM().remove();
				rows.clear(rowli.getRowId());
			}else{
				if(rowli.isEditing()) rowli.focus();
				if(selectIt) that.selectRow(rowli);
			}
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
		
		/**
		 * notify that we want to add a row before
		 * the input row
		 */
		this.notifyAddRowBefore = function(row){
			$.each(listeners, function(i){
				listeners[i].addRowBefore(row);
			});
		}

		/**
		 * notify that the user's location changed
		 */
		this.notifyLocationChanged = function(row){
			$.each(listeners, function(i){
				listeners[i].locationChanged(row);
			});
		}

		/**
		 * notify that we're deleting a row
		 */
		this.notifyDeleteRow = function(row){
			$.each(listeners, function(i){
				listeners[i].deleteRow(row);
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
			addRowBefore : function() { },
			stopClicked : function() { },
			locationChanged : function() { },
			deleteRow : function() { }
		}, list);
	}
	
});
