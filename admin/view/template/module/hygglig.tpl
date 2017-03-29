<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-hygglig" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-hygglig" class="form-horizontal">
          
		  <!-- EID -->
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-eid"><?php echo $entry_eid; ?></label>
            <div class="col-sm-10">
              <input name="hygglig_eid" class="col-xs-12 form-control"  type="text" name="input-eid" value="<?php echo $hygglig_eid; ?>"></input>
            </div>
          </div>
		  <!-- EID -->
		  
		  <!-- SECRET -->
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-secret"><?php echo $entry_secret; ?></label>
            <div class="col-sm-10">
              <input name="hygglig_secret" class="col-xs-12 form-control" type="text" name="input-secret" value="<?php echo $hygglig_secret; ?>"></input>
            </div>
          </div>
		  <!-- SECRET -->
		  
		  <!-- TEST / LIVE -->
		  <div class="form-group">
            <label class="col-sm-2 control-label" for="input-server"><?php echo $entry_server; ?></label>
            <div class="col-sm-10">
              <select name="hygglig_server" id="input-server" class="form-control">
                <?php if ($hygglig_server) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
            </div>
          </div> 		  
		  <!-- TEST / LIVE -->	  
		  
		  <!--Shipping-->
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-hygglig_shipping_status"><?php echo $entry_sent_status; ?></label>
            <div class="col-sm-10">
              <select name="hygglig_shipping_status" id="input-hygglig_shipping_status" class="form-control">
					<!-- ADD NOT ACTIVATED VALUE -->
					<option <?php if ($hygglig_shipping_status == "0") { echo 'selected="selected"';}?> value="0">Not activated</option>
					<?php foreach ($order_statuses as $order_status) { ?>
						<?php if ($order_status['order_status_id'] == $hygglig_shipping_status) { ?>
							<option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
						<?php } else { ?>
							<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
						<?php } ?>
					<?php } ?>
              </select>
            </div>
          </div>   
		  <!--Shipping-->
		  
		  <!--Cancel-->
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-hygglig_cancel_status"><?php echo $entry_cancel_status; ?></label>
            <div class="col-sm-10">
              <select name="hygglig_cancel_status" id="input-hygglig_cancel_status" class="form-control">
					<!-- ADD NOT ACTIVATED VALUE -->
					<option <?php if ($hygglig_cancel_status == "0") { echo 'selected="selected"';}?> value="0">Not activated</option>
					<?php foreach ($order_statuses as $order_status) { ?>
						<?php if ($order_status['order_status_id'] == $hygglig_cancel_status) { ?>
							<option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
						<?php } else { ?>
							<option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
						<?php } ?>
					<?php } ?>
              </select>
            </div>
          </div>   
		  <!--Cancel-->
		  
        </form>
      </div>
	</div>
  </div>
</div>

<?php echo $footer; ?>