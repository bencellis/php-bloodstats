					<div class="row">
						<div class="col-12">
							<b>Alcohol</b>
						</div>
					</div>
					<div class="row">
						<?php if (empty($stats['alcohol'])): ?>
    						<div class="col-12">
    							No Statistics!!!
    						</div>
    					<?php else :?>
    						<div class="col-12">
    							<?php if ($stats['alcohol']['unknown']): ?>
    								Unknown
    							<?php else: ?>
    								<?php echo $stats['alcohol']['units'] . 'u'; ?>
        							<?php if ($stats['alcohol']['estimate']): ?>
        								&nbsp;Estimated.
									<?php endif; ?>
    							<?php endif; ?>
    						</div>
    					<?php endif; ?>
					</div>
