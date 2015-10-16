jQuery(document).ready(function()
{
	jQuery(".post")
		.mouseover(function(){
			jQuery(this).find(".postpoop img").css("visibility","visible");
			//jQuery(this).find("img").show();
		})
		.mouseout(function(){
			jQuery(this).find(".postpoop img").css("visibility","hidden");
			//jQuery(this).find("img").hide();
		})
});