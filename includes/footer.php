    <div class="container">
      <footer>
        <p>&copy; <?php echo $title .  ' ' . date("Y") ?></p>
      </footer>
	</div>

    <!-- Cookie Consent Stuff
    ================================================== -->    
	<!-- Begin Cookie Consent plugin by Silktide - http://silktide.com/cookieconsent -->
	<script type="text/javascript">
		window.cookieconsent_options = {
			"message": "<?php echo $message; ?>" ,
			"dismiss":  "<?php echo $dismissmsg; ?>",
			"learnMore": "<?php echo $moremsg; ?>",
			"link": '<?php echo $morelink; ?>',
			"theme": "<?php echo $theme; ?>"
		};
	</script>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/1.0.9/cookieconsent.min.js"></script>
	<!-- End Cookie Consent plugin -->
	<!-- http://www.google.com/intl/en/policies/technologies/types/ -->    
	
    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>
