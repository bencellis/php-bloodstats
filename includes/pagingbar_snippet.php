    <!-- Paging  -->
    <div class="row">
        <div class="col-md-12">
        	<div>
        		<hr />
        		<?php
                    echo getSimplePagingHTML($page, $pagedays, count($statistics));
        		?>
        		<hr />
        	</div>
        </div>
    </div>
    <!-- End Paging  -->
