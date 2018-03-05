/*
 * Created with Sublime Text 2.
 * license: http://www.lovewebgames.com/jsmodule/index.html
 * User: 田想兵
 * Date: 2015-03-16
 * Time: 20:27:54
 * Contact: 55342775@qq.com
 */
;
(function(root, factory) {
	//amd
	if (typeof define === 'function' && define.amd) {
		define(['$'], factory);
	} else if (typeof exports === 'object') { //umd
		module.exports = factory();
	} else {
		root.Dialog = factory(window.Zepto || window.jQuery || $);
	}
})(this, function($) {
	$.fn.Dialog = function(settings) {
		var list = [];
		$(this).each(function() {
			var dialog = new Dialog();
			var options = $.extend({
				trigger: $(this)
			}, settings);
			dialog.init(options);
			list.push(dialog);
		});
		return list;
	};
	$.Dialog = function(settings) {
		if (settings.type === "alert") {
			var alert = new Dialog();
			var html = '<div class="ui-alert-title">' + settings.content + '</div>';
			var action = '';
			if (settings.button) {
				if (typeof settings.button == 'boolean') {
					settings.button = '确定';
				};
				action = '<p class="ui-dialog-action"><button class="ui-alert-submit  js-dialog-close">' + settings.button + '</button></p>';
			} else if (!settings.timer) {
				settings.timer = 3000;
			}
			html += action;
			var alertOptions = $.extend({
				target: html,
				animate: true,
				show: true,
				mask: true,
				className: "ui-alert",
				afterHide: function(c) {
					this.dispose();
					settings.callback && settings.callback();
				}
			}, settings);
			alert.init(alertOptions);
			if (settings.timer) {
				setTimeout(function() {
					alert.dispose();
					settings.callback && settings.callback();
				}, settings.timer);
			}
			alert.touch(alert.mask, function() {
				alert.hide();
				settings.callback && settings.callback();
			});
		}
		if (settings.type === "confirm") {
			var dialog = new Dialog();
			var html = '<div class="ui-confirm-title b-color-f">' + settings.content + '</div>';
			var action = '';
			if (!settings.buttons) {
				settings.buttons = [{
					'yes': '确定'
				}, {
					'no': '取消'
				}];
			};
			var btnstr = '';
			for (var i = 0, l = settings.buttons.length; i < l; i++) {
				var item = settings.buttons[i];
				if (item.yes) {
					btnstr += '<td><button class="ui-confirm-submit " data-type="yes">' + item.yes + '</button></td>';
				}
				if (item.no) {
					btnstr += '<td><button class="ui-confirm-no" data-type="no">' + item.no + '</button></td>';
				}
				if (item.close) {
					btnstr += '<td><button class="ui-confirm-close js-dialog-close" data-type="close">' + item.close + '</button></td>';
				}
			}
//			action = '<table class="ui-dialog-action"><tr>' + btnstr + '</tr></table>';
			html += action;
			var options = $.extend({
				target: html,
				animate: true,
				show: true,
				fixed:true,
				mask: true,
				className: "ui-alert",
				afterHide: function(c) {
					this.dispose();
				},
				beforeShow: function(c) {
					dialog.touch($('.ui-confirm-submit', c), function() {
						document.removeEventListener("touchmove", handler, false);
						settings.callback && settings.callback.call(dialog, 'yes', c);
					});
					dialog.touch($('.ui-confirm-no', c), function() {
						document.removeEventListener("touchmove", handler, false);
						settings.callback && settings.callback.call(dialog, 'no', c);
					});
					dialog.touch($('.ui-confirm-close', c), function() {
						settings.callback && settings.callback.call(dialog, 'close', c);
					});
				}
			}, settings);
			dialog.init(options);
		}
	};
	/*alert*/
	$.alert = function(content, button, callback, timer, settings) {
			var options = {};
			var defaults = {
				zIndex: 100,
				type: 'alert'
			};
			if (typeof content == 'object') {
				options = $.extend(defaults, content);
			} else {
				options = $.extend(defaults, {
					content: content,
					button: button,
					timer: timer,
					callback: callback
				});
			}
			$.Dialog($.extend(options, settings));
		}
		/*
		buttons :[{yes:"确定"},{no:'取消'},{close:'关闭'}]
		*/
	$.confirm = function(content, buttons, callback, settings) {
		var options = {};
		var defaults = {
			zIndex: 100,
			type: 'confirm'
		};
		if (typeof content == 'object') {
			options = $.extend(defaults, content);
		} else {
			options = $.extend(defaults, {
				content: content,
				buttons: buttons,
				callback: callback
			});
		}
		$.Dialog($.extend(options, settings));
	}
	var Dialog = function() {
		var rnd = Math.random().toString().replace('.', '');
		this.id = 'dialog_' + rnd;
		this.settings = {};
		this.settings.closeTpl = $('<section class="goods-show-title  of-hidden padding-all b-color-f" style="border-bottom:1px solid #F6F6F9"><h3 class="fl g-c-title-h3">配送时间</h3><div class="fr filter-date-list"><a class="ui-confirm-no">取消</a><a class=" ui-confirm-submit">确定</a></div></section>');
		this.settings.titleTpl = $('<div class="ui-dialog-title"></div>');
		this.timer = null;
		this.showed = false;
		this.mask = $();
	}
	Dialog.prototype = {
		init: function(settings) {
			document.addEventListener("touchmove", handler, false);
			var _this = this;
			this.settings = $.extend({
				fixed: false//是否固定位置，
			}, this.settings, settings);
			if (this.settings.mask) {
				this.mask = $('<div class="ui-dialog-mask"/>');
				$('body').append(this.mask);
			}
			$('body').append('<div class="ui-dialog" id="' + this.id + '"></div>');
			this.dialogContainer = $('#' + this.id);
			var zIndex = this.settings.zIndex || 10;
			this.dialogContainer.css({
				'zIndex': zIndex
			});
			if (this.settings.className) {
				this.dialogContainer.addClass(this.settings.className);
			};
			this.mask.css({
				'zIndex': zIndex - 1
			});
			if (this.settings.closeTpl) {
				this.dialogContainer.append(this.settings.closeTpl);
			}
			if (this.settings.title) {
				this.dialogContainer.append(this.settings.titleTpl);
				this.settings.titleTpl.html(this.settings.title);
			}
			this.bindEvent();
			if (this.settings.show) {
				this.show();
			}
		},
		touch: function(obj, fn) {
			var move;
			$(obj).on('click', click);

			function click(e) {
				return fn.call(this, e);
			}
			$(obj).on('touchmove', function(e) {
				move = true;
			}).on('touchend', function(e) {
				e.preventDefault();
				if (!move) {
					var returnvalue = fn.call(this, e, 'touch');
					if (!returnvalue) {
						e.preventDefault();
						e.stopPropagation();
					}
				}
				move = false;
			});
		},
		bindEvent: function() {
			var _this = this;
			if (this.settings.trigger) {
				$(this.settings.trigger).click(function() {
					_this.show()
				});
				_this.touch($(this.settings.trigger), function() {
					_this.show()
				});
			};
			$(this.dialogContainer).on('click', '.js-dialog-close', function() {
					_this.hide();
					return false;
				})
				// $(window).resize(function() {
				// 	_this.setPosition();
				// });
				// $(window).scroll(function() {
				// 	_this.setPosition();
				// })
			$(document).keydown(function(e) {
				if (e.keyCode === 27 && _this.showed) {
					_this.hide();
				}
			});
			$(this.dialogContainer).on('hide', function() {
				_this.hide();
			})
		},
		dispose: function() {
			this.dialogContainer.remove();
			this.mask.remove();
			this.timer && clearInterval(this.timer);
		},
		hide: function() {
			var _this = this;
			if (_this.settings.beforeHide) {
				_this.settings.beforeHide.call(_this, _this.dialogContainer);
			}
			this.showed = false;
			this.mask.hide();
			this.timer && clearInterval(this.timer);
			if (this.settings.animate) {
				this.dialogContainer.removeClass('zoomIn').addClass("zoomOut");
				setTimeout(function() {
					_this.dialogContainer.hide();
					if (typeof _this.settings.target === "object") {
						$('body').append(_this.dialogContainer.hide());
					}
					if (_this.settings.afterHide) {
						_this.settings.afterHide.call(_this, _this.dialogContainer);
					}
				}, 500);
			} else {
				this.dialogContainer.hide();
				if (typeof this.settings.target === "object") {
					$('body').append(this.dialogContainer)
				}
				if (this.settings.afterHide) {
					this.settings.afterHide.call(this, this.dialogContainer);
				}
			}
		},
		show: function() {
			if (typeof this.settings.target === "string") {
				if (/^(\.|\#\w+)/gi.test(this.settings.target)) {
					this.dailogContent = $(this.settings.target);
				} else {
					this.dailogContent = $('<div>' + this.settings.target + '</div>')
				}
			} else {
				this.dailogContent = this.settings.target;
			}
			this.mask.show();
			this.dailogContent.show();
//			this.height = this.settings.height || 'auto' //this.dialogContainer.height();
//			this.width = this.settings.width || 'auto' //this.dialogContainer.width();
			this.dialogContainer.append(this.dailogContent).show().css({
				height: this.height,
				width: this.width
			});
			if (this.settings.beforeShow) {
				this.settings.beforeShow.call(this, this.dialogContainer);
			}
			this.showed = true;
			$(this.settings.trigger).blur();

			this.setPosition();
			var _this = this;
			// $.alert(this.settings.clientWidth)
			this.timer && clearInterval(this.timer);
			if (this.settings.fixed) {
				this.timer = setInterval(function() {
					_this.setPosition();
				}, 1000);
			}
			if (this.settings.animate) {
				this.dialogContainer.addClass('zoomIn').removeClass('zoomOut').addClass('animated');
			}
		},
		setPosition: function() {
			if (this.showed) {
				var _this = this;
				this.dialogContainer.show();
				this.height = this.settings.height;
				this.width = this.settings.width;
				if (isNaN(this.height)) {
					this.height = (this.dialogContainer.outerHeight && this.dialogContainer.outerHeight()) || this.dialogContainer.height();
				}
				if (isNaN(this.width)) {
					this.width = (this.dialogContainer.outerWidth && this.dialogContainer.outerWidth()) || this.dialogContainer.width();
				}
				var clientHeight = this.settings.clientHeight || document.documentElement.clientHeight || document.body.clientHeight;
				var clientWidth = this.settings.clientWidth || document.documentElement.clientWidth || document.body.clientWidth;
				var ml = this.width / 2;
				var mt = this.height / 2;
				var left = clientWidth / 2 - ml;
				var top = clientHeight / 2 - mt;
				left = Math.floor(Math.max(0, left));
				top = Math.floor(Math.max(0, top));
				var position = 'absolute';
				if(_this.settings.fixed){
					position='fixed';
				}
				_this.dialogContainer.css({
					position: position
//					top: top,
//					left: left
				});
			}
		}
	}
	return Dialog;
});