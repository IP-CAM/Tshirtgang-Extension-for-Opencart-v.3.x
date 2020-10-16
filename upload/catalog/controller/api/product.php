<?php
class ControllerApiProduct extends Controller {
	public function index() {
		$this->load->language('api/product');

		// Delete past voucher in case there is an error
		unset($this->session->data['product']);

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/total/product');

			if (isset($this->request->post['product'])) {
				$product = $this->request->post['product'];
			} else {
				$product = '';
			}

			$product_info = $this->model_extension_total_product->getProduct($product);

			if ($product_info) {
				$this->session->data['product'] = $this->request->post['product'];

				$json['success'] = $this->language->get('text_success');
			} else {
				$json['error'] = $this->language->get('error_product');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function add() {
		$this->load->language('api/product');

		$json = array();

		
		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			// Add keys for missing post vars
			$keys = array(
				'title',
				'description'
			);

			foreach ($keys as $key) {
				if (!isset($this->request->post[$key])) {
					$this->request->post[$key] = '';
				}
			}

			if (isset($this->request->post['product'])) {
				echo "Request post product";
				$this->session->data['product'] = array();

				foreach ($this->request->post['product'] as $product) {
					if (isset($product['title']) && isset($product['description'])) {
						$this->session->data['product'][$product['title']] = array(
							'model'             => $product['title']
						);
					}
				}

				$json['success'] = $this->language->get('text_cart');

				unset($this->session->data['shipping_method']);
				unset($this->session->data['shipping_methods']);
				unset($this->session->data['payment_method']);
				unset($this->session->data['payment_methods']);
			} else {
				$Language_id = 1;
				$Style_option_id = "";
				$Color_option_id = "";
				$Size_option_id = "";
				/* ----- WORKS ----- Uncomment after finished testing.
				//check if product already exists
				$getSKU = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku ='".$this->db->escape($this->request->post['sku'])."'");
				foreach ($getSKU->rows as $row) {
					$idExists = $row['product_id'];
					if($idExists) {
						$json['error']['warning'] = $this->language->get('product_already_exists');
						exit;
					}
				}
				*/
				//get option ids
				$getattr = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_description WHERE name = 'Style'");
				foreach ($getattr->rows as $row) {
					$Style_option_id = $row['option_id'];
				}
				if(!$Style_option_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "option SET type = 'select'");
					//get last insert id
					$select_option_id = $this->db->getLastId();

					$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '$select_option_id',
					language_id = '$Language_id',
					name = 'Style'");
					//get last insert id
					$getattr = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_description WHERE name = 'Style'");
					foreach ($getattr->rows as $row) {
						$Style_option_id = $row['option_id'];
					}
				}
				
				$getattr = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_description WHERE name = 'Color'");
				foreach ($getattr->rows as $row) {
					$Color_option_id = $row['option_id'];
				}
				if(!$Color_option_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "option SET type = 'select'");
					//get last insert id
					$select_option_id = $this->db->getLastId();

					$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '$select_option_id',
					language_id = '$Language_id',
					name = 'Color'");
					//get last insert id
					$Color_option_id = $this->db->getLastId();
				}
				$getattr = $this->db->query("SELECT option_id FROM " . DB_PREFIX . "option_description WHERE name = 'Size'");
				foreach ($getattr->rows as $row) {
					$Size_option_id = $row['option_id'];
				}
				if(!$Size_option_id) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "option SET type = 'select'");
					//get last insert id
					$select_option_id = $this->db->getLastId();

					$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '$select_option_id',
					language_id = '$Language_id',
					name = 'Size'");
					//get last insert id
					$Size_option_id = $this->db->getLastId();
				}
				

				// Add a new voucher if set
				if ((utf8_strlen($this->request->post['title']) < 1) || (utf8_strlen($this->request->post['title']) > 64)) {
					$json['error']['from_name'] = $this->language->get('error_from_name');
				}
				/*
				if (($this->request->post['amount'] < $this->config->get('config_voucher_min')) || ($this->request->post['amount'] > $this->config->get('config_voucher_max'))) {
					$json['error']['amount'] = sprintf($this->language->get('error_amount'), $this->currency->format($this->config->get('config_voucher_min'), $this->session->data['currency']), $this->currency->format($this->config->get('config_voucher_max'), $this->session->data['currency']));
				}
				*/

				if (!$json) {
					$code = mt_rand();
					/*
					$code = mt_rand();
					
					$this->session->data['vouchers'][$code] = array(
						'code'             => $code,
						'description'      => sprintf($this->language->get('text_for'), $this->currency->format($this->currency->convert($this->request->post['amount'], $this->session->data['currency'], $this->config->get('config_currency')), $this->session->data['currency']), $this->request->post['to_name']),
						'to_name'          => $this->request->post['to_name'],
						'to_email'         => $this->request->post['to_email'],
						'from_name'        => $this->request->post['from_name'],
						'from_email'       => $this->request->post['from_email'],
						'voucher_theme_id' => $this->request->post['voucher_theme_id'],
						'message'          => $this->request->post['message'],
						'amount'           => $this->currency->convert($this->request->post['amount'], $this->session->data['currency'], $this->config->get('config_currency'))
					);
					*/
					$this->session->data['product'][$code] = array(
						'title'             => $this->request->post['title']
					);

					//$this->db->query("INSERT INTO `" . DB_PREFIX . "squareup_token` SET customer_id='" . (int)$customer_id . "', sandbox='" . (int)$sandbox . "', token='" . $this->db->escape($data['id']) . "', brand='" . $this->db->escape($data['card_brand']) . "', ends_in='" . (int)$data['last_4'] . "', date_added=NOW()");
					//echo "INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($this->request->post['title']) . "'";
					$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($this->request->post['sku']) . "',
						quantity='999', 
						sku = '" . $this->db->escape($this->request->post['sku']) . "',
						status = '1',
						price = '20.99',
						date_available = NOW(),
						date_added = NOW()");
					//get last insert id
					$product_id = $this->db->getLastId();

					//description
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '$product_id',
						name ='" . $this->db->escape($this->request->post['title']) . "',
						meta_title ='" . $this->db->escape($this->request->post['title']) . "',
						language_id = '$Language_id',
						description ='" . $this->db->escape($this->request->post['description']) . "'");
					

					/** Insert Sizing **/
					//get list of option_value_id
					$option_value_id_sizeArray = array();
					$getattr = $this->db->query("SELECT option_value_id, name FROM " . DB_PREFIX . "option_value_description WHERE option_id = '$Size_option_id'");
					foreach ($getattr->rows as $row) {
						$option_value_id_sizeArray[$row['option_value_id']] = $row['name'];
					}
					$sizes = array("Small", "Medium", "Large");
					//enable size options
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '$product_id',
						option_id ='$Size_option_id',
						required = '1'");
					$product_option_id_size = $this->db->getLastId();

					print_r($option_value_id_sizeArray);
					//variations
					foreach($sizes as $size_id => $size_value) {
						$product_option_id = array_search($size_value, $option_value_id_sizeArray);
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_id = '$product_id',
						product_option_id ='$product_option_id_size',
						option_id = '$Size_option_id',
						option_value_id = '$product_option_id',
						quantity = '999',
						price = '10.00'");
					}
					$json['success'] = $this->language->get('text_cart');

					unset($this->session->data['shipping_method']);
					unset($this->session->data['shipping_methods']);
					unset($this->session->data['payment_method']);
					unset($this->session->data['payment_methods']);
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
