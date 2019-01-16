<?php
/**
 * OriginPHP Framework
 * Copyright 2018 - 2019 Jamiel Sharief.
 *
 * Licensed under The MIT License
 * The above copyright notice and this permission notice shall be included in all copies or substantial
 * portions of the Software.
 *
 * @copyright   Copyright (c) Jamiel Sharief
 * @link        https://www.originphp.com
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/debug.css">
    <title><?php echo $debug['message']; ?></title>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row debug-header">
        <div class="exception">
            <small class="exception-namespace"><?php echo $debug['namespace']; ?></small>
            <p class="exception-class"><?php echo $debug['class']; ?> <span class="exception-code"><?php echo $debug['code']; ?></span></p>
            <p class="exception-message"><?php echo $debug['message']; ?></p>
          </div>
      </div>
      <div class="row">
        <div class="col-4 stack-frames">
          <div class="list-group" id="myList" role="tablist">
            <?php
              $attr = ' active';
              foreach ($debug['stackFrames'] as $index => $stack) {
                  $file = str_replace(ROOT, '', $stack['file']); ?>
                  <a class="list-group-item list-group-item-action<?php echo $attr; ?>" data-toggle="list" href="#frame-<?php echo $index; ?>" role="tab">
                    <?php echo $stack['class']; ?>
                    <span class="function"><strong><?php echo $stack['function']; ?></strong></span>
                    <p><?php echo $file; ?> <span class="badge badge-warning"><?php echo $stack['line']; ?></span></p>
                  </a>

                  <?php
                    $attr = '';
              }
            ?>
          </div>
        </div>
        <div class="col-8">
          <div class="tab-content">
            <?php
              $attr = ' show active';
              foreach ($debug['stackFrames'] as $index => $stack) {
                  if (empty($stack['file'])) {
                      continue;
                  }

                  $html = highlight_file($stack['file'], true);
                  $lines = explode('<br />', $html);

                  $preview = '';
                  $i = 1;

                  foreach ($lines as $line) {
                      if (empty($line)) {
                          $line = '&nbsp;';
                      }

                      $class = 'normal';
                      if ($i === $stack['line']) {
                          $class = 'highlight';
                      }
                      $preview .=
                      "<div class=\"{$class}\">
                        <div class=\"preview-line\">{$i}</div>
                        <div class=\"preview-code\">{$line}</div>
                      </div>";

                      ++$i;
                  }

                  echo
                  "<div class=\"tab-pane fade{$attr}\" id=\"frame-{$index}\" role=\"tabpanel\">
                    <div class=\"preview\">".$preview.'</div>
                  </div>';
                  $attr = '';
              }
            ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
  </body>
</html>
