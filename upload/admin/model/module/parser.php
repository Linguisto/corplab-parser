<?php
class ModelModuleParser extends Model {
	
	public function transliterate($text){
		$converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya');
        
		$url = strtr($text, $converter);
		$url = strtolower($url);
		$url = preg_replace('~[^-a-z0-9_]+~u', '-', $url);
		$url = trim($url, "-");
		return $url;
	}

	public function sync_categories($categories){
		$last_cat = end($categories);
		$query = $this->db->query("SELECT DISTINCT `category_id` FROM ".DB_PREFIX."category_description WHERE `name`='$last_cat'");
		$result = $query->row;
		
		if(empty($result)){
			foreach($categories as $cat_id => $cat_name){
				if ($cat_id!='home'){
					
					if (!isset($parent_cat) || empty($parent_cat))
						$query = $this->db->query("SELECT `category_id` FROM ".DB_PREFIX."category_description WHERE `name`='Канцелярские товары' AND `language_id`=2");
    				if (isset($query->row['category_id']) && !empty($query->row['category_id'])) $parent_cat=$query->row['category_id'];
    				
					if (!isset($parent_cat) || empty($parent_cat)){
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_path SET category_id=$cat_id, path_id=$cat_id, level=0");
						$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category SET `category_id`=$cat_id, `image`='', `top`=1, `column`=1, `sort_order`=1, `status`=1");
					} else {
						$this->db->query("INSERT INTO ".DB_PREFIX."category_path SET category_id=$cat_id, path_id=$parent_cat, level=0 ON DUPLICATE KEY UPDATE category_id=$cat_id, path_id=$parent_cat, level=0");
						$this->db->query("INSERT INTO ".DB_PREFIX."category SET `category_id`=$cat_id, `parent_id`='$parent_cat', `image`='', `top`=0, `column`=0, `sort_order`=1, `status`=1 ON DUPLICATE KEY UPDATE `category_id`=$cat_id, `parent_id`='$parent_cat', `image`='', `top`=0, `column`=0, `sort_order`=1, `status`=1");
					}
					
					$this->db->query("INSERT INTO ".DB_PREFIX."category_description SET `category_id`='$cat_id', `language_id`=1, `name`='".$this->db->escape($cat_name)."', `meta_keyword`='".$this->db->escape($cat_name)."', `meta_description`='".$this->db->escape($cat_name)."',`seo_title`='".$this->db->escape($cat_name)."',`seo_h1`='".$this->db->escape($cat_name)."' ON DUPLICATE KEY UPDATE `category_id`='$cat_id', `language_id`=1, `name`='".$this->db->escape($cat_name)."', `meta_keyword`='".$this->db->escape($cat_name)."', `meta_description`='".$this->db->escape($cat_name)."',`seo_title`='".$this->db->escape($cat_name)."',`seo_h1`='".$this->db->escape($cat_name)."'");
					
					$this->db->query("INSERT INTO ".DB_PREFIX."category_description SET `category_id`='$cat_id', `language_id`=2, `name`='".$this->db->escape($cat_name)."', `meta_keyword`='".$this->db->escape($cat_name)."', `meta_description`='".$this->db->escape($cat_name)."',`seo_title`='".$this->db->escape($cat_name)."',`seo_h1`='".$this->db->escape($cat_name)."' ON DUPLICATE KEY UPDATE `category_id`='$cat_id', `language_id`=2, `name`='".$this->db->escape($cat_name)."', `meta_keyword`='".$this->db->escape($cat_name)."', `meta_description`='".$this->db->escape($cat_name)."',`seo_title`='".$this->db->escape($cat_name)."',`seo_h1`='".$this->db->escape($cat_name)."'");
    
					$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_to_store SET category_id=$cat_id, store_id=0");
					
					$keyword = $this->transliterate($cat_name);
					
					$query = $this->db->query("SELECT `url_alias_id` FROM ".DB_PREFIX."url_alias WHERE `keyword`='".$this->db->escape($keyword)."'");
					
					if (empty($query->row)){
						$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='category_id=$cat_id', `keyword`='".$this->db->escape($keyword)."'");
					} else {
						$this->db->query("DELETE FROM ".DB_PREFIX."url_alias WHERE `keyword`='".$this->db->escape($keyword)."'");
						$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='category_id=$cat_id', `keyword`='".$this->db->escape($keyword)."'");
					}
					
					$parent_cat=$cat_id;
				} else {
					$cat_name = 'Канцелярские товары';
					$cat_id = '5';
					
					$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_description SET `category_id`=$cat_id, `language_id`=1, `name`='".$this->db->escape($cat_name)."', `meta_keyword`='".$this->db->escape($cat_name)."', `meta_description`='".$this->db->escape($cat_name)."',`seo_title`='".$this->db->escape($cat_name)."',`seo_h1`='".$this->db->escape($cat_name)."'");
    				$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_description SET `category_id`=$cat_id, `language_id`=2, `name`='".$this->db->escape($cat_name)."', `meta_keyword`='".$this->db->escape($cat_name)."', `meta_description`='".$this->db->escape($cat_name)."',`seo_title`='".$this->db->escape($cat_name)."',`seo_h1`='".$this->db->escape($cat_name)."'");
					$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_to_store SET category_id=$cat_id, store_id=0");
					$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category_path SET category_id=$cat_id, path_id=$cat_id, level=0");
					$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."category SET `category_id`=$cat_id, `image`='', `top`=1, `column`=1, `sort_order`=1, `status`=1");
					$keyword = $this->transliterate($cat_name);
					
					$query = $this->db->query("SELECT `url_alias_id` FROM ".DB_PREFIX."url_alias WHERE `query`='category_id=$cat_id'");
					
					if (empty($query->row)){
						$query = $this->db->query("SELECT `url_alias_id` FROM ".DB_PREFIX."url_alias WHERE `keyword`='".$this->db->escape($keyword)."'");
						if (empty($query->row))
							$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='category_id=$cat_id', `keyword`='".$this->db->escape($keyword)."'");
						else
							$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='category_id=$cat_id', `keyword`='$cat_id'");
					} else {
						$this->db->query("DELETE FROM ".DB_PREFIX."url_alias WHERE `query`='category_id=$cat_id'");
						$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='category_id=$cat_id', `keyword`='".$this->db->escape($keyword)."'");
					}
				}
			}
		}
	}
	
	public function sync_attributes($attributes){
	
		foreach ($attributes as $attribute => $value){
			$query = $this->db->query("SELECT DISTINCT `attribute_id` FROM ".DB_PREFIX."attribute_description WHERE `name`='$attribute'");
			$result = $query->row;
			if (empty($result)){
			
				if ($attribute!=='Страна-производитель')
					$this->db->query("INSERT INTO ".DB_PREFIX."attribute SET `attribute_group_id`=7, `sort_order`=1");
				else
					$this->db->query("INSERT INTO ".DB_PREFIX."attribute SET `attribute_group_id`=7, `sort_order`=0");
					
				$attr_id = $this->db->getLastId();
				$this->db->query("INSERT INTO ".DB_PREFIX."attribute_description SET `attribute_id`=$attr_id, `language_id`=1, `name`='".$this->db->escape($attribute)."'");
				$this->db->query("INSERT INTO ".DB_PREFIX."attribute_description SET `attribute_id`=$attr_id, `language_id`=2, `name`='".$this->db->escape($attribute)."'");
			}
		}
	
	}
	
	public function check_manufacturer($manufacturer){
		$query = $this->db->query("SELECT DISTINCT `manufacturer_id` FROM ".DB_PREFIX."manufacturer WHERE `name`='$manufacturer'");
		if (!empty($query->row))
			return 1;
		else 
			return 0;
	}
	
	public function truncate_products(){
		$this->db->query('TRUNCATE TABLE '.DB_PREFIX.'product');
		$this->db->query('TRUNCATE TABLE '.DB_PREFIX.'product_description');
		$this->db->query('TRUNCATE TABLE '.DB_PREFIX.'product_to_category');
		$this->db->query('TRUNCATE TABLE '.DB_PREFIX.'product_attribute');
		$this->db->query('TRUNCATE TABLE '.DB_PREFIX.'product_to_store');
		$this->db->query('TRUNCATE TABLE '.DB_PREFIX.'product_related');
		$this->db->query("DELETE FROM ".DB_PREFIX."url_alias WHERE `query` LIKE '%product_id=%'");
	}
	
	public function sync_products($product){
		$query = $this->db->query("SELECT DISTINCT `manufacturer_id` FROM ".DB_PREFIX."manufacturer WHERE `name`='".$product['manufacturer']."'");
		$manufacturer_id = $query->row['manufacturer_id'];
		$stock_status_id = 5;
		if ($product['amount']>0) $stock_status_id = 7;
		
		$this->db->query("INSERT INTO ".DB_PREFIX."product SET `product_id`=".$product['code'].", `model`='".$this->db->escape($product['model'])."', `sku`='".$product['SKU']."', `upc`='".$product['UPC']."', `ean`=".$product['code'].", `weight`='".$product['weight']."',`manufacturer_id`='$manufacturer_id',`status`=1,`stock_status_id`=$stock_status_id,`image`='".$product['image']."',`quantity`='".$product['amount']."',`price`='".$product['price']."',`date_added` = NOW(),`subtract`=1 ON DUPLICATE KEY UPDATE `product_id`=".$product['code'].", `model`='".$this->db->escape($product['model'])."', `sku`='".$product['SKU']."', `upc`='".$product['UPC']."', `ean`=".$product['code'].", `weight`='".$product['weight']."',`manufacturer_id`='$manufacturer_id',`status`=1,`stock_status_id`=$stock_status_id,`image`='".$product['image']."',`quantity`='".$product['amount']."',`price`='".$product['price']."',`date_added` = NOW(),`subtract`=1");
		
		$this->db->query("INSERT INTO ".DB_PREFIX."product_description SET `product_id`=".$product['code'].", `language_id`=1, `name`='".$this->db->escape($product['name'])."', `description`='".$this->db->escape($product['description'])."', `meta_description`='".$this->db->escape($product['description'])."', `meta_keyword`='".$this->db->escape($product['name'])."',`seo_title`='".$this->db->escape($product['name'])."',`seo_h1`='".$this->db->escape($product['name'])."',`tag`='".$this->db->escape($product['name'])."' ON DUPLICATE KEY UPDATE `product_id`=".$product['code'].", `language_id`=1, `name`='".$this->db->escape($product['name'])."', `description`='".$this->db->escape($product['description'])."', `meta_description`='".$this->db->escape($product['description'])."', `meta_keyword`='".$this->db->escape($product['name'])."',`seo_title`='".$this->db->escape($product['name'])."',`seo_h1`='".$this->db->escape($product['name'])."',`tag`='".$this->db->escape($product['name'])."'");
		
		$this->db->query("INSERT INTO ".DB_PREFIX."product_description SET `product_id`=".$product['code'].", `language_id`=2, `name`='".$this->db->escape($product['name'])."', `description`='".$this->db->escape($product['description'])."', `meta_description`='".$this->db->escape($product['description'])."', `meta_keyword`='".$this->db->escape($product['name'])."',`seo_title`='".$this->db->escape($product['name'])."',`seo_h1`='".$this->db->escape($product['name'])."',`tag`='".$this->db->escape($product['name'])."' ON DUPLICATE KEY UPDATE `product_id`=".$product['code'].", `language_id`=2, `name`='".$this->db->escape($product['name'])."', `description`='".$this->db->escape($product['description'])."', `meta_description`='".$this->db->escape($product['description'])."', `meta_keyword`='".$this->db->escape($product['name'])."',`seo_title`='".$this->db->escape($product['name'])."',`seo_h1`='".$this->db->escape($product['name'])."',`tag`='".$this->db->escape($product['name'])."'");
		
		if (isset($product['categories']) && !empty($product['categories'])){
			foreach($product['categories'] as $category => $value){
				if ($category!=='home') 
					$this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET `product_id`='".$product['code']."', `category_id`='".$category."', `main_category`=0 ON DUPLICATE KEY UPDATE `product_id`='".$product['code']."', `category_id`='".$category."', `main_category`=0");
				else
					$this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET `product_id`='".$product['code']."', `category_id`='5', `main_category`=0 ON DUPLICATE KEY UPDATE `product_id`='".$product['code']."', `category_id`='5', `main_category`=0");
			}
		}
		
		$this->db->query("INSERT INTO ".DB_PREFIX."product_to_category SET `product_id`='".$product['code']."', `category_id`='".$product['category']."', `main_category`=1  ON DUPLICATE KEY UPDATE `product_id`='".$product['code']."', `category_id`='".$product['category']."', `main_category`=1");
		
		foreach($product['attributes'] as $attribute => $value){
			$query = $this->db->query("SELECT `attribute_id` FROM ".DB_PREFIX."attribute_description WHERE `name`='$attribute' AND `language_id`=2");
			$attr_id = $query->row['attribute_id'];
			if (!empty($attr_id)){
				$this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET `product_id`='".$product['code']."', `attribute_id`=$attr_id, language_id=1, text='$value' ON DUPLICATE KEY UPDATE `product_id`='".$product['code']."', `attribute_id`=$attr_id, language_id=1, text='".$this->db->escape($value)."'");
				$this->db->query("INSERT INTO ".DB_PREFIX."product_attribute SET `product_id`='".$product['code']."', `attribute_id`=$attr_id, language_id=2, text='$value' ON DUPLICATE KEY UPDATE `product_id`='".$product['code']."', `attribute_id`=$attr_id, language_id=2, text='".$this->db->escape($value)."'");
			}
		}
		
		if (!empty($product['relatedGoods'])){
			$relatedGoods = substr($product['relatedGoods'], 0, -1);
			$relatedGoods = explode(';', $relatedGoods);
			
			foreach($relatedGoods as $rel_prod){
				$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_related SET product_id=".$product['code'].", related_id=".$rel_prod);
			}
		}
		
		$this->db->query("INSERT IGNORE INTO ".DB_PREFIX."product_to_store SET `product_id`=".$product['code'].", `store_id`=0");
		
		$keyword=$this->transliterate($product['name']);
		
		$query = $this->db->query("SELECT `url_alias_id` FROM ".DB_PREFIX."url_alias WHERE `query`='product_id=".$product['code']."'");
		if (empty($query->row)){
			$query = $this->db->query("SELECT `url_alias_id` FROM ".DB_PREFIX."url_alias WHERE `keyword`='".$this->db->escape($keyword)."'");
			if (empty($query->row))
				$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='product_id=".$product['code']."', `keyword`='$keyword'");
			else
				$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='product_id=".$product['code']."', `keyword`='".$product['code']."'");
		} else {
			$this->db->query("DELETE FROM ".DB_PREFIX."url_alias WHERE `keyword`='$keyword'");
			$this->db->query("INSERT INTO ".DB_PREFIX."url_alias SET `query`='product_id=".$product['code']."', `keyword`='$keyword'");
		}	
			
	}
	
	public function add_leaves($data){
		$this->db->query("CREATE TABLE IF NOT EXISTS ".DB_PREFIX."leaves_data (product_id int(11), code int(11), xmlid varchar(200), PRIMARY KEY (product_id), UNIQUE INDEX(code))");
		$this->db->query("INSERT INTO ".DB_PREFIX."leaves_data SET product_id=".$data['product_id'].", code=".$data['code'].", xmlid='".$data['xmlid']."' ON DUPLICATE KEY UPDATE product_id=".$data['product_id'].", code=".$data['code'].", xmlid='".$data['xmlid']."'");
	}
	
	public function get_all_product_ids(){
		$query = $this->db->query("SELECT product_id FROM ".DB_PREFIX."product");
		return $query->rows;
	}
	
	public function get_leaves_postdata($product_id){
		$query = $this->db->query("SELECT `code`, `xmlid` FROM ".DB_PREFIX."leaves_data WHERE `product_id`=".$product_id);
		return $query->row;
	}
	
	public function sync_products_leaves($data){
		$stock_status_id = 5;
		if ($data['amount']>0) $stock_status_id = 7;
		$this->db->query("UPDATE ".DB_PREFIX."product SET quantity=".$data['amount'].", stock_status_id=$stock_status_id WHERE product_id=".$data['product_id']);
	}
	
}
?>
