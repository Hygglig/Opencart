<?php
class ControllerModuleHygglig extends Controller {
    private $error = array(); // This is used to set the errors, if any.
 
 
	public function install() {
		$this->load->model('extension/event');
		//Capture checkout
		$this->model_extension_event->addEvent('hygglig', 'catalog/controller/checkout/checkout/before', 'module/hygglig/checkoutRedirect');
		//Capture status change for orders
		$this->model_extension_event->addEvent('hygglig', 'catalog/controller/api/order/history/before', 'module/hygglig/orderStatus');
		
	}
	
	public function uninstall(){
		$this->load->model('extension/event');
		$this->model_extension_event->deleteEvent('hygglig');
	}
 
    public function index() {
        // Loading the language file of hygglig
        $this->load->language('module/hygglig'); 
     
        // Set the title of the page to the heading title in the Language file i.e., Hello World
        $this->document->setTitle($this->language->get('heading_title'));
     
        // Load the Setting Model  (All of the OpenCart Module & General Settings are saved using this Model )
        $this->load->model('setting/setting');
     
        // Start If: Validates and check if data is coming by save (POST) method
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            // Parse all the coming data to Setting Model to save it in database.
            $this->model_setting_setting->editSetting('hygglig', $this->request->post);
     
            // To display the success text on data save
            $this->session->data['success'] = $this->language->get('text_success');
     
            // Redirect to the Module Listing
            $this->response->redirect($this->url->link('module/hygglig', 'token=' . $this->session->data['token'], 'SSL'));
        }
     
        // Assign the language data for parsing it to view
        $data['heading_title'] = $this->language->get('heading_title');
     
        $data['text_edit']    = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');		
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_content_top'] = $this->language->get('text_content_top');
        $data['text_content_bottom'] = $this->language->get('text_content_bottom');      
        $data['text_column_left'] = $this->language->get('text_column_left');
        $data['text_column_right'] = $this->language->get('text_column_right');
     
        $data['entry_code'] = $this->language->get('entry_code');		
		
		// HYGGLIG FIELDS
		$data['entry_eid'] = $this->language->get('entry_eid');
		$data['entry_secret'] = $this->language->get('entry_secret');
		$data['entry_server'] = $this->language->get('entry_server');
		
        $data['entry_layout'] = $this->language->get('entry_layout');
        $data['entry_position'] = $this->language->get('entry_position');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');
     
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');
        $data['button_add_module'] = $this->language->get('button_add_module');
        $data['button_remove'] = $this->language->get('button_remove');
		
		$data['entry_sent_status'] = $this->language->get('entry_sent_status');
		$data['entry_cancel_status'] = $this->language->get('entry_cancel_status');
		

		
         
        // This Block returns the warning if any
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }
     
        // This Block returns the error code if any
        if (isset($this->error['code'])) {
            $data['error_code'] = $this->error['code'];
        } else {
            $data['error_code'] = '';
        }     
     
        // Making of Breadcrumbs to be displayed on site
        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        $data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('module/hygglig', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
          
        $data['action'] = $this->url->link('module/hygglig', 'token=' . $this->session->data['token'], 'SSL'); // URL to be directed when the save button is pressed
     
        $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'); // URL to be redirected when cancel button is pressed
              
		
		///////////////// HYGGLIG FIELDS ///////////////// HYGGLIG FIELDS /////////////////		
		
		// EID
		if (isset($this->request->post['hygglig_eid'])) {
            $data['hygglig_eid'] = $this->request->post['hygglig_eid'];
        } else {
            $data['hygglig_eid'] = $this->config->get('hygglig_eid');
        }   
		
		// SECRET
		if (isset($this->request->post['hygglig_secret'])) {
            $data['hygglig_secret'] = $this->request->post['hygglig_secret'];
        } else {
            $data['hygglig_secret'] = $this->config->get('hygglig_secret');
        }   
		
		// Test or live
		if (isset($this->request->post['hygglig_server'])) {
            $data['hygglig_server'] = $this->request->post['hygglig_server'];
        } else {
            $data['hygglig_server'] = $this->config->get('hygglig_server');
        }
		
        // Auto ship in M-order status
        if (isset($this->request->post['hygglig_shipping_status'])) {
            $data['hygglig_shipping_status'] = $this->request->post['hygglig_shipping_status'];
        } else {
            $data['hygglig_shipping_status'] = $this->config->get('hygglig_shipping_status');
        }
		
		// Auto cancel in M-order status
        if (isset($this->request->post['hygglig_cancel_status'])) {
            $data['hygglig_cancel_status'] = $this->request->post['hygglig_cancel_status'];
        } else {
            $data['hygglig_cancel_status'] = $this->config->get('hygglig_cancel_status');
        }
        
        
		//Get order statuses
		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
		
        $this->response->setOutput($this->load->view('module/hygglig.tpl', $data));

    }

	/* Function that validates the data when Save Button is pressed */
    protected function validate() {
 
        // Block to check the user permission to manipulate the module
        if (!$this->user->hasPermission('modify', 'module/hygglig')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
 
        // Block returns true if no error is found, else false if any error detected
        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
}
