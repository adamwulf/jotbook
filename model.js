jQuery.extend({
	
	
	Row: function(json, model){
	
		// properties
		var that = this;
		var data = json;
		var revert_actions = new Array();
		
		function createRevertAction(func, value){
			return function(){ func(value); }
		}
		
		// methods
		function getNextHelper(row){
			var rows = model.getAllCached();
			var ret = null;
			for(var idx in rows){
				if(!rows[idx].isDeletedHuh() && rows[idx].getPreviousId() == row.getRowId()){
					return rows[idx];
				}
			}
			if(row.getParentId() != null){
				return getNextHelper(row.getParent());
			}
			return null;
		}

		/**
		 * get's the next sibling if any
		 * otherwise recurs on the parent
		 *
		 * ****************************** *
		 *       NEEDS OPTIMIZATION       *
		 * ****************************** *
		 */
		this.getNext = function(){
			var rows = model.getAllCached();
			var ret = null;
			for(var idx in rows){
				if(!rows[idx].isDeletedHuh()){
					if(rows[idx].getParentId() == that.getRowId() &&
					   rows[idx].getPreviousId() == null){
						return rows[idx];
					}else if(rows[idx].getPreviousId() == that.getRowId()){
						ret = rows[idx];
					}
				}
			}
			if(!ret && (that.getParentId() != null)){
				return getNextHelper(that.getParent());
			}
			return ret;
		}
		
		this.hasKids = function(){
			var rows = model.getAllCached();
			var ret = null;
			for(var idx in rows){
				if(!rows[idx].isDeletedHuh()){
					if(rows[idx].getParentId() == that.getRowId()){
						return true;
					}
				}
			}
			return false;
		}
		
		this.getLastKid = function(){
			var ret = null;
			var temp = that;
			var prev = that;
			while(temp = temp.getNext()){
				if(temp.getParentId() == that.getRowId()) ret = temp;
				if(temp.getRowId() == prev.getRowId()) break;
				prev = temp;
			}
			return ret;
		}

		/**
		 * get's the previous sibling's last child, if any
		 * otherwise returns on the previous sibling, if any
		 * otherwise returns on the parent
		 *
		 * this returns the node visually
		 * before this node in teh list.
		 *
		 * A
		 *   C
		 *   D
		 * B
		 *   E
		 *
		 * B.getPrevious() is D
		 *
		 * B.getPreviousId() is A
		 */
		this.getPrevious = function(){
			if(that.getPreviousId()){
				// does the previous have any children?
				var ret = model.getRow(that.getPreviousId());
				while(ret.hasKids()){
					ret = ret.getLastKid();
				}
				return ret;
			}
			return that.getParent();
		}
		
		/**
		 * returns the generation number of the row
		 */
        this.getGeneration = function(){
            var gen = 0;
            var row = that;
            while(row.getParentId()){
                gen++;
                row = row.getParent();
            }
            return gen;
        }
        
        this.isDeletedHuh = function(){
        	return false || data.del;
        }

		this.getRowId = function(){
			return data.row_id;
		}
		
		this.setRowId = function(row_id){
			if(!data.row_id) data.row_id = row_id;
		}
		
		this.getText = function(){
			return data.text;
		}
		this.setText = function(text){
			if(text != data.text){
				revert_actions.push(createRevertAction(function(val){ data.text = val; }, data.text));
				data.text = text;
			}
		}
		
		this.getParent = function(){
			return model.getRow(that.getParentId());
		}
		
		this.getParentId = function(){
			return data.par;
		}
		this.setParentId = function(par){
			revert_actions.push(createRevertAction(function(val){ data.par = val; }, data.par));
			data.par = par;
		}
		
		this.getPreviousId = function(){
			return data.prev;
		}
		this.setPreviousId = function(prev){
			revert_actions.push(createRevertAction(function(val){ data.prev = val; }, data.prev));
			data.prev = prev;
		}
		
		this.lastModifiedBy = function(){
			return data.lmb;
		}
		
		// undo/redo functions
		this.update = function(newJson){
			data = $.extend(data, newJson);
		}

		this.confirm = function(){
			if(revert_actions.length) model.updateRow(that);
		}
		
		this.clearRevertActions = function(){
			revert_actions = new Array();
		}
		
		this.revert = function(){
			while(revert_actions.length > 0){
				var action = revert_actions.pop();
				action();
			}
		}

	},

	Model: function(control){
		// our local cache of $.Note objects
		var cache = new $.HashTable();
		var user_id = null;
		
		//
		// track last modified by user
		// and their datetime
		var lmb = {};
		this.getLMB = function(){
			return lmb;
		}
		
		//
		// track users' location
		var loc = [];
		this.getLocations = function(){
			return loc;
		}
		
		this.getUserId = function(){
			return user_id;
		}
		this.setUserId = function(uid){
			user_id = uid;
		}
		
		var control = null;
		this.setController = function(c){
			control = c;
		}
		
		// a reference to ourselves
		var that = this;
		
		// the datetime that we last
		// loaded everything
		var lastLoad = null;
		this.getLastLoad = function(){
			return lastLoad;
		}
		
		// a list of who is listening to us
		var listeners = new Array();
		

		function loadLocations(data){
			if(typeof(data.tracking) != "undefined"){
				$.each(data.tracking, function(item){
					var userLoc = data.tracking[item];
					if(userLoc.user_id != that.getUserId()){
						that.notifyLoadUserPosition(userLoc.user_id, userLoc.row_id);
					}
				});
			}
		}


		// load a json response from an ajax call
		function loadResponse(data){
			lastLoad = data.dt;
			loadLocations(data);
			var out = new Array();
			$.each(data.rows, function(item){
				var row = data.rows[item];
				var cachedRow = cache.get(row.row_id);
				if(row.lmb && row.lmb != that.getUserId()){
					// update lmb table
					lmb[row.lmb] = row.lm;
				}
				if(cachedRow){
//					if(row.lmb && row.lmb != that.getUserId()){
						// already cached, just update it
						cachedRow.clearRevertActions();
						cachedRow.update(row);
//					}
				}else{
					cachedRow = new $.Row(row, that);
					// not yet in cache, add it
					cache.put(row.row_id, cachedRow);
				}
				out.push(cachedRow);
				that.notifyRowLoaded(cachedRow);
			});
			if(!that.validate()){
				control.stopBajax();
				window.cache = cache;
			}
			return out;
		}

		this.canReachRootNode = function(row, count){
			if(count > 10) return false;
			if(row.getParentId() == null) return true;
			var par = that.getRow(row.getParentId());
			return that.canReachRootNode(par, count+1);
		}
		
		this.validate = function(){
			// make sure the tree is sound
			var valid = true;
//			for(var i=1;i<=6;i++){
//				valid = valid && that.canReachRootNode(that.getRow(i), 0);
//			}
			return valid;
		}
		
		this.getAllCached = function(){
			return cache.toArray();
		}
		
		
		/**
		 * log in the user to get a new unique user id
		 */
		this.login = function(dt){
			var data = { login : true };
			$.bAjax(control.getBAjaxOptions(), {
				data : data,
				type: 'GET',
				error: function(){
					console.log("can't login");
				},
				success: function(data){
					if(data.error){
						console.log("login fail :(");
					}
					that.setUserId(data.user_id);
				}
			});
		}
		


		/**
		 * load the entire list from the server
		 */
		this.getAll = function(dt){
//			console.log("getting all");
			that.notifyLoadBegin();
			var data = { load : true };
			if(dt) data = function(){
							return { load : true,
								dt : dt,
								user_id : that.getUserId(),
								lmb : that.getLMB() };
							};
			$.bAjax(control.getBAjaxOptions(), {
				data : data,
				type: 'GET',
				error: function(){
					that.notifyLoadFail();
				},
				success: function(data){
					if(data.error) return that.notifyLoadFail();
					that.notifyLoadFinish(loadResponse(data));
				}
			});
			return cache.toArray();
		}
		



		/**
		 * notify that the user's location changed
		 */
		this.locationChanged = function(row){
			that.notifyChangingLocation();
			var data = function(){
					return { location : true,
						dt : lastLoad,
						user_id : that.getUserId(),
						row_id : row.getRowId(),
						lmb : that.getLMB() };
					};
			$.bAjax(control.getBAjaxOptions(), {
				data : data,
				type: 'GET',
				error: function(){
					that.notifyChangingLocationFailed();
				},
				success: function(data){
					if(data.error) return that.notifyChangingLocationFailed();
					that.notifyChangingLocationFinished(loadResponse(data));
				}
			});
			return cache.toArray();
		}
		
		/**
		 * load the entire list from the server
		 */
		this.refreshAll = function(){
			return that.getAll(that.getLastLoad());
		}

		/**
		 * save a note
		 * @param a Row object
		 */
		this.updateRow = function(row){
			that.notifySavingRow(row);
			$.bAjax(control.getBAjaxOptions(), {
				data : { edit : true, 
						 dt : lastLoad,
						 row_id : row.getRowId(),
						 text : row.getText(),
						 user_id : that.getUserId() },
				type: 'POST',
				error: function(){
					that.notifySavingFailed(row);
				},
				success: function(data){
					if(data.error) return that.notifySavingFailed(row);
					loadResponse(data);
					that.notifySavingFinished(row);
				}
			});
		}

		/**
		 * insert a blank row before the input row object
		 * @param a Row object
		 */
		this.insertRowBefore = function(row){
			that.notifyInsertBefore(row);
			$.bAjax(control.getBAjaxOptions(), {
				data : { insert_before : true, 
						 dt : lastLoad,
						 row_id : row.getRowId(),
						 user_id : that.getUserId() },
				type: 'POST',
				error: function(){
					that.notifyInsertBeforeFailed(row);
				},
				success: function(data){
					if(data.error) return that.notifyInsertBeforeFailed(row);
					loadResponse(data);
					that.notifyInsertBeforeFinished(row);
				}
			});
		}
		
		/**
		 * indent a row
		 * @param a Row object
		 */
		this.indentRow = function(row){
			that.notifyIndent(row);
			$.bAjax(control.getBAjaxOptions(), {
				data : { indent : true, 
						 dt : lastLoad,
						 row_id : row.getRowId(),
						 user_id : that.getUserId() },
				type: 'POST',
				error: function(){
					that.notifyIndentFailed(row);
				},
				success: function(data){
					if(data.error) return that.notifyIndentFailed(row);
					loadResponse(data);
					that.notifyIndentFinished(row);
				}
			});
		}
		
		/**
		 * outdent a row
		 * @param a Row object
		 */
		this.outdentRow = function(row){
			that.notifyOutdent(row);
			$.bAjax(control.getBAjaxOptions(), {
				data : { outdent : true, 
						 dt : lastLoad,
						 row_id : row.getRowId(),
						 user_id : that.getUserId() },
				type: 'POST',
				error: function(){
					that.notifyOutdentFailed(row);
				},
				success: function(data){
					if(data.error) return that.notifyOutdentFailed(row);
					loadResponse(data);
					that.notifyOutdentFinished(row);
				}
			});
		}
			
		/**
		 * delete a row
		 * @param a Row object
		 */
		this.deleteRow = function(row){
			that.notifyDeleteRow(row);
			$.bAjax(control.getBAjaxOptions(), {
				data : { delete_row : true, 
						 dt : lastLoad,
						 row_id : row.getRowId(),
						 user_id : that.getUserId() },
				type: 'POST',
				error: function(){
					that.notifyDeleteRowFailed(row);
				},
				success: function(data){
					if(data.error) return that.notifyDeleteRowFailed(row);
					loadResponse(data);
					that.notifyDeleteRowFinished(row);
				}
			});
		}
	
		/**
		 * save a note
		 * @param a Row object
		 */
		this.insertRowAfter = function(row){
			that.notifyInsertAfter(row);
			$.bAjax(control.getBAjaxOptions(), {
				data : { insert_after : true, 
						 dt : lastLoad,
						 row_id : row.getRowId(),
						 user_id : that.getUserId() },
				type: 'POST',
				error: function(){
					that.notifyInsertAfterFailed(row);
				},
				success: function(data){
					if(data.error) return that.notifyInsertAfterFailed(row);
					loadResponse(data);
					that.notifyInsertAfterFinished(row);
				}
			});
		}
				
		this.getRow = function(row_id){
			return cache.get(row_id);
		}
		

		/**
		 * add a listener to this model
		 */
		this.addListener = function(list){
			listeners.push(list);
		}
		
		/**
		 * notify everone that we're starting 
		 * to load some data
		 */
		this.notifyLoadBegin = function(){
			$.each(listeners, function(i){
				listeners[i].loadBegin();
			});
		}
		
		/**
		 * we're done loading, tell everyone
		 */
		this.notifyLoadFinish = function(notes){
			$.each(listeners, function(i){
				listeners[i].loadFinish(notes);
			});
		}
		
		/**
		 * we're done loading, tell everyone
		 */
		this.notifyLoadFail = function(){
			$.each(listeners, function(i){
				listeners[i].loadFail();
			});
		}
		
		/**
		 * tell everyone the item we've loaded
		 */
		this.notifyRowLoaded = function(note){
			$.each(listeners, function(i){
				listeners[i].loadRow(note);
			});
		}
		
		
		/**
		 * notify everyone that we're saving a note
		 */
		this.notifySavingRow = function(row){
			$.each(listeners, function(i){
				listeners[i].savingRow(row);
			});
		}
		
		/**
		 * notify everyone that we're saving a note
		 */
		this.notifySavingFailed = function(note){
			$.each(listeners, function(i){
				listeners[i].savingFailed(note);
			});
		}
		
		/**
		 * notify everyone that we're saving a note
		 */
		this.notifySavingFinished = function(note){
			$.each(listeners, function(i){
				listeners[i].savingFinished(note);
			});
		}
		
		/**
		 * notify everyone that we're loggin in
		 */
		this.notifyBeginLogin = function(){
			$.each(listeners, function(i){
				listeners[i].beginLogin();
			});
		}
		
		/**
		 * notify everyone that we're loggin in
		 */
		this.notifyBeginLogin = function(){
			$.each(listeners, function(i){
				listeners[i].beginLogin();
			});
		}
		
		this.notifyLoginFailed = function(){
			$.each(listeners, function(i){
				listeners[i].loginFailed();
			});
		}
		
		this.notifyLoggedIn = function(user_id){
			$.each(listeners, function(i){
				listeners[i].loggedIn(user_id);
			});
		}
		
		this.notifyInsertAfter = function(row){
			$.each(listeners, function(i){
				listeners[i].insertAfter(row);
			});
		}

		this.notifyInsertAfterFailed = function(row){
			$.each(listeners, function(i){
				listeners[i].insertAfterFailed(row);
			});
		}

		this.notifyInsertAfterFinished = function(row){
			$.each(listeners, function(i){
				listeners[i].insertAfterFinished(row);
			});
		}
		
		this.notifyInsertBefore = function(row){
			$.each(listeners, function(i){
				listeners[i].insertBefore(row);
			});
		}

		this.notifyInsertBeforeFinished = function(row){
			$.each(listeners, function(i){
				listeners[i].insertBeforeFinished(row);
			});
		}

		this.notifyInsertBeforeFailed = function(row){
			$.each(listeners, function(i){
				listeners[i].insertBeforeFailed(row);
			});
		}
		
		this.notifyIndent = function(row){
			$.each(listeners, function(i){
				listeners[i].indent(row);
			});
		}

		this.notifyIndentFinished = function(row){
			$.each(listeners, function(i){
				listeners[i].indentFinished(row);
			});
		}

		this.notifyIndentFailed = function(row){
			$.each(listeners, function(i){
				listeners[i].indentFailed(row);
			});
		}
		
		this.notifyOutdent = function(row){
			$.each(listeners, function(i){
				listeners[i].outdent(row);
			});
		}

		this.notifyOutdentFinished = function(row){
			$.each(listeners, function(i){
				listeners[i].outdentFinished(row);
			});
		}

		this.notifyOutdentFailed = function(row){
			$.each(listeners, function(i){
				listeners[i].outdentFailed(row);
			});
		}
		
		this.notifyLoadUserPosition = function(user_id, row_id){
			$.each(listeners, function(i){
				listeners[i].loadUserPosition(user_id, row_id);
			});
		}
		
		this.notifyChangingLocation = function(row){
			$.each(listeners, function(i){
				listeners[i].changingLocation(row);
			});
		}
		
		this.notifyChangingLocationFailed = function(row){
			$.each(listeners, function(i){
				listeners[i].changingLocationFailed(row);
			});
		}
		
		this.notifyChangingLocationFinished = function(row){
			$.each(listeners, function(i){
				listeners[i].changingLocationFinished(row);
			});
		}
		
		this.notifyDeleteRow = function(row){
			$.each(listeners, function(i){
				listeners[i].deleteRow(row);
			});
		}
		
		this.notifyDeleteRowFailed = function(row){
			$.each(listeners, function(i){
				listeners[i].deleteRowFailed(row);
			});
		}
		
		this.notifyDeleteRowFinished = function(row){
			$.each(listeners, function(i){
				listeners[i].deleteRowFinished(row);
			});
		}
		
		
		
	},
	
	/**
	 * let people create listeners easily
	 */
	ModelListener: function(list) {
		if(!list) list = {};
		return $.extend({
			loadBegin : function() { },
			loadFinish : function() { },
			loadRow : function() { },
			loadFail : function() { },
			savingRow : function() { },
			savingFailed : function() { },
			savingFinished : function() { },
			beginLogin : function() { },
			loginFailed : function() { },
			loggedIn : function() { },
			insertAfter : function(row) { },
			insertAfterFailed : function(row) { },
			insertAfterFinished : function(row) { },
			insertBefore : function(row) { },
			insertBeforeFinished : function(row) { },
			insertBeforeFailed : function(row) { },
			indent : function(row) { },
			indentFinished : function(row) { },
			indentFailed : function(row) { },
			outdent : function(row) { },
			outdentFinished : function(row) { },
			outdentFailed : function(row) { },
			loadUserPosition : function(user_id, row_id) { },
			changingLocation : function(row){ },
			changingLocationFailed : function(row){ },
			changingLocationFinished : function(row){ },
			deleteRow : function(row){ },
			deleteRowFailed : function(row){ },
			deleteRowFinished : function(row){ }
		}, list);
	}
});
