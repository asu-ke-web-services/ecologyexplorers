<?php
App::uses('AppModel', 'Model');
/**
 * School Model
 *
 * @property School $School
*/
class School extends AppModel {

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public $validate = array(
			'school_id' => array(
					'rule' => array('between', 3, 3),
					'message' => 'School ID must be of 3 characters'),
			'school_name'  => array(
					'rule' => 'notEmpty'),
			'address'  => array(
					'rule' => 'notEmpty'),
			'city'  => array(
					'rule' => 'notEmpty'),
			'zipcode' => array(
					'rule' => array('postal', null, 'us'),
					'message' => 'Please enter a valid zipcode')
	);

	/**
	 * hasMany associations
	 *
	 * @var array
	*/

	public $recursive = -1;

	public $actsAs = array('Containable');

	public $hasMany = array(
			'Habitat' => array(
					'className' => 'Habitat',
			),
			'Teacher' => array(
					'className' => 'Teacher',
			),
			'TeachersClass' => array(
					'className' => 'TeachersClass',
			),
			'Site' => array(
					'className' => 'Site',
			),
			'TeachersClass' => array(
					'className' => 'TeachersClass',
			)
	);

	//This method is called from the teachers controller to load the schools during user registration.
	public function loadSchools()
	{
		$school = $this->find('list', array(
				'fields' => array(
						'id',
						'school_Name')));

		return $school;
	}

	//Looad all the schools and store it in id and name fashion.
	public function schoolOptions()
	{
		$schools = $this->loadSchools();

		$i = 0;
		foreach ($schools as $k)
		{
			$schooloptions[$i]['name'] = ($k);
			$schooloptions[$i]['value'] = array_search($k,$schools);
			$i++;
		}
		return $schooloptions;
	}

	//Retrieving only the name of the school in the passed parameters.
	public function schoolWithID($schoolID)
	{
		if($schoolID == null)
			return false;

		$temp['id'] = $schoolID;
		if($this->hasAny($temp))
		{
			$conditions = array('Schools.id' => $schoolID);
			$temp = $this->find('first', array('conditions' => $conditions,'fields' => array(
					'id',
					'school_name')));

			$schooloptions[0]['value'] = $temp['School']['id'];
			$schooloptions[0]['name'] = $temp['School']['school_name'];
			return $schooloptions;
		}
		return false;
	}

	//This method is called from the School Controller to create new schools
	public function createSchool($fields)
	{
		$this->create();
		$fields['School']['date_entered'] = date('Y-m-d H:i:s');

		if($this->save($fields))
		{
			return true;
		}
		else
			return false;
	}

	//This method is used to validate if the school id given already exists
	public function checkSchoolIdPresent($fields = null)
	{
		if($this->findBySchoolId($fields) != null)
		{
			return true;
		}
		else
		{
			return false;
		}

	}

	//This method is used to retrieve data based on the users school, data range and protocol.
	public function retrieveData($dataConditions)
	{
		$this->recursive = 2;
		$protocol =$dataConditions['protocol'];
		$startDate=$dataConditions['start_date'];
		$endDate=$dataConditions['end_date'];
		$schoolID=$dataConditions['school_id'];

		$conditionsSchool = array('Schools.id' => $schoolID);
		$conditionsProtocol = array('Habitat.type' => $protocol);

		if($protocol == 'BI')
		{

			$conditionsDate = array('BirdSample.collection_date between ? and ?' => array($startDate, $endDate));

			$fieldsHabitat= array('Habitat.type','Habitat.recording_date','Habitat.radius','Habitat.percent_observed','Habitat.tree_canopy','Habitat.shrubcover','Habitat.gravel_soil','Habitat.lawn','Habitat.paved_building','Habitat.other','Habitat.water');
			$fieldsSchool = array('Schools.school_name','Schools.city','Schools.zipcode');
			$fieldsBirdSample = array('BirdSample.time_start','BirdSample.time_end','BirdSample.collection_date','BirdSample.air_temp','BirdSample.comments');
			$fieldsTeachersClass = array('TeachersClass.class_name');
			$fieldsBirdSpecimen = array('BirdSpecimen.frequency');
			$fieldsBirdTaxon = array('BirdTaxon.common_name');
			$fieldsTeacher = array('Teacher.name');
			$fieldsSite = array('Site.location','Site.site_name');

			$school = $this->find('all',array(
					'conditions' => $conditionsSchool,'contain'=>array(
							'Habitat'=>array(
									'conditions' => $conditionsProtocol,'fields' => $fieldsHabitat,'BirdSample'=>array(
											'conditions'=> $conditionsDate,'fields' => $fieldsBirdSample,'CloudCover','Site'=>array(
													'fields' => $fieldsSite),'TeachersClass'=>array(
															'Teacher'=>array('fields' => $fieldsTeacher),'fields' => $fieldsTeachersClass),'BirdSpecimen'=>array(
																	'BirdTaxon'=>array('fields' => $fieldsBirdTaxon),'fields' => $fieldsBirdSpecimen)
									)
							),
					),
					'fields' => $fieldsSchool,
			));
			
			$i=0;
			$data ='';
			foreach ($school[0]['Habitat'] as $habitat)
			{
				foreach ($habitat['BirdSample'] as $birdSamples)
				{
					foreach ($birdSamples['BirdSpecimen'] as $birdSpecimen)
					{
						$data[$i]['Sample_Date'] = $birdSamples['collection_date'];
						$data[$i]['Site_ID'] = $birdSamples['Site']['site_name'];
						$data[$i]['Bird_Common_Name'] = $birdSpecimen['BirdTaxon']['common_name'];
						$data[$i]['Frequency'] = $birdSpecimen['frequency'];
						$data[$i]['Air_Temperature'] = $birdSamples['air_temp'];
										
						$data[$i]['Cloud_Cover'] = "";
						if(count($birdSamples['CloudCover']) > 0)
						{
							$data[$i]['Cloud_Cover'] = $birdSamples['CloudCover']['cloud_cover_name'];
						}
						$data[$i]['Start_Time'] = $birdSamples['time_start'];
						$data[$i]['End_Time'] = $birdSamples['time_end'];
						$data[$i]['Comments'] = $birdSamples['comments'];
						$data[$i]['Habitat_Recording_Date'] = $habitat['recording_date'];
						$data[$i]['School_Name'] = $school[0]['School']['school_name'];
						$data[$i]['Site_Location'] = $birdSamples['Site']['location'];
						$data[$i]['City'] = $school[0]['School']['city'];
						$data[$i]['ZipCode'] = $school[0]['School']['zipcode'];
						
						$data[$i]['Teachers_Name'] = "";
						$data[$i]['Class_Name'] = "";
						if(count($birdSamples['TeachersClass']) > 0)
						{
							$data[$i]['Teachers_Name'] = $birdSamples['TeachersClass']['Teacher']['name'];
							$data[$i]['Class_Name'] = $birdSamples['TeachersClass']['class_name'];
						}
						$data[$i]['Teachers_Name'] = $birdSamples['TeachersClass']['Teacher']['name'];
						$data[$i]['Class_Name'] = $birdSamples['TeachersClass']['class_name'];			
						$data[$i]['Radius'] = $habitat['radius'];
						$data[$i]['Percent_Observed'] = $habitat['percent_observed'];
						$data[$i]['Tree_Canopy'] = $habitat['tree_canopy'];
						$data[$i]['Shrubcover'] = $habitat['shrubcover'];
						$data[$i]['Gravel_Soil'] = $habitat['gravel_soil'];
						$data[$i]['Lawn'] = $habitat['lawn'];
						$data[$i]['Paved_Building'] = $habitat['paved_building'];
						$data[$i]['Water'] = $habitat['water'];
						$data[$i]['Other'] = $habitat['other'];
						
						
						$i++;
					}
				}
			}
			return $data;
		}
		else if($protocol == 'AR')
		{
			$conditionsDate = array('ArthroSample.collection_date between ? and ?' => array($startDate, $endDate));

			$fieldsHabitat= array('Habitat.type','Habitat.recording_date','Habitat.area','Habitat.num_traps','Habitat.trap_arrange','Habitat.tree_canopy','Habitat.shrubcover','Habitat.gravel_soil','Habitat.lawn','Habitat.paved_building','Habitat.other','Habitat.water');
			$fieldsSchool = array('Schools.school_name','Schools.city','Schools.zipcode');
			$fieldsArthroSample = array('ArthroSample.collection_date','ArthroSample.comments');
			$fieldsTeachersClass = array('TeachersClass.class_name');
			$fieldsArthroSpecimen = array('ArthroSpecimen.frequency','ArthroSpecimen.trap_no');
			$fieldsArthroTaxon = array('ArthroTaxon.taxon_name');
			$fieldsTeacher = array('Teacher.name');
			$fieldsSite = array('Site.location','Site.site_name');

			$school = $this->find('all',array(
					'conditions' => $conditionsSchool,'contain'=>array(
							'Habitat'=>array(
									'conditions' => $conditionsProtocol,'fields' => $fieldsHabitat,'ArthroSample'=>array(
											'conditions'=> $conditionsDate,'fields' => $fieldsArthroSample,'Site'=>array(
													'fields' => $fieldsSite),'TeachersClass'=>array(
															'Teacher'=>array('fields' => $fieldsTeacher),'fields' => $fieldsTeachersClass),'ArthroSpecimen'=>array(
																	'ArthroTaxon'=>array('fields' => $fieldsArthroTaxon),'fields' => $fieldsArthroSpecimen)
									)
							),
					),
					'fields' => $fieldsSchool,
			));

			//pr($school);
			$i=0;
			$data ='';
			foreach ($school[0]['Habitat'] as $habitat)
			{
				foreach ($habitat['ArthroSample'] as $arthroSamples)
				{
					foreach ($arthroSamples['ArthroSpecimen'] as $arthroSpecimen)
					{
						$data[$i]['Sample_Date'] = $arthroSamples['collection_date'];
						$data[$i]['Site_ID'] = $arthroSamples['Site']['site_name'];
						$data[$i]['Trap_ID'] = $arthroSpecimen['trap_no'];
						$data[$i]['Taxon_Name'] = $arthroSpecimen['ArthroTaxon']['taxon_name'];
						$data[$i]['Frequency'] = $arthroSpecimen['frequency'];
						$data[$i]['Comments'] = $arthroSamples['comments'];
						$data[$i]['Habitat_Recording_Date'] = $habitat['recording_date'];
						$data[$i]['School_Name'] = $school[0]['School']['school_name'];
						$data[$i]['Site_Location'] = $arthroSamples['Site']['location'];
						$data[$i]['City'] = $school[0]['School']['city'];
						$data[$i]['Zipcode'] = $school[0]['School']['zipcode'];
						
						$data[$i]['Teachers_Name'] = "";
						$data[$i]['Class_Name'] = "";
						if(count($arthroSamples['TeachersClass']) > 0)
						{
							$data[$i]['Teachers_Name'] = $arthroSamples['TeachersClass']['Teacher']['name'];
							$data[$i]['Class_Name'] = $arthroSamples['TeachersClass']['class_name'];
						}
														
						$data[$i]['Area'] = $habitat['area'];
						$data[$i]['No_Of_Traps'] = $habitat['num_traps'];
						$data[$i]['Trap_Arrange'] = $habitat['trap_arrange'];
						$data[$i]['Tree_Canopy'] = $habitat['tree_canopy'];
						$data[$i]['Shrubcover'] = $habitat['shrubcover'];
						$data[$i]['Gravel_Soil'] = $habitat['gravel_soil'];
						$data[$i]['Lawn'] = $habitat['lawn'];
						$data[$i]['Paved_Building'] = $habitat['paved_building'];
						$data[$i]['Water'] = $habitat['water'];
						$data[$i]['Other'] = $habitat['other'];
						
							
						$i++;
					}
				}
			}
			//pr($data);
			return $data;

		}
		else if($protocol == 'VE')
		{
			$conditionsDate = array('VegSample.collection_date between ? and ?' => array($startDate, $endDate));

			$fieldsHabitat= array('Habitat.type','Habitat.recording_date','Habitat.area','Habitat.tree_canopy','Habitat.shrubcover','Habitat.gravel_soil','Habitat.lawn','Habitat.paved_building','Habitat.other','Habitat.water');
			$fieldsSchool = array('Schools.school_name','Schools.city','Schools.zipcode');
			$fieldsVegSample = array('VegSample.collection_date','VegSample.tree_count','VegSample.shrub_count','VegSample.cactus_count');
			$fieldsTeachersClass = array('TeachersClass.class_name');
			$fieldsVegSpecimen = array('VegSpecimen.veg_no','VegSpecimen.plant_type','VegSpecimen.circumference','VegSpecimen.height','VegSpecimen.canopy','VegSpecimen.comments');
			$fieldsVegTaxon = array('VegTaxon.common_name');
			$fieldsTeacher = array('Teacher.name');
			$fieldsSite = array('Site.location','Site.site_name');

			$school = $this->find('all',array(
					'conditions' => $conditionsSchool,'contain'=>array(
							'Habitat'=>array(
									'conditions' => $conditionsProtocol,'fields' => $fieldsHabitat,'VegSample'=>array(
											'conditions'=> $conditionsDate,'fields' => $fieldsVegSample,'Site'=>array(
													'fields' => $fieldsSite),'TeachersClass'=>array(
															'Teacher'=>array('fields' => $fieldsTeacher),'fields' => $fieldsTeachersClass),'VegSpecimen'=>array(
																	'VegTaxon'=>array('fields' => $fieldsVegTaxon),'fields' => $fieldsVegSpecimen)
									)
							),
					),
					'fields' => $fieldsSchool,
			));

			//pr($school);
			$i=0;
			$data ='';
			foreach ($school[0]['Habitat'] as $habitat)
			{
				foreach ($habitat['VegSample'] as $vegSamples)
				{
					foreach ($vegSamples['VegSpecimen'] as $vegSpecimen)
					{
						
						$data[$i]['Sample_Date'] = $vegSamples['collection_date'];
						$data[$i]['Site_ID'] = $vegSamples['Site']['site_name'];
						$data[$i]['Tree_Count'] = $vegSamples['tree_count'];
						$data[$i]['Shrub_Count'] = $vegSamples['shrub_count'];
						$data[$i]['Cactus_Count'] = $vegSamples['cactus_count'];
						$data[$i]['Veg_ID'] = $vegSpecimen['veg_no'];
						$data[$i]['Plant_Type'] = $vegSpecimen['plant_type'];
						$data[$i]['Common_Name'] = $vegSpecimen['VegTaxon']['common_name'];
						$data[$i]['Circumference'] = $vegSpecimen['circumference'];
						$data[$i]['Height'] = $vegSpecimen['height'];
						$data[$i]['Canopy'] = $vegSpecimen['canopy'];						
						$data[$i]['Comments'] = $vegSpecimen['comments'];
						$data[$i]['Habitat_Recording_Date'] = $habitat['recording_date'];
						$data[$i]['School_Name'] = $school[0]['School']['school_name'];
						$data[$i]['Site_Location'] = $vegSamples['Site']['location'];
						$data[$i]['City'] = $school[0]['School']['city'];
						$data[$i]['Zipcode'] = $school[0]['School']['zipcode'];
						
						$data[$i]['Teachers_Name'] = "";
						$data[$i]['Class_Name'] = "";
						if(count($vegSamples['TeachersClass']) > 0)
						{
							$data[$i]['Teachers_Name'] = $vegSamples['TeachersClass']['Teacher']['name'];
							$data[$i]['Class_Name'] = $vegSamples['TeachersClass']['class_name'];
						}
												
						$data[$i]['Area'] = $habitat['area'];
						$data[$i]['Tree_Canopy'] = $habitat['tree_canopy'];
						$data[$i]['Shrubcover'] = $habitat['shrubcover'];
						$data[$i]['Gravel_Soil'] = $habitat['gravel_soil'];
						$data[$i]['Lawn'] = $habitat['lawn'];
						$data[$i]['Paved_Building'] = $habitat['paved_building'];
						$data[$i]['Water'] = $habitat['water'];
						$data[$i]['Other'] = $habitat['other'];
						
						$i++;
					}
				}
			}
			//pr($data);
			return $data;
		}
		else if($protocol == 'BR')
		{
			$conditionsDate = array('BruchidSample.collection_date between ? and ?' => array($startDate, $endDate));

			$fieldsSchool = array('Schools.school_name','Schools.city','Schools.zipcode');
			$fieldsBruchidSample = array('BruchidSample.collection_date','BruchidSample.tree_type','BruchidSample.site_type',);
			$fieldsTeachersClass = array('TeachersClass.class_name');
			$fieldsBruchidSpecimen = array('BruchidSpecimen.tree_no','BruchidSpecimen.pod_no','BruchidSpecimen.hole_count','BruchidSpecimen.seed_count');
			$fieldsTeacher = array('Teacher.name');
			$fieldsSite = array('Site.location','Site.site_name');

			$school = $this->find('all',array(
					'conditions' => $conditionsSchool,'contain'=>array(
							'Site'=>array(
									'fields' => $fieldsSite,'BruchidSample'=>array(
											'conditions'=> $conditionsDate,'fields' => $fieldsBruchidSample,'TeachersClass'=>array(
													'Teacher'=>array('fields' => $fieldsTeacher),'fields' => $fieldsTeachersClass),'BruchidSpecimen'=>array(
															'fields' => $fieldsBruchidSpecimen)
									)
							),
					),
					'fields' => $fieldsSchool,
			));

			//pr($school);
			$i=0;
			$data ='';
			foreach ($school[0]['Site'] as $site)
			{
				foreach ($site['BruchidSample'] as $bruchidSamples)
				{
					foreach ($bruchidSamples['BruchidSpecimen'] as $bruchidSpecimen)
					{
						
						$data[$i]['Sample_Date'] = $bruchidSamples['collection_date'];
						$data[$i]['Site_ID'] = $site['site_name'];

						if($bruchidSamples['tree_type'] == 'B')
							$data[$i]['Tree_type'] = 'Blue Palo Verde';
						else
							$data[$i]['Tree_type'] = 'Foothills Palo Verde';					

						$data[$i]['Site_type'] = $bruchidSamples['site_type'];
						$data[$i]['Tree_no'] = $bruchidSpecimen['tree_no'];
						$data[$i]['Pod_no'] = $bruchidSpecimen['pod_no'];
						$data[$i]['Hole_count'] = $bruchidSpecimen['hole_count'];
						$data[$i]['Seed_count'] = $bruchidSpecimen['seed_count'];
						$data[$i]['School_Name'] = $school[0]['School']['school_name'];
						$data[$i]['Site_Location'] = $site['location'];
						$data[$i]['City'] = $school[0]['School']['city'];
						$data[$i]['Zipcode'] = $school[0]['School']['zipcode'];						
						$data[$i]['Teachers_Name'] = $bruchidSamples['TeachersClass']['Teacher']['name'];
						$data[$i]['Class_Name'] = $bruchidSamples['TeachersClass']['class_name'];						
										
						$i++;
					}
				}
			}
			//pr($data);
			return $data;
		}
	}
}
