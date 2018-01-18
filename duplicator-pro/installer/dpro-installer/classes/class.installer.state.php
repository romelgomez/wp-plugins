<?php
defined("ABSPATH") or die("");

abstract class DUPX_InstallerMode
{
    const StandardInstall = 0;
    const ImportInstall = 1;
}

class DUPX_InstallerState
{
	const State_Filename = 'installer-state.txt';
    public $mode = DUPX_InstallerMode::StandardInstall;

    public $ovr_dbhost = '';
    public $ovr_dbname = '';
    public $ovr_dbuser = '';
    public $ovr_dbpass = '';
    public $ovr_wp_content_dir = '';

    private static $state_filepath = null;

	private static $instance = null;

    public static function init($clearState) {
        self::$state_filepath = dirname(__FILE__).'/../installer-state.txt';

        if($clearState) {
            SnapLibIOU::rm(self::$state_filepath);
        }
    }

	public static function getInstance()
	{
		if (self::$instance == null) {

			self::$instance = new DUPX_InstallerState();

            self::$instance->initFromQueryString();

			if (file_exists(self::$state_filepath)) {

				$file_contents = file_get_contents(self::$state_filepath);
				$data = json_decode($file_contents);

				foreach ($data as $key => $value) {
					self::$instance->{$key} = $value;
				}
            } else {
                if(self::$instance->mode == DUPX_InstallerMode::ImportInstall) {
                    $wpConfigPath	= "{$GLOBALS['DUPX_ROOT']}/wp-config.php";

					if(file_exists($wpConfigPath)) {
						$defines = DUPX_WPConfig::parseDefines($wpConfigPath);

						self::$instance->ovr_dbhost = SnapLibUtil::getArrayValue($defines, 'DB_HOST');
						self::$instance->ovr_dbname = SnapLibUtil::getArrayValue($defines, 'DB_NAME');
						self::$instance->ovr_dbuser = SnapLibUtil::getArrayValue($defines, 'DB_USER');
						self::$instance->ovr_dbpass = SnapLibUtil::getArrayValue($defines, 'DB_PASSWORD');
                        self::$instance->ovr_wp_content_dir = SnapLibUtil::getArrayValue($defines, 'WP_CONTENT_DIR', false, $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-content');
					} else {
						throw new Exception("{$wpConfigPath} doesn't exist!");
					}
                }


                self::$instance->save();
            }
		}

		return self::$instance;
	}

    public function initFromQueryString()
    {
        if(isset($_GET['mode'])) {
            switch($_GET['mode']) {
                case 'standard':
                    $this->mode = DUPX_InstallerMode::StandardInstall;
                    break;

                case 'overwrite':
                    $this->mode = DUPX_InstallerMode::ImportInstall;
                    break;

                default:
                    throw new Exception("Unknown mode specified: {$_GET['mode']}");
            }
        }
    }

    public function save()
    {
		$data = SnapLibStringU::jsonEncode($this);

        SnapLibIOU::filePutContents(self::$state_filepath, $data);
    }
}

DUPX_InstallerState::init($GLOBALS['INIT']);