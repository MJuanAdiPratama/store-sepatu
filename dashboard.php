<?php
session_start();
    if($_SESSION['status_login'] != true){
        echo '<script>window.location="login.php"</script>';
    }
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
                <h3>Dashboard</h3>
                    <div class="box">
                    <h4>Welcome <?php echo $_SESSION['a_global']->username ?> Di QxuanStore</h4>
                </div>
            </div>
        </div>
        <!-- Footer -->
        <footer>
            <div class="container">
                <small>Copyright &copy; 2023 - QxuanStore</small>
            </div>
        </footer>
    </body>
</html>
