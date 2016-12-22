<?php
if (!defined('_PS_VERSION_'))
  exit;
 
class blockdropdownmenu extends Module
{
  protected $_menu = '';
  protected $_list = '<div class="panel">';
  protected $items_lists = '';
  public function __construct()
  {
    $this->name = 'blockdropdownmenu';
    $this->tab = 'front_office_features';
    $this->version = '1.0';
    $this->author = 'Palomba Lucas';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
    $this->bootstrap = true;
    parent::__construct();
 
    $this->displayName = $this->l('Drop Down Menu');
    $this->description = $this->l('Show a customizable drop down menu');
 
    $this->confirmUninstall = $this->l('Are you sure to uninstall ?');
 
    if (!Configuration::get('MYMODULE_NAME'))      
      $this->warning = $this->l('No name provided');
  }
  public function install($delete_params = true)
    {
        if (!parent::install() ||
            !$this->registerHook('header') ||
            !$this->registerHook('displayBackOfficeHeader') ||
            !$this->registerHook('displayTop')) {
                return false;
            }


        if ($delete_params) {
            if (!$this->installDb() || !Configuration::updateValue('MYMODULE_NAME')) {
                return false;
            }
        }

        return true;
    }

    public function installDb()
    {
        return (Db::getInstance()->execute('
    CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'DDM_Categories` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR( 128 ) NOT NULL,
      `position` TINYINT( 1 ) NOT NULL,
      INDEX (`id`)
    ) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;') &&
            Db::getInstance()->execute('
       CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'DDM_Items` (
      `id` INT INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `id_parent` INT(11) UNSIGNED NOT NULL,
      `name` VARCHAR( 255 ) NOT NULL,
      `img` VARCHAR( 255 ) NOT NULL,
      `is_shop` BOOLEAN NOT NULL DEFAULT 0,
      `position` TINYINT( 1 ) NOT NULL,
      `link` VARCHAR( 255 ) NOT NULL,
      `adress` VARCHAR( 255 ) NOT NULL,
      INDEX ( `id` , `id_parent`)
    ) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;') &&
            Db::getInstance()->execute('
       CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'DDM_subItems` (
      `id` INT INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `id_parent` INT(11) UNSIGNED NOT NULL,
      `name` VARCHAR( 255 ) NOT NULL,
      `position` TINYINT( 1 ) NOT NULL,
      `link` VARCHAR( 255 ) NOT NULL,
      INDEX ( `id` , `id_parent`)
    ) ENGINE = '._MYSQL_ENGINE_.' CHARACTER SET utf8 COLLATE utf8_general_ci;'));
    }


  public function uninstall($delete_params = true)
    {
        if (!parent::uninstall()) {
            return false;
        }

        $this->clearMenuCache();

        if ($delete_params) {
            if (!$this->uninstallDB() || !Configuration::deleteByName('MYMODULE_NAME')) {
                return false;
            }
        }

        return true;
    }

    protected function uninstallDb()
    {
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'DDM_Categories`');
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'DDM_Items`');
        Db::getInstance()->execute('DROP TABLE `'._DB_PREFIX_.'DDM_subItems`');
        return true;
    }

    public function reset()
    {
        if (!$this->uninstall(false)) {
            return false;
        }
        if (!$this->install(false)) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit'.$this->name))
        {
            $newCatName = strval(Tools::getValue('category_name'));
            $newCatPos = (int)Tools::getValue('category_position');
            if (!$newCatName  || empty($newCatName) || !Validate::isGenericName($newCatName))
                $output .= $this->displayError( $this->l('Invalid Category Name') );
            elseif(!$newCatPos || empty($newCatPos))
                $output .= $this->displayError( $this->l('Not integer position value') );
            else
            {
                $params = array(
                   'name' => pSQL($newCatName),
                   'position' => (int)$newCatPos
                  );
                $this->addCategory($params);
                $output .= $this->displayConfirmation($this->l('Category added'));
            }
        }
        if (Tools::isSubmit('submitItem'.$this->name))
        {
            $newItemParentId = (int)Tools::getValue('item_parent_id');
            $newItemName = strval(Tools::getValue('item_name'));
            $newItemPos = (int)Tools::getValue('item_position');
            $newItemImg = $_FILES['uploadedfile'];
            $newItemisShop = (int)Tools::getValue('is_shop');
            $newItemLink = strval(Tools::getValue('item_link'));
            $newItemAdress = strval(Tools::getValue('item_adress'));
            if (!$newItemParentId  || empty($newItemParentId) || !$this->getCategory($newItemParentId))
                $output .= $this->displayError( $this->l('Invalid Category Name') );
            elseif (!$newItemName  || empty($newItemName) || !Validate::isGenericName($newItemName))
                $output .= $this->displayError( $this->l('Invalid Item Name') );
            elseif(!$newItemPos || empty($newItemPos))
                $output .= $this->displayError( $this->l('Not integer position value') );
            else
            {


            //Controllo se c'è una immagine da salvare
            if( $newItemImg['name'] != "" )
            {
                //Formati accettati
                $allowed = array('image/gif', 'image/jpeg', 'image/jpg', 'image/png');

                //Controllo che l'immagine sia in un formato accettato
                if( in_array($newItemImg['type'], $allowed) )
                {
                    $path = '../upload/';

                    //Controllo se esiste già un file con questo nome

                    //Carico il file
                    if( ! move_uploaded_file($newItemImg['tmp_name'], $path.$newItemImg['name']) )
                    {
                        $output .= $this->displayError( $path.$newItemImg['name'] );
                    }
                }
                else
                {
                    $output .= $this->displayError( $this->l('Image format not valid') );
                }
            }   




              $params = array(
                  'id_parent' => (int)$newItemParentId,
                  'name' => pSQL($newItemName),
                  'position' => (int)$newItemPos,
                  'img' => pSQL($path.$newItemImg['name']),
                  'is_shop' => (int)$newItemisShop,
                  'link' => pSQL($newItemLink),
                  'Adress' => pSQL($newItemAdress),
                );
                 $this->addItem($params);
              $output .= $this->displayConfirmation($this->l('Item added'));
            }
            
        }
        if (Tools::isSubmit('submitSubItem'.$this->name))
        {
          $newSubItemParentId = (int)Tools::getValue('sub_item_parent_id');
          $newSubItemName = strval(Tools::getValue('sub_item_name'));
          $newSubItemPos = (int)Tools::getValue('sub_item_position');
          $newSubItemLink = strval(Tools::getValue('sub_item_link'));
          if (!$newSubItemParentId  || empty($newSubItemParentId) || !$this->getItem($newSubItemParentId))
              $output .= $this->displayError( $this->l('Invalid Category Name') );
          elseif (!$newSubItemName  || empty($newSubItemName) || !Validate::isGenericName($newSubItemName))
              $output .= $this->displayError( $this->l('Invalid Sub Item Name') );
          elseif(!$newSubItemPos || empty($newSubItemPos))
              $output .= $this->displayError( $this->l('Not integer position value') );
          elseif(!$newSubItemLink || empty($newSubItemLink))
              $output .= $this->displayError( $this->l('Not validate Link') );
          else
          {
            $params = array(
                        'id_parent' => (int)$newSubItemParentId,
                        'name' => pSQL($newSubItemName),
                        'position' => (int)$newSubItemPos,
                        'link' => pSQL($newSubItemLink) 
                      );
            $this->addSubItem($params);

            $output .= $this->displayConfirmation($this->l('Sub Item added'));
          }
        }
        if (Tools::isSubmit('update'.$this->name))
        {
            $upCatId = (int)Tools::getValue('category_id');
            $upCatName = strval(Tools::getValue('category_name'));
            $upCatPos = (int)Tools::getValue('category_position');

            if (!$upCatName  || empty($upCatName) || !Validate::isGenericName($upCatName))
                $output .= $this->displayError( $this->l('Invalid Category Name') );
            elseif(!$upCatPos || empty($upCatPos))
                $output .= $this->displayError( $this->l('Not integer position value') );
            else
            {   
                $where = $upCatId;
                $params = array(
                   'name' => pSQL($upCatName),
                   'position' => (int)$upCatPos
                  );
                $this->updateCategory($params,$where);
                $output .= $this->displayConfirmation($this->l('Category updated'));
            }
        }
        if (Tools::isSubmit('updateItem'.$this->name))
        {
            $upItemId = (int)Tools::getValue('item_id');
            $upItemParentId = (int)Tools::getValue('item_parent_id');
            $upItemName = strval(Tools::getValue('item_name'));
            $upItemPos = (int)Tools::getValue('item_position');
            $upItemImg = $_FILES['uploadedfile'];
            $upItemisShop = (int)Tools::getValue('is_shop');
            $upItemLink = strval(Tools::getValue('item_link'));
            $upItemAdress = strval(Tools::getValue('item_adress'));
            if (!$upItemParentId  || empty($upItemParentId) || !$this->getCategory($upItemParentId))
                $output .= $this->displayError( $this->l('Invalid Category Name') );
            elseif (!$upItemName  || empty($upItemName) || !Validate::isGenericName($upItemName))
                $output .= $this->displayError( $this->l('Invalid Item Name') );
            elseif(!$upItemPos || empty($upItemPos))
                $output .= $this->displayError( $this->l('Not integer position value') );
            else
            {


              if ($upItemImg['name'] == "") {
                  $params = array(
                    'id_parent' => (int)$upItemParentId,
                    'name' => pSQL($upItemName),
                    'position' => (int)$upItemPos,
                    'is_shop' => (int)$upItemisShop,
                    'link' => pSQL($upItemLink),
                    'adress' => pSQL($upItemAdress)
                  );
              }else{

                      //Formati accettati
                      $allowed = array('image/gif', 'image/jpeg', 'image/jpg', 'image/png');

                      //Controllo che l'immagine sia in un formato accettato
                      if( in_array($upItemImg['type'], $allowed) )
                      {
                          $path = '../upload/';

                          //Controllo se esiste già un file con questo nome

                          //Carico il file
                          if( ! move_uploaded_file($upItemImg['tmp_name'], $path.$upItemImg['name']) )
                          {
                              $output .= $this->displayError( $path.$upItemImg['name'] );
                          }
                      }
                      else
                      {
                          $output .= $this->displayError( $this->l('Image format not valid') );
                      }

                $params = array(
                    'id_parent' => (int)$upItemParentId,
                    'name' => pSQL($upItemName),
                    'position' => (int)$upItemPos,
                    'img' => pSQL($path.$upItemImg['name']),
                    'is_shop' => (int)$upItemisShop,
                    'link' => pSQL($upItemLink),
                    'adress' => pSQL($upItemAdress)
                );
              }
               $where = $upItemId;
              $this->updateItem($params,$where);
              $output .= $this->displayConfirmation($this->l('Item updated'));
            }
           
        }
        if (Tools::isSubmit('updateSubItem'.$this->name))
        {
            $upSubItemId = (int)Tools::getValue('sub_item_id');
            $upSubItemParentId = (int)Tools::getValue('sub_item_parent_id');
            $upSubItemName = strval(Tools::getValue('sub_item_name'));
            $upSubItemPos = (int)Tools::getValue('sub_item_position');
            $upSubItemLink = strval(Tools::getValue('sub_item_link'));
            if (!$upSubItemParentId  || empty($upSubItemParentId) || !$this->getItem($upSubItemParentId))
                $output .= $this->displayError( $this->l('Invalid Category Name') );
            elseif (!$upSubItemName  || empty($upSubItemName) || !Validate::isGenericName($upSubItemName))
                $output .= $this->displayError( $this->l('Invalid Item Name') );
            elseif(!$upSubItemPos || empty($upSubItemPos))
                $output .= $this->displayError( $this->l('Not integer position value') );
            else
            {
 
              $params = array(
                'id_parent' => (int)$upSubItemParentId,
                'name' => pSQL($upSubItemName),
                'position' => (int)$upSubItemPos,
                'link' => pSQL($upSubItemLink)
              );
              
              $where = $upSubItemId;
              $this->updateSubItem($params,$where);
              $output .= $this->displayConfirmation($this->l('Sub Item updated'));
            }
           
        }
        if ( Tools::isSubmit('select'.$this->name) ) {
          $db_type = strval(Tools::getValue('db_type'));
          switch ($db_type) {
            case 1;
               $output .= $this->addCategoryForm();
            break;
            case 2;
               $output .= $this->addItemForm();
            break;
            case 3;
               $output .= $this->addSubItemForm();
            break;
            default:
              $output .= $this->displayError( $this->l('Type of new data not valid') );
              $output .= $this->buttonsForm();
              $output .= $this->renderListCat();
              $output .= $this->renderListItems();
            break;
          }
        }
        if ( Tools::isSubmit('deleteDDM_categories') ) {
           $this->deleteCat(Tools::getValue('id'));
           $output .= $this->displayConfirmation($this->l('Category deleted'));
        }
        if ( Tools::isSubmit('deleteDDM_Items') ) {
           $this->deleteItem(Tools::getValue('id'));
           $output .= $this->displayConfirmation($this->l('Item deleted'));
        }
        if ( Tools::isSubmit('deleteDDM_subItems') ) {
           $this->deleteSubItem(Tools::getValue('id'));
           $output .= $this->displayConfirmation($this->l('Sub Item deleted'));
        }
        if ( Tools::isSubmit('updateDDM_categories') ) {
          $output .= $this->updateCategoryForm();
        }
        if ( Tools::isSubmit('updateDDM_Items') ) {
          $output .= $this->updateItemForm();
        }
        if ( Tools::isSubmit('updateDDM_subItems') ) {
          $output .= $this->updateSubItemForm();
         
        }else{
          $output .= $this->buttonsForm();
          $output .= $this->renderListCat();
          $output .= $this->renderListItems();

        }
        
        return $output;
        
    }

    public function deleteCat($id)
    {
      $items = $this->getItemsbyCat($id);
      foreach ($items as $item) {    
          Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute("DELETE FROM "._DB_PREFIX_."DDM_subItems WHERE id_parent='".$item['id']."'");  
      }
      Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute("DELETE FROM "._DB_PREFIX_."DDM_categories WHERE id='$id'");
      Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute("DELETE FROM "._DB_PREFIX_."DDM_Items WHERE id_parent='$id'");
    }
    public function deleteItem($id)
    {  
      Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute("DELETE FROM "._DB_PREFIX_."DDM_Items WHERE id='$id'");
      Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute("DELETE FROM "._DB_PREFIX_."DDM_subItems WHERE id_parent='$id'");
    }
    public function deleteSubItem($id)
    {  
      Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute("DELETE FROM "._DB_PREFIX_."DDM_subItems WHERE id='$id'");
    }
    public function addCategory($params)
    {
      Db::getInstance()->insert('DDM_Categories', $params);
    }
    public function addItem($params)
    {
      Db::getInstance()->insert('DDM_Items', $params);
    }
    public function addSubItem($params)
    {
      Db::getInstance()->insert('DDM_subItems', $params);
    }
    public function updateCategory($params,$where)
    {
      Db::getInstance()->update('DDM_Categories', $params, 'id ='.$where);
    }
    public function updateItem($params,$where)
    {
      Db::getInstance()->update('DDM_Items', $params, 'id ='.$where);
    }
    public function updateSubItem($params,$where)
    {
      Db::getInstance()->update('DDM_subItems', $params, 'id ='.$where);
    }
    public function addCategoryForm()
    {
       $fields_form[0]['form'] = array(   
        'legend' => array(       
          'title' => $this->l('Add a category')                     // This is the name of the fieldset, which can contain many option fields
          
        ),   
        'input' => array(       
          array(           
            'type' => 'text',
            'label' => $this->l('Category name'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'category_name',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
          array(           
            'type' => 'text',
            'label' => $this->l('Category position'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'category_position',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           )   
        ),
        'submit' => array(
          'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
          'class' => 'btn btn-default pull-right'   
        )
      );
    
      $helper = new HelperForm();
      // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
     
    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;
     
    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );
      return $helper->generateForm($fields_form);
        

    }
    public function addItemForm()
    {

      $categories = $this->getCategories();
      $categories_array = array();
      foreach ($categories as $category) {
        $cat_array = array(
            'id_option' => $category['id'],
            'name' => $category['name']
          );
        array_push($categories_array, $cat_array);
      }
       $fields_form[0]['form'] = array(   
        'legend' => array(       
          'title' => $this->l('Add an item')                     // This is the name of the fieldset, which can contain many option fields
          
        ),   
        'input' => array(  
          array(           
            'type' => 'select',
            'label' => $this->l('Category parent'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'item_parent_id',                           // The name of the object property from which we get the value
            'required' => true,                        // If true, PrestaShop will add a red star next to the field
            'options' => array(
                            'query' => $categories_array,
                            'id' => 'id_option',
                            'name' => 'name' 
                           )
              
           ),     
          array(           
            'type' => 'text',
            'label' => $this->l('Item name'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'item_name',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
          array(           
            'type' => 'text',
            'label' => $this->l('Item position'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'item_position',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
           array(           
            'type' => 'file',
            'label' => $this->l('Image'),
            'name' => 'uploadedfile',
            'id' => 'uploadedfile',
            'display_image' => true,
            'required' => true,
            'desc' => $this->l('Image Item size : <br/>Image Shop size : ')
           ),
           array(           
            'type' => 'switch',
            'label' => $this->l('is Shop ?'),
            'name' => 'is_shop',
            'is_bool' => true,
            'required' => true,
            'values' => array(
                array(
                    'id' => 'is_shop_on',
                    'value' => 1,
                    'label' => $this->l('Oui')
                ),
                array(
                    'id' => 'is_shop_off',
                    'value' => 0,
                    'label' => $this->l('Non')
                )
            )
           ),
            array(           
              'type' => 'text',
              'label' => $this->l('Link for shop'),              // Theoretically optional, but in reality each field has to have a label
              'name' => 'item_link',                           // The name of the object property from which we get the value
              'required' => false                        // If true, PrestaShop will add a red star next to the field
              
           ),
            array(           
              'type' => 'text',
              'label' => $this->l('adress for shop'),              // Theoretically optional, but in reality each field has to have a label
              'name' => 'item_adress',                           // The name of the object property from which we get the value
              'required' => false                        // If true, PrestaShop will add a red star next to the field
              
           )
        ),
        'submit' => array(
          'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
          'class' => 'btn btn-default pull-right'   
        )
      );
    
      $helper = new HelperForm();
      // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
     
    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;
     
    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submitItem'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );
      return $helper->generateForm($fields_form);
        

    }
    public function addSubItemForm()
    {
      $items = $this->getItems();
      $items_array = array();
      foreach ($items as $item) {
        $parentCat = $this->getCategory($item['id_parent']);
        $item_name = $parentCat[0]['name'].' -> '.$item['name'];
        $item_array = array(
            'id_option' => $item['id'],
            'name' => $item_name
          );
        array_push($items_array, $item_array);
      }
       $fields_form[0]['form'] = array(   
        'legend' => array(       
          'title' => $this->l('Add a Sub item')                     // This is the name of the fieldset, which can contain many option fields
          
        ),   
        'input' => array(  
          array(           
            'type' => 'select',
            'label' => $this->l('Item parent'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_parent_id',                           // The name of the object property from which we get the value
            'required' => true,                        // If true, PrestaShop will add a red star next to the field
            'options' => array(
                            'query' => $items_array,
                            'id' => 'id_option',
                            'name' => 'name' 
                           )
              
           ),     
          array(           
            'type' => 'text',
            'label' => $this->l('Sub Item name'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_name',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
          array(           
            'type' => 'text',
            'label' => $this->l('Item position'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_position',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
          array(           
            'type' => 'text',
            'label' => $this->l('Item link'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_link',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           )
           
        ),
        'submit' => array(
          'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
          'class' => 'btn btn-default pull-right'   
        )
      );
    
      $helper = new HelperForm();
      // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
     
    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;
     
    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submitSubItem'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );
      return $helper->generateForm($fields_form);
        

    }
    public function buttonsForm()
    {

      
       $fields_form[0]['form'] = array(   
        'legend' => array(       
          'title' => $this->l('Choose an entry to add')                     // This is the name of the fieldset, which can contain many option fields
          
        ),   
        'input' => array(       
          array(           
            'type' => 'select',
            'label' => $this->l('Type of data'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'db_type',                           // The name of the object property from which we get the value
            'required' => true,                       // If true, PrestaShop will add a red star next to the field
            'options' => array(
                            'query' => $options = array(
                                        array(
                                          'id_option' => 1,       // The value of the 'value' attribute of the <option> tag.
                                          'name' => $this->l('Category')    // The value of the text content of the  <option> tag.
                                        ),
                                        array(
                                          'id_option' => 2,
                                          'name' => $this->l('Item') 
                                        ),
                                        array(
                                          'id_option' => 3,
                                          'name' => $this->l('Sub item') 
                                        )
                            ),
                            'id' => 'id_option',
                            'name' => 'name' 
                           )
           )   
        ),
        'submit' => array(
          'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
          'class' => 'btn btn-default pull-right'   
        )
      );
    
      $helper = new HelperForm();
      // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
     
    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;
     
    // Title and toolbar

    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'select'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );
      return $helper->generateForm($fields_form);
        

    }
    public function updateCategoryForm()
    {
      $catToUpdate = $this->getCategory(Tools::getValue('id'));
        $fields_form[0]['form'] = array(   
          'legend' => array(       
            'title' => $this->l('Edit a category')                     // This is the name of the fieldset, which can contain many option fields
            
          ),   
          'input' => array(  
              array(           
                'type' => 'hidden',
                'label' => $this->l('Category id'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'category_id',                           // The name of the object property from which we get the value
                'required' => true                        // If true, PrestaShop will add a red star next to the field
                
               ),     
              array(           
                'type' => 'text',
                'label' => $this->l('Category name'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'category_name',                           // The name of the object property from which we get the value
                'required' => true                        // If true, PrestaShop will add a red star next to the field
                
               ),
              array(           
                'type' => 'text',
                'label' => $this->l('Category position'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'category_position',                           // The name of the object property from which we get the value
                'required' => true                        // If true, PrestaShop will add a red star next to the field
                
               ),   
          ),
          'submit' => array(
            'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
            'class' => 'btn btn-default pull-right'   
          )
        );
      
      $helper = new HelperForm();
        // Module, token and currentIndex
      $helper->module = $this;
      $helper->name_controller = $this->name;
      $helper->token = Tools::getAdminTokenLite('AdminModules');
      $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
      $helper->fields_value['category_id'] = Tools::getValue('id');
      $helper->fields_value['category_name'] = $catToUpdate[0]['name'];
      $helper->fields_value['category_position'] = $catToUpdate[0]['position'];
      // Language
      $helper->default_form_language = $default_lang;
      $helper->allow_employee_form_lang = $default_lang;
       
      // Title and toolbar
      $helper->title = $this->displayName;
      $helper->show_toolbar = true;        // false -> remove toolbar
      $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
      $helper->submit_action = 'update'.$this->name;
      $helper->toolbar_btn = array(
          'save' =>
          array(
              'desc' => $this->l('Save'),
              'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
              '&token='.Tools::getAdminTokenLite('AdminModules'),
          ),
          'back' => array(
              'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
              'desc' => $this->l('Back to list')
          )
      );
      
        return $helper->generateForm($fields_form);

    }
    public function updateItemForm()
    {
        $ItemToUpdate = $this->getItem(Tools::getValue('id'));

        

        $image = $ItemToUpdate[0]['img'];
        $image_url = ImageManager::thumbnail($image, $this->table.'_'.(int)$ItemToUpdate->id.'.'.$this->imageType, 350,
          $this->imageType, true, true);
        $image_size = file_exists($image) ? filesize($image) / 1000 : false;


        $categories = $this->getCategories();
        $categories_array = array();
        foreach ($categories as $category) {
          $cat_array = array(
              'id_option' => $category['id'],
              'name' => $category['name']
            );
          array_push($categories_array, $cat_array);
        }
         $fields_form[0]['form'] = array(   
          'legend' => array(       
            'title' => $this->l('Edit an item')                     // This is the name of the fieldset, which can contain many option fields
            
          ),   
          'input' => array(
            array(           
                'type' => 'hidden',
                'label' => $this->l('Item id'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'item_id',                           // The name of the object property from which we get the value
                'required' => true                        // If true, PrestaShop will add a red star next to the field
                
            ),  
            array(           
              'type' => 'select',
              'label' => $this->l('Category parent'),              // Theoretically optional, but in reality each field has to have a label
              'name' => 'item_parent_id',                           // The name of the object property from which we get the value
              'required' => true,                        // If true, PrestaShop will add a red star next to the field
              'options' => array(
                              'query' => $categories_array,
                              'id' => 'id_option',
                              'name' => 'name' 
                             )
                
             ),     
            array(           
              'type' => 'text',
              'label' => $this->l('Item name'),              // Theoretically optional, but in reality each field has to have a label
              'name' => 'item_name',                           // The name of the object property from which we get the value
              'required' => true                        // If true, PrestaShop will add a red star next to the field
              
             ),
            array(           
              'type' => 'text',
              'label' => $this->l('Item position'),              // Theoretically optional, but in reality each field has to have a label
              'name' => 'item_position',                           // The name of the object property from which we get the value
              'required' => true                        // If true, PrestaShop will add a red star next to the field
              
             ),
             array(           
              'type' => 'file',
              'label' => $this->l('Image'),
              'name' => 'uploadedfile',
              'id' => 'uploadedfile',
              'image' => $image_url ? $image_url : false,
              'size' => $image_size,
              'display_image' => true,
              'required' => false,
              'desc' => $this->l('Image Item size : <br/>Image Shop size : ')
             ),
             array(           
              'type' => 'switch',
              'label' => $this->l('is Shop ?'),
              'name' => 'is_shop',
              'is_bool' => true,
              'required' => true,
              'values' => array(
                  array(
                      'id' => 'is_shop_on',
                      'value' => 1,
                      'label' => $this->l('Oui')
                  ),
                  array(
                      'id' => 'is_shop_off',
                      'value' => 0,
                      'label' => $this->l('Non')
                  )
              )
             ),
              array(           
                'type' => 'text',
                'label' => $this->l('Link for shop'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'item_link',                           // The name of the object property from which we get the value
                'required' => false                        // If true, PrestaShop will add a red star next to the field
                
             ),
              array(           
                'type' => 'text',
                'label' => $this->l('adress for shop'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'item_adress',                           // The name of the object property from which we get the value
                'required' => false                        // If true, PrestaShop will add a red star next to the field
                
             )
          ),
          'submit' => array(
            'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
            'class' => 'btn btn-default pull-right'   
          )
        );
      
        $helper = new HelperForm();
        // Module, token and currentIndex
      $helper->module = $this;
      $helper->name_controller = $this->name;
      $helper->token = Tools::getAdminTokenLite('AdminModules');
      $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
      $helper->fields_value['item_id'] = Tools::getValue('id');
      $helper->fields_value['item_parent_id'] = $ItemToUpdate[0]['id_parent'];
      $helper->fields_value['item_name'] = $ItemToUpdate[0]['name'];
      $helper->fields_value['item_position'] = $ItemToUpdate[0]['position'];
      $helper->fields_value['is_shop'] = $ItemToUpdate[0]['is_shop'];
      $helper->fields_value['item_link'] = $ItemToUpdate[0]['link'];
      $helper->fields_value['item_adress'] = $ItemToUpdate[0]['adress'];

      // Language
      $helper->default_form_language = $default_lang;
      $helper->allow_employee_form_lang = $default_lang;
       
      // Title and toolbar
      $helper->title = $this->displayName;
      $helper->show_toolbar = true;        // false -> remove toolbar
      $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
      $helper->submit_action = 'updateItem'.$this->name;
      $helper->toolbar_btn = array(
          'save' =>
          array(
              'desc' => $this->l('Save'),
              'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
              '&token='.Tools::getAdminTokenLite('AdminModules'),
          ),
          'back' => array(
              'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
              'desc' => $this->l('Back to list')
          )
      );
        return $helper->generateForm($fields_form);
          

    }
    public function updateSubItemForm()
    {
      $SubItemToUpdate = $this->getSubItem(Tools::getValue('id'));
      $items = $this->getItems();
      $items_array = array();
      foreach ($items as $item) {
        $parentCat = $this->getCategory($item['id_parent']);
        $item_name = $parentCat[0]['name'].' -> '.$item['name'];
        $item_array = array(
            'id_option' => $item['id'],
            'name' => $item_name
          );
        array_push($items_array, $item_array);
      }
       $fields_form[0]['form'] = array(   
        'legend' => array(       
          'title' => $this->l('Edit a Sub item')                     // This is the name of the fieldset, which can contain many option fields
          
        ),   
        'input' => array(
          array(           
                'type' => 'hidden',
                'label' => $this->l('Sub Item id'),              // Theoretically optional, but in reality each field has to have a label
                'name' => 'sub_item_id',                           // The name of the object property from which we get the value
                'required' => true                        // If true, PrestaShop will add a red star next to the field
                
          ),    
          array(           
            'type' => 'select',
            'label' => $this->l('Item parent'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_parent_id',                           // The name of the object property from which we get the value
            'required' => true,                        // If true, PrestaShop will add a red star next to the field
            'options' => array(
                            'query' => $items_array,
                            'id' => 'id_option',
                            'name' => 'name' 
                           )
              
           ),     
          array(           
            'type' => 'text',
            'label' => $this->l('Sub Item name'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_name',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
          array(           
            'type' => 'text',
            'label' => $this->l('Item position'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_position',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           ),
          array(           
            'type' => 'text',
            'label' => $this->l('Item link'),              // Theoretically optional, but in reality each field has to have a label
            'name' => 'sub_item_link',                           // The name of the object property from which we get the value
            'required' => true                        // If true, PrestaShop will add a red star next to the field
            
           )
           
        ),
        'submit' => array(
          'title' => $this->l('   Save   '),                       // This is the button that saves the whole fieldset.
          'class' => 'btn btn-default pull-right'   
        )
      );
        $helper = new HelperForm();
      // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
    $helper->fields_value['sub_item_id'] = Tools::getValue('id');
    $helper->fields_value['sub_item_parent_id'] = $SubItemToUpdate[0]['id_parent'];
    $helper->fields_value['sub_item_name'] = $SubItemToUpdate[0]['name'];
    $helper->fields_value['sub_item_position'] = $SubItemToUpdate[0]['position'];
    $helper->fields_value['sub_item_link'] = $SubItemToUpdate[0]['link'];
    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;
     
    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'updateSubItem'.$this->name;
    $helper->toolbar_btn = array(
        'save' =>
        array(
            'desc' => $this->l('Save'),
            'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
        'back' => array(
            'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->l('Back to list')
        )
    );
      return $helper->generateForm($fields_form);

    }
    public function getCategories()
    {
      
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_Categories ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function getCategory($id)
    {
      
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_Categories WHERE id='.$id.' ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function getItems()
    {
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_Items ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function getItem($id)
    {
      
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_Items WHERE id='.$id.' ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function getItemsbyCat($cat)
    {
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_Items WHERE id_parent = '.$cat.' ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function getSubItem($id)
    {
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_subItems WHERE id='.$id.' ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function getSubItemsbyItem($item)
    {
      $sql = 'SELECT * FROM '._DB_PREFIX_.'DDM_subItems WHERE id_parent = '.$item.' ORDER BY position ASC';
      $res = Db::getInstance()->ExecuteS($sql);
      return $res;
    }
    public function renderListCat()
    {
            
     $categories = $this->getCategories();
      
     $fields_list = array(
            'id' => array(
                'title' => $this->l('#'),
                'type' => 'text',
            ),
            'name' => array(
                'title' => $this->l('category name'),
                'type' => 'text',
            ),
            'position' => array(
                'title' => $this->l('position'),
                'type' => 'text',
            )
        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id';
        $helper->table = 'DDM_categories';
        $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->title = $this->l('Categories list');
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        return $helper->generateList($categories, $fields_list);
    }
    public function renderListItems()
    {
            
     $categories = $this->getCategories();
      foreach ($categories as $category) {
        $items = $this->getItemsbyCat($category['id']);

        $fields_list = array(
            'id' => array(
                'title' => $this->l('#'),
                'type' => 'text',
            ),
            'name' => array(
                'title' => $this->l('item name'),
                'type' => 'text',
            ),
            'position' => array(
                'title' => $this->l('position'),
                'type' => 'text',
            ),
            'img' => array(
                'title' => $this->l('image'),
                'type' => 'file'
            ),
            'is_shop' => array(
                'title' => $this->l('is shop'),
                'type' => 'bool',
                'name' => 'is_shop',
                'icon' => array(                              // If set, an icon will be displayed with icon key matching the field value.
                  0 => 'disabled.gif',                         // Used in combination with type == bool (optional).
                  1 => 'enabled.gif',
                  'default' => 'disabled.gif'
                ),
            ),
            'position' => 'position',
            'link' => array(
                'title' => $this->l('link for shop only'),
                'type' => 'text',
                'name' => 'link_if_shop_on'
            ),
            'adress' => array(
                'title' => $this->l('adress for shop only'),
                'type' => 'text',
                'name' => 'adress_if_shop_on'
            )

        );

        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = true;
        $helper->identifier = 'id';
        $helper->table = 'DDM_Items';
        $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->title = $category['name'];
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;


        $items_lists .= $helper->generateList($items, $fields_list);

        foreach ($items as $item) {
          if ($item['is_shop'] == 0) {
            $items = $this->getSubItemsbyItem($item['id']);
              $fields_list = array(
                'id' => array(
                    'title' => $this->l('#'),
                    'type' => 'text',
                ),
                'name' => array(
                    'title' => $this->l('Sub item name'),
                    'type' => 'text',
                ),
                'position' => array(
                    'title' => $this->l('position'),
                    'type' => 'text',
                ),
                'img' => array(
                    'title' => $this->l('link'),
                    'type' => 'file',
                )
            );

            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = true;
            $helper->identifier = 'id';
            $helper->table = 'DDM_subItems';
            $helper->actions = array('edit', 'delete');
            $helper->show_toolbar = false;
            $helper->module = $this;
            $helper->title = $category['name'].' -> '.$item['name'];
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

            $items_lists .= $helper->generateList($items, $fields_list);
        }
          }
          
      }
     return $items_lists;
    }

    public function hookDisplayTop($params)
    {
        if (Tools::isEmpty($this->_menu)) {
                $this->makeMenu();
            }
      $this->context->smarty->assign(
          array(
              'my_module_name' => Configuration::get('MYMODULE_NAME'),
              'my_module_link' => $this->context->link->getModuleLink('blockdropdownmenu', 'display'),
              'MENU' => $this->_menu
          )
      );

      return $this->display(__FILE__, 'blockdropdownmenu.tpl');
    }
    public function hookDisplayHeader()
    {
      $this->context->controller->addCSS($this->_path.'css/blockdropdownmenu.css', 'all');
    }  
    public function makeMenu()
    {
        $uploadDir = _THEME_PROD_PIC_DIR_;
      $categories = $this->getCategories();
      foreach ($categories as $category) {
        $items = $this->getItemsbyCat($category['id']);
        $this->_menu .= '<li>'.$category['name'];
        if ( !empty($items) ) {
          $this->_menu .= '<div class="dropdownmenu"><div class="dropdowntitles">';
          foreach ($items as $item) {
            if ($item['is_shop'] == true) {
              $this->_menu .= '<div class="dropdowntitle" cid="'.$item['id'].'">'.$item['name'].'
                                  <div class="ddcContent magasin" cid="1">
                                    <div class="frontOffer">
                                      <img src="'.$uploadDir.$item['img'].'" />
                                      <div class="adress">'.$item['adress'].'</div>
                                      <button class="knowMore" href="'.$item['link'].'">En savoir plus...</button>
                                    </div>
                                  </div>
                                </div>';
            }else{
              $this->_menu .= '<div class="dropdowntitle" cid="'.$item['id'].'">'.$item['name'].'
                                  <div class="ddcContent" cid="1">
                                    <div class="links">
                                      <h3>'.$item['name'].'</h3>
                                      <ul>';
              $subItems = $this->getSubItemsbyItem($item['id']);
              foreach ($subItems as $subitem) {
                $this->_menu .= '<li><a href="'.$subitem['link'].'">'.$subitem['name'].'</a></li>';
              }
              $this->_menu .= '</ul>
                                  </div>
                                    <div class="frontOffer">
                                      <img src="'.$uploadDir.$item['img'].'" />
                                    </div>
                                  </div>
                                </div>';
            }
            
          }
          $this->_menu .= '</div></div>';
        }
        
        $this->_menu .= '</li>';
      }
    }
    protected function clearMenuCache()
    {
        $this->_clearCache('blockdropdownmenu.tpl');
    }
    public function hookDisplayBackOfficeHeader($params)
    {
      $this->context->controller->addCSS($this->_path.'css/ddmenu.css');
    }
}
?>