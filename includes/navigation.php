    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
    	<a class="navbar-brand" href="#"><?php echo $_SERVER['HTTP_HOST'] ?></a>
    	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
    		<span class="navbar-toggler-icon"></span>
    	</button>
    	<div class="collapse navbar-collapse" id="navbarCollapse">
    		<ul class="navbar-nav mr-auto">
    			<li class="nav-item active">
    				<a class="nav-link" href="/bloodstats/index.php">Home <span class="sr-only">(current)</span></a>
    			</li>
            	<li class="nav-item dropdown">
            		<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            		BS vs BP
            		</a>
            		<div class="dropdown-menu" aria-labelledby="navbarDropdown">
                		<a class="dropdown-item" href="/bloodstats/bs_vs_bp.php?scope=bpstats">BS vs BP</a>
                		<a class="dropdown-item" href="/bloodstats/bs_vs_bp.php?scope=bp3">BS vs BP (3 AVG)</a>
                		<a class="dropdown-item" href="/bloodstats/bs_vs_bp.php?scope=bp2">BS vs BP (2 AVG)</a>
					</div>
    			</li>
            	<li class="nav-item dropdown">
            		<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            		Lisinopril Stats
            		</a>
            		<div class="dropdown-menu" aria-labelledby="navbarDropdown">
                		<a class="dropdown-item" href="/bloodstats/lisinopril.php?scope=bpstats">Lisinopril vs BP</a>
                		<a class="dropdown-item" href="/bloodstats/lisinopril.php?scope=bpstats-al">Lisinopril vs BP vs Alcohol</a>
                		<a class="dropdown-item" href="/bloodstats/lisinopril.php?scope=bp3">Lisinopril vs BP (3 AVG)</a>
                		<a class="dropdown-item" href="/bloodstats/lisinopril.php?scope=bp3-al">Lisinopril vs BP (3 AVG) vs Alcohol</a>
                		<a class="dropdown-item" href="/bloodstats/lisinopril.php?scope=bp2">Lisinopril vs BP (2 AVG)</a>
                		<a class="dropdown-item" href="/bloodstats/lisinopril.php?scope=bp2-al">Lisinopril vs BP (2 AVG) vs Alcohol</a>
					</div>
    			</li>
            	<li class="nav-item dropdown">
            		<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            		Metamorfin Stats
            		</a>
            		<div class="dropdown-menu" aria-labelledby="navbarDropdown">
                		<a class="dropdown-item" href="/bloodstats/metamorfin.php?scope=bsstats">Metamorfin vs BS</a>
                		<a class="dropdown-item" href="/bloodstats/metamorfin.php?scope=bsstats-al">Metamorfin vs BS vs Alcohol</a>
					</div>
    			</li>
    			<li class="nav-item">
    				<a class="nav-link" href=""></a>
    			</li>
            	<li class="nav-item dropdown">
            		<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            		Alcohol
            		</a>
            		<div class="dropdown-menu" aria-labelledby="navbarDropdown">
                		<a class="dropdown-item" href="/bloodstats/alcohol.php?scope=bsstats">Alcohol vs BS</a>
                		<div class="dropdown-divider"></div>
                		<a class="dropdown-item" href="/bloodstats/alcohol.php?scope=bpstats">Alcohol vs BP</a>
                		<a class="dropdown-item" href="/bloodstats/alcohol.php?scope=bp3">Alcohol vs BP (3 Avg)</a>
                		<a class="dropdown-item" href="/bloodstats/alcohol.php?scope=bp2">Alcohol vs BP (2 Avg)</a>
            		</div>
            	</li>
    		</ul>
    	</div>
    </nav>