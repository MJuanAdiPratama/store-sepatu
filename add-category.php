<?php
session_start();
include 'db.php';
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
        <link rel="stylesheet"  type="text/css" href="css/style.css">
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
                <h3>Tambah Kategori</h3>
                <div class="box">
                    <form action="" method="POST">
                        <input type="text" name="name" placeholder="Category Name" class="input-control" required>
                        <input type="submit" name="submit" value="Submit" class="btn">
                    </form>
                    <?php
                    if(isset($_POST['submit'])){
                        $nama = ucwords($_POST['name']);

                        $insert = mysqli_query($conn, "INSERT INTO tb_category VALUES (
                                                null,
                                                '".$nama."') ");
                        if($insert){
                            echo '<script>alert("Add Data Success)</script>';
                            echo '<script>window.location="data-category.php"</script>';
                        }else{
                            echo 'gagal' .mysqli_error($conn);
                        }
                    }
                    ?>
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
