<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, intial-scale=1">
    <title>Login | Project Uas</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet">
</head>

<body id="bg-login">
    <div class="box-login">
        <h2>login</h2>
        <form action="" method="POST">
            <input type="text" name="user" placeholder="Username" class="input-control">
            <input type="password" name="pass" placeholder="Password" class="input-control">
            <a class="lupa-password" href="">lupa password</a>
            <div class="btn-group">
                <input type="submit" name="submit" value="Login" class="btn">
                <input type="submit" name="submit" value="Register" class="btn">
            </div>
        </form>
        <?php
        if (isset($_POST['submit'])) {
            session_start();
            include 'db.php';

            $pass = mysqli_escape_string($conn, $_POST['pass']);
            $user = mysqli_escape_string($conn, $_POST['user']);

            $cek =  mysqli_query($conn, "SELECT * FROM tb_admin WHERE username = '" . $user . "' AND password = '" . $pass . "'");
            if (mysqli_num_rows($cek) > 0) {
                $d = mysqli_fetch_object($cek);
                $_SESSION['status_login'] = true;
                $_SESSION['a_global'] = $d;
                $_SESSION['admin_id'] = $d->admin_id;
                echo '<script>window.location="index.php"</script>';
            } else {
                echo '<script>alert("Username atau Password Anda Salah")</script>';
            }
        }
        ?>
    </div>
</body>
</html>