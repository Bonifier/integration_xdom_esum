$(document).ready(function () {
	var selector = "p.modx, " + 
		"h1.modx, " + 
		"h2.modx, " + 
		"h3.modx, " + 
		"h4.modx, " + 
		"h5.modx, " + 
		"h6.modx";

	var modxContainers = $(selector);

	modxContainers.each(function (i, eachContainer) {

		var siblingStyleApplied = false;

		$(eachContainer).siblings().each(function (j, eachSibling) {
			siblingStyleApplied = true;

			var cssClass = $(eachContainer).attr('class').replace('modx', '').trim();
			if(!$(eachSibling).hasClass(cssClass)) {
				$(eachSibling).addClass(cssClass);
			}

			// eachSibling.style.cssText += eachContainer.style.cssText;
		});

		if(siblingStyleApplied && $(eachContainer).is(":empty")) {
			$(eachContainer).remove();
		} 
	});
});

/*
 * getStyleObject Plugin for jQuery JavaScript Library
 * From: http://upshots.org/?p=112
 * Ref: http://stackoverflow.com/questions/754607/can-jquery-get-all-css-styles-associated-with-an-element/6416527#6416527
 */

/*
(function($){
    $.fn.getStyleObject = function(){
        var dom = this.get(0);
        var style;
        var returns = {};
        if(window.getComputedStyle){
            var camelize = function(a,b){
                return b.toUpperCase();
            };
            style = window.getComputedStyle(dom, null);
            for(var i = 0, l = style.length; i < l; i++){
                var prop = style[i];
                var camel = prop.replace(/\-([a-z])/g, camelize);
                var val = style.getPropertyValue(prop);
                returns[camel] = val;
            };
            return returns;
        };
        if(style = dom.currentStyle){
            for(var prop in style){
                returns[prop] = style[prop];
            };
            return returns;
        };
        return this.css();
    }

    $.fn.copyCSS = function(source){
    	var styles = $(source).getStyleObject();
    	this.css(styles);
    }
})(jQuery);
*/