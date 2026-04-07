var YingShi = {
	'Url': document.URL,
	'Title': document.title, 
    'jscode': function() {
        
        $(".lazy").lazyload({
        	effect: "fadeIn",
        	threshold: 200,
        	failure_limit: 10,
        	skip_invisible: false
        });
                     
            var currentUrl = window.location.toString().split("#")[0];
            $(".header-tab a").each(function() {
                if (this.href === currentUrl) {
                    $(this).addClass("selected text-yellow-500").removeClass("text-white");
                    return false; // 停止循环
                }
            });
            
        var currentUrl2 = window.location.href.split("#")[0];
            $(".pcnav a").each(function() {
                if (this.href === currentUrl2) {
                    $(this).find('span').addClass("text-yellow-500").removeClass("text-white");
                     $(this).append('<div class="border-2 border-yellow-500 w-5 h-0.5 rounded-lg"></div>')
                    return false; 
                }
        });
            
	}, 
};
$(function() {
	YingShi.jscode();  
});