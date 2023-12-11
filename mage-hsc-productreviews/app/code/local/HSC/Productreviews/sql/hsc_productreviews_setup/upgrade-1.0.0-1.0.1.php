<?php
$installer = new Mage_Sales_Model_Resource_Setup('core_setup');
$code = 'aov_modal_products';

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', $code);

if (!isset($attr) || !$attr->getId()) {
    $installer->addAttribute(Mage_Catalog_Model_Product::ENTITY, $code, array(
        'input'                       => 'text',
        'label'                       => 'AOV Cart Modal Products',
        'global'                      => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible'                     => 1,
        'required'                    => 0,
        'visible_on_front'            => 0,
        'is_html_allowed_on_front'    => 0,
        'searchable'                  => 0,
        'filterable'                  => 0,
        'comparable'                  => 0,
        'is_configurable'             => 0,
        'unique'                      => false,
        'user_defined'                => true,
        'used_in_product_listing'     => false,
        'apply_to'                    => array('simple', 'configurable','grouped'),
        'note'            => 'Add a comma separated list of product IDs used to populate the cart AOV modal pop-up. Simple products only can be added. No products with options can be added.'
    ));

    $sort_order = 12;
    $attribute_group_id = 'General';

    // Add attribute to attribute set(s)
    $attributeSets = ['Koozie', 'Corp and Legal', 'Custom Gifts (W)', 'Custom Printing', 'Name Tag (W)', 'Name Tags', 'Plaques & Awards (W)', 'Signs (W)', 'Stamps (W)'];

    foreach ($attributeSets as $attributeSet) {
        $installer->addAttributeToSet(Mage_Catalog_Model_Product::ENTITY, $attributeSet, $attribute_group_id, $code, $sort_order);
    }
}

$attrCat = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_category', 'aov_modal_category_products');
if (!$attrCat->getId()) {
    $installer->addAttribute('catalog_category', 'aov_modal_category_products',  array(
        'type'              => 'text',
        'label'             => 'AOV Modal Products',
        'note'              => 'Add a comma separated list of product IDs used to populate the cart AOV modal pop-up. Simple products only can be added. No products with options can be added.',
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
