<?php
/**
 * @package admin
 * @copyright Copyright 2003-2018 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: move_product_confirm.php for CEON URI Mapping 2018-03-29 08:49:16Z webchills $
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

        $products_id = zen_db_prepare_input($_POST['products_id']);
        $new_parent_id = zen_db_prepare_input($_POST['move_to_category_id']);

        $duplicate_check = $db->Execute("select count(*) as total
                                        from " . TABLE_PRODUCTS_TO_CATEGORIES . "
                                        where products_id = '" . (int)$products_id . "'
                                        and categories_id = '" . (int)$new_parent_id . "'");

        if ($duplicate_check->fields['total'] < 1) {
          $db->Execute("update " . TABLE_PRODUCTS_TO_CATEGORIES . "
                        set categories_id = '" . (int)$new_parent_id . "'
                        where products_id = '" . (int)$products_id . "'
                        and categories_id = '" . (int)$current_category_id . "'");

          // reset master_categories_id if moved from original master category
          $check_master = $db->Execute("select products_id, master_categories_id from " . TABLE_PRODUCTS . " where products_id='" .  (int)$products_id . "'");
          if ($check_master->fields['master_categories_id'] == (int)$current_category_id) {
            $db->Execute("update " . TABLE_PRODUCTS . "
                          set master_categories_id='" . (int)$new_parent_id . "'
                          where products_id = '" . (int)$products_id . "'");
          }

          // BEGIN CEON URI MAPPING 1 of 1
          require_once(DIR_WS_CLASSES . 'class.CeonURIMappingAdminProductPages.php');
          
          $ceon_uri_mapping_admin = new CeonURIMappingAdminProductPages();
          
          $ceon_uri_mapping_admin->moveProductConfirmHandler($products_id, $product_type,
            $zc_products->get_handler($product_type), $new_parent_id);
          
          // END CEON URI MAPPING 1 of 1
          // reset products_price_sorter for searches etc.
          zen_update_products_price_sorter((int)$products_id);
          zen_record_admin_activity('Moved product ' . (int)$products_id . ' from category ' . (int)$current_category_id . ' to category ' . (int)$new_parent_id, 'notice');

        } else {
          $messageStack->add_session(ERROR_CANNOT_MOVE_PRODUCT_TO_CATEGORY_SELF, 'error');
        }

        zen_redirect(zen_href_link(FILENAME_CATEGORIES, 'cPath=' . $new_parent_id . '&pID=' . $products_id . (isset($_GET['page']) ? '&page=' . $_GET['page'] : '')));
