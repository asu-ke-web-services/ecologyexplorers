<?php

$host = "localhost";
$user = "root";
$pass = "root";
$port = 8889;
$db = "ecoexplorers_new"; # desitnation 
$db2 = "ecoexplorers_old"; # source

ini_set('max_execution_time', 60);

$mysqli_temp = new mysqli($host, $user, $pass, $db, $port); 

if (mysqli_connect_errno())
{
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}
$query = "CREATE DATABASE IF NOT EXISTS datamigration";
$result = $mysqli_temp->query($query) or die($mysqli_temp->error.__LINE__);
mysqli_close($mysqli_temp);

$mysqli_old = new mysqli($host, $user, $pass,$db2, $port);
$mysqli_new = new mysqli($host, $user, $pass,$db , $port);

echo "Connection established";?>
<br>

<?php

$query_new_school_table = "CREATE TABLE IF NOT EXISTS `schools` (
		`id` int NOT NULL AUTO_INCREMENT,
		`school_id` varchar(3) NOT NULL,
		`school_name` varchar(100) DEFAULT NULL,
		`address` varchar(100) DEFAULT NULL,
		`zipcode` varchar(10) DEFAULT NULL,
		`city` varchar(50) DEFAULT NULL,
		`date_entered` datetime DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE(`school_id`),
		INDEX(`school_id`)
		);";
$result_new_school_table = $mysqli_new->query($query_new_school_table) or die($mysqli_new->error.__LINE__);

$query_old_school = "SELECT * FROM school";
$result_old_school = $mysqli_old->query($query_old_school) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE SCHOOL DATA
if($result_old_school->num_rows > 0) {

	$querySchool = "INSERT into schools (school_id,school_name,address,zipcode,city,date_entered) VALUES ";
	while($row = $result_old_school->fetch_assoc())
	{
		//Harcoding null values if the zipcode and address contain null values.
		$address = ($row['address'] == NULL) ? "NULL" : "'".$row['address']."'";

		//Zipcode cannot contain text. Changing the value to null if it does.
		if($row['zipcode'] == 'test' ||$row['zipcode'] == NULL )
			$row['zipcode'] = "NULL";
		$querySchool = $querySchool."('".$row['school_id']."','".$mysqli_old->real_escape_string($row['school_name'])."',".$address.",".$row['zipcode'].",'".$row['city']."','".$row['date_entered']."'),";
	}
	$querySchool = substr($querySchool, 0, -1);
	$querySchool = $querySchool.";";

}
else {
echo 'NO RESULTS';
}
$result_old_school->close();
//echo $querySchool;


$result = $mysqli_new->query($querySchool) or die($mysqli_new->error.__LINE__);
echo "Schools table inserted with data";?>
<br>

<?php


$query_new_school = "SELECT id,school_id FROM schools";
$result_new_school = $mysqli_new->query($query_new_school) or die($mysqli_old->error.__LINE__);
while($row = $result_new_school->fetch_assoc())
{
	$schoolMapping[$row['id']] = $row['school_id'];
}


$query_new_teachers_table = "CREATE TABLE IF NOT EXISTS `teachers` (
		`id` int NOT NULL AUTO_INCREMENT,
		`email_address` varchar(160) DEFAULT NULL,
		`password` varchar(160) NOT NULL,
		`type` varchar(1) NOT NULL DEFAULT 'P',
		`name` varchar(255) DEFAULT NULL,
		`school_id` int DEFAULT NULL,
		`date_created` datetime DEFAULT NULL,
		`last_login` datetime DEFAULT NULL,
		`old_teacher_id` varchar(50) DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE(`email_address`),
		INDEX(`email_address`,`password`),
		FOREIGN KEY (school_id) REFERENCES schools(id)
		);";
$result_new_teachers_table = $mysqli_new->query($query_new_teachers_table) or die($mysqli_new->error.__LINE__);


$query_old_teacher = "SELECT * FROM teacher";
$result_old_teacher = $mysqli_old->query($query_old_teacher) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE TEACHERs DATA
if($result_old_teacher->num_rows > 0) {

	$queryteacher = "INSERT into teachers (old_teacher_id,email_address,password,type,name,school_id,date_created) VALUES ";
	$i=0;
	while($row = $result_old_teacher->fetch_assoc())
	{
		//If the teacher has no email associated, then add a default email address of "nullemail" is added
		if($row['email'] == NULL)
			$row['email'] = 'nullemail'.$i++.'@null.com';

		//There are 3 teachers with ecology.explorers@asu.edu email address and may have some data associated with them.
		//Append their email address with number.
		if($row['email'] == 'ecology.explorers@asu.edu')
			$row['email'] = 'ecology.explorers'.$i++.'@asu.edu';

		//Map the school ids with their primary key from the school table.
		$school = array_search($row['school_id'], $schoolMapping);

		//If the teacher has no school associated,teachers profile is not created for that data.
		if($school == NULL)
			continue;

		//Duplicate email address are removed.
		if($row['teacher_id'] == 'brbarker' || $row['teacher_id'] == 'crvitale' || $row['teacher_id'] == 'phillipshope' || $row['teacher_id'] == '148148' || $row['teacher_id'] == 'tscarlson'||$row['teacher_id'] =='test2')
			continue;

		//This teacher has 4 profile but has data associated with only one. Keeping that profile and removing the rest.
		if($row['email'] == 'jhdonova@guhsdaz.org' && $row['teacher_id'] != 'jhdonova' )
			continue;

		$queryteacher = $queryteacher."('".$row['teacher_id']."','".$row['email']."','5ef3f435d713ec7e69ee08f93f42a322ce180627','T','".$row['name']."',".$school.",now()),";
	}
	$queryteacher = substr($queryteacher, 0, -1);
	$queryteacher = $queryteacher.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_teacher->close();
//echo $queryteacher;


$result = $mysqli_new->query($queryteacher) or die($mysqli_new->error.__LINE__);
echo "Teachers table inserted with data";?><br>
<?php



$query_new_sites_table = "CREATE TABLE IF NOT EXISTS `sites` (
				`id` int NOT NULL AUTO_INCREMENT,
				`site_id` varchar(50) NOT NULL,
				`school_id` int  NOT NULL,
				`site_name` varchar(40) DEFAULT NULL,
				`address` varchar(100) DEFAULT NULL,
				`description` varchar(100),
				`city` varchar(50) DEFAULT NULL,
				`zipcode` varchar(10) DEFAULT NULL,
				`date_entered` datetime DEFAULT NULL,
				`location` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`id`),
				UNIQUE(`site_id`),
				FOREIGN KEY (school_id) REFERENCES schools(id)
				) ;";
$result_new_sites_table = $mysqli_new->query($query_new_sites_table) or die($mysqli_new->error.__LINE__);

$query_old_sites = "SELECT * FROM sites";
$result_old_sites = $mysqli_old->query($query_old_sites) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE SITES DATA
if($result_old_sites->num_rows > 0) {

	$querysites = "INSERT into sites (site_id,site_name,address,description,city,zipcode,location,school_id,date_entered) VALUES ";
	while($row = $result_old_sites->fetch_assoc())
	{

		$location = ($row['location'] == NULL) ? "NULL" : "'".$row['location']."'";
		$address = ($row['address'] == NULL  || $row['address'] == ' ') ? "NULL" : "'".$row['address']."'";
		$city = ($row['city'] == NULL || $row['city'] == ' ') ? "NULL" : "'".$row['city']."'";
		$description = ($row['description'] == NULL || $row['description'] == ' ') ? " " : "".$row['description']."";
		$zipcode = ($row['zipcode'] == NULL || $row['zipcode'] == ' ' || $row['zipcode'] == 'Mesa') ? "NULL" : $row['zipcode'];

		if($row['sitename'] == NULL)
			$row['sitename'] = $row['description'];
		if($row['sitename'] == NULL && $row['description'] == NULL)
			$row['sitename'] = $row['address'];

		if($row['site_id'] == 'bbc' || $row['site_id'] == 'BBC' || $row['site_id'] == 'M1')
			$row['site_id'] = $row['site_id'].$row['sitename'];

		if($row['site_id'] == 'TEST')
			continue;

		$school = array_search($row['school_id'], $schoolMapping);
		$querysites = $querysites."('".$row['site_id']."','".$row['sitename']."',".$address.",'".$mysqli_old->real_escape_string($description)."',".$city.",".$zipcode.",".$location.",".$school.",'".$row['date_entered']."'),";
	}
	$querysites = substr($querysites, 0, -1);
	$querysites = $querysites.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_sites->close();
// echo $querysites;

$result = $mysqli_new->query($querysites) or die($mysqli_new->error.__LINE__);
echo "Sites table inserted with data";?><br>
<?php


$query_new_teacher = "SELECT id,old_teacher_id FROM teachers";
$result_new_teacher = $mysqli_new->query($query_new_teacher) or die($mysqli_old->error.__LINE__);
while($row = $result_new_teacher->fetch_assoc())
{
	$teacherMapping[$row['id']] = $row['old_teacher_id'];
}


$query_new_teachersclass_table = "CREATE TABLE IF NOT EXISTS `teachers_classes` (
						`id` int NOT NULL AUTO_INCREMENT,
						`class_name` varchar(50) DEFAULT NULL,
						`grade` varchar(10) DEFAULT NULL,
						`date_entered` datetime DEFAULT NULL,
						`old_class_id` int NOT NULL,
						`teacher_id` int  NOT NULL,
						`school_id` int  NOT NULL,
						PRIMARY KEY (`id`),
						FOREIGN KEY (school_id) REFERENCES schools(id),
						FOREIGN KEY (teacher_id) REFERENCES teachers(id)
						);";

$result_new_teachersclass_table = $mysqli_new->query($query_new_teachersclass_table) or die($mysqli_new->error.__LINE__);

$query_old_teachersclass = "SELECT * FROM class";
$result_old_teachersclass = $mysqli_old->query($query_old_teachersclass) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE TEACHERSCLASS DATA
if($result_old_teachersclass->num_rows > 0) {

	$queryteachersclass = "INSERT into teachers_classes (class_name,grade,teacher_id,school_id,date_entered,old_class_id) VALUES ";
	while($row = $result_old_teachersclass->fetch_assoc())
	{
		//Classes that do not have teachers in the teachers table
		if($row['teacher_id'] == 'cgries'||$row['teacher_id'] == 'TESTING' || $row['teacher_id'] ==  'test2')
			continue;

		//Mismatch in the teacher id case
		if($row['teacher_id'] == 'jfreed')
			$row['teacher_id'] = 'Jfreed';

		//Duplicate teacher profiles were created. Removed the duplicates from the teacher table and hence the mapping back in class table
		if($row['teacher_id'] == 'tscarlson')
			$row['teacher_id'] = 'tcarlson';

		//Additional space at the end of teacher id.
		if($row['teacher_id'] == 'tsupplee ')
			$row['teacher_id'] = 'tsupplee';

		$grade= ($row['grade'] == NULL) ? "NULL" : "'".$row['grade']."'";

		$school = array_search($row['school_id'], $schoolMapping);
		$teacher = array_search($row['teacher_id'], $teacherMapping);

		$queryteachersclass = $queryteachersclass."('".$row['class_name']."',".$grade.",".$teacher.",".$school.",'".$row['date_entered']."',".$row['class_id']."),";
	}
	$queryteachersclass= substr($queryteachersclass, 0, -1);
	$queryteachersclass = $queryteachersclass.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_teachersclass->close();
//echo $queryteachersclass;


$result = $mysqli_new->query($queryteachersclass) or die($mysqli_new->error.__LINE__);
echo "Teachers Class table inserted with data";?><br>
<?php


$query_new_sites = "SELECT id,site_id FROM sites";
$result_new_sites = $mysqli_new->query($query_new_sites) or die($mysqli_old->error.__LINE__);
while($row = $result_new_sites->fetch_assoc())
{
	$sitesMapping[$row['id']] = $row['site_id'];
}


$query_new_habitat_table = "CREATE TABLE IF NOT EXISTS `habitats` (
								`id` int NOT NULL AUTO_INCREMENT,
								`type` varchar(2) NOT NULL ,
								`recording_date` date DEFAULT NULL,
								`area` smallint DEFAULT NULL,
								`shrubcover` smallint DEFAULT NULL,
								`tree_canopy` smallint DEFAULT NULL,
								`lawn` TINYINT DEFAULT NULL,
								`other` TINYINT DEFAULT NULL,
								`paved_building` TINYINT DEFAULT NULL,
								`gravel_soil` TINYINT DEFAULT NULL,
								`water` TINYINT DEFAULT NULL,
								`num_traps` smallint DEFAULT NULL,
								`trap_arrange` varchar(255) DEFAULT NULL,
								`percent_observed` TINYINT DEFAULT NULL,
								`radius` smallint DEFAULT NULL,
								`date_entered` datetime DEFAULT NULL,
								`old_habitat_id` int NOT NULL,
								`site_id` int NOT NULL,
								`school_id` int  NOT NULL,
								PRIMARY KEY (`id`),
								FOREIGN KEY (school_id) REFERENCES schools(id),
								FOREIGN KEY (site_id) REFERENCES sites(id)
								);";

$result_new_habitat_table = $mysqli_new->query($query_new_habitat_table) or die($mysqli_new->error.__LINE__);


$query_old_arthro_hab = "SELECT * FROM arthro_hab";
$result_old_arthro_hab = $mysqli_old->query($query_old_arthro_hab) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE TEACHERSCLASS DATA
if($result_old_arthro_hab->num_rows > 0) {

	$queryhabitats = "INSERT into habitats (type,recording_date,area,shrubcover,tree_canopy,lawn,other,paved_building,gravel_soil,water,num_traps,trap_arrange,percent_observed,radius,site_id,school_id,date_entered,old_habitat_id) VALUES ";
	while($row = $result_old_arthro_hab->fetch_assoc())
	{

		$row['percent_observed']= "NULL";
		$row['radius']= "NULL";
		$date_entered= ($row['date_entered'] == NULL) ? "NULL" : "'".$row['date_entered']."'";
		$water= ($row['water'] == NULL) ? "NULL" : $row['water'];
		$recording_date= ($row['recording_date'] == NULL) ? "NULL" : "'".$row['recording_date']."'";
		$school = array_search($row['school_id'], $schoolMapping);
		$site = array_search($row['site_id'], $sitesMapping);

		if($row['site_id'] == 'M1')
		{
			$site = array_search('M1M1', $sitesMapping);
		}

		//Testing data that was present in database.
		if($school == 132)
			continue;

		$queryhabitats = $queryhabitats."('AR',".$recording_date.",".$row['area'].",".$row['shrubcover'].",".$row['tree_canopy'].",".$row['lawn'].",".$row['other'].",".$row['paved_building'].",".$row['gravel_soil'].",".$water.",".$row['num_traps'].",'".$mysqli_old->real_escape_string($row['trap_arrange'])."',".$row['percent_observed'].",".$row['radius'].",".$site.",".$school.",".$date_entered.",".$row['habitat_id']."),";

	}
	$queryhabitats= substr($queryhabitats, 0, -1);
	$queryhabitats = $queryhabitats.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_arthro_hab->close();
// echo $queryhabitats;


$result = $mysqli_new->query($queryhabitats) or die($mysqli_new->error.__LINE__);
echo "Habitats table inserted with data";?><br>
<?php


$query_new_arthro_taxon_table = "CREATE TABLE IF NOT EXISTS `arthro_taxon` (
										`id` int NOT NULL AUTO_INCREMENT,
										`taxon` varchar(6) NOT NULL ,
										`taxon_name` varchar(50) DEFAULT NULL,
										`date_entered` datetime DEFAULT NULL,
										PRIMARY KEY (`id`),
										UNIQUE(`taxon`)
										);";

$result_new_arthro_taxon_table = $mysqli_new->query($query_new_arthro_taxon_table) or die($mysqli_new->error.__LINE__);


$query_old_arthro_taxon = "SELECT * FROM arthro_taxa";
$result_old_arthro_taxon  = $mysqli_old->query($query_old_arthro_taxon) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_arthro_taxon->num_rows > 0) {

	$queryarthro_taxon = "INSERT into arthro_taxon (taxon,taxon_name,date_entered) VALUES ";
	while($row = $result_old_arthro_taxon->fetch_assoc())
	{

		$queryarthro_taxon = $queryarthro_taxon."('".$row['taxon']."','".$row['taxon_name']."','".$row['date_entered']."'),";

	}
	$queryarthro_taxon= substr($queryarthro_taxon, 0, -1);
	$queryarthro_taxon = $queryarthro_taxon.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_arthro_taxon->close();
//echo $queryarthro_taxon;


$result = $mysqli_new->query($queryarthro_taxon) or die($mysqli_new->error.__LINE__);
echo "Arthro taxon table inserted with data";?><br>
<?php



$query_new_habitats = "SELECT id,site_id,old_habitat_id FROM habitats WHERE type = 'AR'";
$result_new_habitats = $mysqli_new->query($query_new_habitats) or die($mysqli_old->error.__LINE__);
while($row = $result_new_habitats->fetch_assoc())
{
	$temp = $row['site_id']." ".$row['old_habitat_id'];
	$habitatsMapping[$row['id']] = $temp;

}

$query_new_class = "SELECT id,old_class_id FROM teachers_classes";
$result_new_class = $mysqli_new->query($query_new_class) or die($mysqli_old->error.__LINE__);
while($row = $result_new_class->fetch_assoc())
{
	$classMapping[$row['id']] = $row['old_class_id'];

}


$query_new_arthro_samples_table = "CREATE TABLE IF NOT EXISTS `arthro_samples` (
				`id` int NOT NULL AUTO_INCREMENT,
				`site_id` int DEFAULT NULL,
				`teachers_class_id` int  DEFAULT NULL,
				`habitat_id` int DEFAULT NULL,
				`collection_date` date DEFAULT NULL,
				`comments` varchar(250) DEFAULT NULL,
				`date_entered` datetime DEFAULT NULL,
				`observer` varchar(255) DEFAULT NULL,
				`old_sample_id` int DEFAULT NULL,
				PRIMARY KEY (`id`),
				FOREIGN KEY (site_id) REFERENCES sites(id) ,
				FOREIGN KEY (teachers_class_id) REFERENCES teachers_classes(id),
				FOREIGN KEY (habitat_id) REFERENCES habitats(id)
				);";

$result_new_arthro_samples_table = $mysqli_new->query($query_new_arthro_samples_table) or die($mysqli_new->error.__LINE__);


$query_old_arthro_samples= "SELECT * FROM arthro_samples";
$result_old_arthro_samples  = $mysqli_old->query($query_old_arthro_samples) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_arthro_samples->num_rows > 0) {

	$queryarthro_samples = "INSERT into arthro_samples (collection_date,comments,observer,site_id,habitat_id,teachers_class_id,date_entered,old_sample_id) VALUES ";
	while($row = $result_old_arthro_samples->fetch_assoc())
	{

		$site = array_search($row['site_id'], $sitesMapping);

		if($row['site_id'] == 'M1')
		{
			$site = array_search('M1M1', $sitesMapping);
		}

		$habitat = array_search(($site." ".$row['habitat_id']), $habitatsMapping);
		$class = array_search($row['class_id'], $classMapping);

		$observer = ($row['observer'] == NULL) ? "NULL" : "'".$row['observer']."'";
		$comments = ($row['comments'] == NULL) ? "NULL" : "'".$row['comments']."'";
		$class = ($class == NULL) ? "NULL" : $class;

		//Testing data that was present in database.
		$queryarthro_samples = $queryarthro_samples."('".$row['sample_date']."',".$comments.",".$observer.",".$site.",".$habitat.",".$class.",'".$row['date_entered']."',".$row['sample_id']."),";

	}
	$queryarthro_samples= substr($queryarthro_samples, 0, -1);
	$queryarthro_samples = $queryarthro_samples.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_arthro_samples->close();
//echo $queryarthro_samples;


$result = $mysqli_new->query($queryarthro_samples) or die($mysqli_new->error.__LINE__);
echo "Arthro Samples table inserted with data";?><br>
<?php


$query_new_arthroSamples = "SELECT id,old_sample_id FROM arthro_samples";
$result_new_arthroSamples= $mysqli_new->query($query_new_arthroSamples) or die($mysqli_old->error.__LINE__);
while($row = $result_new_arthroSamples->fetch_assoc())
{
	$arthroSamplesMapping[$row['id']] = $row['old_sample_id'];

}

$query_new_arthro_taxon = "SELECT id,taxon FROM arthro_taxon";
$result_new_arthro_taxon= $mysqli_new->query($query_new_arthro_taxon) or die($mysqli_old->error.__LINE__);
while($row = $result_new_arthro_taxon->fetch_assoc())
{
	$arthroTaxonMapping[$row['id']] = $row['taxon'];
}


$query_new_arthro_specimens_table = "CREATE TABLE IF NOT EXISTS `arthro_specimens` (
		`id` int NOT NULL AUTO_INCREMENT,
		`trap_no` varchar(20) NOT NULL,
		`arthro_taxon_id` int DEFAULT NULL,
		`frequency` int(10) NOT NULL,
		`arthro_sample_id` int  NOT NULL,
		PRIMARY KEY (`id`),
		FOREIGN KEY (arthro_taxon_id) REFERENCES arthro_taxon(id) ,
		FOREIGN KEY (arthro_sample_id) REFERENCES arthro_samples(id)
		);";

$query_new_arthro_specimens_table = $mysqli_new->query($query_new_arthro_specimens_table) or die($mysqli_new->error.__LINE__);


$query_old_arthro_specimens= "SELECT * FROM arthro_specimens";
$result_old_arthro_specimens  = $mysqli_old->query($query_old_arthro_specimens) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_arthro_specimens->num_rows > 0) {

	$queryarthro_specimens = "INSERT into arthro_specimens (trap_no,arthro_taxon_id,frequency,arthro_sample_id) VALUES ";
	while($row = $result_old_arthro_specimens->fetch_assoc())
	{
		$arthrotaxon = array_search($row['taxon'], $arthroTaxonMapping);
		$arthrosample = array_search($row['sample_id'], $arthroSamplesMapping);

		//Removing entries that do not have a sample id associated.
		if($arthrosample == NULL)
			continue;

		$queryarthro_specimens = $queryarthro_specimens."('".$row['trap_id']."',".$arthrotaxon.",".$row['frequency'].",".$arthrosample."),";

	}
	$queryarthro_specimens= substr($queryarthro_specimens, 0, -1);
	$queryarthro_specimens = $queryarthro_specimens.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_arthro_specimens->close();
//echo $queryarthro_specimens;


$result = $mysqli_new->query($queryarthro_specimens) or die($mysqli_new->error.__LINE__);
echo "Arthro Specimens table inserted with data";?><br>
<?php



$query_old_bird_hab = "SELECT * FROM bird_hab";
$result_old_bird_hab = $mysqli_old->query($query_old_bird_hab) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE TEACHERSCLASS DATA
if($result_old_bird_hab->num_rows > 0) {

	$queryhabitats = "INSERT into habitats (type,recording_date,area,shrubcover,tree_canopy,lawn,other,paved_building,gravel_soil,water,num_traps,trap_arrange,percent_observed,radius,site_id,school_id,date_entered,old_habitat_id) VALUES ";
	while($row = $result_old_bird_hab->fetch_assoc())
	{

		$row['trap_arrange']= "NULL";
		$row['area']= "NULL";
		$row['num_traps']= "NULL";
		$date_entered= ($row['date_entered'] == NULL) ? "NULL" : "'".$row['date_entered']."'";
		$water= ($row['water'] == NULL) ? "NULL" : $row['water'];
		$recording_date= ($row['recording_date'] == NULL) ? "NULL" : "'".$row['recording_date']."'";

		$school = array_search($row['school_id'], $schoolMapping);
		$site = array_search($row['site_id'], $sitesMapping);

		if($row['site_id'] == 'M1')
		{
			$site = array_search('M1M1', $sitesMapping);
		}
		if($row['site_id'] == 'BBC')
		{
			$site = array_search('BBCN33.26.420W111.46.360', $sitesMapping);
		}
		if($row['site_id'] == 'bbc')
		{
			$site = array_search('bbcbbcourt', $sitesMapping);
		}

		//Testing data that was present in database.
		if($school == 132)
			continue;

		$queryhabitats = $queryhabitats."('BI',".$recording_date.",".$row['area'].",".$row['shrubcover'].",".$row['tree_canopy'].",".$row['lawn'].",".$row['other'].",".$row['paved_building'].",".$row['gravel_soil'].",".$water.",".$row['num_traps'].",".$row['trap_arrange'].",".$row['percent_observed'].",".$row['radius'].",".$site.",".$school.",".$date_entered.",".$row['habitat_id']."),";

	}
	$queryhabitats= substr($queryhabitats, 0, -1);
	$queryhabitats = $queryhabitats.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_bird_hab->close();
//echo $queryhabitats;


$result = $mysqli_new->query($queryhabitats) or die($mysqli_new->error.__LINE__);
echo "Habitats table inserted with bird data";?><br>
<?php



$query_new_bird_taxon_table = "CREATE TABLE IF NOT EXISTS `bird_taxon` (
		`id` int NOT NULL AUTO_INCREMENT,
		`species_id` varchar(4) NOT NULL,
		`common_name` varchar(100) DEFAULT NULL,
		`date_entered` datetime DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE(`species_id`)
		);";

$result_new_bird_taxon_table = $mysqli_new->query($query_new_bird_taxon_table) or die($mysqli_new->error.__LINE__);


$query_old_bird_taxon = "SELECT * FROM birdlist";
$result_old_bird_taxon  = $mysqli_old->query($query_old_bird_taxon) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_bird_taxon->num_rows > 0) {

	$querybird_taxon = "INSERT into bird_taxon (species_id,common_name,date_entered) VALUES ";
	while($row = $result_old_bird_taxon->fetch_assoc())
	{

		$querybird_taxon = $querybird_taxon."('".$row['species_id']."','".$mysqli_old->real_escape_string($row['common_name'])."',now()),";

	}
	$querybird_taxon= substr($querybird_taxon, 0, -1);
	$querybird_taxon = $querybird_taxon.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_bird_taxon->close();
//echo $querybird_taxon;


$result = $mysqli_new->query($querybird_taxon) or die($mysqli_new->error.__LINE__);
echo "Bird taxon table inserted with data";?><br>
<?php



$query_new_cloud_cover_table = "CREATE TABLE IF NOT EXISTS `cloud_cover` (
					`id` int NOT NULL AUTO_INCREMENT,
					`cloud_cover_name` varchar(50) DEFAULT NULL,
					PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

$result_new_cloud_cover_table = $mysqli_new->query($query_new_cloud_cover_table) or die($mysqli_new->error.__LINE__);


$query_old_cloud_cover = "SELECT * FROM cloud_cover";
$result_old_cloud_cover  = $mysqli_old->query($query_old_cloud_cover) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_cloud_cover->num_rows > 0) {

	$querycloud_cover = "INSERT into cloud_cover (cloud_cover_name) VALUES ";
	while($row = $result_old_cloud_cover->fetch_assoc())
	{

		$querycloud_cover = $querycloud_cover."('".$row['cloud_cover_name']."'),";

	}
	$querycloud_cover= substr($querycloud_cover, 0, -1);
	$querycloud_cover = $querycloud_cover.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_cloud_cover->close();
//echo $querycloud_cover;


$result = $mysqli_new->query($querycloud_cover) or die($mysqli_new->error.__LINE__);
echo "Cloud Cover table inserted with data";?><br>
<?php




$query_new_habitats = "SELECT id,site_id,old_habitat_id FROM habitats WHERE type = 'BI'";
$result_new_habitats = $mysqli_new->query($query_new_habitats) or die($mysqli_old->error.__LINE__);
while($row = $result_new_habitats->fetch_assoc())
{
	$temp = $row['site_id']." ".$row['old_habitat_id'];
	$habitatsMapping[$row['id']] = $temp;

}

$query_new_bird_samples_table = "CREATE TABLE IF NOT EXISTS `bird_samples` (
		`id` int NOT NULL AUTO_INCREMENT,
		`site_id` int  NOT NULL,
		`habitat_id` int  NOT NULL,
		`teachers_class_id` int  NOT NULL,
		`observer` varchar(255) DEFAULT NULL,
		`collection_date` date DEFAULT NULL,
		`time_start` time DEFAULT NULL,
		`time_end` time DEFAULT NULL,
		`air_temp` int(4) DEFAULT NULL,
		`cloud_cover_id` int DEFAULT NULL,
		`comments` varchar(250) DEFAULT NULL,
		`date_entered` datetime DEFAULT NULL,
		`old_sample_id` int DEFAULT NULL,
		PRIMARY KEY (`id`),
		FOREIGN KEY (site_id) REFERENCES sites(id),
		FOREIGN KEY (habitat_id) REFERENCES habitats(id),
		FOREIGN KEY (teachers_class_id) REFERENCES teachers_classes(id),
		FOREIGN KEY (cloud_cover_id) REFERENCES cloud_cover(id)
		);";

$result_new_bird_samples_table = $mysqli_new->query($query_new_bird_samples_table) or die($mysqli_new->error.__LINE__);


$query_old_bird_samples= "SELECT * FROM bird_surveys";
$result_old_bird_samples  = $mysqli_old->query($query_old_bird_samples) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_bird_samples->num_rows > 0) {

	$querybird_samples = "INSERT into bird_samples (collection_date,time_start,time_end,air_temp,cloud_cover_id,comments,observer,site_id,habitat_id,teachers_class_id,date_entered,old_sample_id) VALUES ";
	while($row = $result_old_bird_samples->fetch_assoc())
	{

		$site = array_search($row['site_id'], $sitesMapping);

		if($row['site_id'] == 'M1')
		{
			$site = array_search('M1M1', $sitesMapping);
		}
		if($row['site_id'] == 'BBC')
		{
			$site = array_search('BBCN33.26.420W111.46.360', $sitesMapping);
		}
		if($row['site_id'] == 'bbc')
		{
			$site = array_search('bbcbbcourt', $sitesMapping);
		}

		$habitat = array_search(($site." ".$row['habitat_id']), $habitatsMapping);
		$class = array_search($row['class_id'], $classMapping);

		$observer = ($row['observer'] == NULL) ? "NULL" : "'".$row['observer']."'";
		$comments = ($row['comments'] == NULL) ? " " : $row['comments'];
		$timestart = ($row['time_start'] == NULL) ? "NULL" : "'".$row['time_start']."'";
		$timeend = ($row['time_end'] == NULL) ? "NULL" : "'".$row['time_end']."'";
		$airtemp = ($row['air_temp'] == NULL) ? "NULL" : $row['air_temp'];
		$cloud = ($row['cloud_cover'] == NULL) ? "NULL" : $row['cloud_cover'];
		$class = ($class == NULL) ? "NULL" : $class;

		if($row['temp_units'] =='C' && $row['air_temp'] != NULL)
		{
			$airtemp = ($airtemp * 1.8) + 32 ;
		}

		if($row['cloud_cover'] == 0 || $row['cloud_cover'] == '0'|| $row['cloud_cover'] == "0")
		{
			$cloud = "NULL";
		}
		$querybird_samples = $querybird_samples."('".$row['survey_date']."',".$timestart.",".$timeend.",".$airtemp.",".$cloud.",'".$mysqli_old->real_escape_string($comments)."',".$observer.",".$site.",".$habitat.",".$class.",'".$row['date_entered']."',".$row['survey_id']."),";

	}
	$querybird_samples= substr($querybird_samples, 0, -1);
	$querybird_samples = $querybird_samples.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_bird_samples->close();
//echo $querybird_samples;


$result = $mysqli_new->query($querybird_samples) or die($mysqli_new->error.__LINE__);
echo "Bird Samples table inserted with data";?><br>
<?php

$query_new_birdSamples = "SELECT id,old_sample_id FROM bird_samples";
$result_new_birdSamples= $mysqli_new->query($query_new_birdSamples) or die($mysqli_old->error.__LINE__);
while($row = $result_new_birdSamples->fetch_assoc())
{
	$birdSamplesMapping[$row['id']] = $row['old_sample_id'];

}

$query_new_bird_taxon = "SELECT id,species_id FROM bird_taxon";
$result_new_bird_taxon= $mysqli_new->query($query_new_bird_taxon) or die($mysqli_old->error.__LINE__);
while($row = $result_new_bird_taxon->fetch_assoc())
{
	$birdTaxonMapping[$row['id']] = $row['species_id'];
}


$query_new_bird_specimens_table = "CREATE TABLE IF NOT EXISTS `bird_specimens` (
					`id` int NOT NULL AUTO_INCREMENT,
					`bird_sample_id` int  NOT NULL,
					`species_id` int NOT NULL,
					`frequency` int(10) NOT NULL,
					PRIMARY KEY (`id`),
					FOREIGN KEY (species_id) REFERENCES bird_taxon(`id`) ,
					FOREIGN KEY (bird_sample_id) REFERENCES bird_samples(`id`)
					) ;";

$query_new_bird_specimens_table = $mysqli_new->query($query_new_bird_specimens_table) or die($mysqli_new->error.__LINE__);


$query_old_bird_specimens= "SELECT * FROM birds";
$result_old_bird_specimens  = $mysqli_old->query($query_old_bird_specimens) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_bird_specimens->num_rows > 0) {

	$querybird_specimens = "INSERT into bird_specimens (species_id,frequency,bird_sample_id) VALUES ";
	while($row = $result_old_bird_specimens->fetch_assoc())
	{
		$birdtaxon = array_search($row['species_id'], $birdTaxonMapping);
		$birdsample = array_search($row['survey_id'], $birdSamplesMapping);

		if($row['frequency'] == NULL)
			$row['frequency'] = 0;

		//Removing entries that do not have a sample id associated.
		if($birdsample == NULL)
			continue;

		if($birdtaxon == NULL)
			continue;

		$querybird_specimens = $querybird_specimens."(".$birdtaxon.",".$row['frequency'].",".$birdsample."),";

	}
	$querybird_specimens= substr($querybird_specimens, 0, -1);
	$querybird_specimens = $querybird_specimens.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_bird_specimens->close();
//echo $querybird_specimens;


$result = $mysqli_new->query($querybird_specimens) or die($mysqli_new->error.__LINE__);
echo "Bird Specimens table inserted with data";?><br>
<?php


$query_old_veg_hab = "SELECT * FROM vegetation_hab";
$result_old_veg_hab = $mysqli_old->query($query_old_veg_hab) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE TEACHERSCLASS DATA
if($result_old_veg_hab->num_rows > 0) {

	$queryhabitats = "INSERT into habitats (type,recording_date,area,shrubcover,tree_canopy,lawn,other,paved_building,gravel_soil,water,num_traps,trap_arrange,percent_observed,radius,site_id,school_id,date_entered,old_habitat_id) VALUES ";
	while($row = $result_old_veg_hab->fetch_assoc())
	{

		$row['trap_arrange']= "NULL";
		$row['percent_observed']= "NULL";
		$row['radius']= "NULL";
		$row['num_traps']= "NULL";

		$date_entered= ($row['date_entered'] == NULL) ? "NULL" : "'".$row['date_entered']."'";
		$water= ($row['water'] == NULL) ? "NULL" : $row['water'];
		$recording_date= ($row['recording_date'] == NULL) ? "NULL" : "'".$row['recording_date']."'";

		$school = array_search($row['school_id'], $schoolMapping);
		$site = array_search($row['site_id'], $sitesMapping);

		if($row['site_id'] == 'M1')
		{
			$site = array_search('M1M1', $sitesMapping);
		}
		if($row['site_id'] == 'BBC')
		{
			$site = array_search('BBCN33.26.420W111.46.360', $sitesMapping);
		}
		if($row['site_id'] == 'bbc')
		{
			$site = array_search('bbcbbcourt', $sitesMapping);
		}

		//Testing data that was present in database.
		if($school == 132)
			continue;

		$queryhabitats = $queryhabitats."('VE',".$recording_date.",".$row['area'].",".$row['shrubcover'].",".$row['tree_canopy'].",".$row['lawn'].",".$row['other'].",".$row['paved_building'].",".$row['gravel_soil'].",".$water.",".$row['num_traps'].",".$row['trap_arrange'].",".$row['percent_observed'].",".$row['radius'].",".$site.",".$school.",".$date_entered.",".$row['habitat_id']."),";

	}
	$queryhabitats= substr($queryhabitats, 0, -1);
	$queryhabitats = $queryhabitats.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_veg_hab->close();
//echo $queryhabitats;


$result = $mysqli_new->query($queryhabitats) or die($mysqli_new->error.__LINE__);
echo "Habitats table inserted with veg data";?><br>
<?php



$query_new_veg_taxon_table = "CREATE TABLE IF NOT EXISTS `veg_taxon` (
		`id` int NOT NULL AUTO_INCREMENT,
		`species_id` varchar(6) NOT NULL,
		`type` varchar(10) DEFAULT NULL,
		`common_name` varchar(100) DEFAULT NULL,
		`date_entered` datetime DEFAULT NULL,
		PRIMARY KEY (`id`),
		UNIQUE(`species_id`)
		) ;";

$result_new_veg_taxon_table = $mysqli_new->query($query_new_veg_taxon_table) or die($mysqli_new->error.__LINE__);


$query_old_veg_taxon = "SELECT * FROM veg_species_list";
$result_old_veg_taxon  = $mysqli_old->query($query_old_veg_taxon) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_veg_taxon->num_rows > 0) {

	$querybird_taxon = "INSERT into veg_taxon (species_id,common_name,date_entered) VALUES ";
	while($row = $result_old_veg_taxon->fetch_assoc())
	{

		$querybird_taxon = $querybird_taxon."('".$row['species_id']."','".$mysqli_old->real_escape_string($row['common_name'])."',now()),";

	}
	$querybird_taxon= substr($querybird_taxon, 0, -1);
	$querybird_taxon = $querybird_taxon.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_veg_taxon->close();
//echo $querybird_taxon;


$result = $mysqli_new->query($querybird_taxon) or die($mysqli_new->error.__LINE__);
echo "Veg taxon table inserted with data";?><br>
<?php



$query_new_habitats = "SELECT id,site_id,old_habitat_id FROM habitats WHERE type = 'VE'";
$result_new_habitats = $mysqli_new->query($query_new_habitats) or die($mysqli_old->error.__LINE__);
while($row = $result_new_habitats->fetch_assoc())
{
	$temp = $row['site_id']." ".$row['old_habitat_id'];
	$habitatsMapping[$row['id']] = $temp;

}

$query_new_veg_samples_table = "CREATE TABLE IF NOT EXISTS `veg_samples` (
				`id` int NOT NULL AUTO_INCREMENT,
				`tree_count` int(10) DEFAULT NULL,
				`cactus_count` int(10) DEFAULT NULL,
				`collection_date` date DEFAULT NULL,
				`observer` varchar(255) DEFAULT NULL,
				`shrub_count` int(10) DEFAULT NULL,
				`date_entered` datetime DEFAULT NULL,
				`site_id` int NOT NULL,
				`habitat_id` int NOT NULL,
				`teachers_class_id` int  DEFAULT NULL,
				`old_sample_id` int DEFAULT NULL,
				PRIMARY KEY (`id`),
				FOREIGN KEY (site_id) REFERENCES sites(id),
				FOREIGN KEY (teachers_class_id) REFERENCES teachers_classes(id),
				FOREIGN KEY (habitat_id) REFERENCES habitats(id)
				);";

$result_new_veg_samples_table = $mysqli_new->query($query_new_veg_samples_table) or die($mysqli_new->error.__LINE__);


$query_old_veg_samples= "SELECT * FROM veg_survey";
$result_old_veg_samples  = $mysqli_old->query($query_old_veg_samples) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_veg_samples->num_rows > 0) {

	$queryveg_samples = "INSERT into veg_samples (collection_date,tree_count,cactus_count,shrub_count,observer,site_id,habitat_id,teachers_class_id,date_entered,old_sample_id) VALUES ";
	while($row = $result_old_veg_samples->fetch_assoc())
	{

		$site = array_search($row['site_id'], $sitesMapping);

		$habitat = array_search(($site." ".$row['habitat_id']), $habitatsMapping);
		$class = array_search($row['class_id'], $classMapping);

		$observer = ($row['observer'] == NULL) ? "NULL" : "'".$row['observer']."'";
		$class = ($class == NULL) ? "NULL" : $class;


		$queryveg_samples = $queryveg_samples."('".$row['survey_date']."',".$row['tree_count'].",".$row['cactus_count'].",".$row['shrub_count'].",".$observer.",".$site.",".$habitat.",".$class.",'".$row['date_entered']."',".$row['survey_id']."),";

	}
	$queryveg_samples= substr($queryveg_samples, 0, -1);
	$queryveg_samples = $queryveg_samples.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_veg_samples->close();
//echo $queryveg_samples;


$result = $mysqli_new->query($queryveg_samples) or die($mysqli_new->error.__LINE__);
echo "Veg Samples table inserted with data";?><br>
<?php

$query_new_vegSamples = "SELECT id,old_sample_id FROM veg_samples";
$result_new_vegSamples= $mysqli_new->query($query_new_vegSamples) or die($mysqli_old->error.__LINE__);
while($row = $result_new_vegSamples->fetch_assoc())
{
	$vegSamplesMapping[$row['id']] = $row['old_sample_id'];

}

$query_new_veg_taxon = "SELECT id,species_id FROM veg_taxon";
$result_new_veg_taxon= $mysqli_new->query($query_new_veg_taxon) or die($mysqli_old->error.__LINE__);
while($row = $result_new_veg_taxon->fetch_assoc())
{
	$vegTaxonMapping[$row['id']] = $row['species_id'];
}


$query_new_veg_specimens_table = "CREATE TABLE IF NOT EXISTS `veg_specimens` (
						`id` int NOT NULL AUTO_INCREMENT,
						`veg_no` varchar(50) NOT NULL,
						`veg_sample_id` int  NOT NULL,
						`plant_type` varchar(6) DEFAULT NULL,
						`species_id` int NOT NULL,
						`circumference` double(15,5) DEFAULT NULL,
						`canopy` double(15,5) DEFAULT NULL,
						`height` double(15,5) DEFAULT NULL,
						`comments` varchar(100) DEFAULT NULL,
						PRIMARY KEY (`id`),
						FOREIGN KEY (species_id) REFERENCES veg_taxon(`id`) ,
						FOREIGN KEY (veg_sample_id) REFERENCES veg_samples(`id`)
						) ;
						";

$result_new_bird_specimens_table = $mysqli_new->query($query_new_veg_specimens_table) or die($mysqli_new->error.__LINE__);


$query_old_veg_specimens= "SELECT * FROM veg_measure";
$result_old_veg_specimens  = $mysqli_old->query($query_old_veg_specimens) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_veg_specimens->num_rows > 0) {

	$queryveg_specimens = "INSERT into veg_specimens (species_id,veg_sample_id,veg_no,plant_type,circumference,canopy,height,comments) VALUES ";
	while($row = $result_old_veg_specimens->fetch_assoc())
	{
		$vegtaxon = array_search($row['species_id'], $vegTaxonMapping);
		$vegsample = array_search($row['survey_id'], $vegSamplesMapping);
		$comments = ($row['location'] == NULL) ? " " : $row['location'];

		//Removing entries that do not have a sample id associated.
		if($vegsample == NULL)
			continue;

		if($vegtaxon == NULL)
			continue;

		$queryveg_specimens = $queryveg_specimens."(".$vegtaxon.",".$vegsample.",".$row['veg_id'].",'".$row['plant_type']."',".$row['circumference'].",".$row['canopy'].",".$row['height'].",'".$comments."'),";

	}
	$queryveg_specimens= substr($queryveg_specimens, 0, -1);
	$queryveg_specimens = $queryveg_specimens.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_veg_specimens->close();
//echo $queryveg_specimens;


$result = $mysqli_new->query($queryveg_specimens) or die($mysqli_new->error.__LINE__);
echo "Veg Specimens table inserted with data";?><br>
<?php



$query_new_bruchid_samples_table = "CREATE TABLE IF NOT EXISTS `bruchid_samples` (
		`id` int NOT NULL AUTO_INCREMENT,
		`site_type` varchar(20) DEFAULT NULL,
		`tree_type` varchar(10) DEFAULT NULL,
		`location` varchar(255) DEFAULT NULL,
		`observer` varchar(255) DEFAULT NULL,
		`collection_date` date DEFAULT NULL,
		`date_entered` datetime DEFAULT NULL,
		`site_id` int NOT NULL,
		`teachers_class_id` int  NOT NULL,
		`old_sample_id` int DEFAULT NULL,
		PRIMARY KEY (`id`),
		FOREIGN KEY (teachers_class_id) REFERENCES teachers_classes(id),
		FOREIGN KEY (site_id) REFERENCES sites(id)
		);";

$result_new_bruchid_samples_table = $mysqli_new->query($query_new_bruchid_samples_table) or die($mysqli_new->error.__LINE__);


$query_old_bruchid_samples= "SELECT * FROM bruchid_sample";
$result_old_bruchid_samples  = $mysqli_old->query($query_old_bruchid_samples) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_bruchid_samples->num_rows > 0) {

	$querybruchid_samples = "INSERT into bruchid_samples (collection_date,site_type,tree_type,location,observer,site_id,teachers_class_id,date_entered,old_sample_id) VALUES ";
	while($row = $result_old_bruchid_samples->fetch_assoc())
	{

		$site = array_search($row['site_id'], $sitesMapping);
		$class = array_search($row['class_id'], $classMapping);
		$observer = ($row['observer'] == NULL) ? "NULL" : "'".$row['observer']."'";
		$location = ($row['location'] == NULL) ? "NULL" : "'".$row['location']."'";
		$class = ($class == NULL) ? "NULL" : $class;
		$treetype = ($row['tree_type'] == 'blue' || $row['tree_type'] == 'bluee') ? "'B'" : "'F'" ;


		$querybruchid_samples = $querybruchid_samples."('".$row['sample_date']."','".$row['site_type']."',".$treetype.",".$location.",".$observer.",".$site.",".$class.",'".$row['date_entered']."',".$row['sample_id']."),";

	}
	$querybruchid_samples= substr($querybruchid_samples, 0, -1);
	$querybruchid_samples = $querybruchid_samples.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_bruchid_samples->close();
//echo $querybruchid_samples;


$result = $mysqli_new->query($querybruchid_samples) or die($mysqli_new->error.__LINE__);
echo "Bruchid Samples table inserted with data";?><br>
<?php



$query_new_bruchidSamples = "SELECT id,old_sample_id FROM bruchid_samples";
$result_new_bruchidSamples= $mysqli_new->query($query_new_bruchidSamples) or die($mysqli_old->error.__LINE__);
while($row = $result_new_bruchidSamples->fetch_assoc())
{
	$bruchidSamplesMapping[$row['id']] = $row['old_sample_id'];

}

$query_new_bruchid_specimens_table = "CREATE TABLE IF NOT EXISTS `bruchid_specimens` (
				`id` int NOT NULL AUTO_INCREMENT,
				`tree_no` varchar(10) NOT NULL,
				`pod_no` varchar(10) NOT NULL,
				`bruchid_sample_id` int  NOT NULL,
				`hole_count` int(10) DEFAULT NULL,
				`seed_count` int(10) DEFAULT NULL,
				PRIMARY KEY (`id`),
				FOREIGN KEY (bruchid_sample_id) REFERENCES bruchid_samples(`id`)
				);";

$result_new_bruchid_specimens_table = $mysqli_new->query($query_new_bruchid_specimens_table) or die($mysqli_new->error.__LINE__);


$query_old_bruchid_specimens= "SELECT * FROM bruchid_pods";
$result_old_bruchid_specimens  = $mysqli_old->query($query_old_bruchid_specimens) or die($mysqli_old->error.__LINE__);

// GOING THROUGH THE ARTHROHAB DATA
if($result_old_bruchid_specimens->num_rows > 0) {

	$querybruchid_specimens = "INSERT into bruchid_specimens (tree_no,pod_no,hole_count,seed_count,bruchid_sample_id) VALUES ";
	while($row = $result_old_bruchid_specimens->fetch_assoc())
	{
		$bruchidsample = array_search($row['sample_id'], $bruchidSamplesMapping);

		$pod = ($row['pod_id'] == ' ') ? "NULL" : $row['pod_id'];
		$hole= ($row['hole_count'] == NULL) ? "NULL" : $row['hole_count'];
		$seed = ($row['seed_count'] == NULL) ? "NULL" : $row['seed_count'];

		$querybruchid_specimens = $querybruchid_specimens."('".$row['tree_id']."','".$pod."',".$hole.",".$seed.",".$bruchidsample."),";

	}
	$querybruchid_specimens= substr($querybruchid_specimens, 0, -1);
	$querybruchid_specimens = $querybruchid_specimens.";";

}
else {
	echo 'NO RESULTS';
}
$result_old_bruchid_specimens->close();
//echo $querybruchid_specimens;


$result = $mysqli_new->query($querybruchid_specimens) or die($mysqli_new->error.__LINE__);
echo "Bruchid Specimens table inserted with data";?><br>
<?php

$query_new_alter_teachers= "ALTER TABLE teachers DROP old_teacher_id;";
$result = $mysqli_new->query($query_new_alter_teachers) or die($mysqli_new->error.__LINE__);

$query_new_alter_arthro_samples= "ALTER TABLE arthro_samples DROP old_sample_id;";
$result = $mysqli_new->query($query_new_alter_arthro_samples) or die($mysqli_new->error.__LINE__);

$query_new_alter_bird_samples= "ALTER TABLE bird_samples DROP old_sample_id;";
$result = $mysqli_new->query($query_new_alter_bird_samples) or die($mysqli_new->error.__LINE__);

$query_new_alter_veg_samples= "ALTER TABLE veg_samples DROP old_sample_id;";
$result = $mysqli_new->query($query_new_alter_veg_samples) or die($mysqli_new->error.__LINE__);

$query_new_alter_bruchid_samples= "ALTER TABLE bruchid_samples DROP old_sample_id;";
$result = $mysqli_new->query($query_new_alter_bruchid_samples) or die($mysqli_new->error.__LINE__);

$query_new_alter_habitat= "ALTER TABLE habitats DROP old_habitat_id;";
$result = $mysqli_new->query($query_new_alter_habitat) or die($mysqli_new->error.__LINE__);

$query_new_alter_teachers_classes= "ALTER TABLE teachers_classes DROP old_class_id;";
$result = $mysqli_new->query($query_new_alter_teachers_classes) or die($mysqli_new->error.__LINE__);

echo "Successully removed old database columns in new database";?><br>
<?php

// CLOSE CONNECTION
mysqli_close($mysqli_new);
mysqli_close($mysqli_old);


?>


