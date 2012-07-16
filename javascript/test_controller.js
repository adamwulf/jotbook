jQuery.extend({

	Controller: function(model, view){
	
		var stop = false;
		var that = this;
	
		model.setController(this);
		/**
		 * set up default options for
		 * the bundled AJAX calls
		 */
		var bAjaxOptions = {
			url : "ajax.php",
			type : "POST",
			dataType : "json",
			success : function(data, q_len){
				qcount = q_len;
//				if(q_len == 0 && !stop){
//					model.refreshAll();
//				}
			}
		};
		
		this.stopBajax = function(){
			stop = true;
		}
		
		this.getBAjaxOptions = function(){
			return bAjaxOptions;
		}

		/**
		 * listen to the view
		 */
		var vlist = $.ViewListener({
			indentRow : function(row) {
				var next = row.getNext();
				if(next && next.getPreviousId() == row.getRowId()){
					next.setPreviousId(row.getPreviousId());
					next.confirm();
				}
				var newPrev = model.getRow(row.getPreviousId()).getLastKid();
				row.setParentId(row.getPreviousId());
				if(newPrev){
					row.setPreviousId(newPrev.getRowId()); // not correct, but good for testing
				}else{
					row.setPreviousId(null); // not correct, but good for testing
				}
				view.loadRow(row);
				row.confirm();
			},
			outdentRow : function(row) {
				// if we have the same parent, then make the next guy
				// have me as a parent
				var last = row.getLastKid();
				next = row;
				while(next = next.getNext()){
					if(next.getParentId() == row.getParentId()){
						next.setPreviousId(last ? last.getRowId() : last);
						next.setParentId(row.getRowId());
						view.loadRow(next);
						next.confirm();
						last = next;
					}
				}
				var next = row.getNext();
				if(next && next.getPreviousId() == row.getParentId()){
					next.setPreviousId(row.getRowId());
					next.confirm();
				}
				// only indent if there's a parent to attach to
				row.setPreviousId(row.getParentId()); // not correct, but good for testing
				row.setParentId(row.getParent().getParentId());
				view.loadRow(row);
				row.confirm();
			},
			stopClicked : function(){
				that.stopBajax();
			}
		});
		view.addListener(vlist);

		/**
		 * listen to the model
		 */
		var mlist = $.ModelListener({
			loadBegin : function() {
				console.log("begin loading");
			},
			loadFinish : function() {
				console.log("load finished");
			},
			loadRow : function(row) {
				console.log("updating row" + row.getRowId());
				view.loadRow(row);
			},
			loadFail : function() {
				console.log("load failed");
			},
			savingRow : function() { },
			savingFailed : function(row) {
				row.revert();
				view.loadRow(row);
			},
			savingFinished : function() { }
		});
		model.addListener(mlist);

		model.login();		
		model.getAll();
	}
	
});
