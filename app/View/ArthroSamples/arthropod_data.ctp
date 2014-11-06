
<div>
		<?php echo $this->element('links'); ?>
</div>

	<?php $this->Html->addCrumb('Submit Data', '/teachers/submitData');
	$this->Html->addCrumb('Habitat Check', '/habitats/habitatCheck');
	$this->Html->addCrumb('Arthropod Data', '/arthroSamples/arthropodData');
  echo $this->Html->getCrumbs(' > ', array(
		'url' => array('controller' => 'teachers', 'action' => 'index'),
		'escape' => false
));
?>


<?php echo $this->Form->create('ArthroSample',array('class'=>'form'));   ?>

<div class="formfield">
<legend>
	<b>Protocol : Arthropods</b>
</legend>
<br>

		<p>
		<i><b>Please fill out the arthropod data sheet prior to entering data. Remember while entering survey data:</b> </i>
	</p>
	<p>The survey form cannot be submitted without data being entered</p>
	<p>There may not be duplicate entries (i.e. two rows with the same trap
		ID and Order)</p>
	<p>All entries in a row should be filled, no column can be left
		unfilled</p>


	<?php echo $this->Form->input('school_id',array('div'=>'formfield','options' => $schooloptions,'disabled' => 'disabled'));
	echo $this->Form->input('Teacher',array('value' => $teacherName,'disabled' => 'disabled','div'=>'formfield','error' => array('wrap' => 'div','class' => 'formerror')));
	echo $this->Form->input('site_name',array('disabled' => 'disabled','div'=>'formfield','options' => $siteOptions,'error' => array('wrap' => 'div','class' => 'formerror')));
	echo $this->Form->input('class_name',array('disabled' => 'disabled','div'=>'formfield','options' => $classOptions,'error' => array('wrap' => 'div','class' => 'formerror')));
	echo $this->Form->input('collection_date',array('div'=>'formfield'));
	echo $this->Form->input('observer',array('label' =>'Observer\'s Name', 'div'=>'formfield'));
	echo $this->Form->input('comments',array('div'=>'formfield','type'=>'textarea','rows' => 3, 'cols' => 35));  ?>
	<br>
	<br>
	<p align="center">
		<b>Survey Data : </b>
	</p>
	<p align="center"><b>Please make sure that you enter all 3 values. </b></p>
	<table>
		<tr>
			<th>Trap No</th>
			<th>Arthropod Order</th>
			<th>Tally Number</th>
		</tr>

		<?php foreach(range(0,60) as $i) { ?>
      <?php if($i == 31) { ?>
        <tr>
          <td colspan=3>
            <a href="#more" class="show_more_button">Add more inputs</a> 
            <a name="more"/>
          </td>
        </tr>
      <?php } ?>
      <tr <?php if($i > 30) echo 'class="show_more"'; ?>>
        <td><?php echo $this->Form->input('ArthroSpecimen'.$i.'trap_no',array('label' => ''))?> </td>
        <td><?php echo $this->Form->input('ArthroSpecimen'.$i.'taxon',array('label' => '','empty' => 'Select','options' => $orderOptions))?> </td>
        <td><?php echo $this->Form->input('ArthroSpecimen'.$i.'frequency',array('type' =>'select','label' => '', 'options' => range(0, 100)))?></td>
      </tr>
		<?php }?>
	</table>
</div>

  <script>
  $(document).ready(function() {
    $('.show_more').hide(); 
    $('.show_more_button').on('click', function() { 
      $('.show_more').show(); 
      $('.show_more_button').hide(); 
     });
  });
  </script>
<p><b>Please double check your entries before you click Submit.</b></p>
<?php echo $this->Form->end('Submit Arthropod Data',array('div'=>'submit')); ?>
