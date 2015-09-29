<?php
class ControllerModuleParser extends Controller{
	private $error = array();
	
	public function index() {	
		if  (isset($this->request->post['parser']) && $this->request->post['parser']==1){
			$cmd = sprintf('php -q %s/cli/index.php route=module/parser/parse < /dev/null > %s/admin/parsedfiles/parser.log &', $_SERVER['DOCUMENT_ROOT'], $_SERVER['DOCUMENT_ROOT']);
			shell_exec($cmd);
			unset($cmd);
			$this->data['success'] = 'Скрипт запущен! Парсинг начат! Отчёт придёт на roman@corplab.ru';
		} 
		
		if  (isset($this->request->post['parser']) && $this->request->post['parser']==2){
			$cmd = sprintf('php -q %s/cli/index.php route=module/parser/sync_leaves < /dev/null > %s/admin/parsedfiles/parser.log &', $_SERVER['DOCUMENT_ROOT'], $_SERVER['DOCUMENT_ROOT']);
			shell_exec($cmd);
			unset($cmd);
			$this->data['success'] = 'Синхронизация остатков начата!';
		}
		
		$this->language->load('module/parser');
		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->document->setTitle($this->data['heading_title']);
		$this->renderPage();	
		
	}
	
	protected function renderPage(){
			
		$url = '';

		if (isset($this->request->get['filter_category_id'])) {
			$url .= '&filter_category_id=' . (int)$this->request->get['filter_category_id'];
		}

		if (isset($this->request->get['filter_manufacturer_id'])) {
			$url .= '&filter_manufacturer_id=' . (int)$this->request->get['filter_manufacturer_id'];
		}

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . $this->request->get['filter_name'];
		}
		
		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . $this->request->get['filter_model'];
		}
		
		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}
		
		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}		

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}
						
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/parser', 'token=' . $this->session->data['token'] . $url, 'SSL'),       		
      		'separator' => ' :: '
   		);
   		
   		$this->data['action'] = $this->url->link('module/parser/index', 'token=' . $this->session->data['token'] . $url, 'SSL');

		$this->template = 'module/parser.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
		$this->response->setOutput($this->render());
		
	}
	
	public function parse(){
		//script settings
		$parsedDomain = 'http://relefopt.ru';
		$hrefs = array();
		$path_to_img = 'data/relief/';
		$parsed_folder = DIR_APPLICATION . 'parsedfiles/';
		$products = array();
		$product_data=array();
		$goodsCount = 0;
		
		//username and password of account
		$username = 'info@corplab.ru';
		$password = '0987654321';
		
		//login form action url
		$url="http://relefopt.ru/ajax/rc.user.php"; 
		$postinfo = "user_login=".$username."&user_password=".$password."&auth_form=Y&action=auth";

		$cookie_file = DIR_APPLICATION."/parsedfiles/cookie.txt";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
		curl_exec($ch);
		
		$this->load->model('module/parser');
		$this->load->model('catalog/manufacturer');
		require_once(DIR_APPLICATION . 'simple_html_dom.php');
		
		$link = "http://relefopt.ru/catalog/?AJAX=Y&PAGEN_1=1&pagenum=48&sort_field=RZN&sort_order=asc&in_stock_n=Y&composite=N&catalog=y";
		$CatPage = new simple_html_dom();
		$CatPage->load_file($link, true);
		//почему-то перестало работать
		//$CatPage = file_get_html("http://relefopt.ru/catalog/?AJAX=Y&PAGEN_1=1&pagenum=48&sort_field=RZN&sort_order=asc&in_stock_n=Y&composite=N&catalog=y");
		$maxCatPage = (int)$CatPage->find('.page-list li a', -1)->plaintext;
		$maxCatPage = (int)$CatPage->find('.page-list li a', -1)->plaintext;
		$CatPage->clear();
		unset($CatPage);
		
		$start = microtime(true);
		
		for ($i=1; $i<=$maxCatPage; $i++){
			$link = 'http://relefopt.ru/catalog/?AJAX=Y&PAGEN_1='.$i.'&pagenum=48&sort_field=RZN&sort_order=asc&in_stock_n=Y&composite=N&catalog=y';
			$CatPage = new simple_html_dom();
			$CatPage->load_file($link, true);
			// находим все ссылки на товары
			foreach($CatPage->find('ul.rc-catalog li a.rc-catalog__prod') as $element) $hrefs[] = $parsedDomain.$element->href;
			$CatPage->clear();
			unset($CatPage);
		}
		//$i=0;
		foreach($hrefs as $href){
			$html = @file_get_contents($href);
			if (!empty($html)) $product = str_get_html($html);
			
			if (isset($product) && !empty($product)){
			
				$product_data['categories'] = array();
			
				//получаем категории и заносим в базу
				foreach($product->find('ul.breadcrumbs li a') as $el){
					$cat_id = preg_replace('/\D+/','',$el->href);
					if (empty($cat_id)) $cat_id = 'home';
					$product_data['categories'][$cat_id] = $el->plaintext;
				}
						
				$this->model_module_parser->sync_categories($product_data['categories']);
			
				foreach($product->find('ul.simple-list li.visible') as $el) 
					$product_data['attributes'][$el->first_child()->plaintext] = str_replace($el->first_child()->plaintext . '&nbsp;&ndash;&nbsp;', '', $el->plaintext);
				foreach($product->find('ul.simple-list li.hid') as $el) 
					$product_data['attributes'][$el->first_child()->plaintext] = str_replace($el->first_child()->plaintext . '&nbsp;&ndash;&nbsp;', '', $el->plaintext);
				
				foreach($product->find('span.rc-item__prod') as $el) $product_data['name'] = $el->plaintext;
			
				foreach($product->find('.detail-info') as $el) $detinfo = $el->plaintext;
				$detinfo = explode('&nbsp;&ndash;&nbsp;', $detinfo);
				if(!empty($detinfo)){
					if (!empty($detinfo[1])) $product_data['code'] = preg_replace('/\D+/', '', $detinfo[1]);
					if (!empty($detinfo[2])){
						$product_data['SKU'] = preg_replace('/;/', '', $detinfo[2]);
						$product_data['SKU'] = preg_replace('/\s\W+/', '', $product_data['SKU']);
					} 
					if (isset($detinfo[3]) && !empty($detinfo[3])){
						$product_data['UPC'] = preg_replace('/\D+/', '', $detinfo[3]);
					
						if (isset($detinfo[4]) && !empty($detinfo[4]))
							$product_data['attributes']['Страна-производитель'] = $detinfo[4];
						else
							$product_data['attributes']['Страна-производитель'] = $detinfo[3];
					} else {
						$product_data['UPC'] = '';
						$product_data['SKU'] = '';
						$product_data['attributes']['Страна-производитель'] = $detinfo[2];
					}
				}
				unset($detinfo);
			
				$this->model_module_parser->sync_attributes($product_data['attributes']);
			
				foreach($product->find('.detail-block form.rc-2basket') as $el){
					$code = $el->getAttribute('data-id');
					$xmlid = $el->getAttribute('data-xmlid');
				} 
			
				$postinfo = "action=define&product[$code][type]=basket&product[$code][xmlid]=$xmlid";
			
				$leaves = array(
					'product_id' => $product_data['code'],
					'code' => $code,
					'xmlid' => $xmlid
				);
			
				$this->model_module_parser->add_leaves($leaves);
			
				curl_setopt($ch, CURLOPT_URL, "http://relefopt.ru/ajax/rc.catalog.php");
				curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
				$response = curl_exec($ch);
				$response = json_decode($response, true);
				$product_data['price'] = (float)$response['product'][$code]['price'];
				$product_data['amount'] = (int)$response['product'][$code]['available'];
			
				foreach($product->find('.detail-block div p') as $el) $product_data['description'] = $el->plaintext;
				if (empty($product_data['description'])) $product_data['description'] = '';
				end($product_data['categories']); //перемещаем указатель в конец массива категорий
				$product_data['category'] = key($product_data['categories']); //ставим id категории товару
			
				$product_data['manufacturer'] = trim($product->find('.detail-block strong.title a', 0)->plaintext);
				$product_data['model'] = trim($product->find('.detail-block strong.title a', 1)->plaintext);
			
				$manufacturer_exists = $this->model_module_parser->check_manufacturer($product_data['manufacturer']);
				
				$man_data['name'] = $product_data['manufacturer'];
				$man_data['image'] ='';
				$man_data['manufacturer_description'] = array(
					1 => array(
						'meta_keyword' => $product_data['manufacturer'],
						'meta_description' => $product_data['manufacturer'],
						'description' => $product_data['manufacturer'],
						'seo_title' => $product_data['manufacturer'],
						'seo_h1' => $product_data['manufacturer']
					),
					2 => array(
						'meta_keyword' => $product_data['manufacturer'],
						'meta_description' => $product_data['manufacturer'],
						'description' => $product_data['manufacturer'],
						'seo_title' => $product_data['manufacturer'],
						'seo_h1' => $product_data['manufacturer']
					)
				);
				$man_data['manufacturer_store'][] = 0;
				$man_data['keyword'] = $this->model_module_parser->transliterate($product_data['manufacturer']);
				$man_data['sort_order'] = 0;
			
				if ($manufacturer_exists == 0)
					$this->model_catalog_manufacturer->addManufacturer($man_data);
				else
					$this->model_catalog_manufacturer->editManufacturer($manufacturer_exists,$man_data);
				
				unset($man_data);
				
				$product_data['weight'] = $product->find('.info-table tr td', 1)->plaintext;
			
				$related_goods='';
			
				foreach($product->find('.related-goods .gallery-prod .rc-catalog .rc-catalog__item .rc-catalog__code') as $el){
					$related_goods .=  preg_replace('/\D+/', '', $el->plaintext) . ';';
				} 
			
				$product_data['relatedGoods'] = $related_goods;
			
				foreach($product->find('.visual .highslide') as $el) $img_src = $parsedDomain.$el->href;
				if (!empty($img_src)){
					$product_data['image'] = $path_to_img.$product_data['code'].'.jpg';
					file_put_contents(DIR_IMAGE . $product_data['image'], file_get_contents($img_src));
				}else{
					$product_data['image'] = '';
				}
			
				$product->clear();
				unset($product);
				$this->model_module_parser->sync_products($product_data);
				unset($product_data);
			
				++$goodsCount;
			}
			//if ($i>=1) break;
			//$i++;
		}
		curl_close($ch);
		unset($hrefs);
		
		$end = microtime(true);
		$runtime = $end - $start;
		file_put_contents($parsed_folder.'worktime.txt', "[".date('d/m/Y H:i:s', $end)."] Мой господин, я провела полный парсинг $goodsCount товаров за ".date('H:i:s', $runtime)."\r\n", FILE_APPEND);
		
		$email_to = "roman@corplab.ru";
		$mail = new Mail();

		$mail->protocol = $this->config->get('config_mail_protocol');
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->hostname = $this->config->get('config_smtp_host');
		$mail->username = $this->config->get('config_smtp_username');
		$mail->password = $this->config->get('config_smtp_password');
		$mail->port = $this->config->get('config_smtp_port');
		$mail->timeout = $this->config->get('config_smtp_timeout');            
		$mail->setTo($email_to);
		$mail->setFrom("noreply@corplab.ru");
		$mail->setSender("Парсер corplab");
		$mail->setSubject("Отчёт о прасинге товаров за ".date('d/m/Y', $end));
		$mail->setText("Полный парсинг $goodsCount товаров прошёл успешно! Это заняло ".date('H часов i минут s секунд.', $runtime));

		$mail->send();
		
		unset($mail);
		
	}
	
	public function sync_leaves(){
	
		//settings
		$parsed_folder = DIR_APPLICATION . 'parsedfiles/';
		$goodsCount=0;
		
		//models, etc
		$this->load->model('module/parser');
		$prod_ids = $this->model_module_parser->get_all_product_ids();
		
		
		//username and password of account
		$username = 'info@corplab.ru';
		$password = '0987654321';
		
		$url="http://relefopt.ru/ajax/rc.user.php"; 
		$postinfo = "user_login=".$username."&user_password=".$password."&auth_form=Y&action=auth";
		
		$start = microtime(true);
		
		$cookie_file = DIR_APPLICATION."/parsedfiles/cookie.txt";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOBODY, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_USERAGENT,
			"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
		curl_exec($ch);
		
		foreach ($prod_ids as $record){
			$postinfo = $this->model_module_parser->get_leaves_postdata($record['product_id']);
			if (!empty($postinfo)){
				$code = $postinfo['code'];
				$postinfo = sprintf("action=define&product[%d][type]=basket&product[%d][xmlid]=%s", $code, $code, $postinfo['xmlid']);
				curl_setopt($ch, CURLOPT_URL, "http://relefopt.ru/ajax/rc.catalog.php");
				curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
				$response = curl_exec($ch);
				$response = json_decode($response, true);
				$product_data = array(
					'product_id' => (int)$record['product_id'],
					'amount' => (int)$response['product'][$code]['available']
				);
				$this->model_module_parser->sync_products_leaves($product_data);
			}
			$goodsCount++;
		}
		curl_close($ch);
		unset($prod_ids);
		
		$end = microtime(true);
		$runtime = $end - $start;
		
		file_put_contents($parsed_folder.'worktime.txt', "[".date('d/m/Y H:i:s', $end)."] Мой господин, я синхронизировала остатки $goodsCount товаров за ".date('H:i:s', $runtime)."\r\n", FILE_APPEND);
	}
	
}
?>
