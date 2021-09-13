/**
 * 实现jquery/zepto的方法。
 */
;(function(window,document , realJQuery){
	
	if(realJQuery){
		return ;
	}
	var Utils = function(selector , context ) {
	     return new Utils.prototype.init(selector , context , rootjQuery);
	}
	var rootjQuery = Utils(document), 
	rquickExpr = /^(?:[^#<]*(<[\w\W]+>)[^>]*$|#([\w\-]*)$)/; 
	
	Utils.fn = Utils.prototype = {
        init: function(selector, context, rootjQuery) {

	    	var match, elem, ret, doc; 
	    	 // Handle $(""), $(null), $(undefined), $(false) 
	    	if ( !selector ) { 
	    		return this; 
	    	} 
	    	// Handle $(DOMElement) 
	    	if ( selector.nodeType ) { 
	    		this.context = this[0] = selector; 
	    		this.length = 1; 
	    		return this; 
	    	} 
	    	// Handle HTML strings 
	    	if ( typeof selector === "string" ) { 
	    		if ( selector.charAt(0) === "<" && selector.charAt( selector.length - 1 ) === ">" && selector.length >= 3 ) { 
		    		// Assume that strings that start and end with <> are HTML and skip the regex check 
		    		match = [ null, selector, null ]; 
		    	} else { 
		    		match = rquickExpr.exec( selector ); 
		    	} 
	    		// Match html or make sure no context is specified for #id 
	    		// match[1]不为null，则为html字符串，match[2]不为null，则为元素id 
	    		if ( match && (match[1] || !context) ) { 
	    			// HANDLE: $(html) -> $(array) 
	    			if ( match[1] ) { 
	    				
	    				return jQuery.merge( this, selector ); 
	    				
	    			} else { 
	    				elem = document.getElementById( match[2] ); 
	    				// Check parentNode to catch when Blackberry 4.6 returns 
	    				// nodes that are no longer in the document #6963 
	    				if ( elem && elem.parentNode ) { 
	    					// Handle the case where IE and Opera return items 
	    					// by name instead of ID 
	    					// ie6，7和Opera存在此bug，当一个标签name和一个标签id值相等时， 
	    					// document.getElementById(#id)函数将返回提前出现的标签元素 
	    					if ( elem.id !== match[2] ) { 
	    						// 如果存在以上Bug，则返回由find函数返回的document文档的后代元素集合 
	    						return rootjQuery.find( selector ); 
	    					} 
	    					// Otherwise, we inject the element directly into the jQuery object 
	    					this.length = 1; 
	    					this[0] = elem; 
	    				} 
	    				this.context = document; 
	    				this.selector = selector; 
	    				return this; 
	    			} 
	    			// HANDLE: $(expr, $(...)) 
	    			// context不存在或者context为jQuery对象 
	    		} else if ( !context || context.jquery ) { 
	    			return ( context || rootjQuery ).find( selector ); 
	    			// HANDLE: $(expr, context) 
	    			// (which is just equivalent to: $(context).find(expr) 
	    			// context为className或者dom节点元素 
	    		} else { 
	    			// 等同于jQuery(context).find(selector) 
	    			return this.constructor( context ).find( selector ); 
	    		} 
	    		// 处理$(fn)===$(document).ready(fn) 
	    	} else if ( jQuery.isFunction( selector ) ) { 
	    		return rootjQuery.ready( selector ); 
	    	} 
	    	// 处理$(jQuery对象) 
    		if ( selector.selector !== undefined ) { 
    			this.selector = selector.selector; 
    			this.context = selector.context; 
    		} 
	    	// 当第一个参数selector为jQuery对象时，将selector中的dom节点合并到this对象中，并返回this对象 
	    	return jQuery.makeArray( selector, this ); 
    	
        }
    }
	Utils.fn.init.prototype = Utils.fn;
	
	
})(window , document , jQuery);