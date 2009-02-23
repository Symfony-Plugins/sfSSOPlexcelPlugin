<h2>search</h2>
Accounts with a <tt>logonCount</tt> of zero: 

<pre>
<?php if(is_array($results)): ?>
  <?php foreach ($results as $result): ?>
    <?php echo $result['distinguishedName'] . "\n";?>
  <?php endforeach; ?>
<?php else: ?>
  No result.
<?php endif;?>
</pre>
