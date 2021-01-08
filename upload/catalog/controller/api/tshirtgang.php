<?php
class ControllerApiTshirtgang extends Controller {
	public $version = 202; //version number
	public function index() {
		$this->load->language('api/tshirtgang');

		// Delete past voucher in case there is an error
		unset($this->session->data['product']);

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$this->load->model('extension/total/tshirtgang');

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
		$this->load->language('api/tshirtgang');

		$json = array();

		
		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');
		} else {
			// Add keys for missing post vars
			$keys = array();

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
				$Language_id = $this->config->get('config_language_id');
				$Style_option_id = "";
				$Color_option_id = "";
				$Size_option_id = "";
				
				//check if product already exists
				$getSKU = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE sku ='".$this->db->escape($this->request->post['sku'])."'");
				foreach ($getSKU->rows as $row) {
					$idExists = $row['product_id'];
					if($idExists) {
						//echo "product already exists";
						echo $json['error']['warning'] = $this->language->get('product_already_exists');
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
				if($this->version < $this->request->post['min_ver']) {
					$json['error'] = $this->language->get('version_outdated');
				}
				// Add a new voucher if set
				if ((utf8_strlen($this->request->post['title']) < 1)) {
					$json['error'] = $this->language->get('missing_title');
				}

				if (!$json) {
					$code = mt_rand();

					$this->session->data['product'][$code] = array(
						'title'             => $this->request->post['title']
					);

					$this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($this->request->post['sku']) . "',
						quantity='998', 
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
						description ='" . $this->db->escape($this->request->post['description']) . "'");
					
					//enable color options
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '$product_id',
						option_id ='$Color_option_id',
						required = '1'");
					$product_option_id_color = $this->db->getLastId();

					//enable size options
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '$product_id',
						option_id ='$Size_option_id',
						required = '1'");
					$product_option_id_size = $this->db->getLastId();

					$usedColorArray = array();
					$usedSizeArray = array();
					/** Insert variations **/
					foreach($this->request->post['variations'] as $key => $value) {
						$product_option_description_id_style = "";
						$product_option_description_id_color = "";
						$product_option_description_id_size = "";
						$insert_style = $this->db->escape($this->request->post['variations'][$key]['Style']);
						$insert_color = $this->db->escape($this->request->post['variations'][$key]['Color']);
						$insert_size = $this->db->escape($this->request->post['variations'][$key]['Size']);
						$insert_price = $this->db->escape($this->request->post['variations'][$key]['Price']);

						//get list of option_value_id (COLOR)
						$getattr = $this->db->query("SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE option_id = '$Color_option_id' AND name='$insert_color'");
						foreach ($getattr->rows as $row) {
							$product_option_description_id_color = $row['option_value_id'];
						}
						if(!$product_option_description_id_color) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '$Color_option_id'");
							$option_value_id = $this->db->getLastId();

							$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '$option_value_id',
							language_id = '$Language_id',
							option_id = '$Color_option_id',
							name = '$insert_color'");
							$product_option_description_id_color = $this->db->getLastId();
						}

						//get list of option_value_id (SIZE)
						$getattr = $this->db->query("SELECT option_value_id FROM " . DB_PREFIX . "option_value_description WHERE option_id = '$Size_option_id' AND name='$insert_size'");
						foreach ($getattr->rows as $row) {
							$product_option_description_id_size = $row['option_value_id'];
						}
						if(!$product_option_description_id_size) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '$Size_option_id'");
							$option_value_id = $this->db->getLastId();

							$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '$option_value_id',
							language_id = '$Language_id',
							option_id = '$Size_option_id',
							name = '$insert_size'");
							$product_option_description_id_size = $this->db->getLastId();
						}
						if(!in_array($insert_color, $usedColorArray)) {
							//insert variations - color
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_id = '$product_id',
							product_option_id ='$product_option_id_color',
							option_id = '$Color_option_id',
							option_value_id = '$product_option_description_id_color',
							quantity = '999',
							price_prefix = '+',
							price = '0.00'");
							array_push($usedColorArray, $insert_color);
						}
						if(!in_array($insert_size, $usedSizeArray)) {
							//insert variations - size
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_id = '$product_id',
							product_option_id ='$product_option_id_size',
							option_id = '$Size_option_id',
							option_value_id = '$product_option_description_id_size',
							quantity = '999',
							price_prefix = '+',
							price = '$insert_price'");
							array_push($usedSizeArray, $insert_size);
						}
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

	public function get() {
		$this->load->language('api/order');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');

		} else {

			$orders = array();
			$keys = array();
			foreach ($keys as $key) {
				if (!isset($this->request->post[$key])) {
					$this->request->post[$key] = '';
				}
			}

			$getattr = $this->db->query("SELECT " . DB_PREFIX . "order.*, " . DB_PREFIX . "order_product.model, " . DB_PREFIX . "order_product.quantity, order_option_size.value AS SizeType, order_option_color.value AS ColorType
			FROM `" . DB_PREFIX . "order` 
			RIGHT JOIN " . DB_PREFIX . "order_product 
				ON " . DB_PREFIX . "order.order_id = " . DB_PREFIX . "order_product.order_id
			RIGHT JOIN " . DB_PREFIX . "order_option AS order_option_size
				ON " . DB_PREFIX . "order_product.order_product_id = order_option_size.order_product_id AND order_option_size.name = 'Size'
			RIGHT JOIN " . DB_PREFIX . "order_option AS order_option_color
				ON " . DB_PREFIX . "order_product.order_product_id = order_option_color.order_product_id AND order_option_color.name = 'Color'
			RIGHT JOIN " . DB_PREFIX . "order_status 
				ON " . DB_PREFIX . "order.order_status_id = " . DB_PREFIX . "order_status.order_status_id
			WHERE " . DB_PREFIX . "order.date_added > '".$this->db->escape($this->request->post['lastscan'])."' AND " . DB_PREFIX . "order_status.name IN('Processing', 'Pending', 'Processed')
		  	");
			foreach ($getattr->rows as $row) {
				array_push($orders,
					array("order_id" => $row['order_id'],
						"date_added" => $row['date_added'],
						"shipping" => array("firstname" => $row['shipping_firstname'],
											"lastname" => $row['shipping_lastname'],
											"address1" => $row['shipping_address_1'],
											"address2" => $row['shipping_address_2'],
											"city" => $row['shipping_city'],
											"state" => $row['shipping_zone'],
											"postalcode" => $row['shipping_postcode'],
											"country" => $row['shipping_country'],
											"email" => $row['email'],
											"phone" => $row['telephone']),
						"line_items" => array("sku" => $row['model'],
										"quantity" => $row['quantity'],
										"style" => "na",
										"color" => $row['ColorType'],
										"size" => $row['SizeType'],
										"comment" => $row['comment'])
					));

			}

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($orders));

		}
	}
	/**
	 * Update Tracking
	 *
	 * @return  [type]
	 *
	 * @example
	 */
	public function shipitem() {
		$this->load->language('api/order');

		$json = array();

		if (!isset($this->session->data['api_id'])) {
			$json['error']['warning'] = $this->language->get('error_permission');

		} else {

			$keys = array();
			foreach ($keys as $key) {
				if (!isset($this->request->post[$key])) {
					$this->request->post[$key] = '';
				}
			}
			//get shipped status id
			$getattr = $this->db->query("SELECT order_status_id FROM " . DB_PREFIX . "order_status WHERE name = 'Shipped'");
			foreach ($getattr->rows as $row) {
				$order_status_id = $row['order_status_id'];
			}
			$shipping_courier_id = "";
			//get shipping courier, or insert it
			$getattr = $this->db->query("SELECT shipping_courier_id FROM " . DB_PREFIX . "shipping_courier WHERE shipping_courier_name = '".$this->db->escape($this->request->post['courier'])."'");
			foreach ($getattr->rows as $row) {
				$shipping_courier_id = $row['shipping_courier_id'];
			}
			if(!$shipping_courier_id) {
				$NextId = 0;
				//get max
				$getattr = $this->db->query("SELECT MAX(shipping_courier_id) FROM " . DB_PREFIX . "shipping_courier");
				foreach ($getattr->rows as $row) {
					$NextId = $row['MAX(shipping_courier_id)']++;
				}

				$this->db->query("INSERT INTO " . DB_PREFIX . "shipping_courier SET shipping_courier_id = '$NextId',
					shipping_courier_code = '".$this->db->escape($this->request->post['courier_code'])."',
					shipping_courier_name = '".$this->db->escape($this->request->post['courier'])."'");
				//get last insert id
				$shipping_courier_id = $this->db->getLastId();
			}


			//update order table
			$getattr = $this->db->query("UPDATE " . DB_PREFIX . "order SET tracking = '".$this->db->escape($this->request->post['tracking'])."', 
				order_status_id = '$order_status_id'
				WHERE order_id = '".$this->db->escape($this->request->post['oc_order_id'])."'");
			
			//create shipment 
			$this->db->query("INSERT INTO " . DB_PREFIX . "order_shipment SET order_id = '".$this->db->escape($this->request->post['oc_order_id'])."',
				date_added = NOW(),
				shipping_courier_id = '$shipping_courier_id',
				tracking_number = '".$this->db->escape($this->request->post['tracking'])."'");

			$json['success'] = $this->language->get('tracking_updates');

			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));

		}
	}
}
