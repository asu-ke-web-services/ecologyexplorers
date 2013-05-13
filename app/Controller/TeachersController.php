<?php
App::uses('AppController', 'Controller');
App::uses('Utitlity','Security');
App::uses('CakeEmail', 'Utitlity');


/**
 * Teachers Controller
 *
 * @property Teacher $Teacher
*/
class TeachersController extends AppController {
	public $helpers = array('Html', 'Form','CSV');

	//The email address from which all the mail will be sent. Emails can be sent to admins and also to the teachers.
	var $fromEmailAddress= array('rohit.srikanta@asu.edu' => 'Ecology Explorers');


	public function index()
	{
		$this->Teacher->recursive = 0;
		$this->set('teachers', $this->paginate());
	}


	//Method to validate the user credentials before login.
	public function login($fields = null)
	{
		if($this->Session->check('User'))
		{
			$this->Session->setFlash('You have already logged in. Please logout.');
			$this->redirect(array(
					'action' => 'index'));

		}

		if ($this->request->is('post'))
		{

			$user = $this->Teacher->validateLogin($this->request->data['Teacher']['email_address'],$this->request->data['Teacher']['password']);
			if ($user)
			{
				if("pending" == $user)
				{
					$this->Session->setFlash('Your account is not yet approved. You will receive an e-mail once its approved.');
				}
				else
				{
					//Writing the user information into the session variable and redirecting to the home page.
					$this->Session->setFlash('Login Successful');
					$this->Session->write('User', $user);
					$this->Session->write('Username', $user['Teacher']['name']);
					$this->Session->write('UserType',$user['Teacher']['type']);
					
					//Prompting the user to change the username and password the profile has default values 
					$pos = strpos($user['Teacher']['email_address'], "nullemail");
					if($user['Teacher']['password'] == Security::hash("CAPLTER") && $pos !== false)
					{
						$this->Session->setFlash('Login Successful. Please change the email address and password.');
						$this->redirect(array(
								'action' => 'editProfile'));
					}	
					if($user['Teacher']['password'] == Security::hash("CAPLTER"))
					{
						$this->Session->setFlash('Login Successful. Please change the password.');
						$this->redirect(array(
								'action' => 'editProfile'));
					}
					if($pos !== false)
					{
						$this->Session->setFlash('Login Successful. Please correct the email address.');
						$this->redirect(array(
								'action' => 'editProfile'));
					}
					$this->redirect(array(
							'action' => 'index'));
				}
			}
			else
			{
				$this->Session->setFlash('Unable to login. Please check your email address and password that was entered');
			}
		}
	}

	//Method to logout the user.
	public function logout()
	{
		if($this->Session->check('User'))
		{
			$this->Session->destroy();
			$this->Session->setFlash('You have been logged out!');
			$this->redirect(array(
					'controller' => 'pages', 'action' => 'display'));
			//exit();
		}
	}

	//Method that redirects to the home page of the techer model.
	public function home()
	{
		$this->redirect(array('action' => 'index'));
	}

	//Method to register a new teacher into the system.
	public function register()
	{
		//Loading the school model so that the school dropdown can be populated.
		$this->set('schooloptions', ClassRegistry::init('School')->schoolOptions());

		if ($this->request->is('post'))
		{

			//Check to see if the email address already exists in the database.
			if($this->Teacher->checkEmailAddressExists($this->request->data['Teacher']['email_address']))
			{
				$this->Session->setFlash('Email Address already exists. Please try a different one.');
			}
			else
			{
				//If the above validation, along with the model validation is satisfied, create the user profile.

				if ($this->Teacher->createUser($this->request->data))
				{
					$body = '<br>'.$this->request->data['Teacher']['name'].' has recently created an account in Ecology Explorer\'s website.
							Please review the user details and take necessary action.<br><br>
							This is a autogenerated mail. Please do not reply to this mail.';
					$subject = 'New account registered in Ecology Explorers';
					$to = 'rohit.srikanta@asu.edu';
					$fromEmailAddress= array('rohit.srikanta@asu.edu' => 'Ecology Explorers');

					$this->sendemail($body,$to,$this->fromEmailAddress,$subject);

					$this->Session->setFlash(__('Profile Created. Data can be submitted once your profile is approved. Until then please feel free to download existing data.'));

					//After registering, the user is redirected to the main page.
					$this->redirect(array(
							'action' => 'index'));
				}
				else
				{
					//Errors that has to be fixed before creating user profile.
					$this->Session->setFlash(__('Unable to create profile. Please try again after correcting the errors shown below.'));
				}
			}
		}
	}

	//This method is called to send email to the user or the admin. Users get email when the profile is approved is created and if password has to be reset.
	//Admin will get an email when a new profile is created and needs to be approved.
	function sendemail($body,$to,$from,$subject)
	{

		$Email = new CakeEmail();
		$Email->from($from)
		->to($to)
		->emailFormat('html')
		->subject($subject)
		->send($body);
	}

	//This method is called to approve the users pending profile. This is accessible by admins only. 
	function approveUser()
	{
		if($this->authorizedUser())
		{
			$query = $this->Teacher->getUsers();
			$this->set('teachersYetToBeApproved', $query);

			if ($this->request->is('post'))
			{
				$temp['value'] = $this->request->data['Approve'];

				for($i=0; $i<count($query); $i++)
				{
					if(1 == $temp['value'][$query[$i]['Teacher']['id']])
					{
						$this->Teacher->approveUser($query[$i]['Teacher']['id'], 'T');

						$body = 'Your account has been approved for Ecology Explorers Data Center. Please login to submit data to Ecology Explorers Data Center.
								<br><br>This is a autogenerated mail. Please do not reply to this mail.';
						$to = $query[$i]['Teacher']['email_address'];
						$subject = 'Ecology Explorers Profile Approved';
						$this->sendemail($body,$to,$this->fromEmailAddress,$subject);
					}
				}

				$this->set('teachersYetToBeApproved', $this->Teacher->getUsers());
			}
		}
	}

	//This method is called before accessing any admin functionality
	function authorizedUser()
	{
		if('A' != $this->Session->read('UserType'))
		{
			$this->Session->setFlash(__('You do not have permissions to access this page !'));
			return false;
		}
		else
			return true;
	}

	//This method is used to change the users profile. This functionality can be performed by the admin.
	function modifyUser()
	{
		if($this->authorizedUser())
		{
			$userList = $this->Teacher->userList($this->Session->read('User'));
			$this->set('userList', $userList);
		}
	}

	//After the details has been entered by the admin in the edit UI, the validations are done before commiting the data.
	public function editUser($id = null)
	{
		if (!$id) {
			throw new NotFoundException(__('Invalid Teacher'));
		}

		$teacher = $this->Teacher->getUserDetails($id);

		if (!$teacher) {
			throw new NotFoundException(__('Invalid Teacher'));
		}

		$this->set('schooloptions', ClassRegistry::init('School')->schoolOptions());

		$userTypeOptions = array(
				array(
						'name' => 'Teacher','value' => 'T'),array(
								'name' => 'Admin','value' => 'A'),array(
										'name' => 'Pending','value' => 'P'),);
		$this->set('userTypeOptions', $userTypeOptions);


		if ($this->request->is('post') || $this->request->is('put'))
		{
			if($this->authorizedUser())
			{
				$this->Teacher->id = $id;

				if ($this->Teacher->saveModification($this->request->data))
				{
					$this->Session->setFlash('Teachers detail has been updated.');
					$this->redirect(array('action' => 'modifyUser'));
				}
				else
				{
					$this->Session->setFlash('Unable to update teachers detail.');
				}
			}
		}

		if (!$this->request->data)
		{
			$this->request->data = $teacher;
		}
	}

	//This method is used to delete a user from the DB. 
	public function deleteUser($id,$name)
	{
		if($this->authorizedUser())
		{
			if ($this->request->is('get'))
			{
				throw new MethodNotAllowedException();
			}

			if($this->Teacher->deleteTeacher($id))
			{
				$this->Session->setFlash($name .' has been deleted.');
				$this->redirect(array(
						'action' => 'modifyUser'));
			}
			else
			{
				$this->Session->setFlash('Unable to delete the user.');
			}
		}
	}

	//This method is called to reset the users password to a default value.
	public function userResetPassword($id,$name)
	{

		if($this->authorizedUser())
		{
			if ($this->Teacher->userResetPassword($id))
			{
				$this->Session->setFlash($name .'\'s password has been reset to "CAPLTER".');
				$this->redirect(array(
						'action' => 'modifyUser'));
			}
		}
	}

	//This method is called to change the current users profile. 
	public function editProfile()
	{
		if(!$this->Session->check('User'))
		{
			$this->Session->setFlash('Please login to access this page.');
			$this->redirect(array(
					'action' => 'login'));
		}
		$this->set('schooloptions', ClassRegistry::init('School')->schoolOptions());

		$user = $this->Session->read('User');
		$oldEmail = $user['Teacher']['email_address'];

		if ($this->request->is('post') || $this->request->is('put'))
		{
			if($this->request->data['Teacher']['password'] != $this->request->data['Teacher']['confirm_password'])
			{
				$this->Session->setFlash('The passwords you have entered do not match. Please verify the passwords again.');
				return;
			}
			if($oldEmail != $this->request->data['Teacher']['email_address'])
			{
				if($this->Teacher->checkEmailAddressExists($this->request->data['Teacher']['email_address']))
				{
					$this->Session->setFlash('Email Address already exists. Please try a different one.');
				}
				else
				{
					$this->Session->setFlash('Unable to update your profile. Please check the email address that you have entered.');
					return;
				}
			}
			else
			{
				if ($this->Teacher->saveModification($this->request->data))
				{
					$user = $this->Teacher->getUserDetails($user['Teacher']['id']);
					$this->Session->write('User', $user);

					$this->Session->setFlash('Your Profile has been updated.');
					$this->redirect(array(
							'action' => 'index'));
				}
				else
				{
					$this->Session->setFlash('Unable to update your profile.');
					return;
				}
			}
		}

		if (!$this->request->data)
		{
			$this->request->data = $user;
		}
	}

	//Submit data can be performed by the logged in user. Before data can be submitted, the user has to select the protocol, site and class.
	public function submitData()
	{

		if(!$this->Session->check('User'))
		{
			$this->Session->setFlash('Please login to access the page.');
			$this->redirect(array('action' => 'index'));
		}

		$user = $this->Session->read('User');

    # todo: refactor this to just get the existing protocol, site, class configurations to prevent someone from selecting invalid options from the three drop downs
		$habitatTypeOptions = array(
				array(
						'name' => 'Arthropods','value' => 'AR'),array(
								'name' => 'Birds','value' => 'BI'),array(
										'name' => 'Bruchids','value' => 'BR'),array(
												'name' => 'Vegetation','value' => 'VE'));
		$this->set('habitatTypeOptions', $habitatTypeOptions);
		$this->set('siteIDOptions', $this->Teacher->getSiteIDs($user));
		$this->set('classIDOptions', $this->Teacher->getClassIDs($user));

		//Based on the users protocol, he is redirected to the habitat check page or directly to data submission.
		if ($this->request->is('post'))
		{
			if($this->request->data['SubmitData']['protocol'] == 'BR')
			{
				$this->redirect(array(
						'controller' => 'BruchidSamples','action' => 'bruchidData',$this->request->data['SubmitData']['protocol'],$this->request->data['SubmitData']['site'],$this->request->data['SubmitData']['class']));
			}
			else
			{
				$this->redirect(array(
						'controller' => 'habitats','action' => 'habitatCheck',$this->request->data['SubmitData']['protocol'],$this->request->data['SubmitData']['site'],$this->request->data['SubmitData']['class']));
			}
		}
	}

	//This page is accessible by all. Based on the data range, school and protocol selected, data is retrieved if present for that combination.
	public function downloadData()
	{
		$habitatTypeOptions = array(
				array(
						'name' => 'Ecology Explorers Arthropod Survey','value' => 'AR'),array(
								'name' => 'Ecology Explorers Bird Survey','value' => 'BI'),array(
										'name' => 'Ecology Explorers Bruchid Survey','value' => 'BR'),array(
												'name' => 'Ecology Explorers Vegetation Survey','value' => 'VE'));
		$this->set('habitatTypeOptions', $habitatTypeOptions);

		$this->set('schooloptions', ClassRegistry::init('School')->schoolOptions());

		if ($this->request->is('post'))
		{

			$dataConditions['protocol'] = $this->request->data['retrieveData']['protocol'];
			$dataConditions['start_date'] = $this->request->data['retrieveData']['start_date']['year'].'-'.$this->request->data['retrieveData']['start_date']['month'].'-'.$this->request->data['retrieveData']['start_date']['day'];
			$dataConditions['end_date'] = $this->request->data['retrieveData']['end_date']['year'].'-'.$this->request->data['retrieveData']['end_date']['month'].'-'.$this->request->data['retrieveData']['end_date']['day'];
			$dataConditions['school_id'] = $this->request->data['retrieveData']['school_id'];

			$this->Session->delete('dateRetrieved');
			$this->redirect(array('controller' => 'teachers','action' => 'retrievedData',$dataConditions['protocol'],$dataConditions['start_date'],$dataConditions['end_date'],$dataConditions['school_id']));
		}
	}

	//This method does the extraction from the database for the given combination.
	function retrievedData()
	{
		$param = $this->passedArgs;

		$dataConditions['protocol'] = $param[0];
		$dataConditions['start_date'] = $param[1];
		$dataConditions['end_date'] = $param[2];
		$dataConditions['school_id'] = $param[3];
		$this->Session->write('dataConditions',$dataConditions);

		$data = ClassRegistry::init('School')->retrieveData($dataConditions);


		if(empty($data))
		{
			$this->Session->setFlash('No data exists for the given protocol, date range and school combination.');
			$this->redirect(array('controller' => 'teachers','action' => 'downloadData'));
			return;
		}
		$this->Session->write('data',$data);
	}

	//This method is used to convert the data into a csv file.
	function export()
	{
		$this->set('dateRetrieved', $this->Session->read('data'));
		$this->layout = null;
		$this->autoLayout = false;
		Configure::write('debug', '0');
	}

	//If the user forgets the password, then an email is sent to his registered email id. A new random password will be sent to his email account.
	function forgotPassword()
	{
		if ($this->request->is('post'))
		{
			$fields = array('Teacher.id','Teacher.email_address','Teacher.name');
			$conditions = array("Teacher.email_address" => $this->request->data['Teacher']['email_address'],);
			$user = $this->Teacher->find('first',array('conditions' => $conditions,'fields'=>$fields));
			if($user)
			{
				$newPassword = substr( str_shuffle( 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$' ) , 0 , 15 );
				$user['Teacher']['password'] = $newPassword;
				if($this->Teacher->saveModification($user))
				{

					$body = 'New password has been generated for '.$user['Teacher']['name'].'<br>
							<p>Username : '.$user['Teacher']['email_address'].'</p>
									<p>Password : '.$newPassword.'</p>
											Please create a new password once you login. <br>
											<br><br>This is a autogenerated mail. Please do not reply to this mail.';
					$to = $user['Teacher']['email_address'];
					$subject = 'Ecology Explorers New Password';
					$this->sendemail($body,$to,$this->fromEmailAddress,$subject);
						
					$this->Session->setFlash('A new password has been sent to the given email address. Please login with the new password.');
					$this->redirect(array('controller' => 'teachers','action' => 'login'));
				}
			}
			else
			{
				$this->Session->setFlash('The given email address does not exist. Plese check the email address provided.');
			}

		}
	}

	//This method is just a place holder for links to species data.
	function modifySpeciesData()
	{
		if($this->authorizedUser())
		{
			//Just redirection to the new page.
		}
		else
		{
			$this->Session->setFlash(__('You do not have permissions to access this page !'));
			$this->redirect(array(
					'action' => 'index'));
		}
	}


}
