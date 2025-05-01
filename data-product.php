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
                <h3>Data Produk</h3>
                <div class="box">
                    <p><a href="add-product.php">Add Data</a></p>
                    <table border="1" cellspacing="0" class="table">
                        <thead>
                            <tr>
                                <th width="60px">No</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Deskripsi</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th width="150px">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $no = 1;
                                $produk = mysqli_query($conn, "SELECT * FROM tb_category LEFT JOIN tb_produk USING (id_category) ORDER BY id_produk DESC");
                                if(mysqli_num_rows($produk) > 0){

                                
                                while($row = mysqli_fetch_array($produk)){
                            ?>
                            <tr>
                                <td align="center"><?php echo $no++ ?></td>
                                <td><?php echo $row['nama_produk'] ?></td>
                                <td>Rp.<?php echo number_format ($row['harga_produk'])  ?></td>
                                <td><?php echo $row['deskripsi_produk'] ?></td>
                                <td> <a href="product/<?php echo $row['image_produk'] ?>" target="_blank"><img src ="product/<?php echo $row['image_produk'] ?>"width="50px"></a></td>
                                <td><?php echo ($row['status_produk'] == 0)? 'Sold out' : 'In Stock';?></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $row ['id_produk'] ?>">Edit</a> || <a href="delete.php?idp=<?php echo $row ['id_produk']?>" onclick="return confirm('Confirm To Remove ?')">Remove</a>
                                </td>
                            </tr>
                            <?php } } else{ ?>
                                    <tr>
                                        <td colspan="7">No Data</td>
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
