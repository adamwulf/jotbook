
jQuery.extend({

	Controller: function(model, view, loop){
	
		var loop = (typeof(loop) == "undefined") ? true : loop;
		var stop = false;
		var that = this;
		window.throttle = 100;
	
		model.setController(this);
		view.setController(this);
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
				$("#queue").text("Requests in Queue: " + (qcount));
				if(q_len == 0 && !stop && loop){
//					model.refreshAll();
					setTimeout(function(){ model.refreshAll(); }, window.throttle);
				}
			}
		};

        $('#refreshbutton').click(function(){
			model.refreshAll();
        });


		this.getUserId = function(){
			return model.getUserId();
		}
		
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
				model.indentRow(row);
			},
			outdentRow : function(row) {
				model.outdentRow(row);
			},
			stopClicked : function(){
				that.stopBajax();
			},
			addRowAfter : function(row){
				model.insertRowAfter(row);
			},
			addRowBefore : function(row){
				model.insertRowBefore(row);
			},
			locationChanged : function(row){
				model.locationChanged(row);
			},
			deleteRow : function(row){
				model.deleteRow(row);
			}
		});
		view.addListener(vlist);

		/**
		 * listen to the model
		 */
		var mlist = $.ModelListener({
			loadBegin : function() {
//				console.log("begin loading");
			},
			loadFinish : function() {
//				console.log("load finished");
			},
			loadRow : function(row) {
				view.loadRow(row);
			},
			loadFail : function() {
//				console.log("load failed");
			},
			savingRow : function() { },
			savingFailed : function(row) {
				row.revert();
				view.loadRow(row);
			},
			savingFinished : function() { },
			loadUserPosition : function(user_id, row_id){
				view.loadUserPosition(user_id, row_id);
			},
			insertBefore : function(newRow, row){
				view.insertRowBefore(newRow, row);
			}
		});
		model.addListener(mlist);

		model.login();		
		model.getAll();
	}
	
});
