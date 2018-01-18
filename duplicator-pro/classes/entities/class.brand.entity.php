<?php
defined("ABSPATH") or die("");

/**
 * Brand entity layer
 *
 * Standard: PSR-2
 * @link http://www.php-fig.org/psr/psr-2
 *
 * @package DUP_PRO
 * @subpackage classes/entities
 * @copyright (c) 2017, Snapcreek LLC
 * @license	https://opensource.org/licenses/GPL-3.0 GNU Public License
 *
 * @todo Finish Docs
 */

/* @var $global DUP_PRO_Global_Entity */
/* @var $brand DUP_PRO_Brand_Entity */

require_once(DUPLICATOR_PRO_PLUGIN_PATH.'/classes/entities/class.json.entity.base.php');

// For those brand types that do not require any configuration ahead of time
abstract class DUP_PRO_Brand_Modes
{
	const keepPlugin   = 0;
	const removePlugin = 1;
}

// For those brand types that do not require any configuration ahead of time
abstract class DUP_PRO_BRAND_IDS
{
    const defaultBrand = -2;
}

class DUP_PRO_Brand_Entity extends DUP_PRO_JSON_Entity_Base
{
	public $name		 = '';
	public $notes		 = '';
	public $brandMode	 = DUP_PRO_Brand_Modes::removePlugin;
	public $editable	 = true;
	public $logo         = '<i class="fa fa-bolt"></i> Duplicator Pro';
	public $active		 = false;

	function __construct()
	{
		parent::__construct();
		$this->name = DUP_PRO_U::__('');
	}

	public static function get_all()
	{
		$default_brand = self::get_default_brand();
		$brands = self::get_by_type(get_class());
		array_unshift($brands, $default_brand);
		return $brands;
	}

	public static function delete_by_id($brand_id)
	{
		parent::delete_by_id_base($brand_id);
	}

	public static function get_by_id($id)
	{
		if ($id == DUP_PRO_BRAND_IDS::defaultBrand) {
			return self::get_default_brand();
		}

		$brand = self::get_by_id_and_type($id, get_class());

		return $brand;
	}

	public function get_mode_text()
	{
		$txt = DUP_PRO_U::__('Unknown');

		switch ($this->brandMode) {
			case DUP_PRO_Brand_Modes::keepPlugin :
				$txt = DUP_PRO_U::__('Keep Plugin');
				break;
			case DUP_PRO_Brand_Modes::removePlugin :
				$txt = DUP_PRO_U::__('Remove Plugin');
				break;
		}

		return $txt;
	}

	public function save()
	{
		parent::save();
	}

    public static function get_default_brand()
    {
        $global = DUP_PRO_Global_Entity::get_instance();
        $default_brand = new DUP_PRO_Brand_Entity();
        $default_brand->name                 = DUP_PRO_U::__('Default');
        $default_brand->notes                = DUP_PRO_U::__('The default content used when a brand is not defined');
        $default_brand->id                   = DUP_PRO_BRAND_IDS::defaultBrand;
		$default_brand->logo                 = DUP_PRO_U::__('<i class="fa fa-bolt"></i> Duplicator Pro');
        $default_brand->editable             = false;
        return $default_brand;
    }

}