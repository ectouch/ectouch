var c=1;
function sendSms(){
	var mobile = $('#mobile_phone').val();
	var flag = $('#flag').val();
	var code = $("#sms_code").val();
	var captcha = $("#captcha").val();
	if(mobile.length == ''){
		alert('请填写手机号码');
		return false;
	}
	if(c==0){
		alert('发送频繁');
		return false;
	}
	RemainTime();
	$.post("index.php?m=default&c=sms&a=send&flag="+flag, { "mobile": mobile, "sms_code": code, "captcha": captcha },function(result){
		if (result.code==2){
			alert('验证码已成功发送');
		}else{
			if(result.msg){
				alert(result.msg);
			}else{
				alert('手机验证码发送失败');
			}
		}
	}, "json");
}

function register2(){
	var status = true;
	var mobile = $('#mobile_phone').val();
	var mobile_code = $('#mobile_code').val();
	var password = $('#password').val();
	if(mobile.length == ''){
		alert('请填写手机号码');
		return false;
	}
	if(mobile_code.length == ''){
		alert('请填写手机验证码');
		return false;
	}
	if(password.length == ''){
		alert('请填写登录密码');
		return false;
	}
	if(!$("#agreement").attr("checked")){
		alert('请阅读用户协议并同意');
		return false;
	}
	$.ajax({
		type: "POST",
		url: "index.php?m=default&c=sms&a=check",
		data: "mobile="+mobile+"&mobile_code="+mobile_code+"&flag=register",
		dataType: "json",
		async: false,
		success: function(result){
			if (result.code!=2){
				alert(result.msg);
				status = false;
			}
		}
	});
	return status;
}

function submitForget(){
	var status = true;
	var mobile = $('#mobile_phone').val();
    var mobile_code = $('#mobile_code').val();
	if(mobile.length == ''){
		alert('请填写手机号码');
		return false;
	}
	if(mobile_code.length == ''){
		alert('请填写手机验证码');
		return false;
	}
	$.ajax({
		type: "POST",
		url: "index.php?m=default&c=sms&a=check",
		data: "mobile="+mobile+"&mobile_code="+mobile_code,
		dataType: "json",
		async: false,
		success: function(result){
			if (result.code!=2){
				alert(result.msg);
				status = false;
			}
		}
	});
	return status;
}
		
var iTime = 59;
var Account;

function RemainTime(){
  //document.getElementById('zphone').disabled = true;
  var iSecond,sSecond="",sTime="";
  if (iTime >= 0){
    iSecond = parseInt(iTime%60);
    if (iSecond >= 0){
      sSecond = iSecond + "秒";
    }
    sTime=sSecond;
    if(iTime==0){
      clearTimeout(Account);
      sTime='获取手机验证码';
      iTime = 59;
	  c=1;
      //document.getElementById('zphone').disabled = false;
    }else{
	 
      Account = setTimeout("RemainTime()",1000);
      iTime=iTime-1;
	  c=0;
    }
  }else{
    sTime='没有倒计时';
  }
  //document.getElementById('zphone').value = sTime;
  $('#zphone').html(sTime);
}