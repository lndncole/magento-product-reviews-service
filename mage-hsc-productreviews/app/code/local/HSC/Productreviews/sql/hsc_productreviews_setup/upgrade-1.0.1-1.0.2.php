<?php
//Set new EAV on product level
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$productEavCode = 'aov_modal_subheader';

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', $productEavCode);

if (!isset($attr) || !$attr->getId()) {
    $installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $productEavCode, array(
        'input'                       => 'text',
        'label'                       => 'AOV Cart Modal Sub-header',
        'global'                      => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'                     => 1,
        'required'                    => 0,
        'visible_on_front'            => 0,
        'is_html_allowed_on_front'    => 1,
        'searchable'                  => 0,
        'filterable'                  => 0,
        'comparable'                  => 0,
        'is_configurable'             => 0,
        'unique'                      => false,
        'user_defined'                => true,
        'used_in_product_listing'     => false,
        'apply_to'                    => array('simple', 'configurable'),
        'note'            => 'Add a subheader to display on the AOV modal at checkout when this product triggers the AOV modal.'
    ));

    $sort_order = 12;
    $attribute_group_id = 'General';

    // Add attribute to attribute set(s)
    $attributeSets = ['Koozie', 'Corp and Legal', 'Custom Gifts (W)', 'Custom Printing', 'Name Tag (W)', 'Name Tags', 'Plaques & Awards (W)', 'Signs (W)', 'Stamps (W)'];

    foreach ($attributeSets as $attributeSet) {
        $installer->addAttributeToSet(Mage_Catalog_Model_Product::ENTITY, $attributeSet, $attribute_group_id, $productEavCode, $sort_order);
    }
}

//Set new EAV on category level
$categoryEavCode = 'aov_modal_cat_subheader';

$attrCat = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_category', $categoryEavCode);
if (!$attrCat->getId()) {
    $installer->addAttribute('catalog_category', $categoryEavCode,  array(
        'type'              => 'text',
        'label'             => 'AOV Cart Modal Sub-header',
        'note'              => 'Add a subheader to display on the AOV modal at checkout when this category triggers the AOV modal.',
        'input'             => 'text',
        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'wysiwyg_enabled'   => false,
        'visible'           => true,
        'required'          => false,
        'default'           => false,
        'group'             => 'General Information',
    ));
}

// End install
$installer->endSetup();
