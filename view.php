<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "poster_db";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the row ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT * FROM posterss WHERE id=$id");

if($result->num_rows == 0){
    echo "Record not found!";
    exit;
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="gu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Poster</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Roboto', Arial, sans-serif;
    background: linear-gradient(to right, #e0f7fa, #fce4ec);
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
    box-sizing: border-box;
}

.container {
    max-width: 700px;
    width: 100%;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    padding: 25px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.container:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.15);
}

.header {
    display: flex;
    align-items: center;
    margin-bottom: 25px;
    justify-content: center; /* Centers content horizontally */
}

.header img {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #ff4081;
    margin-right: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}

.header img:hover {
    transform: scale(1.1);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.header h2 {
    font-size: 30px;
    color: #ff4081;
    margin: 0;
    position: relative;
}

.header h2::after {
    content: '';
    display: block;
    width: 50px;
    height: 3px;
    background: #ff4081;
    margin-top: 6px;
    border-radius: 2px;
}

.details p {
    margin: 10px 0;
    font-size: 17px;
    color: #555;
    line-height: 1.4;
}

.details p strong {
    color: #ff4081;
    width: 90px;
    display: inline-block;
}

a {
    display: inline-block;
    margin-top: 25px;
    padding: 12px 20px;
    background: #ff4081;
    color: #fff;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    transition: background 0.3s, transform 0.3s;
}

a:hover {
    background: #e91e63;
    transform: translateY(-3px);
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .header img {
        margin-bottom: 15px;
        margin-right: 0;
    }

    .details p strong {
        width: auto;
        display: inline;
    }
}
</style>


<div class="container">
  <div class="header">
    <?php if($row['ફોટો']){ ?>
      <img src="<?php echo $row['ફોટો']; ?>" alt="User Photo">
    <?php } else { ?>
      <img src="default-avatar.png" alt="No Image">
    <?php } ?>
    <h2><?php echo $row['નામ']; ?></h2>
  </div>
  <div class="details">
    <p><strong>ગામ:</strong> <?php echo $row['ગામ']; ?></p>
    <p><strong>તાલુકો:</strong> <?php echo $row['તાલુકો']; ?></p>
    <p><strong>જિલ્લો:</strong> <?php echo $row['જિલ્લો']; ?></p>
    <p><strong>મોબાઈલ:</strong> <?php echo $row['મોબાઈલ_નંબર']; ?></p>
    <p><strong>વ્યવસાય:</strong> <?php echo $row['વ્યવસાય']; ?></p>
    <p><strong>તારીખ:</strong> <?php echo $row['તારીખ']; ?></p>
    <p><strong>Created At:</strong> <?php echo $row['created_at']; ?></p>
  </div>
  <a href="data.php">⬅ Back</a>
</div>


</body>
</html>
