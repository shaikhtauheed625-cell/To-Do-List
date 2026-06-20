    <script src="<?php echo SITE_URL; ?>/assets/js/app.js?v=<?php echo filemtime(dirname(__DIR__) . '/assets/js/app.js'); ?>"></script>
    
    <!-- Custom Page Scripts -->
    <?php if(isset($custom_js)): ?>
        <?php echo $custom_js; ?>
    <?php endif; ?>
</body>
</html>
