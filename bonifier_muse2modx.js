$(document).ready(function () {

    // ensure container style is well applied to modx contents
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
            if(cssClass) {
    			if(!$(eachSibling).hasClass(cssClass)) {
    				$(eachSibling).addClass(cssClass);
    			}
            }

            // assume only classes are intended to replicate
			// eachSibling.style.cssText += eachContainer.style.cssText;
		});

		if(siblingStyleApplied && $(eachContainer).is(":empty")) {
			$(eachContainer).remove();
		} 
	});

    // menu position calculation & adjustment
    var menuItemLastWidth = 0;
    var menuItemTextNodes = $('.MenuItemLabel > p');
    var regex = /(\d+)/g;

    for(var i = 0; i < menuItemTextNodes.length; i++) {
        var textWidth = $(menuItemTextNode[i]).width();
        var textPadding = $(menuItemTextNode[i]).parent().css('left');
        var tmpMatch = textPadding.match(regex);

        // set left attribute of the muse container of this menu item
        if(menuItemLastWidth) {
            $(menuItemTextNode[i]).parent().parent().parent().css('left', menuItemLastWidth + "px");
        }

        // if left attribute is defined with numbers & width unit is px
        if(tmpMatch.length == 1 && textPadding.replace(regex, '') == "px") {
            menuItemLastWidth = tmpMatch[0] * 2 + textWidth;
            $(menuItemTextNode[i]).parent().width(menuItemLastWidth);
        }
    }
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