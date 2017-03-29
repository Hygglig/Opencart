<?php
class ControllerModuleHygglig extends Controller {
    public function index() {
        $this->load->language('module/hygglig'); // loads the language file of hygglig
        $data['heading_title'] = $this->language->get('heading_title'); // set the heading_title of the module
         $this->log->write('CATALOG');
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/hygglig.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/module/hygglig.tpl', $data);
        } else {
            return $this->load->view('module/hygglig.tpl', $data);
        }
    }
	//Function that activates or cancel order in Hygglig

	public function orderStatus(){
		//Get Hygglig order referens and Status
		$this->load->model('checkout/order');
		$status = $this->request->post['order_status_id'];
		$order_id = $this->request->get['order_id'];
		$data = $this->model_checkout_order->getOrder($order_id);

		$hygglig_shipping = Array();
		$hygglig_cancel = Array();
		$hygglig_shipping = $this->config->get('hygglig_shipping_status');
		$hygglig_cancel = $this->config->get('hygglig_cancel_status');

		$paymentMethod = $data['payment_method'];

		//Check if Status is one of two selected by merchant in Admin
		if(($status == $hygglig_shipping || $status == $hygglig_cancel) && $paymentMethod == "Hygglig"){
			$hyggligOrderNr = $data['payment_custom_field'];

			$hygglig_server = $this->config->get('hygglig_server');
			//Test or live?
			if($hygglig_server){
				$hygglig_server = 'https://sandbox.hygglig.com/Manage/api/CheckoutOrder/';
			}
			else{
				$hygglig_server = 'https://www.hygglig.com/Manage/api/CheckoutOrder/';
			}
			//Ship or cancel?
			if($status == 3){
				$hygglig_server .= 'SendOrder?';
			}
			else{
				$hygglig_server .= 'CancelOrder?';
			}

			$hygglig_merchantKey = $this->config->get('hygglig_eid');
			$hygglig_secretKey = $this->config->get('hygglig_secret');
			$hyggligChecksum = sha1(strtoupper($hyggligOrderNr . $hygglig_secretKey));

			//Create postdata
			$postData = array(
				'mId' => $hygglig_merchantKey,
				'ordernr' => $hyggligOrderNr,
				'mac' => $hyggligChecksum
			);

			//CurlIt
			$ch = curl_init($hygglig_server . 'mId=' . $postData['mId'] . '&ordernr=' . $postData['ordernr'] . '&mac=' . $postData['mac']);
			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json; encoding=utf-8'
				),
				CURLOPT_POSTFIELDS => json_encode($postData)
			));
			// Send the request
			$response = curl_exec($ch);
		}
	}


	//Interupt Opencart process to Checkout and redirect to Hygglig page
	public function checkoutRedirect(){
		header('"Location: ' . $this->response->redirect($this->url->link('module/hygglig/renderHyggligPage')) . '"');
	}

	//Render Hygglig checkout page
	public function renderHyggligPage(){

		//Hygglig var
		$hygglig['name'] = "Hygglig checkout";
		$hygglig['checkout_link'] = $this->url->link('module/hygglig/renderHyggligPage');

		// Validate cart has products and has stock.
		if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
			$this->response->redirect($this->url->link('checkout/cart'));
		}

		// Validate minimum quantity requirements.
		$products = $this->cart->getProducts();

		foreach ($products as $product) {
			$product_total = 0;
			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}
			if ($product['minimum'] > $product_total) {
				$this->response->redirect($this->url->link('checkout/cart'));
			}
		}

		//Test or live mode?
		$hygglig_server = $this->config->get('hygglig_server');
		if($hygglig_server){
			$hygglig_server = 'https://sandbox.hygglig.com';
		}
		else{
			$hygglig_server = 'https://www.hygglig.com';
		}
		$this->load->language('checkout/checkout');
		$this->document->setTitle($hygglig['name']);
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment.js');
		$this->document->addScript($hygglig_server . '/Checkout/Content/iframeResizer/iframeResizer.min.js');


		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart')
		);

		$data['breadcrumbs'][] = array(
			'text' => $hygglig['name'],
			'href' => $hygglig['checkout_link'],
		);

		$data['heading_title'] = $hygglig['name'];

		$data['text_checkout_option'] = sprintf($this->language->get('text_checkout_option'), 1);
		$data['text_checkout_account'] = sprintf($this->language->get('text_checkout_account'), 2);
		$data['text_checkout_payment_address'] = sprintf($this->language->get('text_checkout_payment_address'), 2);
		$data['text_checkout_shipping_address'] = sprintf($this->language->get('text_checkout_shipping_address'), 3);
		$data['text_checkout_shipping_method'] = sprintf($this->language->get('text_checkout_shipping_method'), 4);
		$data['text_hygglig_shipping_method'] = 'Frakt';


		if ($this->cart->hasShipping()) {
			$data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 5);
			$data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 6);
		} else {
			$data['text_checkout_payment_method'] = sprintf($this->language->get('text_checkout_payment_method'), 3);
			$data['text_checkout_confirm'] = sprintf($this->language->get('text_checkout_confirm'), 4);
		}

		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['logged'] = $this->customer->isLogged();

		if (isset($this->session->data['account'])) {
			$data['account'] = $this->session->data['account'];
		} else {
			$data['account'] = '';
		}

		$data['shipping_required'] = $this->cart->hasShipping();

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		//Address flag is set to true to bypass validation in Corefile
		$this->session->data['payment_address'] = true;

		//Type of payment is Hygglig
		$this->session->data['payment_method'] = 'hygglig';

		$this->response->setOutput($this->load->view('module/hyggligcheckout.tpl', $data));
	}
	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	public function customfield() {
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	//Return checkout HTML snippet
	public function getCheckout(){

		//Hygglig constants
		$hygglig_merchantKey = $this->config->get('hygglig_eid');
		$hygglig_secretKey = $this->config->get('hygglig_secret');
		$hygglig_server = $this->config->get('hygglig_server');

		//Test or live?
		if($hygglig_server){
			$hygglig_server = 'https://sandbox.hygglig.com/Checkout/api/Checkout/';
		}
		else{
			$hygglig_server = 'https://www.hygglig.com/Checkout/api/Checkout/';
		}

		$token = $this->request->get['Token'];

		if(strlen($token)>1){
			//Token is set - get iFrame from Token

			// Store the response as a TOKEN
			$token = strtoupper($token);

			//Next part is to ask for the iFrame
			$getiframeCheckSum = SHA1(strtoupper($token.$hygglig_secretKey));
			$getiframeAPI = $hygglig_server . 'GetIFrame';

			//Destroy previous $create
			$create = Array();

			//GetIFrame
			$create['MerchantKey'] = $hygglig_merchantKey;
			$create['Checksum'] = $getiframeCheckSum;
			$create['Token'] = $token;

			// Setup cURL
			$ch = curl_init($getiframeAPI);
			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json; encoding=utf-8'
				),
				CURLOPT_POSTFIELDS => json_encode($create)
			));

			// Send the request
			$response = curl_exec($ch);

			// Check for errors
			if($response === FALSE){
				die(curl_error($ch));
			}
			else
			{
				//Close Curl
				curl_close($ch);
				//Clear old result
				$responseData = null;
				//Echo result
				$responseData = json_decode($response,true,1024,JSON_BIGINT_AS_STRING);
				$text = $responseData["HtmlText"];
				//Return HTML
				echo $text;
			}
		}

		else{

			$isLoggedIn = $this->request->get['Customer'];

			//Create
			$create = Array();
			$cart = Array();


			//Load
			$this->load->model('checkout/order');

			//Get order info
			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

			//Total amount including tax
			$hygglig_totalamount = $order_info['total'];

			//Shipping excluding tax
			if(isset($this->session->data['shipping_method'])){
				$shippingArr = $this->session->data['shipping_method'];
			}


			//Total amount pre discounts
			if(isset($this->session->data['shipping_method'])){
				$hygglig_pre_discounts = (($this->cart->getSubTotal() + $shippingArr['cost'])*1.25);
			}
			else{
				$hygglig_pre_discounts = ($this->cart->getSubTotal()*1.25);
			}

			//Total discounts (discounts is pre tax)
			$hygglig_total_discount = ($hygglig_pre_discounts - $hygglig_totalamount)*0.8;

			$create['MerchantKey'] = $hygglig_merchantKey;
			$create['SuccessURL'] = $this->url->link('module/hygglig/success');
			$create['CheckoutURL'] = $this->url->link('module/hygglig/renderHyggligPage');
			$create['PushNotificationURL'] = $this->url->link('module/hygglig/push');
			$create['TermsURL'] = $this->url->link('module/hygglig/terms');
			$create['OrderReference'] = $this->session->data['order_id'];
			$create['Currency'] = "SEK";

			if($isLoggedIn){
				$address_id = $this->customer->getAddressId();
				$this->load->model('account/address');
				$address = $this->model_account_address->getAddress($address_id);
				$create['Email'] = $this->customer->getEmail();
				$create['Postcode'] = $address['postcode'];
			}

			$tempTotal = 0;

			//Products
			$products = $this->cart->getProducts();
			foreach ($products as $product) {
				//Price with ta
				$cart[] = array(
					'ArticleNumber' => $product['product_id'],
					'ArticleName' => strip_tags($product['name']),
					'Description' => $product['model'],
					'Quantity' => ($product['quantity']*100),
					'Price' => ($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))*100),
					'VAT' => (($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))-$product['price']) / $product['price']) * 10000
				);
				$tempTotal += intval($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))*intval($product['quantity']*10000));
			}

			//Discounts
			if($hygglig_total_discount>0){
				$cart[] = array(
					'ArticleNumber' => 88888,
					'ArticleName' => 'Rabatt',
					'Description' => 'Total rabatt',
					'Quantity' => 100,
					'Price' => -($this->tax->calculate($hygglig_total_discount, $product['tax_class_id'], $this->config->get('config_tax'))*100),
					'VAT' => (($this->tax->calculate($hygglig_total_discount, $product['tax_class_id'], $this->config->get('config_tax'))-$hygglig_total_discount) / $hygglig_total_discount) * 10000
				);
				$tempTotal -= ($this->tax->calculate($hygglig_total_discount, $product['tax_class_id'], $this->config->get('config_tax'))*10000);

			}

			//Shipping
			if(isset($this->session->data['shipping_method'])){
				if($shippingArr['cost'] > 0){
					$cart[] = array(
						'ArticleNumber' => 99999,
						'ArticleName' => strip_tags($shippingArr['title']),
						'Description' => $shippingArr['code'],
						'Quantity' => 100,
						'Price' => $this->tax->calculate($shippingArr['cost'], $shippingArr['tax_class_id'], $this->config->get('config_tax'))*100,
						'VAT' => (($this->tax->calculate($shippingArr['cost'], $shippingArr['tax_class_id'], $this->config->get('config_tax'))-$shippingArr['cost'])/$shippingArr['cost'])*10000
					);
					$tempTotal += $this->tax->calculate($shippingArr['cost'], $product['tax_class_id'], $this->config->get('config_tax'))*10000;
				}
			}

			foreach ($cart as $item) {
				$create['Articles'][] = $item;
			}

			$create['Checksum'] = (SHA1(round($tempTotal,-2) . strtoupper($hygglig_secretKey)));

			$startCheckoutAPI = $hygglig_server . 'StartCheckout';

			// Setup cURL
			$ch = curl_init($startCheckoutAPI);

			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json'
				),
				CURLOPT_POSTFIELDS => json_encode($create) ////////
			));

			// Send the request
			$response = curl_exec($ch);


			// Check for errors
			if($response === FALSE){
				die(curl_error($ch));
			}
			else
			{
				//Close Curl
				curl_close($ch);

				// Decode the response
				$responseData = json_decode($response, true);

				// Store the response as a TOKEN
				$token = strtoupper($responseData['Token']);

				//Next part is to ask for the iFrame
				$getiframeCheckSum = SHA1(strtoupper($token.$hygglig_secretKey));
				$getiframeAPI = $hygglig_server . 'GetIFrame';

				//Destroy previous $create
				$create = Array();

				//GetIFrame
				$create['MerchantKey'] = $hygglig_merchantKey;
				$create['Checksum'] = $getiframeCheckSum;
				$create['Token'] = $token;

				// Setup cURL
				$ch = curl_init($getiframeAPI);
				curl_setopt_array($ch, array(
					CURLOPT_POST => TRUE,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_HTTPHEADER => array(
						'Content-Type: application/json; encoding=utf-8'
					),
					CURLOPT_POSTFIELDS => json_encode($create)
				));

				// Send the request
				$response = curl_exec($ch);

				// Check for errors
				if($response === FALSE){
					die(curl_error($ch));
				}
				else
				{
					//Close Curl
					curl_close($ch);
					//Clear old result
					$responseData = null;
					//Echo result
					$responseData = json_decode($response,true,1024,JSON_BIGINT_AS_STRING);
				}
			}

			$text = $responseData["HtmlText"];
			//Return HTML
			echo $text;
		}
	}
	//Update Checkout
	public function updateCheckout(){
		//Get Token
		$token = $this->request->get['Token'];
		$comment = $this->request->get['Comment'];
		if(isset($token)){

			//Create
			$create = Array();
			$cart = Array();

			//Hygglig constants
			$hygglig_merchantKey = $this->config->get('hygglig_eid');
			$hygglig_secretKey = $this->config->get('hygglig_secret');
			$hygglig_server = $this->config->get('hygglig_server');

			//Test or live?
			if($hygglig_server){
				$hygglig_server = 'https://sandbox.hygglig.com/Checkout/api/Checkout/';
			}
			else{
				$hygglig_server = 'https://www.hygglig.com/Checkout/api/Checkout/';
			}

			//Load
			$this->load->model('checkout/order');

			$order_id = $this->session->data['order_id'];

			//Get order info
			$order_info = $this->model_checkout_order->getOrder($order_id);

			// Products
			$products = $this->cart->getProducts();

			//Total amount including tax
			$hygglig_totalamount = $order_info['total'];

			//Shipping excluding tax
			$shippingArr = $this->session->data['shipping_method'];

			//Total amount pre discounts
			$hygglig_pre_discounts = (($this->cart->getSubTotal() + $shippingArr['cost'])*1.25);

			//Total discounts (discounts is pre tax)
			$hygglig_total_discount = ($hygglig_pre_discounts - $hygglig_totalamount)*0.8;

			$tempTotal = 0;

			//Clear SQL from products
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "'");

			foreach ($products as $product) {
				//Price with tax
				$cart[] = array(
					'ArticleNumber' => $product['product_id'],
					'ArticleName' => strip_tags($product['name']),
					'Description' => $product['model'],
					'Quantity' => ($product['quantity']*100),
					'Price' => ($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))*100),
					'VAT' => (($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))-$product['price']) / $product['price']) * 10000
				);


				$this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', tax = '" . (float)($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))-$product['price']) . "', reward = '" . (int)$product['reward'] . "'");
				$order_product_id = $this->db->getLastId();
				foreach ($product['option'] as $option) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
				}

				$tempTotal += ($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'))*($product['quantity']*10000));
			}


			//Shipping
			if($shippingArr['cost'] > 0){
				$cart[] = array(
					'ArticleNumber' => 99999,
					'ArticleName' => strip_tags($shippingArr['title']),
					'Description' => $shippingArr['code'],
					'Quantity' => 100,
					'Price' => $this->tax->calculate($shippingArr['cost'], $shippingArr['tax_class_id'], $this->config->get('config_tax'))*100,
					'VAT' => (($this->tax->calculate($shippingArr['cost'], $shippingArr['tax_class_id'], $this->config->get('config_tax'))-$shippingArr['cost'])/$shippingArr['cost'])*10000
				);
				$tempTotal += $this->tax->calculate($shippingArr['cost'], $product['tax_class_id'], $this->config->get('config_tax'))*10000;
			}

			//SQL VAR
			$sqlTot = $tempTotal/10000;
			$sqlTotex =  $sqlTot * 0.8;
			$sqlTax = $sqlTot * 0.2;

			//Update SQL - SHIPPING
			$this->db->query("UPDATE `oc_order_total` SET `title` = '" . $shippingArr['title'] . "', `value` = '" . $shippingArr['cost'] . "' WHERE `code` = 'shipping' AND `order_id` = '" . $order_id . "'");
			$this->db->query("UPDATE `oc_order_total` SET `value` = '" . $sqlTot . "' WHERE `code` = 'total' AND `order_id` = '" . $order_id . "'");
			$this->db->query("UPDATE `oc_order_total` SET `value` = '" . $sqlTax . "' WHERE `code` = 'tax' AND `order_id` = '" . $order_id . "'");
			$this->db->query("UPDATE `oc_order_total` SET `value` = '" . $sqlTotex . "' WHERE `code` = 'sub_total' AND `order_id` = '" . $order_id . "'");
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET shipping_code = '" . $shippingArr['code'] . "', shipping_method = '" . $shippingArr['title'] . "',total = '" . $sqlTot . "' WHERE order_id = '" . $order_id . "'");

			//..and add comment to order
			if(isset($comment)){
				$this->db->query("UPDATE `" . DB_PREFIX . "order` SET comment = '" . $comment . "' WHERE order_id = '" . $order_id . "'");
			}



			foreach ($cart as $item) {
				$create['Articles'][] = $item;
			}

			$updateCheckoutAPI = ($hygglig_server . 'UpdateOrder');

			$create['MerchantKey'] = $hygglig_merchantKey;
			$create['Checksum'] = (SHA1(round($tempTotal,-2) . strtoupper($hygglig_secretKey)));
			$create['Token'] = strtoupper($token);

			// Setup cURL
			$ch = curl_init($updateCheckoutAPI);
			curl_setopt_array($ch, array(
			CURLOPT_POST => TRUE,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json'
				),
				CURLOPT_POSTFIELDS => json_encode($create)
				)
			);

			// Send the request
			$response = curl_exec($ch);
			print_r($response);
			print_r($this->session->data);
		}
	}
	//Create callback that process order.
	public function push(){

		//Load model and retrive values
		$this->load->model('checkout/order');
		$hyggligOrderRef = $this->request->post['orderNumber'];
		$order_id = $this->request->post['orderReference'];

		//Load stored order info
		$data = $this->model_checkout_order->getOrder($order_id);

		//Check if payment is already registerd
		if($data['payment_code'] == 'hygglig' || $data['payment_method'] == 'Hygglig'){
			//Payment already reg do nothing..-
		}
		else{
			//New payment
			$hyggligPrefix = "Hygglig: ";
			$data['invoice_prefix'] = $hyggligPrefix;

			//Update Firstname
			$data['firstname'] = $this->request->post['firstName'];
			$data['payment_firstname'] = $this->request->post['firstName'];
			$data['shipping_firstname']= $this->request->post['firstName'];

			//Update Lastname
			$data['lastname'] = $this->request->post['lastName'];
			$data['payment_lastname'] = $this->request->post['lastName'];
			$data['shipping_lastname']= $this->request->post['lastName'];

			//Update Telephone
			$data['telephone'] = $this->request->post['phoneNumber'];

			//Update Email
			$data['email'] = $this->request->post['email'];

			//Update Address
			$data['payment_address_1'] = $this->request->post['address'];
			$data['shipping_address_1'] = $this->request->post['address'];

			//Update City
			$data['payment_city'] = $this->request->post['city'];
			$data['shipping_city'] = $this->request->post['city'];

			//Update Postcode
			$data['payment_postcode'] = $this->request->post['postalCode'];
			$data['shipping_postcode'] = $this->request->post['postalCode'];

			//Set country to Sweden
			$data['payment_country_id'] = 203;
			$data['shipping_country_id'] = 203;
			$data['payment_country'] = "Sweden";
			$data['shipping_country'] = "Sweden";

			//Clear fields
			$data['payment_zone_id'] = '';
			$data['payment_zone'] = '';
			$data['payment_zone_code'] = '';
			$data['shipping_zone_id'] = '';
			$data['shipping_zone'] = '';
			$data['shipping_zone_code'] = '';

			//Update order
			$this->db->query("UPDATE `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . 1 . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', custom_field = '" . $this->db->escape(json_encode($data['custom_field'])) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(json_encode($data['payment_custom_field'])) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(json_encode($data['shipping_custom_field'])) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', payment_custom_field = '" . $hyggligOrderRef . "', payment_method = '" . "Hygglig" . "', payment_code= '" . "hygglig" . "', commission = '" . (float)$data['commission'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

			//Add history and send e-mail
			$this->model_checkout_order->addOrderHistory($order_id, 15,"Paid with Hygglig - " . $this->request->post['orderNumber'],true);
		}

	}
	//Show success page
	public function success(){

		//Load Hygglig Success
		$token = $this->request->get['token'];
		if(isset($token)){

			// Store the response as a TOKEN
			$token = strtoupper($token);
			$hygglig_merchantKey = $this->config->get('hygglig_eid');
			$hygglig_secretKey = $this->config->get('hygglig_secret');
			$hygglig_server = $this->config->get('hygglig_server');

			//Test or live?
			if($hygglig_server){
				$hygglig_server = 'https://sandbox.hygglig.com/Checkout/api/Checkout/';
			}
			else{
				$hygglig_server = 'https://www.hygglig.com/Checkout/api/Checkout/';
			}
			//Next part is to ask for the iFrame
			$getiframeCheckSum = SHA1(strtoupper($token.$hygglig_secretKey));
			$getiframeAPI = $hygglig_server . 'GetIFrame';

			//Destroy previous $create
			$create = Array();

			//GetIFrame
			$create['MerchantKey'] = $hygglig_merchantKey;
			$create['Checksum'] = $getiframeCheckSum;
			$create['Token'] = $token;

			// Setup cURL
			$ch = curl_init($getiframeAPI);
			curl_setopt_array($ch, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json; encoding=utf-8'
				),
				CURLOPT_POSTFIELDS => json_encode($create)
			));

			// Send the request
			$response = curl_exec($ch);

			// Check for errors
			if($response === FALSE){
				die(curl_error($ch));
			}
			else
			{
				//Close Curl
				curl_close($ch);
				//Clear old result
				$responseData = null;
				//Echo result
				$responseData = json_decode($response,true,1024,JSON_BIGINT_AS_STRING);
				$text = $responseData["HtmlText"];
			}


			$this->load->language('checkout/success');

			if (isset($this->session->data['order_id'])) {
				$this->cart->clear();

				// Add to activity log
				$this->load->model('account/activity');

				if ($this->customer->isLogged()) {
					$activity_data = array(
						'customer_id' => $this->customer->getId(),
						'name'        => $this->customer->getFirstName() . ' ' . $this->customer->getLastName(),
						'order_id'    => $this->session->data['order_id']
					);

					$this->model_account_activity->addActivity('order_account', $activity_data);
				}

				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
				unset($this->session->data['guest']);
				unset($this->session->data['comment']);
				unset($this->session->data['order_id']);
				unset($this->session->data['coupon']);
				unset($this->session->data['reward']);
				unset($this->session->data['voucher']);
				unset($this->session->data['vouchers']);
				unset($this->session->data['totals']);
			}

			$this->document->setTitle($this->language->get('heading_title'));

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_basket'),
				'href' => $this->url->link('checkout/cart')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_checkout'),
				'href' => $this->url->link('checkout/checkout', '', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_success'),
				'href' => $this->url->link('checkout/success')
			);

			$data['heading_title'] = $this->language->get('heading_title');

			if ($this->customer->isLogged()) {
				$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', true), $this->url->link('account/order', '', true), $this->url->link('account/download', '', true), $this->url->link('information/contact'));
			} else {
				$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
			}

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			$data['hyggligHtml'] = $text;

			$this->response->setOutput($this->load->view('module/hyggligsuccess.tpl', $data));
		}

	}


}
