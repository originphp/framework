<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

    <title>Origin Framework</title>
  </head>

  <?php
    function warning($message)
    {
        bootstrapAlert('warning', $message);
    }

    function success($message)
    {
        bootstrapAlert('success', $message);
    }
    function error($message)
    {
        bootstrapAlert('danger', $message);
    }
    function info($message)
    {
        bootstrapAlert('info', $message);
    }
    function bootstrapAlert($type, $message)
    {
        echo "<div class=\"alert alert-{$type}\" role=\"alert\">{$message}</div>";
    }
  ?>

  <body>
    <div class="container">

      <h1>OriginPHP Framework</h1>
      <p>This is the test page to see that all is working ok. You can remove or change this by editing the <strong>config/Routes.php</strong>. This is the route that is used to show this page:</p>
      <pre>
          Router::add('/', ['controller' => 'pages', 'action' => 'display', 'home']);
      </pre>
      <h2>Status</h2>
      <?php
        $tmp = TMP;
        if (is_writeable(TMP)) {
            success("{$tmp} is writeable.");
        } else {
            warning("{$tmp} folder is NOT writeable. Run <em>chmod 0755 {$tmp}</em>");
        }
      ?>
      <?php

      $logs = LOGS;
      if (is_writeable(LOGS)) {
          success("{$logs} is writeable.");
      } else {
          warning("{$logs} is NOT writeable. Run <em>chmod 0755 {$logs}</em>");
      }
      ?>

      <?php
        $databaseConfig = CONFIG.DS.'database.php';

        if (file_exists($databaseConfig)) {
            success('config/database.php found.');
        } else {
            warning('config/database.php not found.');
        }
      ?>

      <?php
        $databaseConfig = CONFIG.DS.'database.php';
        use Origin\Model\ConnectionManager;

        if (file_exists($databaseConfig)) {
            try {
                $db = ConnectionManager::get('default');
                success('Connected to database.');
            } catch (\Exception $e) {
                warning('Unable to connect to the database. Please check the configuration and that the database exists.');
            }
        }
      ?>

      <?php
        if (file_exists(CONFIG.DS.'server.php')) {
            success('config/server.php found');
        } else {
            info('config/server.php not found. You can optionally have different configurations for each deployment, e.g Development, Staging, Production.');
        }
      ?>

    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>
