<?php
class ControllerApiProduct extends Controller {
	public $version = 200; //version number
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
				
				//check if product already exists
				$getSKU = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku ='".$this->db->escape($this->request->post['sku'])."'");
				foreach ($getSKU->rows as $row) {
					$idExists = $row['product_id'];
					if($idExists) {
						$json['error']['warning'] = $this->language->get('product_already_exists');
						exit;
					}
				}
				
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
				
				//minimum version number
				if($this->request->post['min_ver'] < $this->version) {
					$json['error'] = $this->language->get('version_outdated');
				}
				// Add a new voucher if set
				if ((utf8_strlen($this->request->post['title']) < 1) || (utf8_strlen($this->request->post['title']) > 64)) {
					$json['error'] = $this->language->get('missing_title');
				}

				if (!$json) {
					$code = mt_rand();

					$this->session->data['product'][$code] = array(
						'title'             => $this->request->post['title']
					);

					$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($this->request->post['sku']) . "',
						quantity='999', 
						sku = '" . $this->db->escape($this->request->post['sku']) . "',
						status = '1',
						price = '" . $this->db->escape($this->request->post['base_price']) . "',
						image = 'catalog/{$this->db->escape($this->request->post['sku'])}.png',
						date_available = NOW(),
						date_added = NOW()");
					//get last insert id
					$product_id = $this->db->getLastId();

					//description
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '$product_id',
						name ='" . $this->db->escape($this->request->post['title']) . "',
						meta_title ='" . $this->db->escape($this->request->post['title']) . "',
						language_id = '$Language_id',
						description ='" . $this->db->escape(htmlspecialchars($this->request->post['description'])) . "'");
					
					//enable size options
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '$product_id',
						option_id ='$Size_option_id',
						required = '1'");
					$product_option_id_size = $this->db->getLastId();

					/** Insert variations **/
					foreach($this->request->post['variations'] as $key => $value) {
						$product_option_description_id_style = "";
						$product_option_description_id_color = "";
						$product_option_description_id_size = "";
						$insert_style = $this->db->escape($this->request->post['variations'][$key]['Style']);
						$insert_color = $this->db->escape($this->request->post['variations'][$key]['Color']);
						$insert_size = $this->db->escape($this->request->post['variations'][$key]['Size']);
						$insert_price = $this->db->escape($this->request->post['variations'][$key]['Price']);

						//get list of option_value_id
						$option_value_id_sizeArray = array();
						$getattr = $this->db->query("SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE option_id = '$Size_option_id' AND name='$insert_size'");
						foreach ($getattr->rows as $row) {
							//$option_value_id_sizeArray[$row['option_value_id']] = $row['name'];
							$product_option_description_id_size = $row['option_value_id'];
						}
						//if not available, insert it
						if(!$product_option_description_id_size) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '$Size_option_id'");
							$option_value_id = $this->db->getLastId();

							$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '$option_value_id',
							language_id = '$Language_id',
							option_id = '$Size_option_id',
							name = '$insert_size'");
							$product_option_description_id_size = $this->db->getLastId();
						}

						//insert variations
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_id = '$product_id',
						product_option_id ='$product_option_id_size',
						option_id = '$Size_option_id',
						option_value_id = '$product_option_description_id_size',
						quantity = '999',
						price_prefix = '+',
						price = '$insert_price'");
						
					}

					//insert product
					if(copy($this->request->post['image_url'], DIR_IMAGE."catalog/".$this->request->post['sku'].".png")) {
						//add images
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '$product_id',
							image = 'catalog/{$this->db->escape($this->request->post['sku'])}.png'");
					}

					//enable store
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '$product_id'");

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
