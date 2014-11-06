<?php
App::uses('AppController', 'Controller');
App::uses('CakeEmail', 'Utitlity');

/**
 * ArthroSamples Controller
 *
 * @property ArthroSample $ArthroSample
*/
class ArthroSamplesController extends AppController {

	/**
	 * index method
	 *
	 * @return void
	 */
	public function index() {
		$this->ArthroSample->recursive = 0;
		$this->set('arthroSamples', $this->paginate());
	}

	//This method is used to submit the anthropod data from the user.
	public function arthropodData()
	{
		//Submission of data is a privilege of logged in users. Checking if the user has logged it.
		if(!$this->Session->check('User'))
		{
			$this->Session->setFlash('Please login to access this page.');
			$this->redirect(array(
					'action' => 'login'));
		}
		
		//Data is passed from the habitat check page which will be used to submit the data.
		$param = $this->passedArgs;

		$user = $this->Session->read('User');
		$this->set('teacherName', $user['Teacher']['name']);

		//Hard coding the school id of the current user
		$this->set('schooloptions', ClassRegistry::init('School')->schoolWithID($user['Teacher']['school_id']));

		$this->set('siteOptions',ClassRegistry::init('Site')->getSiteName($param[0]));

		$this->set('classOptions', ClassRegistry::init('TeachersClass')->getClassName($param[1]));

		//Prepopulating the arthrotaxon drop down box with the values retrieved from DB.
		$this->set('orderOptions',  ClassRegistry::init('ArthroTaxon')->getOrderList());
		
		#$dataOptions = array(array('name' => '5','value' => '5'),array('name' => '10','value' => '10'),array('name' => '15','value' => '15'),array('name' => '20','value' => '20'));
		#$this->set('dataOptions', $dataOptions);

		if ($this->request->is('post'))
		{
			$this->request->data['ArthroSample']['site_id'] = $param[0];
			$this->request->data['ArthroSample']['teachers_class_id'] = $param[1];
			$this->request->data['ArthroSample']['habitat_id'] = $param[2];
			
		
			$result = $this->ArthroSample->validateData($this->request->data);
			
			if($result == "negative"){
				$this->Session->setFlash("Tally number cannot be less than zero.");
				return;
			}
			
			if($result != false){
				$this->Session->setFlash("Duplicate data present at row ".($result).". Please verify the data entered before submitting.");
				return;
			}

			if($this->ArthroSample->savingthedata($this->request->data))
			{				
				$this->Session->setFlash("Arthropod Data has been saved. ");
				$this->sendEmailNotification();
				$this->redirect(array('controller' => 'teachers','action' => 'dataSubmissionSuccess'));
				
			}
			else{
				$this->Session->setFlash("Unable to save the data. Please check the data and try again.");
			}
		}
	}
	
	
	public function sendEmailNotification()
	{
		$user = $this->Session->read('User');
		$name = $user['Teacher']['name'];
		$email =$user['Teacher']['email_address'];
		
		$body = '<br>'.$name.' has recently submitted Arthropod Data in Ecology Explorer\'s Data Center.<br><br>
							'.$name.' registered email is '.$email;
		$subject = 'New Arthropod Data submitted at Ecology Explorers Data Center';
		$to = 'admin';
		
		$this->sendEmail($body,$to,$subject);
	}
	
	public function modifyArthropodData()
	{
		//Checking if the user logged in is an admin.
		if('A' != $this->Session->read('UserType'))
		{
			$this->Session->setFlash(__('You do not have permissions to access this page !'));
		}
		else
		{
			$this->ArthroSample->recursive = 0;
			$startDate = $this->request->params['pass']['0'];
			$endDate = $this->request->params['pass']['1'];
			$this->set('ArthroSample', $this->ArthroSample->getArthropodData($startDate,$endDate));
			$this->set('startDate', $startDate);
			$this->set('endDate',$endDate);
		}
	}
	
	public function edit($id = null,$startDate,$endDate) {
		if (!$id) {
			throw new NotFoundException(__('Invalid Arthropod Data ID'));
		}
	
		$this->ArthroSample->recursive = 1;
		
		$this->set('orderOptions',  ClassRegistry::init('ArthroTaxon')->getOrderList());
		$ArthroData = $this->ArthroSample->findById($id);
		
		if (!$ArthroData) {
			throw new NotFoundException(__('Invalid Arthropod Sample ID'));
		}
	
		if ($this->request->is('post') || $this->request->is('put')) {
			$this->ArthroSample->id = $id;
			if ($this->ArthroSample->saveAll($this->request->data)) {
				$this->Session->setFlash('Arthropod Data has been updated.');
				$this->redirect(array('controller' => 'ArthroSamples', 'action' => 'modifyArthropodData',$startDate,$endDate));
			} else {
				$this->Session->setFlash('Unable to update Arthropod details.');
			}
		}
	
		if (!$this->request->data) {
			$this->request->data = $ArthroData;	
		}
	}
	
	public function modifyDataPickDate(){
		
		//Checking if the user logged in is an admin.
		if('A' != $this->Session->read('UserType'))
		{
			$this->Session->setFlash(__('You do not have permissions to access this page !'));
		}
		else
		{
			$this->ArthroSample->recursive = 0;
			
			$habitatTypeOptions = array(
					array(
							'name' => 'Arthropods','value' => 'AR'),array(
									'name' => 'Birds','value' => 'BI'),array(
											'name' => 'Bruchids','value' => 'BR'),array(
													'name' => 'Vegetation','value' => 'VE'));
			
			$this->set('habitatTypeOptions', $habitatTypeOptions);
			
			if ($this->request->is('post') || $this->request->is('put'))
			{
				$start_date = $this->request->data['DataSampleDate']['start_date']['year'].'-'.$this->request->data['DataSampleDate']['start_date']['month'].'-'.$this->request->data['DataSampleDate']['start_date']['day'];
				$end_date = $this->request->data['DataSampleDate']['end_date']['year'].'-'.$this->request->data['DataSampleDate']['end_date']['month'].'-'.$this->request->data['DataSampleDate']['end_date']['day'];
				$protocol = $this->request->data['DataSampleDate']['protocol'];
				
				if($protocol === 'AR')
					$this->redirect(array('controller' => 'ArthroSamples','action' => 'modifyArthropodData',$start_date,$end_date));
				if($protocol === 'BI')
					$this->redirect(array('controller' => 'BirdSamples','action' => 'modifyBirdData',$start_date,$end_date));
				if($protocol === 'VE')
					$this->redirect(array('controller' => 'VegSamples','action' => 'modifyVegData',$start_date,$end_date));
				if($protocol === 'BR')
					$this->redirect(array('controller' => 'BruchidSamples','action' => 'modifyBruchidData',$start_date,$end_date));
			}
		}
		
	}

}
