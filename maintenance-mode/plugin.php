<?php

class pluginMaintenanceMode extends Plugin {

	public function init()
	{
		$this->dbFields = array(
			'enable'=>false,
			'allow_bots'=>false,
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
			exit( $this->getValue('message') );
		}
	}
	
	//Not used yet...
	public function _bot_detected() {

	  return (
		isset($_SERVER['HTTP_USER_AGENT'])
		&& preg_match('/bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT'])
	  );
	}
}