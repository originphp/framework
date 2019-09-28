<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

  <title><?= $errorCode ?> - <?= $errorMessage ?></title>
  <style>
    html,
    body {
      height: 100%
    }

    body {
      margin: 0;
      padding: 20px;
      background-color: #FFC900;

    }

    .block span,
    .block p {
      font-weight: 700;

      color: #fff;
    }
  </style>
</head>

<body>
  <div class="h-100 row align-items-center">
    <div class="block col-md-12 text-center">
      <span class="display-1 d-block"><?= $errorCode ?></span>
      <div class="mb-4 lead">
        <p><?= $errorMessage ?></p>
      </div>
    </div>
  </div>
</body>

</html>
