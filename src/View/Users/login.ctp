<?php
/**
 * @var \App\View\AppView $this
 */
?>
<h1>Login</h1>
<?php 
   echo $this->Form->create();
   echo $this->Form->control('email');
   echo $this->Form->control('password');
   echo $this->Form->button(__('Login'), ['class' => 'btn btn-primary']);
   echo $this->Form->end();
?>