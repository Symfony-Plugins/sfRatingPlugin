<?php echo isset($message) ? $message : '' ?>
<?php if (isset($token)): ?>
<script type="text/javascript">
$('<?php echo 'current_rating_'.$token ?>').style.width = <?php echo (string)(sfConfig::get('app_rating_star_width', 25) * $rating) ?>+'px';
</script>
<?php endif; ?>