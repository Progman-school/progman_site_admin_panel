<?php require_once "admin_room_controller.php"; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="styles/admin.css">
    <title>Admin room</title>
</head>
<body>
<?php if (!isset($_SESSION['authorization'])) { ?>
    <form class="authorization" method="post">
        <h3>Entre to the admin panel</h3>
        <div>
            <input type="text" name="login" placeholder="login">
        </div>
        <div>
            <input type="password" name="password" placeholder="password">
        </div>
        <button type="submit">LOGIN</button>
    </form>
<?php exit();?>
<?php }?>
    <form class="reset" method="post">
        <input type="hidden" name="reset" value="1">
        How are you doing, <b><?= $_SESSION['authorization']['login']?></b>!
        <button type="submit">logout</button>
    </form>
    <form class="navigation">
        <input type="submit" class="<?= @$_GET['navigation'] == "certificates" ? "pressed" : "" ?>" name="navigation" value="certificates">
        <input type="submit" class="<?= @$_GET['navigation'] == "tags" ? "pressed" : "" ?>" name="navigation" value="tags">
        <input type="submit" class="<?= @$_GET['navigation'] == "courses" ? "pressed" : "" ?>" name="navigation" value="courses">
    </form>
<?php
    if (@$_GET['navigation'] == "tags") {
        include "sections/tags.php";
    } elseif (@$_GET['navigation'] == "certificates") {
        include "sections/certificates.php";
    } elseif (@$_GET['navigation'] == "courses") {
        include "sections/courses.php";
    } else {
        echo "<div class='welcome_block'>
                <h2>WELCOME TO THE ADMIN ROOM</h2>
                <strong>Choose the section of settings above.</strong>
            </div>";
    }
?>
    <script src="js/admin_main.js"></script>
</body>
</html>