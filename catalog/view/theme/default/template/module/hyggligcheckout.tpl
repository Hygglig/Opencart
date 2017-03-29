<?php echo $header; ?>
<div class="container">
  <ul class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
    <?php } ?>
  </ul>
  <?php if ($error_warning) { ?>
  <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
  </div>
  <?php } ?>
  <div class="row">
		<div id="content"><?php echo $content_top; ?>
			<div class="panel-group" id="accordion">
			<!-- Shipping etc -->
			<?php if ($shipping_required) { ?>
			<div class="col-sm-4">
				<!-- Show shipping on top of Hygglig -->				
				<div class="panel panel-default">
				  <div class="panel-heading">
					<h4 class="panel-title"><?php echo $text_hygglig_shipping_method; ?></h4>
				  </div>
				  <div class="panel-collapse collapse" id="collapse-shipping-method">
					<div class="panel-body"></div>
				  </div>
				</div>				
			</div>
			<?php } ?>
			<div class="<?php if ($shipping_required) { echo 'col-sm-8'; }else{ echo 'col-sm-offset-2 col-sm-8'; } ?>">
				<!-- HYGGLIG -->
				<div class="panel panel-default">
				  <div class="panel-heading">
					<h4 class="panel-title">Checkout</h4>
				  </div>
				  <div id="hyggligCheckout">
					<div class="panel-body"></div>
				  </div>
				</div>
				<!-- HYGGLIG -->
				<div class="panel panel-default" style="display:none;">
				  <div class="panel-heading">
					<h4 class="panel-title"><?php echo $text_checkout_option; ?></h4>
				  </div>
				  <div class="panel-collapse collapse" id="collapse-checkout-option">
					<div class="panel-body"></div>
				  </div>
				</div>
			</div>	
		</div>
	</div>
  </div>
</div>
<form id="shippingMethodOptions" style="display:none;" action="#">
	<input name="country_id" value="203"/>
	<input name="zone_id" value="3088"/>
	<input name="postcode" value="11745"/>
</form>
<form id="firstShippingMethod" style="display:none;" action="#"></form>
<form id="hyggligPredefinedValues" style="display:none;" action="#">
	<input name="payment_method" value="cod"/>
	<input name="comment" value=""/>
	<input name="agree" value="1"/>
</form>
<style>
	textarea.form-control{
		height:50px;
	}
</style>
<script type="text/javascript"><!--

//TODO - AUTO SELECT IF ALREADY CHOOSEN SHIPPING METHOD
// MOVE TO EXTERNAL JS LIBRARY
//On ready
$(document).ready(function() {

	$(document).on( "click",'button.btn.btn-danger.btn-xs',function(e) {
		$.ajax({
		url: 'index.php?route=total/shipping/quote',
		type: 'POST',
		data: $('#shippingMethodOptions').serialize(),
		success: function(result) {
			$shipping_methods = result['shipping_method'];
			//There was no shipping found to match cart items
			if(typeof $shipping_methods != 'undefined'){
				$.each($shipping_methods, function(index, $quote) {
					$.each($quote['quote'], function(index, $code) {
						//Store first shipping method in form and save it to do include shipping in calculation and then exit
						$('#firstShippingMethod').html('<input name="shipping_method" value="' + $code['code'] + '"/>');
						return false;
					});
					return false;
				});		
				$.ajax({
					url: 'index.php?route=total/shipping/shipping',
					type: 'POST',
					data: $('#firstShippingMethod').serialize(),
					success: function(result) {	
						$.get( "index.php?route=common/cart/info", function( data ) {
						  $("#cart").html($(data).unwrap().html());
						});
						byPassAddress(true);
					}
				});
			}
			else{
				//Forward without shipping
				byPassAddress(true);
			}
		}
	});	    	
		
	});

	$.ajax({
		url: 'index.php?route=total/shipping/quote',
		type: 'POST',
		data: $('#shippingMethodOptions').serialize(),
		success: function(result) {
			$shipping_methods = result['shipping_method'];
			//There was no shipping found to match cart items
			if(typeof $shipping_methods != 'undefined'){
				$.each($shipping_methods, function(index, $quote) {
					$.each($quote['quote'], function(index, $code) {
						//Store first shipping method in form and save it to do include shipping in calculation and then exit
						$('#firstShippingMethod').html('<input name="shipping_method" value="' + $code['code'] + '"/>');
						return false;
					});
					return false;
				});		
				$.ajax({
					url: 'index.php?route=total/shipping/shipping',
					type: 'POST',
					data: $('#firstShippingMethod').serialize(),
					success: function(result) {	
						$.get( "index.php?route=common/cart/info", function( data ) {
						  $("#cart").html($(data).unwrap().html());
						});
						ifGuestAutoForward();
					}
				});
			}
			else{
				//Forward without shipping
				ifGuestAutoForward();
			}
		}
	});	    
});
function ifGuestAutoForward(){	
	$.ajax({				
		url: 'index.php?route=checkout/guest',
		dataType: 'html',
		success: function(html) {
			byPassAddress();
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});	
}
function byPassAddress(update){
	<?php if ($shipping_required) { ?>
	$.ajax({
		url: 'index.php?route=checkout/shipping_method',
		dataType: 'html',
		complete: function() {
			$('#button-guest').button('reset');
		},
		success: function(html) {
			$('#collapse-shipping-method .panel-body').html(html);
			$('#collapse-shipping-method').parent().find('.panel-heading .panel-title').html('<a href="#collapse-shipping-method" data-toggle="collapse" data-parent="#accordion" class="accordion-toggle"><?php echo $text_hygglig_shipping_method; ?> <i class="fa fa-caret-down"></i></a>');
			$('a[href=\'#collapse-shipping-method\']').trigger('click');
			getPaymentMethods(update);
			},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
	<?php } else { ?>
		//Add fucntion if no shipping is Req
		getPaymentMethods();
	<?php } ?>
}
//Store shipping method
function saveShippingMethod($shippingCode){
	$('#firstShippingMethod').html('<input name="shipping_method" value="' + $shippingCode + '"/>');
	$.ajax({
		url: 'index.php?route=total/shipping/shipping',
		type: 'POST',
		data: $('#firstShippingMethod').serialize(),
		success: function(result) {	
			//Refresh ajax
			$.get( "index.php?route=common/cart/info", function( data ) {
			  $("#cart").html($(data).unwrap().html());
			});
			ifGuestAutoForward();
		}
	});	
}
//Get payment methods
function getPaymentMethods(update){
	$.ajax({
		url: 'index.php?route=checkout/payment_method',
		dataType: 'html',
		success: function(html) {
			chooseHyggligPaymentMethod(update);
		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});
}
//Choose Hygglig payment method
function chooseHyggligPaymentMethod(update){
	$.ajax({
        url: 'index.php?route=checkout/payment_method/save',
        type: 'post',
        data: $('#hyggligPredefinedValues').serialize(),
        dataType: 'json',
        success: function(json) {
            if (json['redirect']) {
                location = json['redirect'];
            } else {
				//Check if update is flagged
				if(update == true){
					//Get part of SRC == Token
					updateCheckout($('iframe').attr("src").split("c=")[1]);
				}else{
					$.ajax({
						url: 'index.php?route=checkout/confirm',
						dataType: 'html',
						success: function(html) {
								getCheckout();					
						},
						error: function(xhr, ajaxOptions, thrownError) {
							alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
						}
					});
				}	                
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });	
}
//Get intitial checkout
function getCheckout(){	
	$token = getUrlParameter('token');	
	if($token == null){
		$token = 0;
	}
	$.ajax({				
		url: 'index.php?route=module/hygglig/getCheckout',
		data: { Customer: "<?php if ($logged) { echo 1;}else{echo 0;}?>", Token: $token },
		dataType: 'html',
		success: function(html) {
			//Get HTML for checkout
			$('#hyggligCheckout').html('<div class="panel-body">' + html + '</div>');

		},
		error: function(xhr, ajaxOptions, thrownError) {
			alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
		}
	});	
}
//Function to update Hygglig checkout and Ajax cart
function updateCheckout(token){
	var sendData = {
		Token: token,
		Comment: $('#collapse-shipping-method textarea').val(),
	};

	
	$.ajax({
		url:'index.php?route=module/hygglig/updateCheckout',
		data: sendData,
		success: function(){	
			_hyggligCheckout.updateHygglig();	
			if($('#collapse-shipping-method').hasClass('in')){				
			}
			else{
				$('#collapse-shipping-method').collapse("show");
			}
		}
	});
}
//Update shipping
$(document).delegate('#button-shipping-method', 'click', function() {
	updateShippingCall();
});


function updateShippingCall(){	
	$.ajax({
        url: 'index.php?route=checkout/shipping_method/save',
        type: 'post',
        data: $('#collapse-shipping-method input[type=\'radio\']:checked, #collapse-shipping-method textarea'),
        dataType: 'json',
        beforeSend: function() {
        	$('#button-shipping-method').button('loading');
		},
        success: function(json) {
            $('.alert, .text-danger').remove();
            if (json['redirect']) {
                location = json['redirect'];
            } else if (json['error']) {
                $('#button-shipping-method').button('reset');
                if (json['error']['warning']) {
                    $('#collapse-shipping-method .panel-body').prepend('<div class="alert alert-danger">' + json['error']['warning'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');
                }
            } else {
				//Add method to form
				$('#firstShippingMethod').html('<input name="shipping_method" value="' + $('input[name=shipping_method]:checked').val() + '"/>');
				$.ajax({
					url: 'index.php?route=total/shipping/shipping',
					type: 'POST',
					data: $('#firstShippingMethod').serialize(),
					success: function(result) {					
						$.get( "index.php?route=common/cart/info", function( data ) {
						  $("#cart").html($(data).unwrap().html());
						  getPaymentMethods(true);
						});
					}
				});					
				$('#button-shipping-method').button('reset');
            }
        },
        error: function(xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });
}

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

//--></script>
<?php echo $footer; ?>
