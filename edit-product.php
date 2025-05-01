<?php
session_start();
include 'db.php';
    if($_SESSION['status_login'] != true){
        echo '<script>window.location="login.php"</script>';
    }

    $product = mysqli_query($conn, "SELECT * FROM tb_produk WHERE id_produk = '".$_GET['id']."'");
    if(mysqli_num_rows($product) == 0){
        echo '<script>window.location="data-product.php"</script>'; 
    }
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
        <script src="https://cdn.ckeditor.com/4.14.0/standard/ckeditor.js"></script>
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
                <h3>Edit</h3>
                <div class="box">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <select class="input-control" name="category" required> 
                            <option value="">--Add--</option>
                            <?php
                                $kategori = mysqli_query($conn, "SELECT * FROM tb_category ORDER BY id_category DESC");
                                while($r = mysqli_fetch_array($kategori)){
                                
                            ?>
                            <option value="<?php echo $r['id_category'] ?>" <?php echo ($r['id_category'] == $p->id_category)? '
                            selected':''; ?>><?php echo $r['nama_category'] ?></option>
                            <?php } ?>
                        </select>
                        <input type="text" name="name" class="input-control" placeholder="Product Name" value="<?php echo $p->nama_produk?>" required>
                        <input type="text" name="harga" class="input-control" placeholder="Price" value="<?php echo $p->harga_produk?>" required>
                        
                        <img src="product/<?php echo $p->image_produk ?>" width="150px">
                        <input type="hidden" name="foto" value="<?php echo $p->image_produk ?>">
                        <input type="file" name="gambar" class="input-control">
                        <textarea class="input-control" name="deskripsi" placeholder="Description"> <?php echo $p->deskripsi_produk ?></textarea>
                        <select class="input-control" name="status">
                            <option value="">--Add--</option>
                            <option value="1" <?php echo($p->status_produk == 1)? 'selected': ''; ?>>Active</option>
                            <option value="0" <?php echo($p->status_produk == 0)? 'selected': ''; ?>>Not Active</option>
                        </select>
                        <input type="submit" name="submit" value="Submit" class="btn">
                    </form>
                    <?php
                    if(isset($_POST['submit'])){  

                        //data inputan dari form
                        $kategori    = $_POST['category'];
                        $name        = $_POST['name'];
                        $harga       = $_POST['harga'];
                        $deskripsi   = $_POST['deskripsi'];
                        $status      = $_POST['status'];
                        $foto        = $_POST['foto'];

                        //data gambar yang baru
                        $filename = $_FILES['gambar']['name'];
                        $tmp_name = $_FILES['gambar']['tmp_name'];

                        //jika admin ganti gambar
                        if($filename != ''){
                            $type1 = explode('.', $filename);
                            $type2 = $type1[1];
    
                            $newname = 'produk'.time().'.'.$type2;

                            //file format yang di izinkan
                        $type_diizinkan = array('jpg','jpeg','png','gif');

                            //validasi format file
                        if(!in_array($type2, $type_diizinkan)){
                            //jika format file tidak ada di dalam tipe di izinkan
                            echo '<script>alert("File Format Not Allowed")</script> ';

                       }else{
                            unlink('./product/'.$foto);
                            move_uploaded_file($tmp_name, './product/'.$filename);
                            $namagambar = $newname;
                       }
                       
                       }else{
                        //jika admin tidak ganti gambar
                        $namagambar = $foto;

                       }

                       //query update data produk
                        $update = mysqli_query($conn, "UPDATE tb_produk SET    
                                                id_category = '".$kategori."',
                                                nama_produk = '".$name."',
                                                harga_produk = '".$harga."',
                                                deskripsi_produk = '".$deskripsi."',
                                                image_produk = '".$namagambar."',
                                                status_produk = '".$status."'
                                                WHERE id_produk = '".$p->id_produk."'  ");
                        if($update){
                            echo '<script>alert("Change Data Succes")</script>';                
                            echo '<script>window.location="data-product.php"</script>';
                        }else{
                            echo 'gagal'.mysqli_error($conn);
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
        <script>
            CKEDITOR.replace('deskripsi');
        </script>
    </body>
</html> 
