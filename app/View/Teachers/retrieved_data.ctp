	<div>
		<?php echo $this->element('links'); ?>
	</div>
	<?php  $this->Html->addCrumb('Profile', 'index'); 
	$this->Html->addCrumb('Download Data', 'downloadData');
	$this->Html->addCrumb('Data Retrieved', 'retrievedData');
echo $this->Html->getCrumbs(' > ', array(
		'url' => array('controller' => 'teachers', 'action' => 'index'),
		'escape' => false
));
?>

	<p>Congratulations - your data is ready!</p>
	<p>
		<?php
  echo $this->Html->link($this->Html->image('downloadData.png', array('width'=>'150px', 'alt'=>'download')).'Download',
      array('controller'=>'teachers','action'=>'export'), array('target'=>'_blank', 'escape'=>false));
		?></p>
	<br>


	<p> The data will be downloaded as a CSV file which can be imported directly into your spreadsheet program (i.e. EXCEL).  </p>
