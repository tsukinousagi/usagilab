<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>status</title>

    <!-- Bootstrap -->
    <link href="<?php echo(base_url()); ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo(base_url()); ?>css/font-awesome.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
<div class="container">

  <h1>status for <?php echo($group); ?></h1>
<ul>
<li>Page generated at: <?php echo(date('r')); ?></li>
<li>Your IP address: <?php echo(getenv('REMOTE_ADDR')."(".gethostbyaddr(getenv("REMOTE_ADDR")).")"); ?></li>
</ul>
<table class="table">
<?php if (!$mobile_flag) { ?>
<thead>
<tr>
<td>Status</td>
<td>Device</td>
<td>Note</td>
<td>Last Seen</td>
</tr>
</thead>
<?php } ?>
<?php foreach($status as $k => $v) { ?>
<tbody>
<tr>
<td><?php if ($v['offline'] == 'N') { ?><i class="fa fa-check"></i><?php } else { ?><i class="fa fa-exclamation-triangle"></i><?php } ?>
<td><?php echo($v['device']); ?>
<td><?php echo($v['note']); ?>
<td><?php echo($v['updated']); ?>
</tr>
<?php } ?>
</tbody>
</table>
<a href="<?php echo(base_url()); ?>files/alive.7z">Download update script</a>
</div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="<?php echo(base_url()); ?>js/bootstrap.min.js"></script>
  </body>
</html>
