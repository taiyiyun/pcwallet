// JavaScript Document
$(function(){
	var Wheight=$(window).height();
	var Wwidth=$(window).width();
	var scrollTop=$(window).scrollTop();
	var scrollleft=$(window).scrollLeft();
	$("#maskbg").height(Wheight);
	$("#maskbg").width(Wwidth);
	$("#maskbg").hide();
	$("#maskCon").hide();
	$("#maskSuccess").hide();
	$("#maskFalse").hide();
	$("#lottery").hide();
	$(".LoBtn").hide();
	//$("#maskCon").opsition()
	$(".touzhu").click(function(){
		      var login = $('#login').val();
			  if(login == 0){
				  
				  location.href="/user/login?url=http://dai.yuanbao.com/loan_index/detail/?id=" + $('#inid').val();
             
				  }else{
				  var number = $('#num').val();
				  $('#number').val(number);
				  $('#confirmnum').html(number);
				  if(!isNaN(number)){ 
					if(number <50)
					{
					   $("#errormsg").html("投标金额不得低于50");
		               showFaildiv();
					}
					if(number%50 != 0){
					   $("#errormsg").html("投标金额必须是50的倍数");
		               showFaildiv();
					}else{
						$("#maskbg").show();
						$("#maskCon").show();
						$("#maskbg").offset({top:scrollTop});
						//$("#maskCon").css({top:scrollTop+Wheight/3,left:Wwidth/3});
						$(window).scroll(function(){
						$("#maskbg").offset({top:$(window).scrollTop()});
						$("#maskCon").offset({top:$(window).scrollTop()});
						$("#maskCon").css({top:$(window).scrollTop()+Wheight/2,left:Wwidth/2});
						})
					}
				 }else{
					$("#errormsg").html("请填写正确的投标金额");
		            showFaildiv();
				 }
			 }
		})
	//关闭弹出层
	$(".closeBtn").click(function(){
		$("#maskCon").hide();
		$("#maskbg").hide();
		$("#maskSuccess").hide();
		$("#maskFalse").hide();
		})
	    $(".BidBackBtn").click(function(){$("#maskSuccess").hide();$("#maskFalse").hide(); $("#maskbg").hide();location.reload();})
		$(".fanhui").click(function(){$("#maskSuccess").hide();$("#maskFalse").hide(); $("#maskbg").hide();location.reload();})
	    $(".LoBtn").click(function(){location.href="/loan_index/lottery"});
	})

	function showSuccessdiv(flag)
	{
		var Wheight=$(window).height();
		var Wwidth=$(window).width();
		var scrollTop=$(window).scrollTop();
		var scrollleft=$(window).scrollLeft();
		if(flag){
			$("#lottery").show();
			$(".LoBtn").show();
		}else{
			$("#goback").removeClass("fanhui");
			$("#goback").addClass('BidBackBtn');
		}
		$("#maskSuccess").show();
		$("#maskSuccess").css({top:scrollTop+Wheight/2,left:Wwidth/2});
		$(window).scroll(function(){
			$("#maskbg").offset({top:$(window).scrollTop()});
			$("#maskSuccess").offset({top:$(window).scrollTop()});
			$("#maskSuccess").css({top:$(window).scrollTop()+Wheight/2,left:Wwidth/2});
		});
	}

	function showFaildiv()
	{
	    var Wheight=$(window).height();
		var Wwidth=$(window).width();
		var scrollTop=$(window).scrollTop();
		var scrollleft=$(window).scrollLeft();
		$("#maskFalse").show();
		$("#maskFalse").css({top:scrollTop+Wheight/2,left:Wwidth/2});
		$(window).scroll(function(){
			$("#maskbg").offset({top:$(window).scrollTop()});
			$("#maskFalse").offset({top:$(window).scrollTop()});
			$("#maskFalse").css({top:$(window).scrollTop()+Wheight/2,left:Wwidth/2});
		});
	}


	
	
