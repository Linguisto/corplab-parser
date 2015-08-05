<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <?php if (isset($error_warning)) { ?>
  <div class="warning"><?php echo $error_warning; ?></div>
  <?php } ?>
  <?php if (isset($success)) { ?>
  <div class="success"><?php echo $success; ?></div>
  <?php } ?>
  <div class="box">
  	<div class="heading">
	  <h1><img src="view/image/product.png" alt="" /> <?php echo $heading_title; ?></h1>
	</div>
    <div class="content">
  		 <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form" style="width:355px;margin:75px auto;">
  		 	<fieldset style="font-size:22px; line-height:1.5em;"> 
  		 		<input type="radio" id="pp" name="parser" value="1"><label for="pp"> Синхронизировать товары</label><br /> 
  		 		<input type="radio" id="pl" name="parser" value="2"><label for="pl"> Синхронизировать остатки</label><br /> 
  		 	</fieldset>
			<button type="submit" style="display:block; font-size:22px; width:100px; border: 1px solid rgb(102, 153, 255); background: rgb(102, 153, 255) none repeat scroll 0% 0%; color: #FFF; width: 100px; height: 30px; border-radius: 5px; cursor: pointer; margin: 35px auto;">Старт</button>
		</form>
    </div>
  </div>
</div>
<?php echo $footer; ?>
