<?php

class PeepSoDeveloperToolsPageHome extends PeepSoDeveloperToolsPage
{
	public function __construct()
	{
		$this->title='Developer Tools <nobr><small>by PeepSo</small></nobr>';
	}

	public function page()
	{

		$this->page_start();
		echo $this->page_data();
		$this->page_end('');
	}

	public function page_data()
	{
        $PeepSoDeveloperTools = PeepSoDeveloperTools::get_instance();
		ob_start();
		?>
		<div id="welcome-panel" class="welcome-panel">
			<div class="welcome-panel-content">
				<h2>
					<?php _e('Welcome to Developer Tools by PeepSo!','peepsodebug');?>
				</h2>

				<p class="about-description">
					<?php _e('Use the tabs on top to use the available functions:','peepsodebug');?>
				</p>
				<?php
				foreach($PeepSoDeveloperTools->pages_config as $page) {
					if('home' == $page) { continue; }
					?>
					<p>
					<h3>
						<a href="<?php menu_page_url( 'peepsodebug_'.$page);?>"><?php echo $PeepSoDeveloperTools->pages[$page]->title;?></a>
					</h3>
					<?php echo $PeepSoDeveloperTools->pages[$page]->description;?>
					</p>
					<?php
				}
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function peepsodebug_buttons($buttons)
	{
		return array();
	}
}

// EOFpage_phpinfo.php