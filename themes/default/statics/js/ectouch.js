var handler = function(e) { //禁止浏览器默认行为
	e.preventDefault();
};
$(function($) {

	var cityTop;

	function d_messages(content, position) { //消息弹出层
		var style_text = "";
		if(position == 1) { //顶部弹出
			style_text = "border:none; background: rgba(0,0,0,.7); color:#fff; max-width:100%; top:0; position:fixed; left:0; right:0; border-radius:0;";
		}
		if(position == 2) { //页面中间弹出
			style_text = "border:none; background: rgba(0,0,0,.7); color:#fff; max-width:90%; min-width:1rem; margin:0 auto; border-radius:.8rem;";
		}
		layer.open({
			style: style_text,
			type: 0,
			anim: 3,
			content: content,
			shade: false,
			time: 2
		})
	}

	function d_messages_btn(content, btn1, btn2) { //确定取消弹出层
		layer.open({
			content: content,
			btn: [btn1, btn2],
			shadeClose: false,
			yes: function() {},
			no: function() {}
		});
	}
	$(".con-filter-div .swiper-scroll").css("max-height", $(window).height());
	if($(".swiper-scroll").hasClass("swiper-scroll")) { //滚动相关js
		swiper_scroll();
	}

	/*点击关闭顶部层*/
	$(".ect-header-banner i.icon-guanbi1").click(function() {
		$(".ect-header-banner").hide();
	});

	/*判断文本框是否有值显示隐藏清空按钮*/
	var input_texts = $(".j-input-text");
	var is_nulls = $(".j-text-all").find(".j-is-null");
	var is_yanjing = $(".j-text-all").find(".j-yanjing");
	input_texts.bind('focus', function() {
		is_nulls.removeClass('active');
		//$(this).parents(".j-text-all").addClass("active").siblings().removeClass("active");//开启后 文本框获得焦点即可改变下边框颜色
		if($(this).val() != "") {
			$(this).siblings('.j-is-null').addClass('active');
		}
	});
	input_texts.bind('input', function() {
		if($(this).val() == "") {
			$(this).siblings('.j-is-null').removeClass('active');
		} else {
			$(this).siblings('.j-is-null').addClass('active');
		}
	});

	is_nulls.click(function() { /*点击清空标签文本框内容删*/
		$(this).siblings(".j-input-text").val("");
		$(this).siblings(".j-input-text").focus();
	});

	is_yanjing.click(function() { /*密码框点击切换普通文本*/
		input_text_atr = $(this).siblings(".input-text").find(".j-input-text");
		if(input_text_atr.attr("type") == "password" && $(this).hasClass("disabled")) {
			input_text_atr.attr("type", "text");
		} else {
			input_text_atr.attr("type", "password");
		}
		input_text_atr.focus();
		$(this).toggleClass("disabled");
	});

	/*三种模式商品列表切换*/
	var sequence = ["icon-icon-square", "icon-pailie", "icon-viewlist"];
	var p_l_product = ["product-list-big", "product-list-medium", "product-list-small"];
	$(".j-a-sequence").click(function() {
		var icon_sequence = $(this).find("i").attr("data");
		var len = sequence.length;
		var key = icon_sequence;
		icon_sequence++;
		if(icon_sequence >= len) {
			icon_sequence = 0;
		}
		/*更换排序列表图标class*/
		$(this).find(".iconfont").removeClass(sequence[key]).addClass(sequence[icon_sequence]);
		$(this).find(".iconfont").attr("data", icon_sequence);
		/*更换商品列表class*/
		$(".j-product-list").removeClass(p_l_product[key]).addClass(p_l_product[icon_sequence]);
		$(".j-product-list").attr("data", icon_sequence);
	});

	$(".j-search-check").click(function() { /*搜索店铺商品切换*/
		if($(this).attr("data") == 1) {
			$(this).attr("data", 2);
			$(this).find("span").html("商品");
		} else {
			$(this).attr("data", 1);
			$(this).find("span").html("店铺");
		}
	});

	/*手风琴下拉效果*/
	$(".j-sub-menu").hide();
	$(".j-menu-select").click(function() {
		$(this).next(".j-sub-menu").slideToggle().siblings('.j-sub-menu').slideUp();
		$(this).toggleClass("active").siblings().removeClass("active");
		var scorll_swiper = new Swiper('.swiper-scroll', {
			scrollbar: false,
			direction: 'vertical',
			slidesPerView: 'auto',
			mousewheelControl: true,
			freeMode: true
		});

	});

	/*多选并限制个数  －  ［商品筛选将值传给em标签］  */
	$(".j-get-limit .ect-select").not(".j-checkbox-all").click(function() {
		get_text = $(this).parents(".j-get-limit");
		s_t_em_value = get_text.prev(".select-title").find(".t-jiantou em"); //获取需要改变值的em标签
		checked = $(this).find("label").hasClass("active");
		ischecked = $(this).parents(".j-get-limit").attr("data-istrue");
		var s_t_em_text = "",
			s_get_label_num = 0;
		var active_jiantou = get_text.prev(".j-menu-select").find(".j-t-jiantou");
		active_jiantou.addClass("active");
		if(get_text.find(".j-checkbox-all label").hasClass("active")) { //当点击非j-checkbox-all的时候删除其选中状态
			get_text.find(".j-checkbox-all label").removeClass("active");
		}
		if(ischecked == "true") {
			$(this).find("label").toggleClass("active");
		}
		if(checked) {
			$(this).find("label").removeClass("active");
			$(this).parents(".j-get-limit").attr("data-istrue", "true")
		}
		if(ischecked == "false") {
			d_messages("筛选最多不能超过5个", "2");
		}
		s_get_label = get_text.find("label.active"); //获取被选中label
		s_get_label_num = s_get_label.length;
		if(s_get_label_num <= 0) {
			active_jiantou.removeClass("active");
			$(".j-checkbox-all label").addClass("active");
			s_t_em_text = $(this).siblings(".j-checkbox-all").find("label").text() + "、";
		}
		if(s_get_label_num >= 5) {
			$(this).parents(".j-get-limit").attr("data-istrue", "false")
		} else {
			//			$(".div-messages").removeClass("active");
			$(this).parents(".j-get-limit").attr("data-istrue", "true")
		}
		s_get_label.each(function() {
			s_t_em_text += $(this).text() + "、";
		});
		s_t_em_value.text(s_t_em_text.substring(0, s_t_em_text.length - 1));

	});
	$(".j-checkbox-all").click(function() {
		checkbox_all = $(this).find("label"); //获取值为“全部”的label
		s_t_em_value = $(this).parent().prev(".select-title").find(".t-jiantou em"); //获取需要改变值的em标签
		checkbox_all_text = $(this).find("label").text();
		if(!checkbox_all.hasClass("active")) {
			$(this).find("label").addClass("active").parents(".ect-select").siblings().find("label").removeClass("active");
			s_t_em_value.text(checkbox_all_text); //将calss为j-checkbox-all的label的值赋值给需要改变的em标签
			$(this).parent(".j-get-limit").prev(".select-title").find(".t-jiantou").removeClass("active");
			$(this).parents(".j-get-limit").attr("data-istrue", "true")
		}
	});

	/*筛选按钮中清空选项*/
	$(".j-filter-reset").click(function() {
		$(".con-filter-div label").removeClass("active");
		$(".j-checkbox-all label").addClass("active");
		$(".j-radio-switching").removeClass("active");
		$(".j-menu-select .j-t-jiantou").removeClass("active");
		$(".j-menu-select .j-t-jiantou em").text("全部");
		$(".j-filter-city span.text-all-span").css("color", "#555");
		$(".j-filter-city span.text-all-span").text("请选择");
		$(".div-messages").removeClass("active");
		$(this).parents(".j-get-limit").attr("data-istrue", true)
	});

	/*多选*/
	$(".j-get-more .ect-select").click(function() {
		if(!$(this).find("label").hasClass("active")) {
			$(this).find("label").addClass("active");
			if($(this).find("label").hasClass("label-all")) {
				$(".j-select-all").find(".ect-select label").addClass("active");
			}
		} else {
			$(this).find("label").removeClass("active");
			if($(this).find("label").hasClass("label-all")) {
				$(".j-select-all").find(".ect-select label").removeClass("active");
			}
		}
	});
	/*多选只点击单选按钮 - 全选，全不选*/
	$(".j-get-i-more .j-select-btn").click(function() {
		if($(this).parents(".ect-select").hasClass("j-flowcoupon-select-disab")) {
			d_messages("同商家只能选择一个", 2);
		} else {
			is_select_all = true;
			if($(this).parent("label").hasClass("label-this-all")) {
				if(!$(this).parent("label").hasClass("active")) {
					$(this).parents(".j-get-i-more").find(".ect-select label").addClass("active");
				} else {
					$(this).parents(".j-get-i-more").find(".ect-select label").removeClass("active");
				}
			}

			if(!$(this).parent("label").hasClass("label-this-all") && !$(this).parent("label").hasClass("label-all")) {
				$(this).parent("label").toggleClass("active");
				is_select_this_all = true;
				select_this_all = $(this).parents(".j-get-i-more").find(".ect-select label").not(".label-this-all");

				select_this_all.each(function() {
					if(!$(this).hasClass("active")) {
						is_select_this_all = false;
						return false;
					}
				})
				if(is_select_this_all) {
					$(this).parents(".j-get-i-more").find(".label-this-all").addClass("active");
				} else {
					$(this).parents(".j-get-i-more").find(".label-this-all").removeClass("active");
				}
			}

			var select_all = $(".j-select-all").find(".ect-select label");
			select_all.each(function() {
				if(!$(this).hasClass("active")) {
					is_select_all = false;
					return false;
				}
			});
			if(is_select_all) {
				$(".label-all").addClass("active");
			} else {
				$(".label-all").removeClass("active");
			}
		}
	});

	/*单选*/
	$(".j-get-one .ect-select").click(function() {
		get_tjiantou = $(this).parent(".j-get-one").prev(".select-title").find(".t-jiantou");
		$(this).find("label").addClass("active").parent(".ect-select").siblings().find("label").removeClass("active");
		get_tjiantou.find("em").text($(this).find("label").text());
		if($(this).hasClass("j-checkbox-all")) {
			get_tjiantou.removeClass("active");
		} else {
			get_tjiantou.addClass("active");
		}
		if($(this).parents("show-goods-attr")) { //赋值给goods-attr
			s_get_label = $(".show-goods-attr .s-g-attr-con").find("label.active"); //获取被选中label
			var get_text = '';
			s_get_label.each(function() {
				get_text += $(this).text() + "、";
			});
			$(".j-goods-attr").find(".t-goods1").text(get_text.substring(0, get_text.length - 1));
		}
	});

	/*单选consignee*/
	$(".j-get-consignee-one label").click(function() {
		$(this).addClass("active").parents(".flow-checkout-adr").siblings().find("label").removeClass("active");
	});

	/*选择收货人信息*/
	$(".j-flow-get-consignee .flow-checkout-adr").click(function() {
		$(this).addClass("active").siblings(".flow-checkout-adr").removeClass("active");
	});

	/*商品详情所在地区*/
	$(".j-get-city-one .ect-select").click(function() {
		city_span = $(".j-filter-city span.text-all-span");
		city_txt = $(".j-city-left li.active").text() + " " + $(this).parents(".j-sub-menu").prev(".j-menu-select").find("label").text() + " " + $(this).find("label").text();
		$(".j-get-city-one").find(".ect-select label").removeClass("active");
		$(this).find("label").addClass("active");
		city_span.text(city_txt);
		if($(".j-filter-city span.text-all-span").hasClass("j-city-scolor")) {
			$(".j-filter-city span.text-all-span").css("color", "#1CBB7F");
		}
		$("body").removeClass("show-city-div");
		$("html,body").animate({
			scrollTop: cityTop
		}, 0);
	});
	/*商品详情仓库选择*/
	$(".j-get-depot-one .ect-select").click(function() {
		city_span = $(".j-filter-depot span.text-all-span");
		city_txt = $(this).find("label").text();
		$(".j-get-depot-one").find(".ect-select label").removeClass("active");
		$(this).find("label").addClass("active");
		city_span.text(city_txt);
		if($(".j-filter-depot span.text-all-span").hasClass("j-city-scolor")) {
			$(".j-filter-depot span.text-all-span").css("color", "#ec5151");
		}
		$("body").removeClass("show-depot-div");
		$("html,body").animate({
			scrollTop: cityTop
		}, 0);
	});

	/*订单提交页面单选赋值*/
	$(".s-g-list-con .j-get-one .ect-select").click(function() {
		dist_span = $(this).find("label>dd").html();
		t_goods1 = $(this).parents(".j-show-get-val").find(".t-goods1"); //需要获取弹出层em标签
		t_goods1.html(dist_span);

	});

	/*商品详情 红心*/
	//$(".j-heart").click(function() {
	//$(this).toggleClass("active");
	//});

	/*发票赋值*/
	$(".flow-receipt .r-btn-submit").click(function() {
		if($("body").hasClass("show-receipt-div")) {
			document.removeEventListener("touchmove", handler, false);
			$("body").removeClass("show-receipt-div");
			is_no = $(".flow-receipt-type .active").attr("data");
			f_r_type = $(".flow-receipt-type .active").text();
			f_r_title = $(".flow-receipt-title .j-input-text").val();
			f_r_cont = $(".flow-receipt-cont .active").text();
			if(is_no == "no" || is_no == "") {
				f_r_title = "无";
			}
			if(f_r_title == "") {
				f_r_title = "个人";
			}
			receipt_title = $(this).parents(".j-f-c-receipt").find(".receipt-title");
			receipt_name = $(this).parents(".j-f-c-receipt").find(".receipt-name");
			receipt_title.text(f_r_type);
			receipt_name.text(f_r_title);
			return false;
		}
	});

	/*优惠券赋值*/
	$(".flow-coupon .c-btn-submit").click(function() {
		if($("body").hasClass("show-coupon-div")) {
			document.removeEventListener("touchmove", handler, false);
			$("body").removeClass("show-coupon-div");
			coupon_list = $(this).parents(".flow-coupon").find(".ect-select label.active");
			coupon_price = $(this).parents(".j-f-c-s-coupon").find(".t-goods1 .coupon-price");
			coupon_num = 0;
			coupon_list.each(function() {
				coupon_num += parseInt($(this).attr("data"));
			});
			coupon_price.text("¥" + coupon_num + ".00");
			return false;
		}
	});

	/*=======================================================*/

	/*点击弹出搜索层*/
	$(".j-search-input").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(".search-div,.new-maxbox").addClass("active");
		// $('#newinput').focus();
	});
	/*关闭搜索层*/
	$(".j-close-search").click(function() {
		document.removeEventListener("touchmove", handler, false);
		$(".search-div,.new-maxbox").removeClass("active");
	});

	/*城市筛选单选city*/
	$(".j-filter-city").click(function() {
		cityTop = $(window).scrollTop();
		$("body").addClass("show-city-div");
	});

	/*点击弹出仓库筛选*/
	$(".j-filter-depot").click(function() {
		cityTop = $(window).scrollTop();
		$("body").addClass("show-depot-div");
	});
	/*点击筛选弹出层*/
	$(".j-s-filter").click(function() {
		cityTop = $(window).scrollTop();
		$("body").addClass("show-filter-div");
	});
	/*点击关闭筛选弹出层*/
	$(".j-close-filter-div").click(function() {
		if($("body").hasClass("show-site-div")) {
			document.removeEventListener("touchmove", handler, false);
			$("body").removeClass("show-site-div");
			return false;
		}
		if($("body").hasClass("show-city-div")) {
			$("body").removeClass("show-city-div");
			$("html,body").animate({
				scrollTop: cityTop
			}, 0);

			return false;
		}
		if($("body").hasClass("show-filter-div")) {
			$("body").removeClass("show-filter-div");
			$("html,body").animate({
				scrollTop: cityTop
			}, 0);

			return false;
		}
		if($("body").hasClass("show-depot-div")) {
			$("body").removeClass("show-depot-div");
			$("html,body").animate({
				scrollTop: cityTop
			}, 0);
			return false;
		}
	});
	/*点击切换－滑动选择按钮*/
	$(".j-radio-switching").click(function() {
		if($(this).hasClass("active")) {
			$(this).removeClass("active");
			$(this).attr("data", 0);
		} else {
			$(this).addClass("active");
			$(this).attr("data", 1);
		}
	});
	/*点击弹出层 － 订单提交页面自提点*/
	$(".j-goods-site-li").click(function() {
		document.addEventListener("touchmove", handler, false);
		$("body").addClass("show-site-div");
	});
	/*点击弹出层 － 订单提交页优惠券*/
	$(".j-f-c-s-coupon").click(function() {
		document.addEventListener("touchmove", handler, false);
		$("body").addClass("show-coupon-div");
	});
	/*发票弹出*/
	$(".j-f-c-receipt").click(function() {
		document.addEventListener("touchmove", handler, false);
		$("body").addClass("show-receipt-div");
	});
	/*弹出层方式*/
	$(".j-show-div").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(this).find(".j-filter-show-div").addClass("show");
		$(".mask-filter-div").addClass("show");
	});
	$(".j-show-div1").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(".j-filter-show-div").addClass("show");
		$(".mask-filter-div").addClass("show");
	});
	$(".j-show-div-1").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(".j-filter-show-div").addClass("show");
		$(".mask-filter-div").addClass("show");
	});
	$(".j-show-div-2").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(".j-filter-show-div-2").addClass("show");
		$(".mask-filter-div").addClass("show");
	});

	/*关闭弹出层*/
	$(".mask-filter-div,.show-div-guanbi").click(function() {
		document.removeEventListener("touchmove", handler, false);
		if($(".j-filter-show-div,.j-filter-show-div-2").hasClass("show")) {
			$(".j-filter-show-div,.j-filter-show-div-2").removeClass("show");
			$(".mask-filter-div").removeClass("show");
			return false;
		}
		if($(".j-filter-show-list").hasClass("show")) {
			$(".j-filter-show-list").removeClass("show");
			$(".mask-filter-div").removeClass("show");
			return false;
		}
	});

	/*点击弹出层 商品列表区域弹出层*/
	$(".j-show-list").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(".j-filter-show-list").addClass("show");
		$(".mask-filter-div").addClass("show");
	});

	/*购物车点击展开优惠说明*/
	$(".flow-have-cart .j-icon-show").click(function() {
			$(this).parents(".g-promotion-con").toggleClass("active");
		})
		/*购物车悬浮按钮编辑状态*/
	$(".f-cart-filter-btn .span-bianji").click(function() {
		$(".f-cart-filter-btn").addClass("active");
	})
	$(".f-cart-filter-btn .j-btn-default").click(function() {
		$(".f-cart-filter-btn").removeClass("active");
	})

	/*数字增减*/
	$(".div-num-disabled").find("input").attr("readonly", true);
	$(".div-num a").click(function() {
		if(!$(this).parent(".div-num").hasClass("div-num-disabled")) {
			if($(this).hasClass("num-less")) {
				num = parseInt($(this).siblings("input").val());
				min_num = parseInt($(this).attr("data-min-num"));
				if(num > min_num) {
					num -= 1;
					$(this).siblings("input").val(num);
				} else {
					
				}
				return false;
			}
			if($(this).hasClass("num-plus")) {
				num = parseInt($(this).siblings("input").val());
				max_num = parseInt($(this).attr("data-max-num"));
				if(num < max_num) {
					num += 1;
					$(this).siblings("input").val(num);
				} else {
					d_messages("不能大超过最大数量", "2");
				}
				return false;
			}
		} else {
			d_messages("该商品不能增减", "2");
		}
	});
	$(".div-num input").bind("change", function() {
		num = parseInt($(this).val());
		max_num = parseInt($(this).siblings(".num-plus").attr("data-max-num"));
		min_num = parseInt($(this).siblings(".num-less").attr("data-min-num"));
		if(num > max_num) {
			$(this).val(max_num);
			d_messages("不能大超过最大数量", "2");
			return false;
		}
		if(num < min_num) {
			$(this).val(min_num);
			d_messages("不能小于最小数量", "2");
			return false;
		}
	});

	/*订单提交页*/
	$(".j-flow-checkout-pro span.t-jiantou").click(function() {
			$(this).parents(".flow-checkout-pro").toggleClass("active");
		})
		/*文本框获得焦点下拉*/
	$(".text-all-select .j-input-text").focus(function() {
		$(this).parents(".text-all-select").find(".text-all-select-div").show();
	});
	$(".text-all-select-div li").click(function() {
		text_select = $(this).text();
		$(this).parents(".text-all-select").find(".j-input-text").val(text_select);
		$(this).parents(".text-all-select").find(".text-all-select-div").hide();
		return false;
	});
	/*店铺街*/
	$(".j-s-nav-select,.j-shopping-menu-close").click(function() {
		$(".shopping-menu").toggleClass("active");
	});

	if($(".j-shopping-pro-list").hasClass("j-shopping-pro-list")) {
		$(window).scroll(function() {
			shopping_menu_h = $(".j-shopping-menu").outerHeight();
			shopping_menu_t = $(".j-shopping-pro-list").offset().top - $(document).scrollTop();
			if(shopping_menu_t <= shopping_menu_h) {
				$(".j-shopping-list").addClass("active");
			} else {
				$(".j-shopping-list").removeClass("active");
			}
		});
	}
	$(".j-menu-fixed>ul>li").click(function() {
		if($(this).hasClass("active")) {
			$(this).removeClass("active");
		} else {
			$(this).addClass("active").siblings().removeClass("active");
		}
	});

	/*评价星级*/
	$(".j-evaluation-star .evaluation-star").click(function() {
		var star_num = $(this).index();
		$(".j-evaluation-star .evaluation-star").removeClass("active");
		for(var j = 0; j <= star_num; j++) {
			$(".j-evaluation-star .evaluation-star").eq(j).addClass("active");
		}
		$(".j-evaluation-value").val(star_num + 1);
	});
	/*text-area1 文本框限制文字个数 － 实时监控*/
	if($(".text-area1").hasClass("text-area1")) {
		$(".text-area1").each(function() {
			$(this).find("span").text($(this).find("textarea").attr("maxlength"));
		});
	}
	$(".text-area1 textarea").bind("input", function() {
		count_span = $(this).siblings("span");
		max_length = $(this).attr("maxlength");
		textarea_length = $(this).val().length;
		if(max_length - textarea_length < 0) {
			count_span.text(0);
		} else {
			count_span.text(max_length - textarea_length);
		}

	});

	/*页面向上滚动js*/

	$(".filter-top").click(function() {
		$("html,body").animate({
			scrollTop: 0
		}, 200);
	});

	$(window).scroll(function() {
		var prevTop = 0,
			currTop = 0;
		currTop = $(window).scrollTop();
		win_height = $(window).height() * 2;
		if(currTop >= win_height) {
			$(".filter-top").stop().fadeIn(200);
		} else {
			$(".filter-top").stop().fadeOut(200);
		}
		//prevTop = currTop; //IE下有BUG，所以用以下方式
		setTimeout(function() {
			prevTop = currTop
		}, 0);
	});

	// 初始化图片上传插件
	if($("#demo").hasClass("demo")) {
		$("#demo").zyUpload({ // 宽度
			itemWidth: "120px", // 文件项的宽度
			itemHeight: "100px", // 文件项的高度
			url: "../upload/UploadAction/", // 上传文件的路径
			multiple: false, // 是否可以多个文件上传
			dragDrop: false, // 是否可以拖动上传文件
			del: true, // 是否可以删除文件
			finishDel: false, // 是否在上传文件完成后删除预览
			/* 外部获得的回调接口 */
			onSelect: function(files, allFiles) { // 选择文件的回调方法
				console.info("当前选择了以下文件：");
				console.info(files);
				console.info("之前没上传的文件：");
				console.info(allFiles);
			},
			onDelete: function(file, surplusFiles) { // 删除一个文件的回调方法
				console.info("当前删除了此文件：");
				console.info(file);
				console.info("当前剩余的文件：");
				console.info(surplusFiles);
			},
			onSuccess: function(file) { // 文件上传成功的回调方法
				console.info("此文件上传成功：");
				console.info(file);
			},
			onFailure: function(file) { // 文件上传失败的回调方法
				console.info("此文件上传失败：");
				console.info(file);
			},
			onComplete: function(responseInfo) { // 上传完成的回调方法
				console.info("文件上传完成");
				console.info(responseInfo);
			}
		});
	}
})

function swiper_scroll() {
	var scorll_swiper = new Swiper('.swiper-scroll', {
		scrollbar: false,
		direction: 'vertical',
		slidesPerView: 'auto',
		mousewheelControl: true,
		freeMode: true
	});
}
$(function() {
	$('#loading').hide();
})

/*关联商品*/
$(".my-com-nav1").click(function() {
	$(".my-com-nav1").siblings(".ect-select").find("label").removeClass("active")
	$(this).siblings(".ect-select").find("label").addClass("active");
});

/*菜单点击添加样式*/
$(function() {
	$('.oncle-color').click(function() {
		for(var i = 0; i < $('.oncle-color').size(); i++) {
			if(this == $('.oncle-color').get(i)) {
				$('.oncle-color').eq(i).children('a').addClass('active');
			} else {
				$('.oncle-color').eq(i).children('a').removeClass('active');
			}
		}
	})
});
/*开店流程分类*/
$(".my-com-nav2").click(function() {
	my_com_nav2 = $(this).siblings(".ect-select").find("label");
	j_open_two_select = $(".j-open-two-select").find(".ect-select label");
	j_open_two_select_all = $(".j-open-two-select-all").find(".ect-select label");
	if(my_com_nav2.hasClass("active")) {
		my_com_nav2.removeClass("active");
	} else {
		my_com_nav2.addClass("active");
	}

	j_open_two_select.each(function() {
		if(!$(this).hasClass("active")) {
			j_open_two_select_all.removeClass("active");
			return false;
		} else {
			j_open_two_select_all.addClass("active");
		}
	});
	return false;
});
$(".my-com-nav-one").click(function() {
	j_open_two_select_all = $(".j-open-two-select-all").find(".ect-select label");
	j_open_two_select = $(".j-open-two-select").find(".ect-select label");
	if(!j_open_two_select_all.hasClass("active")) {
		j_open_two_select_all.addClass("active");
		j_open_two_select.addClass("active");
	} else {
		j_open_two_select_all.removeClass("active");
		j_open_two_select.removeClass("active");
	}

});
/*首页导航*/
function adv_index() {
	/*if ($(window).scrollTop() > 120) {
		$(".index-nav-box").addClass("active");
	} else {
		$(".index-nav-box").removeClass("active");
	}*/
	if($(window).scrollTop() > 10) {
		$(".goods-fixed").addClass("active");
		$(".goods-left-jiat").addClass("active");
		$(".goods-header-nav-box").addClass("active");
	} else {
		$(".goods-fixed").removeClass("active");
		$(".goods-left-jiat").removeClass("active");
		$(".goods-header-nav-box").removeClass("active");
	}
}


function guanzhu_index() {
	/*
	if ($(window).scrollTop() > 120) {
		$(".ect-header-banner").addClass("active");
	} else {
		$(".ect-header-banner").removeClass("active");
	}*/
}
$(function($) {
	adv_index();
	$(window).scroll(function() {
		adv_index();
		guanzhu_index();
	});
	/*首页二维码*/
	$(".j-ewm-box").click(function() {
		document.addEventListener("touchmove", handler, false);
		$(".index-bg-box").addClass("active");
		$(".index-weixin-box").addClass("active");
	});
	$(".j-ewm-close").click(function() {
		document.removeEventListener("touchmove", handler, false);
		$(".index-bg-box").removeClass("active");
		$(".index-weixin-box").removeClass("active");
	});
	$(".index-bg-box").click(function() {
		document.removeEventListener("touchmove", handler, false);
		$(".index-bg-box").removeClass("active");
		$(".index-weixin-box").removeClass("active");
	});
	/*头部导航*/

	$(".j-nav-box").on("click",function() {
		$(".j-nav-content").toggleClass("active");
	});
	/*goods弹框*/
	$(".n-goods-bg,.n-j-show-goods-img").click(function() {
		$(".goods-photo").toggleClass("active");
		$(".n-goods-bg").toggleClass("active");
	});
	/*导航弹框*/
	$(".icon-gengduo").click(function() {
		$(".goods-scoll-bg").addClass("active");
		if(!$(".goods-nav").hasClass("active")){
			$(".goods-nav").addClass("active");
			$(".goods-scoll-bg").addClass("active");
			return false;
		}else{
			$(".goods-nav").removeClass("active");
			$(".goods-scoll-bg").removeClass("active");
			return false;
		}
	});
	$(".goods-scoll-bg").click(function() {
		$(".goods-scoll-bg").removeClass("active");
		$(".goods-nav").removeClass("active");
	});
	//详情导航滚动隐藏
		$(function($) {
			$(window).scroll(function() {
				if($(window).scrollTop() > 0) {
				  $(".goods-scoll-bg").removeClass("active");
				  $(".goods-nav").removeClass("active");
				};
			});
		});

	if($(".swiper-scroll").hasClass("swiper-scroll")) {
		var scorll_swiper = new Swiper('.swiper-scroll', {
			scrollbar: '.swiper-scrollbar',
			direction: 'vertical',
			slidesPerView: 'auto',
			mousewheelControl: true,
			freeMode: true
		});
	}

	function d_messages(m_text) {
		//弹出消息
		$(".div-messages").text(m_text);
		m_marginLeft = $(".div-messages").innerWidth() / 2;
		$(".div-messages").css("margin-left", -m_marginLeft);
		if(!$(".div-messages").hasClass()) {
			$(".div-messages").addClass("active");
		}
		setTimeout(function() {
			$(".div-messages").removeClass("active");
		}, 3000);
	}

	/*订单提交页面单选赋值*/
	$(".s-g-list-con .j-get-one .ect-select").click(function() {
		dist_span = $(this).find("span").text();
		dist_em = $(this).find("em").text();
		if($(this).parents(".j-show-goods-text").hasClass("show-time-con")) {
			$(this).parents(".j-show-goods-text").siblings(".distribution-time").find(".d-time-date").text(dist_span);
		} else {
			$(this).parents(".j-show-goods-text").siblings(".j-goods-dist").find(".t-goods1 span").text(dist_span);
			$(this).parents(".j-show-goods-text").siblings(".j-goods-dist").find(".t-goods1 em").text(dist_em);
		}
	});

	/*弹出配送方式*/
	$(".j-goods-dist").click(function() {
		document.addEventListener("touchmove", handler, false);
		$("body").addClass("show-dist-div");
	});

	/*弹出商品属性*/
	$(".j-goods-attr").on("click", function() {
		document.addEventListener("touchmove", handler, false);
		$("body").addClass("show-attr-div");
	});

	/*关闭商品详情弹出层*/
	$(".mask-filter-div,.show-div-guanbi").click(function() {
		document.removeEventListener("touchmove", handler, false);
		if($("body").hasClass("show-attr-div")) {
			$("body").removeClass("show-attr-div");
			return false;
		}
		if($("body").hasClass("show-coupon-div")) {
			$("body").removeClass("show-coupon-div");
			return false;
		}
		if($("body").hasClass("show-service-div")) {
			$("body").removeClass("show-service-div");
			return false;
		}
		if($("body").hasClass("show-dist-div")) {
			$("body").removeClass("show-dist-div");
			return false;
		}
		if($("body").hasClass("show-time-div")) {
			$("body").removeClass("show-time-div");
			return false;
		}
	});

	/*数字增减*/
	$(".div-num-disabled").find("input").attr("readonly", true);
	$(".div-num a").click(function() {
		if(!$(this).parent(".div-num").hasClass("div-num-disabled")) {
			if($(this).hasClass("num-less")) {
				num = parseInt($(this).siblings("input").val());
				min_num = parseInt($(this).attr("data-min-num"));
				if(num > min_num) {
					num -= 1;
					$(this).siblings("input").val(num);
				} else {
					d_messages("不能小于最小数量");
				}
				return false;
			}
			if($(this).hasClass("num-plus")) {
				num = parseInt($(this).siblings("input").val());
				max_num = parseInt($(this).attr("data-max-num"));
				if(num < max_num) {
					num += 1;
					$(this).siblings("input").val(num);
				} else {
					d_messages("不能大超过最大数量");
				}
				return false;
			}
		} else {
			d_messages("该商品不能增减");
		}
	});
	$(".div-num input").bind("change", function() {
		num = parseInt($(this).val());
		max_num = parseInt($(this).siblings(".num-plus").attr("data-max-num"));
		min_num = parseInt($(this).siblings(".num-less").attr("data-min-num"));
		if(num > max_num) {
			$(this).val(max_num);
			d_messages("不能大超过最大数量");
			return false;
		}
		if(num < min_num) {
			$(this).val(min_num);
			d_messages("不能小于最小数量");
			return false;
		}
	});

	$(".del").click(function() {
		if(!confirm('您确定要删除吗？')) {
			return false;
		}
		var url = 'index.php?m=default&c=user&a=clear_history';
		$.get(url, '', function(data) {
			if(1 == data.status) {
				location.reload();

			} else {
				alert("删除失败");
			}
		}, 'json');
		return false;
	});
	/*导航弹框*/
	$(".icon-gengduo").click(function() {
		$(".goods-nav").toggleClass("active");
	});

	$('.goods-header-nav-box li').click(function() {
		for(var i = 0; i < $('.goods-header-nav-box li').size(); i++) {
			if(this == $('.goods-header-nav-box li').get(i)) {
				$('.goods-header-nav-box li').eq(i).children('a').addClass('hover');
			} else {
				$('.goods-header-nav-box li').eq(i).children('a').removeClass('hover');
			}
		}
	})
});
// 本地存储
localData = {
	hname: location.hostname ? location.hostname : 'localStatus',
	isLocalStorage: window.localStorage ? true : false,
	dataDom: null,

	initDom: function() { //初始化userData
		if(!this.dataDom) {
			try {
				this.dataDom = document.createElement('input'); //这里使用hidden的input元素
				this.dataDom.type = 'hidden';
				this.dataDom.style.display = "none";
				this.dataDom.addBehavior('#default#userData'); //这是userData的语法
				document.body.appendChild(this.dataDom);
				var exDate = new Date();
				exDate = exDate.getDate() + 30;
				this.dataDom.expires = exDate.toUTCString(); //设定过期时间
			} catch(ex) {
				return false;
			}
		}
		return true;
	},
	set: function(key, value) {
		if(this.isLocalStorage) {
			window.localStorage.setItem(key, value);
		} else {
			if(this.initDom()) {
				this.dataDom.load(this.hname);
				this.dataDom.setAttribute(key, value);
				this.dataDom.save(this.hname)
			}
		}
	},
	get: function(key) {
		if(this.isLocalStorage) {
			return window.localStorage.getItem(key);
		} else {
			if(this.initDom()) {
				this.dataDom.load(this.hname);
				return this.dataDom.getAttribute(key);
			}
		}
	},
	remove: function(key) {
		if(this.isLocalStorage) {
			localStorage.removeItem(key);
		} else {
			if(this.initDom()) {
				this.dataDom.load(this.hname);
				this.dataDom.removeAttribute(key);
				this.dataDom.save(this.hname)
			}
		}
	}
}


/*单选*/
$(".j-get-one .ect-select-raise").click(function() {
	get_tjiantou = $(this).parent(".j-get-one").prev(".select-title").find(".t-jiantou");
	$(this).find("label").addClass("active").parent(".ect-select-raise").siblings().find("label").removeClass("active");
	get_tjiantou.find("em").text($(this).find("label").text());
	if($(this).hasClass("j-checkbox-all")) {
		get_tjiantou.removeClass("active");
	} else {
		get_tjiantou.addClass("active");
	}
	if($(this).parents("show-goods-attr")) { //赋值给goods-attr
		s_get_label = $(".show-goods-attr .s-g-attr-con").find("label.active"); //获取被选中label
		var get_text = '';
		s_get_label.each(function() {
			get_text += $(this).text() + "、";
		});
		$(".j-goods-attr").find(".t-goods1").text(get_text.substring(0, get_text.length - 1));
	}
});

/*订单提交页面单选赋值*/
$(".s-g-list-con .j-get-one .ect-select-raise").click(function() {
	dist_span = $(this).find("label>dd").html();
	t_goods1 = $(this).parents(".j-show-get-val").find(".t-goods1"); //需要获取弹出层em标签
	t_goods1.html(dist_span);

});
