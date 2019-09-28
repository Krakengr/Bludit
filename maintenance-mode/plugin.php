<?php

class pluginMaintenanceMode extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'enable'=>false,
			'message'=>'Temporarily down for maintenance.'
		);
	}

	public function form()
	{
		global $L;

		$html  = '<div class="alert alert-primary" role="alert">';
		$html .= $this->description();
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('Enable maintenance mode').'</label>';
		$html .= '<select name="enable">';
		$html .= '<option value="true" '.($this->getValue('enable')===true?'selected':'').'>Enabled</option>';
		$html .= '<option value="false" '.($this->getValue('enable')===false?'selected':'').'>Disabled</option>';
		$html .= '</select>';
		$html .= '</div>';

		$html .= '<div>';
		$html .= '<label>'.$L->get('Message').'</label>';
		$html .= '<input name="message" id="jsmessage" type="text" value="'.$this->getValue('message').'">';
		$html .= '</div>';

		return $html;
	}

	public function beforeAll()
	{
		
		$login = new Login();

		if ($this->getValue('enable') && !$login->isLogged() ) 
		{
			echo $this->template();
			exit;
			
			//exit( $this->getValue('message') );
		}
	}
	
	//Not used yet...
	public function _bot_detected() 
	{

	  return (
		isset($_SERVER['HTTP_USER_AGENT'])
		&& preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
	  );
	}
	
	public function template()
	{
		
		$html = '<!DOCTYPE html>
			<html lang="' . Theme::lang() . '">
			<head>
				<title>Site Maintenance</title>
				<style>
				  body { text-align: center; padding: 150px; }
				  h1 { font-size: 50px; }
				  body { font: 20px Helvetica, sans-serif; color: #333; }
				  article { display: block; text-align: left; width: 650px; margin: 0 auto; }
				  a { color: #dc8100; text-decoration: none; }
				  a:hover { color: #333; text-decoration: none; }
				</style>
			</head>
			<body>
				<article>
					<h1>We&rsquo;ll be back soon!</h1>
					<div>
						<p>' . $this->getValue('message') . '</p>
					</div>
				</article>
			</body>
			</html>';
			
		return $html;
	}
}