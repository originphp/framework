<?php
/**
 * More color scheme ideas from:.
 *
 * @link https://flatuicolors.com/palette/defo.
 */
?>

<style>
body {
  margin-bottom:60px;
}
.debugbar {

  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  z-index:1000;
  background-color:#3498db;
}

#debugbar-console,#debugbar-request,#debugbar-sql,#debugbar-session ,#debug-hide-tab{
  display:none;
}
.debugbar-header{
  min-height: 52px;
}
.debugbar-body {
  clear:both;
  background:white;
  font-family: SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono","Courier New",monospace;
font-size: 1em;
height:90%;

}
.debugbar-body dd,.debugbar-body dt {
  margin-bottom:0px; /** bootstrap bug fix **/
}
.debugbar-body dd,.debugbar-body dt {
  padding:20px;
}
.debugbar-body dd, .debugbar-body dt,.debugar-body td {
  border-bottom: 1px solid #bdc3c7;
}
.debugbar-body .table td ,.debugbar-body .table th{
  border:none;
  border-bottom: 1px solid #bdc3c7;
}
.debugbar-body div {
  height:100%;
  overflow-y : auto;
  overflow-x : auto;
  display:none;
}

.debugbar-header a {
  padding: 14px 16px;
  transition: 0.3s;
  float: left;
   border: none;
   outline: none;
   cursor: pointer;
   color:white;
   font-weight:bold;
   text-decoration: none;

}
.debugbar-memory {
  padding: 14px 16px;
  color:#fff;
}
.debugbar-header a:hover {
  text-decoration:none;
}
.debugbar-header a.active {
  background-color:#f1c40f;
  color:#192a56;
}
</style>

<div class="debugbar">
  <div class="debugbar-header">
    <a id="debug-console-tab" class="debugbar-tab" href="#">Console</a>
    <a id="debug-request-tab" class="debugbar-tab" href="#">Request</a>
    <a id="debug-session-tab" class="debugbar-tab" href="#">Session</a>
    <a id="debug-sql-tab" class="debugbar-tab" href="#">SQL</a>
    <div class="debugbar-memory float-right">
      <span> Memory: <?= $debug_vars['memory'] ?> </span>
    </div>
    <a id="debug-hide-tab" class="debugbar-tab float-right" href="#">&times; hide</a>
  </div>
  <div class="debugbar-body">
    <div id='debugbar-console'>
      <dl class="row">
        <?php
          foreach ($debug_vars as $key => $value) {
              ?>
              <dt class="col-sm-1"><?= $key; ?></dt>
              <dd class="col-sm-11"><pre><?php print_r($value); ?></pre></dd>
            <?php
          }
        ?>
        </dl>
      </div>
    <div id="debugbar-request">
          <dl class="row">
      <?php
        foreach ($debug_request as $key => $value) {
            ?>
            <dt class="col-sm-1"><?= $key; ?></dt>
            <dd class="col-sm-11"><pre><?php print_r($value); ?></pre></dd>
          <?php
        }
      ?>
      </dl>
    </div>
    <div id="debugbar-session">
          <dl class="row">
      <?php
        foreach ($debug_session as $key => $value) {
            ?>
            <dt class="col-sm-1"><?= $key; ?></dt>
            <dd class="col-sm-11"><pre><?php print_r($value); ?></pre></dd>
          <?php
        }
      ?>
      </dl>
    </div>
    <div id="debugbar-sql">
      <table class="table">
        <thead>
          <tr>
            <th scope="col">Query</th>
              <th scope="col">Error</th>
                <th scope="col">Affected</th>
                  <th scope="col">Time</th>
          </tr>
        </thead>
        <tbody>
          <?php
            foreach ($debug_sql as $query) {
                ?>
                <tr>
                  <td><?= $query['query']; ?></td>
                  <td><?= $query['error']; ?></td>
                  <td><?= $query['affected']; ?></td>
                  <td><?= $query['time']; ?></td>
                </tr>
              <?php
            }
          ?>
        </tbody>
      </table>
    </div>
  </div>

<script>

  $( document ).ready(function() {
    debugbarTabSwitcher();

  });
  function debugbarTabSwitcher(){
    $( "#debug-hide-tab" ).click(function() {
        $(".debugbar-body div").hide();
        $(".debugbar-memory").show();
        $(this).hide();
          $(".debugbar").height('52px');
    });
    $( "#debug-console-tab" ).click(function() {
      debugbarTabClick();
      $( "#debugbar-console" ).show();
      $(this).addClass('active');

    });
    $( "#debug-request-tab" ).click(function() {
        debugbarTabClick();
      $( "#debugbar-request" ).show();
      $(this).addClass('active');

    });

    $( "#debug-session-tab" ).click(function() {
      debugbarTabClick();
      $( "#debugbar-session" ).show();
      $(this).addClass('active');

    });
    $( "#debug-sql-tab" ).click(function() {
      debugbarTabClick();
      $( "#debugbar-sql" ).show();
      $(this).addClass('active');
    });
  }
  function debugbarTabClick(){
      $(".debugbar").height('100%');
    $("#debug-hide-tab").show();
    $(".debugbar-memory").hide();
    $(".debugbar-body div").hide();
    $(".debugbar-tab").removeClass('active');
    
  }
</script>
