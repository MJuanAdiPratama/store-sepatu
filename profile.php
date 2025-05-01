<?php
session_start();
include 'db.php';
if ($_SESSION['status_login'] != true) {
    echo '<script>window.location="login.php"</script>';
}

$query = mysqli_query($conn, "SELECT * FROM tb_admin WHERE admin_id = '" . $_SESSION['admin_id'] . "' ");
$d = mysqli_fetch_object($query);

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, intial-scale=1">
    <title>QxuanStore</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Header -->
    <header>
        <div class="container">
            <h1><a href="dashboard.php">QxuanStore</a></h1>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="data-category.php">Category</a></li>
                <li><a href="data-product.php">Product</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
        </div>
    </header>

    <!-- Content -->
    <div class="section">
        <div class="container">
            <h3>Profile</h3>
            <div class="box">
                <form action="" method="POST">
                    <input type="text" name="nama" class="input-control" value="<?php echo $d->username ?>" required>
                    <input type="text" name="user" class="input-control" value="<?php echo $d->admin_name ?>" required>
                    <input type="text" name="hp" class="input-control" value="<?php echo $d->admin_telp ?>" required>
                    <input type="email" name="email" class="input-control" value="<?php echo $d->admin_email ?>" required>
                    <input type="text" name="address" class="input-control" value="<?php echo $d->admin_address ?>" required>
                    <input type="submit" name="submit" value="Change Profile" class="btn">
                </form>
                <?php
                if (isset($_POST['submit'])) {

                    $nama       = ucwords($_POST['nama']);
                    $user       = $_POST['user'];
                    $hp         = $_POST['hp'];
                    $email      = $_POST['email'];
                    $address    = ucwords($_POST['address']);

                    $update = mysqli_query($conn, "UPDATE tb_admin SET 
                                            admin_name = '" . $nama . "',
                                            username = '" . $user . "',
                                            admin_telp = '" . $hp . "',
                                            admin_email = '" . $email . "',
                                            admin_address = '" . $address . "'
                                            WHERE admin_id = '" . $d->admin_id . "'");
                    if ($update) {
                        echo '<script>alert("Data Change Success")</script>';
                        echo '<script>window.location="profile.php"</script>';
                    } else {
                        echo 'Failed' . mysqli_error($conn);
                    }
                }
                ?>
            </div>

            <h3>Change Password</h3>
            <div class="box">
                <form action="" method="POST">
                    <input type="password" name="pass1" placeholder="New Password" class="input-control" required>
                    <input type="password" name="pass2" placeholder="Password Confirmation" class="input-control" required>
                    <input type="submit" name="change_password" value="Change Password" class="btn">
                </form>
                <?php
                if (isset($_POST['change_password'])) {


                    $pass1       = $_POST['pass1'];
                    $pass2       = $_POST['pass2'];
                    if ($pass2 != $pass1) {
                        echo '<script>alert("New Password Confirmation Unfit")</script>';
                    } else {

                        $u_pass = mysqli_query($conn, "UPDATE tb_admin SET
                                    password       = '" . ($pass1) . "'
                                    WHERE password = '". ($d->password). "'");
                        if ($u_pass) {
                            echo '<script>alert("Data Change Success")</script>';
                            echo '<script>window.location="profile.php"</script>';
                        } else {
                            echo 'Failed' . mysqli_error($conn);
                        }
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <footer>
        <div class="container">
            <small>Copyright &copy; 2023 - QxuanStore.</small>
        </div>
    </footer>
</body>

</html>