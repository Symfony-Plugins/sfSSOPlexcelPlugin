<?php use_helper('I18N') ?>
<h2>Make a search</h2>
<form action="<?php echo url_for('ldapSearch/search') ?>" method="post">
  <table>
    <?php echo $form ?>
  </table>

  <input type="submit" value="<?php echo __('Search') ?>" />
  
</form>


<?php if(isset($result)): ?>
<h2>your result</h2>
  <?php if($result == false): ?>
  error..
  <?php else: ?>
  <ul>
    <li>first name : <?php echo isset($result['givenName'])? $result['givenName'] :'' ?></li>
    <li>last name :  <?php echo isset($result['sn'])? $result['sn'] :'' ?></li>
    <li>dn :         <?php echo isset($result['cn'])? $result['cn'] :'' ?></li>
    <li>company :    <?php echo isset($result['company'])? $result['company'] :'' ?></li>
    <li>departement :<?php echo isset($result['department'])? $result['department'] :'' ?></li>
    <li>manager dn:  <?php echo isset($result['manager'])? $result['manager'] :'' ?></li>
  </ul>
  <?php endif; ?>
<?php endif;?>
