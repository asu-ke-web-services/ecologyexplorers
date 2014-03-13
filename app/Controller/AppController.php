<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');

Configure::write('adminEmailAddress',array('email'=>'Ecology.Explorers@asu.edu','name' => 'Ecology Explorers'));
Configure::write('fromEmailAddress',array('email'=>'Ecology.Explorers@asu.edu','name' => 'Ecology Explorers'));


/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package       app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

	//This method is called to send email to the user or the admin. Users get email when the profile is approved is created and if password has to be reset.
	//Admin will get an email when a new profile is created and needs to be approved.
	//Admin will also get an email when new data has been submitted by users in Data Center
	public function sendEmail($body,$to,$subject)
	{
		$fromEmailAddress= array(Configure::read('fromEmailAddress.email') =>  Configure::read('fromEmailAddress.name'));
		
		if($to == "admin")
			$to = array(Configure::read('adminEmailAddress.email') =>  Configure::read('adminEmailAddress.name'));
		
		$Email = new CakeEmail('smtp');
		$Email->from($fromEmailAddress)
		->to($to)
		->emailFormat('html')
		->subject($subject)
		->send($body);
	}
}


