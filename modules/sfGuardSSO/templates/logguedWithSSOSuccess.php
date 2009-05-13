<?php sfDynamics::load('accueil_sso'); ?>
<h2 class="accueil">Bonjour <?php echo $sf_user->getRenderName() ?></h2>

<p class="line_button clearfix">
  <a href="<?php echo url_for('@homepage') ?>" class="bouton_01"> <span class="png_fix"><span class="png_fix"><span class="png_fix">Entrer</span></span></span></a>
  <a href="<?php echo url_for('@sso_change_user') ?>" class="spacer_button bouton_01"> <span class="png_fix"><span class="png_fix"><span class="png_fix">Changer d'utilisateur</span></span></span></a>
</p>

