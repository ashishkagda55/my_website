<?php
// ✅ Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "poster_db";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// ✅ Handle Delete request
if(isset($_GET['delete_id'])){
    $delete_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM posterss WHERE id=$delete_id");
    echo "deleted"; // for JS fetch response
    exit;
}

// ✅ Pagination Settings
$limit = 100; // per page 100 records only
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// ✅ Fetch all data with pagination
$sql    = "SELECT * FROM posterss ORDER BY id DESC LIMIT $start, $limit";
$result = $conn->query($sql);

// ✅ Total records for pagination count
$totalResult = $conn->query("SELECT COUNT(*) as total FROM posterss");
$totalRows   = $totalResult->fetch_assoc()['total'];
$totalPages  = ceil($totalRows / $limit);

// ✅ Fetch unique Taluka & Jilla for dropdowns
$talukaResult = $conn->query("SELECT DISTINCT તાલુકો FROM posterss ORDER BY તાલુકો ASC");
$jillaResult  = $conn->query("SELECT DISTINCT જિલ્લો FROM posterss ORDER BY જિલ્લો ASC");
?>

<!DOCTYPE html>
<html lang="gu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Poster Data</title>
<style>
body { font-family: Arial,sans-serif; background: #f4f7fb; margin:0; padding:20px; }
h2 { text-align:center; color:#333; margin-bottom:20px; }
.table-container { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.actions { display:flex; justify-content:space-between; flex-wrap:wrap; margin-bottom:15px; gap:10px; }
.search-box input, select { padding:8px 12px; border:1px solid #ccc; border-radius:8px; }
.btn-download { background:#28a745; color:#fff; padding:8px 16px; border:none; border-radius:8px; cursor:pointer;}
.btn-download:hover { background:#218838; }
table { width:100%; border-collapse: collapse; margin-top:10px; }
table th, table td { border:1px solid #ddd; padding:12px 15px; text-align:center; }
table th { background:#007bff; color:#fff; text-transform:uppercase; }
table tr:nth-child(even) { background:#f9f9f9; }
table tr:hover { background:#f1f1f1; }
.user-img { width:60px; height:60px; object-fit:cover; border-radius:8px; border:2px solid #007bff;}
.pagination { margin-top:15px; text-align:center;}
.pagination a { display:inline-block; margin:0 5px; padding:6px 12px; border:1px solid #007bff; border-radius:6px; text-decoration:none; color:#007bff;}
.pagination a.active, .pagination a:hover { background:#007bff; color:#fff; }
.btn-view, .btn-delete { padding:6px 12px; margin:2px; border:none; border-radius:6px; cursor:pointer; color:#fff; }
.btn-view { background:#007bff;} .btn-view:hover { background:#0056b3; }
.btn-delete { background:#dc3545;} .btn-delete:hover { background:#a71d2a; }
/* Popup Modal */
#popupModal { display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);}
#popupContent { background:#fff; padding:30px; border-radius:12px; max-width:600px; margin:80px auto; position:relative; box-shadow:0 4px 20px rgba(0,0,0,0.2);}
#popupContent img { max-width:100%; border-radius:8px; margin-bottom:10px;}
#popupClose { position:absolute; top:10px; right:15px; font-size:22px; cursor:pointer; color:#333;}
</style>
</head>
<body>

<h2>📊 Posters Data</h2>

<div class="table-container">
  <div class="actions">
    <div class="search-box">
      <input type="text" id="searchInput" placeholder="Search by Taluka or Jilla...">
    </div>
    <select id="talukaFilter">
      <option value="">Filter by Taluka</option>
      <?php while($t = $talukaResult->fetch_assoc()){ ?>
        <option value="<?php echo $t['તાલુકો'];?>"><?php echo $t['તાલુકો'];?></option>
      <?php } ?>
    </select>
    <select id="jillaFilter">
      <option value="">Filter by Jilla</option>
      <?php while($j = $jillaResult->fetch_assoc()){ ?>
        <option value="<?php echo $j['જિલ્લો'];?>"><?php echo $j['જિલ્લો'];?></option>
      <?php } ?>
    </select>
    <button class="btn-download" onclick="downloadExcel()">⬇ Download Excel</button>
    <button class="btn-download"><a href="index.php">⬅ Back</a> </button>
  </div>

  <table id="dataTable">
    <thead>
      <tr>
        <th>ID</th><th>નામ</th><th>ગામ</th><th>તાલુકો</th><th>જિલ્લો</th>
        <th>મોબાઈલ નંબર</th><th>વ્યવસાય</th><th>તારીખ</th><th>ફોટો</th><th>Created At</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if($result->num_rows>0){
        while($row=$result->fetch_assoc()){ ?>
          <tr>
            <td><?php echo $row['id'];?></td>
            <td><?php echo $row['નામ'];?></td>
            <td><?php echo $row['ગામ'];?></td>
            <td><?php echo $row['તાલુકો'];?></td>
            <td><?php echo $row['જિલ્લો'];?></td>
            <td><?php echo $row['મોબાઈલ_નંબર'];?></td>
            <td><?php echo $row['વ્યવસાય'];?></td>
            <td><?php echo $row['તારીખ'];?></td>
            <td><?php if($row['ફોટો']){ ?><img src="<?php echo $row['ફોટો'];?>" class="user-img"><?php }else{ echo "No Image";}?></td>
            <td><?php echo $row['created_at'];?></td>
            <td>
              <a href="view.php?id=<?php echo $row['id']; ?>" class="btn-view">View</a>
              <button class="btn-delete" onclick="deleteRow(<?php echo $row['id'];?>)">Delete</button>
            </td>
          </tr>
      <?php }}else{ echo '<tr><td colspan="11">No records found.</td></tr>'; } ?>
    </tbody>
  </table>

  <div class="pagination">
    <?php for($i=1;$i<=$totalPages;$i++){ ?>
      <a href="?page=<?php echo $i;?>" class="<?php if($i==$page) echo 'active';?>"><?php echo $i;?></a>
    <?php } ?>
  </div>
</div>

<!-- Popup Modal -->
<div id="popupModal">
  <div id="popupContent">
    <span id="popupClose" onclick="closePopup()">&times;</span>
    <div id="popupBody"></div>
  </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("keyup",filterTable);
document.getElementById("talukaFilter").addEventListener("change",filterTable);
document.getElementById("jillaFilter").addEventListener("change",filterTable);

function filterTable(){
  var search=document.getElementById("searchInput").value.toLowerCase();
  var taluka=document.getElementById("talukaFilter").value.toLowerCase();
  var jilla=document.getElementById("jillaFilter").value.toLowerCase();
  var rows=document.querySelectorAll("#dataTable tbody tr");
  rows.forEach(row=>{
    let t=row.cells[3].innerText.toLowerCase();
    let j=row.cells[4].innerText.toLowerCase();
    let matchSearch=!search || t.includes(search) || j.includes(search);
    let matchTaluka=!taluka || t===taluka;
    let matchJilla=!jilla || j===jilla;
    row.style.display=(matchSearch && matchTaluka && matchJilla)?"":"none";
  });
}

// Download Excel
function downloadExcel(){
  let table=document.getElementById("dataTable").outerHTML;
  let url='data:application/vnd.ms-excel,'+encodeURIComponent(table);
  let a=document.createElement("a");
  a.href=url;
  a.download="database_data.xls";
  a.click();
}

// View Row
function viewRow(id){
  fetch('?get_id='+id).then(r=>r.text()).then(resp=>{
    if(resp){
      let data=JSON.parse(resp);
      let html=`<h2>${data.નામ}</h2>
        <p><strong>ગામ:</strong> ${data.ગામ}</p>
        <p><strong>તાલુકો:</strong> ${data.તાલુકો}</p>
        <p><strong>જિલ્લો:</strong> ${data.જિલ્લો}</p>
        <p><strong>મોબાઈલ:</strong> ${data.મોબાઈલ_નંબર}</p>
        <p><strong>વ્યવસાય:</strong> ${data.વ્યવસાય}</p>
        <p><strong>તારીખ:</strong> ${data.તારીખ}</p>
        <p><strong>Created At:</strong> ${data.created_at}</p>
        ${data.ફોટો ? `<img src='${data.ફોટો}'>`:'No Image'}`;
      document.getElementById("popupBody").innerHTML=html;
      document.getElementById("popupModal").style.display="block";
    }
  });
}

// Delete Row
function deleteRow(id){
  if(confirm("Are you sure you want to delete this record?")){
    fetch('?delete_id='+id).then(r=>r.text()).then(resp=>{
      if(resp=='deleted'){
        alert("Record deleted successfully!");
        location.reload();
      }
    });
  }
}

// Close Popup
function closePopup(){ document.getElementById("popupModal").style.display="none"; }
</script>

<?php
// ✅ Inline view data
if(isset($_GET['get_id'])){
  $id=(int)$_GET['get_id'];
  $res=$conn->query("SELECT * FROM posterss WHERE id=$id");
  echo $res->num_rows>0 ? json_encode($res->fetch_assoc()) : '';
  exit;
}
?>

</body>
</html>
