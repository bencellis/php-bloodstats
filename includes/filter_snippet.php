      <div class="row">
        <div class="col-12 text-center border border-info">
            <form class="form-inline">
            	<input type="hidden" name="page" value="1" />
            	<input type="hidden" name="scope" value="<?php echo empty($_REQUEST['scope']) ? '' : $_REQUEST['scope']; ?>" />
            	<label class="my-1 mr-2" for="pagedays">Days Per Page</label>
            	<select name="pagedays" class="custom-select my-1 mr-sm-2" id="pagedays">
            		<option selected>Days Per Page</option>
            		<option value="28">Four Weeks (28 days)</option>
            		<option value="42">Six Weeks (42 days)</option>
            		<option value="60">Two months (60 days)</option>
            		<option value="90">Three months - Default (90 days)</option>
            	</select>
            	<button type="submit" class="btn btn-primary my-1">View</button>
            </form>
        </div>
      </div>