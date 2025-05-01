<?php
session_start();
    include'db.php';
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
                <li><a href="data-Product.php">Product</a></li>
                <li><a href="logout.php">Log Out</a></li>
            </ul>
            </div>
        </header>

        <!-- Content -->
        <div class="section">
            <div class="container">
                <h3>Kategori</h3>
                <div class="box">
                    <p><a href="add-category.php">Tambah Kategori</a></p>
                    <table border="1" cellspacing="0" class="table">
                        <thead>
                            <tr>
                                <th width="60px">No</th>
                                <th>Kategori</th>
                                <th width="140px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                                $category = mysqli_query($conn, "SELECT * FROM tb_category ORDER BY id_category DESC");
                                if(mysqli_num_rows($category) > 0){
                                while($row = mysqli_fetch_array($category)){
                            ?>
                            <tr>
                                <td align="center"><?php echo $no++ ?></td>
                                <td><?php echo $row['nama_category'] ?></td>
                                <td>
                                    <a href="edit-category.php?id=<?php echo $row ['id_category'] ?>">Edit</a> || <a href="delete.php?idk=<?php echo $row ['id_category']?>" onclick="return confirm('Confirm To Remove ?')">Remove</a>
                                </td>
                            </tr>
                            <?php }}else{ ?>
                                <tr>
                                    <td colspan="3">No Data</td>
                                </tr>
                            <?php } ?> 
                        </tbody>
                    </table>
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
