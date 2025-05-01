<?php
include("db.php");
if (isset($_POST['login'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

// $data = mysqli_query($conn,"SELECT * FROM tb_user WHERE username = '$user' and password = '$pass'");
// $d=mysqli_fetch_assoc($data);
// $cek-mysqli_num_rows($data);

$cek = mysqli_num_rows(mysqli_query($conn,"select * from tb_admin where username = '$user' and password = '$pass'"));
$data = mysqli_fetch_array(mysqli_query($conn, "select * from tb_admin where username = '$user' and password = '$pass'"));
}

if ($cek == 1)
    {
        session_start();
        $_SESSION['user'] = $data['username'];
        if ($data['level'] == 1)
        {
            echo "<script>alert('login berhasil');window.location='index.php'</script>";
        }
        else
            echo "<script>alert('login berhasil');window.location='index.php'</script>";
        }
        else
        {
        echo "<script>alert('login anda salah');window.location='login.php'</script>";
        echo $cek;
        }

?>