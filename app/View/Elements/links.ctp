<br>
<div class="topright">
	<?php echo $this->Html->link($this->Session->read('Username'),array('controller' => 'teachers', 'action' => 'home')); ?>
	<?php if($this->Session->check('User'))
		{
			echo $this->Form->postLink('Logout', array('controller' => 'teachers','action' => 'logout'));
		}
?>
</div>
<div class="topleft">
	<?php 
	echo $this->Html->link('<< Back','javascript:history.back(1);');?>
	</div>
<br>
<br>