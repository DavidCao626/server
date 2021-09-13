<?php
$url = urldecode($_GET['url']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        #back {
            position: fixed;
            width: 80px;
            height: 44px;
            z-index: 999;
            opacity: 0;
        }

        #iframe {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            border: none;
        }
    </style>
</head>
<body>
<div id="back" onclick="back()"></div>
<iframe src="<?php echo $url ?>" id="iframe"></iframe>
<script>
    function back() {
        window.history.back();
    }
</script>
</body>
</html>