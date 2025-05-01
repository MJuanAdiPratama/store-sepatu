<?php
    error_reporting(0);
    include 'db.php';
    $contact = mysqli_query($conn, "SELECT admin_telp, admin_email, admin_address FROM tb_admin WHERE admin_id = 1");
    $a = mysqli_fetch_object($contact)
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
            <h1><a href="index.php">QxuanStore</a></h1>
            <ul>
                <li><a href="product.php">Product</a></li>
                <li><a href="login.php">logout</a></li>
            </ul>
            </div>
        </header>

        <!-- Search Bar -->
        <div class="search">
            <div class="container">
                <form action="product.php">
                    <input type="text" name="search" placeholder="Search Product" value="<?php echo $_GET['search'] ?>">
                    <input type="hidden" name="kat" value="<?php echo $_GET['kat'] ?>">
                    <input type="submit" name="Search" value="Search">
                </form>
            </div>
        </div>


        <!-- product -->
        <div class="section">
             <div class="container">
                <h3>Product</h3>
                <div class="box">
                <?php 
                if($_GET['search'] != '' || $_GET['kat'] != ''){
                    $where = "AND nama_produk LIKE '%".$_GET['search']."%' AND id_category LIKE '%".$_GET['kat']."%' And id_category LIKE '%".$_GET['kat']."%'" ;
                }

                    $product = mysqli_query($conn, "SELECT * FROM tb_produk WHERE status_produk = 1 $where ORDER BY id_produk DESC");
                    if(mysqli_num_rows($product) > 0){
                        while($p = mysqli_fetch_array($product)){
                ?>
                <a href="detail-product.php?id=<?php echo $p ['id_produk'] ?>">
                <div class="col-4">
                    <img src="product/<?php echo $p['image_produk'] ?>">
                    <p class="nama"><?php echo substr($p['nama_produk'], 0, 30)  ?></p>
                    <p class="harga">Rp. <?php echo number_format($p['harga_produk']) ?></p>
                </div>
                </a>
            <?php }}else{ ?>
                <p>Product Not Found</p>
            <?php } ?>
             </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="container">
                <h4>Address</h4>
                <p><?php echo $a ->admin_address ?></p>

                <h4>Email</h4>
                <p><?php echo $a ->admin_email ?></p>

                <h4>Contact</h4>
                <p><?php echo $a ->admin_telp ?></p>
                <small>Copyright &copy; 2023 - QxuanStore.</small>
            </div>
        </div>
    </body>
</html>
