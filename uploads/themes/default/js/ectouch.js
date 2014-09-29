/* 搜索验证 */
function check(Id){
	var strings = document.getElementById(Id).value;
	if(strings.replace(/(^\s*)|(\s*$)/g, "").length == 0){
		return false;
	}
	return true;
}
/*商品分类页*/
$(function($) {
	$(".ect-category-all ul li .panel-body").click(function(){
		if(!$(this).parent("li").hasClass("category-select")){
			$(this).parent("li").addClass("category-select");
		}else{
			$(this).parent("li").removeClass("category-select");
		}
	});
});

/*商品列表页面点击显示/隐藏下拉*/
$(".category-list").click(function(){
	if($(".category-list-show")){
		if($(".ect-wrapper").hasClass("select")){
			$(".ect-wrapper").removeClass("select");
		}else{
			$(".ect-wrapper").addClass("select");
		}
	}
});
/*商品列表页面点击隐藏下拉*/
$(".ect-pro-list,.ect-wrapper div a.select").click(function(){
	if($(".ect-wrapper").hasClass("select")){
		$(".ect-wrapper").removeClass("select");
	}
});


/*点击下拉菜单*/
function openMune(){
	if($(".ect-nav").is(":visible")){
		$(".ect-nav").hide();
	}else{
		$(".ect-nav").show();
	}
}/**
* jquery Begin
* @returns {undefined}
*/          
$(function(){
	//点击筛选
	$('.filter_list li').click(function(){
		$(this).addClass('av-selected').siblings('li').removeClass('av-selected');
		var value = $(this).children('a').attr('data');
		if(value.indexOf('|') > 0){
			var val_array = value.split('|');
			$(this).siblings('input[name=price_min]').val(val_array[0]);
			$(this).siblings('input[name=price_max]').val(val_array[1]);
		}else{
			$(this).siblings('input').val(value);
			//属性
			var i = 0;
			var new_attr = new Array();
			$('.filter_attr').each(function() {
				if($(this).hasClass('av-selected')){
					new_attr[i] = $(this).children('a').attr('data');
					i = i+1;
				}
			});
			var new_attr_str = new_attr.join('.');
			//属性参数具体值
			$('input[name=filter_attr]').val(new_attr_str);
		}
	});
	//显示更多
	$('.av-options').click(function(){
		if ($(this).children('a').hasClass("avo-more-down")){
			var i = $('.filter_list li').size();
			var h = Math.round(i/2)*40;
			$(this).siblings('.filter_list').css('max-height', h);
			$(this).children('a').removeClass('avo-more-down').addClass('avo-more-up').html('收起更多<i></i>');
		}else{
			$(this).siblings('.filter_list').css('max-height','') ;
			$(this).children('a').removeClass('avo-more-up').addClass('avo-more-down').html('查看更多<i></i>');
		}
	})
	
	/**
	* 商品分类页跳转页面
	*/
	$('.pagenav-select').change(function(){
		 window.location.href= $(this).val();
	});
	
	/**选择配送方式**/
	
	$('#selected1').click(function(){
		   $('#shipping').slideToggle("fast");
		});
		
	$('#selected2').click(function(){
	   $('#payment').slideToggle("fast");
	});
	/**
	红包
	*/
	$('#selected4').click(function(){
		
	   $('#bonus_box').slideToggle("fast");
	
	});
	/**
    包装
	*/
	$('#selected5').click(function(){
		
	   $('#package_box').slideToggle("fast");
	
	});
	/**
    祝福贺卡
	*/
	$('#selected6').click(function(){
		
	   $('#card_box').slideToggle("fast");
	
	});
	
	/**选择配送地址**/
	
	$('#selected7').click(function(){
		   $('#address').slideToggle("fast");
		});
	
	/**选择余额**/
	
	$('#selected8').click(function(){
		   $('#surplus').slideToggle("fast");
		});
	
	
	/**选择积分**/
	
	$('#selected9').click(function(){
		   $('#integral').slideToggle("fast");
		});
	
	
	$('.modRadio').click(function(){
		    if( $(".modRadio i").attr('class') == 'fr'){
				 $(".modRadio i").removeClass("fr");
				 $(".modRadio i").addClass("fl");
				 $(".modRadio ins").html('否');
				 $('#inviype_box').css('display','none');
				 $("#ECS_NEEDINV").attr("checked",'false')
				}
				else{
				 $(".modRadio i").removeClass("fl");
				 $(".modRadio i").addClass("fr");
				 $(".modRadio ins").html('是');
				 $('#inviype_box').css('display','block');
				 $("#ECS_NEEDINV").attr("checked",'true')
					}
		       
		});
	
})

//ectouch js 20140724
/*点击返回顶部*/
  $(window).scroll(function () {
		if ($(this).scrollTop() >50) {
			$('#scrollUp').fadeIn();
		} else {
			$('#scrollUp').fadeOut();
		}
	});
	// scroll body to 0px on click
	$('#scrollUp').click(function () {
		$('#scrollUp').tooltip('hide');
		$('body,html').animate({
			scrollTop: 0
		}, 200);
		return false;
	});	
	
	/*商品详情页*/
$(function() {
	$('.spxq table,.spxq div').width('100%');
	//商品图片滚动自适应
	gfimg = $(".goodfocus .bd ul li img");
	wdsHeight = ($(window).height() - $(".f-h1").height())/1.5;
	wdsWidth = $(window).width();
	gfimg.each(function(){
		if($(this).height() > $(this).width()){
			if($(this).height() > wdsHeight){
				$(this).height(wdsHeight);
				$(this).width("auto");	
			}
		}else{
			$(this).width("98%");
			$(this).height("auto");
		}
	});
	/*判断user-tab内容高度不够时撑开*/
	var user_tab_height = $(".user-tab .tab-content .tab-pane");
	var window_height = $(window).height()/3;
	user_tab_height.css("min-height",window_height);
});