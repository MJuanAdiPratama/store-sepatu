<?php

    include 'db.php';

    if(isset($_GET['idk'])){
        $delete = mysqli_query($conn, "DELETE FROM tb_category WHERE id_category = '".$_GET['idk']."'");
        echo '<script>window.location="data-category.php"</script>';
    }


    if(isset($_GET['idp'])){
        $product = mysqli_query($conn, "SELECT image_produk FROM tb_produk WHERE id_produk = '".$_GET['idp']."'");
        $p = mysqli_fetch_object($product);

        unlink('./produk/'.$p->image_produk);
        $delete = mysqli_query($conn, "DELETE FROM tb_produk WHERE id_produk = '".$_GET['idp']."'");
        echo '<script>window.location="data-product.php"</script>';
    }
    ?>
