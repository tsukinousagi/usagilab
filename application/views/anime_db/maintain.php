<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>anime db</title>

    <!-- Bootstrap -->
    <link href="<?php echo(base_url()); ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo(base_url()); ?>css/font-awesome.min.css" rel="stylesheet">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  <style>
body {
  padding-top: 50px;
}
.padme {
  padding: 40px 15px;
}
  </style>
  </head>
  <body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">日本動畫資料庫</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="<?php echo(site_url()); ?>/anime_db/index">標題</a></li>
            <li class="active"><a href="#">資料維護</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>

    <div class="container">
<div class="padme">

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">新增或更新資料</h3>
  </div>
  <div class="panel-body">
<button id="btn_run" type="button" class="btn btn-default">
<span class="glyphicon glyphicon-play" aria-hidden="true"></span> 執行
</button>
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">輸出結果</h3>
  </div>
  <div class="panel-body">
<textarea id="output" class="form-control" rows="8">

</textarea>
  </div>
</div>
</div>
    </div><!-- /.container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="<?php echo(base_url()); ?>js/bootstrap.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    $('#btn_run').click(function() {
        $(this).prop('disabled', true);
        $.ajax({
            url: '<?php echo(site_url()); ?>/anime_db/fetch_title',
            type: 'POST',
            cache: false,
            data: {action: 'flush_cache'},
            xhrFields: {
                onprogress: function(e) {
                    $("#output").html(e.target.responseText)
                    var psconsole = $('#output');
                    if (psconsole.length) {
                       psconsole.scrollTop(psconsole[0].scrollHeight - psconsole.height());
                    }
                }
            },
            success: function(text) {
                $('#btn_run').prop('disabled', false);
            },
        });
    });
});
</script>

  </body>
</html>
