$(function($) {
	var header = $('.main-left').outerWidth();
	$('.sidemenu-header').click(function() {
		$('.main-left').toggleClass('active');
		header = $('.main-left').outerWidth();
		$.cookie("menuheader", header);
		if ($(this).parent().hasClass('active')) {
			$('.main-content').addClass('active');
		} else {
			$('.main-content').removeClass('active');
		}
	});
	$(".nav-list").collapse("show");
	//tabmenu
	$('.main-sidemenu-cont li').bind('click',function() {
		$('.main-sidemenu-cont li').removeClass('active');
		$(this).addClass('active');
		$('.main-content-content').hide().eq($('.main-sidemenu-list li').index(this)).show();
	})
	$(".main-collapse").click(function() {
		$('.main-content-sidemenu').toggleClass("active");
		$('.main-content-content').toggleClass("select");
		$(this).toggleClass("active");
		//$('.layer-shade').toggleClass('active');
	});
	//cookie
	//menu-state
	if ($.cookie("menuheader") != undefined) {
		//cookie记录的index
		if ($.cookie("menuheader") <= 50) {
			$('.main-left').removeClass('active');
			$('.main-content').removeClass('active');
		}
	} else {
		$.cookie("menuheader", header);
	}
	//pagination
	$('.pagination li').click(function () {
		$(this).addClass('active').siblings().removeClass('active');
	})
	//menu-state
	$('.main-left').find('.nav-list li').click(function() {
		$('.main-left').find('.nav-list li').removeClass('active');
		var index = $('.main-left').find('.nav-list li').index(this);
		$.cookie("current", index);
		$(this).addClass("active");
	});
	//cookie记录的index
	if ($.cookie("current") != null) {
		var num = $.cookie("current");
		$('.main-left').find('.nav-list li').eq(num).addClass('active').siblings().removeClass('active');
	}
	//编辑
	$('.edit,.btn-addArticle').click(function() {
		//$('.layer-shade').show();
		layer.open({
			type: 2,
			title: '内容编辑',
			shadeClose: true,
			shade: 0,
			maxmin: true,
			area: ['893px', '600px'],
			content: 'comm-edit.html'
		});

	});
	$('.delete-btn').on('click', function () {
		layer.confirm('确认要删除吗？', {
			btn: ['确定', '取消'] //按钮
		}, function () {
			layer.msg('删除成功', {icon: 1});
		}, function () {
		});
	});
	//datapicker
	$.cxCalendar.defaults.type = "datetime";
	$.cxCalendar.defaults.format = "YYYY-MM-DD HH:mm:ss";
	$('.date ').cxCalendar({
		//type: 'datetime',
		//format: 'YYYY-MM-DD HH:mm:ss',
	});
//	select:multiple
	//移到右边
	$('.add').click(function(){
		//先判断是否有选中
		if(!$(".selectLeft option").is(":selected")){
			alert("请选择需要移动的选项")
		}
		//获取选中的选项，删除并追加给对方
		else{
			$('.selectLeft option:selected').appendTo('.selectRight');
		}
	});

	//移到左边
	$('.remove').click(function(){
		//先判断是否有选中
		if(!$(".selectRight option").is(":selected")){
			alert("请选择需要移动的选项")
		}
		else{
			$('.selectRight option:selected').appendTo('.selectLeft');
		}
	});
	//全部移到右边
	$('.add_all').click(function(){
		$('.selectLeft option').appendTo('.selectRight');
	});

	//全部移到左边
	$('.remove_all').click(function(){
		$('.selectRight option').appendTo('.selectLeft');
	});

	//双击选项
	$('.selectLeft').dblclick(function(){
		$("option:selected",this).appendTo('.selectRight');
	});
	//双击选项
	$('.selectRight').dblclick(function(){
		$("option:selected",this).appendTo('.selectLeft');
	});
});

$(function($) {
	//pjax
	$('#loading').hide();
	$(document).pjax('a', '#pjax-container');
});