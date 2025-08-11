<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Помилка!</title>
  <style>
    body {
      margin: 0;
    }
    .page-body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      text-align: center;
      box-sizing: border-box;
      padding: 20px 15px 100px;
    }
    img {
      max-width: 100%;
      height: auto;
    }
    .page-logo {
      margin-bottom: 30px;
    }
    .page-title {
      font-size: 24px;
      max-width: 460px;
      margin: 0 auto;
    }
    .inner {
      max-width: 100%;
    }
    #error {
      text-align: left;
      margin-top: 30px;
      display: none;
      white-space: normal;
    }
    #error pre {
      white-space: normal;
    }
  </style>
</head>
<body>
  <div onclick="document.getElementById('error').style.display = 'block';" style="width: 20px; height: 20px; position:fixed;right:0px; top:0px;"></div>
  <div class="page-body">
    <div class="inner">
      <div class="page-logo">
        <?php
          if (file_exists('themes/personal/logo.svg')) {
            $image = '/themes/personal/logo.svg';
          } else if (file_exists('themes/personal/logo.png')) {
            $image = '/themes/personal/logo.png';
          } else if (file_exists('themes/personal/logo.jpg')) {
            $image = '/themes/personal/logo.jpg';
          } else {
            $image = '';
          }
          if ($image) {
            print '<div class="page-logo"><img src="' . $image . '" alt=""></div>';
          }
        ?>
      </div>
      <div class="page-title">На сайті відбулася несподівана помилка. Будь ласка, спробуйте ще раз пізніше. </div>
      <div class="page-title">На сайте произошла неожиданная ошибка. Пожалуйста, попробуйте еще раз позже. </div>
      <div class="page-title">There was an unexpected error on the site. Please try again later. </div>
      <div id="error"><?php print PHP_EOL.$message.PHP_EOL;?></div>
    </div>
  </div>
</body>
</html>

