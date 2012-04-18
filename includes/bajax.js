jQuery.extend({

	/**
	 * keep track of multiple queues
	 */
	bAjaxQs : { },

	/**
	 * define the functionality
	 * for a bundled AJAX Queue
	 */
	bAjaxQ : function(q_options){
		
		var inQ = new Array();
		var sending = false;
		var sendingQ = new Array();
		var opt_success = q_options.success;
		
		/**
		 * the bundled AJAX request came back successfully
		 * so notify each request in the queue of it's
		 * specific response, in the order the reqest was sent
		 */
		function success(data){
			if(typeof(data.error) != "undefined" && data.error){
				return error(null, data.message, null);
			}
			if(opt_success){
				$.each(sendingQ, function(i){
					if(sendingQ[i].success) sendingQ[i].success(data[i]);
				});
				opt_success(data, inQ.length);
			}
			sending = false;
			sendQ();
		}
		
		function error(request, textStatus, errorThrown){
			inQ = $.merge(sendingQ, inQ);
			sendingQ = new Array();
			console.log("ajax fail : " + textStatus);
			console.log(errorThrown);
			sending = false;
			sendQ();
		}
		
		/**
		 * simple function to escape \ ' and " with a \
		 */
		function addslashes(str){
			return (str+'').replace(/([\\"])/g, "\\$1");
		}
		
		/**
		 * simple function to serialize an object
		 * to a json string
		 */
		function serialize(obj){
			var out = "";
			$.each(obj, function(i, val){
				if(val == null){
					out = out + "\"" + i + "\" : null,";
				}else if(typeof(val) == "number"){
					out = out + "\"" + i + "\" : " + val + ",";
				}else if(typeof(val) == "boolean"){
					out = out + "\"" + i + "\" : " + (val ? "true" : "false") + ",";
				}else if(typeof(val) == "object"){
					out = out + "\"" + i + "\" : " + serialize(val) + ",";
				}else{ // string etc
					out = out + "\"" + i + "\" : \"" + addslashes(val) + "\",";
				}
			});
			if(out.length) out = out.substr(0, out.length - 1);
			return "{" + out + "}";
		}
		
		/**
		 * if our queue has items ready to send,
		 * then bundle them up into a single request
		 * and send it
		 */
		function sendQ(){
			if(inQ.length){
				sending = true;
				sendingQ = inQ;
				inQ = new Array();
				
				var data = "[";
				
				$.each(sendingQ, function(i){
					if(data.length > 1) data += ",";
					if(typeof(sendingQ[i].data) == "function"){
						data += serialize(sendingQ[i].data());
					}else{
						data += serialize(sendingQ[i].data);
					}
				});
				data += "]";
				
				$.ajax($.extend(q_options,{
					data : { data : data },
					success : success,
					error : error
				}));
			}
		}
		
		/**
		 * add the input request to the queue
		 * and send the queue if we can
		 */
		this.addToQ = function(options){
			inQ.push(options);
			if(!sending) sendQ();
		}
	},

	/**
	 * Send a bundled AJAX request
	 */
	bAjax: function(q_options, options){
		if(!this.bAjaxQs[q_options.url]){
			this.bAjaxQs[q_options.url] = new this.bAjaxQ(q_options);
		}
		this.bAjaxQs[q_options.url].addToQ(options);
	}
	
});
