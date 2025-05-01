<?php
    error_reporting(0);
    include 'db.php';
    $contact = mysqli_query($conn, "SELECT admin_telp, admin_email, admin_address FROM tb_admin WHERE admin_id = 1");
    $a = mysqli_fetch_object($contact);

    $product = mysqli_query($conn, "SELECT * FROM tb_produk WHERE id_produk ='".$_GET['id']."'");
    $p = mysqli_fetch_object($product)
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
                <li><a href="index.php">Product</a></li>
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

        <!-- Product Detail -->
        <div class="section">
            <div class="container">
                Detail Product
                <div class="box">
                    <div class="col-2">
                        <img src="product/<?php echo $p ->image_produk ?>" width="100%">
                    </div>

                    <div class="col-2">
                        <h3><?php echo $p-> product_name ?></h3>
                        <h4>RP. <?php echo number_format($p-> harga_produk)  ?></h4>
                        <p>Description :<br>
                        <?php echo$p-> deskripsi_produk ?> 
                    </p>
                    <p><a href="https://api.whatsapp.com/send?phone=<?php echo $a ->admin_telp ?>
                    &text=Hai, saya tertarik dengan produk sepatu anda" target="blank">Hubungi Via WhatsApps <img src="img/wa.png" width="50px"></a>
                    </p>
                    </div>

                </div>
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
                <small>Copyright &copy; 2023 - QxuanStore</small>
            </div>
        </div>
    </body>
</html>
