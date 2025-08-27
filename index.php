<?php
// ✅ Secure Database connection (PDO)
$host = "localhost";
$db   = "poster_db";
$user = "root";
$pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode(["success" => false, "message" => "Database Connection failed: ".$e->getMessage()]));
}

// ✅ Handle POST request (AJAX save)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $name     = trim($_POST['name']     ?? '');
    $village  = trim($_POST['village']  ?? '');
    $taluka   = trim($_POST['taluka']   ?? '');
    $district = trim($_POST['district'] ?? '');
    $mobile   = trim($_POST['mobile']   ?? '');
    $business = trim($_POST['business'] ?? '');
    $date     = trim($_POST['date']     ?? '');
    $photo    = null;

    // ✅ File Upload Handling (input name: photo)
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(["success" => false, "message" => "Invalid image type"]);
            exit;
        }

        $fileName = time() . "_" . bin2hex(random_bytes(5)) . "." . $ext;
        $target = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photo = "uploads/" . $fileName;
        }
    }

    if ($mobile === '') {
        echo json_encode(["success" => false, "message" => "Mobile number required"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT id FROM posterss WHERE `મોબાઈલ_નંબર` = ? LIMIT 1");
        $stmt->execute([$mobile]);

        if ($stmt->rowCount() > 0) {
            $baseSql = "UPDATE posterss 
                        SET `નામ`=?, `ગામ`=?, `તાલુકો`=?, `જિલ્લો`=?, 
                            `વ્યવસાય`=?, `તારીખ`=?, `created_at`=NOW()";
            $params  = [$name, $village, $taluka, $district, $business, $date];

            if ($photo) {
                $baseSql .= ", `ફોટો`=?";
                $params[] = $photo;
            }
            $baseSql .= " WHERE `મોબાઈલ_નંબર`=?";
            $params[] = $mobile;
            $pdo->prepare($baseSql)->execute($params);
            $mode = "update";
        } else {
            $sql = "INSERT INTO posterss
                    (`નામ`, `ગામ`, `તાલુકો`, `જિલ્લો`, `મોબાઈલ_નંબર`, `વ્યવસાય`, `તારીખ`, `ફોટો`, `created_at`)
                    VALUES (?,?,?,?,?,?,?,?,NOW())";
            $pdo->prepare($sql)->execute([$name,$village,$taluka,$district,$mobile,$business,$date,$photo]);
            $mode = "insert";
        }

        $pdo->commit();
        echo json_encode(["success" => true, "mode" => $mode]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="gu">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>નિમણૂક પત્ર એડિટર</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;800&display=swap" rel="stylesheet">
<style>
:root { --canvas-w: 1080px; --canvas-h: 1530px; }
* { box-sizing: border-box; font-family: "Noto Sans Gujarati", sans-serif; }
body { margin: 18px; background: #f0f3f6; color: #111; display: flex; flex-direction: column; align-items: center; }
.controls { width: 100%; max-width: 420px; background: #fff; border-radius: 12px; padding: 14px; box-shadow: 0 8px 24px rgba(15, 15, 15, 0.08); margin-bottom: 20px; }
.controls h2 { margin: 0 0 8px; font-size: 18px; font-weight: 800; }
.controls label { display: block; font-weight: 700; margin-top: 10px; font-size: 13px; }
.controls input { width: 100%; padding: 8px 10px; margin-top: 6px; border-radius: 8px; border: 1px solid #d6d6d6; font-size: 14px; }
button { border: 0; padding: 10px 14px; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 14px; }
.primary { background: #111; color: #fff; transition: 0.3s; }
.primary:hover { background: #333; }
.ghost { background: #f3f4f6; transition: 0.3s; }
.ghost:hover { background: #e5e7eb; }

.canvas-wrap { width: 100%; max-width: var(--canvas-w); aspect-ratio: 1080 / 1530; position: relative; display: none; margin-bottom: 20px; }
.poster { width: 100%; height: 88%; border-radius: 12px; box-shadow: 0 14px 40px rgba(0, 0, 0, 0.25); position: relative; overflow: hidden; }
.poster img#bgImage { width:100%; height:100%; object-fit:cover; position:absolute; top:0; left:0; z-index:-1; }
.overlay { position: absolute; inset: 0; pointer-events: none; }
.center-frame { position: absolute; top: 16.5%; left: 39.5%; height: 17.2%; width: 19%; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 10px; overflow: hidden; }
.center-frame img { width: 100%; height: 100%; object-fit: cover; }
.text-overlay { position: absolute; font-weight: 700; color: #ff0000; white-space: pre-wrap; word-break: break-word; font-family: monospace; line-height: 1.3; text-align: left; }
#nameOut { top: 33.5%; left: 38%; font-size: 2.5vw; }
#villageOut { top: 39.6%; left: 27%; font-size: 2.3vw; }
#talukaOut { top: 39.8%; left: 58%; font-size: 2.3vw; }
#districtOut { top: 44%; left: 29%; font-size: 2.3vw; }
#mobileOut { top: 45%; left: 58%; font-size: 2.3vw; }
#businessOut { top: 67.9%; left: 63%; font-size: 2.3vw; max-width: 35%; text-align: center; }
#dateOut { top: 84%; left: 34%; font-size: 2.3vw; }

#goToFormBtn {
  display: none; 
  margin-bottom: 20px; 
  background: #2563eb; 
  color: white; 
  border: none; 
  font-weight: bold; 
  padding: 10px 16px; 
  border-radius: 8px; 
  cursor: pointer; 
  transition: 0.3s; 
}
#goToFormBtn:hover { background: #1e40af; }

/* ✅ Follow Modal */
#followModal { position: fixed; inset: 0; background: rgba(0,0,0,0.75); display: flex; justify-content: center; align-items: center; z-index: 9999; }
#followModal div { background: #fff; padding: 20px 30px; border-radius: 12px; text-align: center; max-width: 360px; }
#followBtn { background: #E1306C; color: #fff; border: none; padding: 10px 16px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
#followBtn:hover { background: #c72a61; }
</style>
</head>
<body>

<!-- ✅ Follow Modal -->
<div id="followModal">
  <div>
    <h2 style="margin-bottom:16px;">સૌપ્રથમ Instagram ફોલો કરો</h2>
    <p style="margin-bottom:20px;" >શ્રી સોનલ સેના નો પોસ્ટર બનાવિયા બાદ શ્રી સોનલ સેના ગુજરાત ની id સાથે કોલોબ્રેશન કરવા નું રહેસે જો નહીં હોય તો તમે સભ્ય ગણાસો નહીં 
id: shree_sonal_sena_gujrat</p><br>
    <p style="margin-bottom:20px;">Form ભરવા માટે અમારી Instagram profile ફોલો કરો.</p>
    <button id="followBtn">Follow on Instagram</button>
  </div>
</div>

<div class="controls" id="formWrap">
  <h2>પોસ્ટર માટે ફોર્મ</h2>
  <label>નામ</label><input id="nameIn" type="text">
  <label>ગામ</label><input id="villageIn" type="text">
  <label>તાલુકો</label><input id="talukaIn" type="text">
  <label>જિલ્લો</label><input id="districtIn" type="text">
  <label>મોબાઈલ નંબર</label><input id="mobileIn" type="text" required>
  <label>વ્યવસાય</label><input id="businessIn" type="text">
  <label>તારીખ</label><input id="dateIn" type="date">
  <label>પ્રોફાઇલ ફોટો</label><input id="centerImgIn" type="file" accept="image/*">
  <div style="margin-top:12px;">
    <button id="applyBtn" class="primary" type="button">Apply</button>
    <button id="resetBtn" class="ghost" type="button">Reset</button>
    <a href="data.php" style="text-decoration:none;">
      <button type="button" class="ghost">View Store Data</button>
    </a>
  </div>
</div>

<div class="canvas-wrap" id="posterWrap">
  <div id="poster" class="poster">
    <img id="bgImage" src="new-bg.jpg" alt="Background">
    <div class="overlay">
      <div class="center-frame"><img id="centerImg" src="" alt=""></div>
      <pre id="nameOut" class="text-overlay"></pre>
      <pre id="villageOut" class="text-overlay"></pre>
      <pre id="talukaOut" class="text-overlay"></pre>
      <pre id="districtOut" class="text-overlay"></pre>
      <pre id="mobileOut" class="text-overlay"></pre>
      <pre id="businessOut" class="text-overlay"></pre>
      <pre id="dateOut" class="text-overlay"></pre>
    </div>
  </div>
</div>

<button id="goToFormBtn">Go To Form</button>
<button id="downloadBtn" class="primary" style="display:none">Download Ultra HD PNG</button>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
const get = id => document.getElementById(id);
const setText = (id, value) => get(id).textContent = value;
let uploadedImageBase64 = "";

// ✅ Follow Modal JS
const followModal = get('followModal');
const followBtn = get('followBtn');
const formWrapEl = get('formWrap');

// Initially hide form
formWrapEl.style.display = 'none';

// Check if user has already seen follow modal
const hasFollowed = localStorage.getItem('hasFollowedInstagram');

if (!hasFollowed) {
    followModal.style.display = 'flex'; // show modal first time
} else {
    followModal.style.display = 'none';
    formWrapEl.style.display = 'block'; // show form directly
}

const instaUser = "shree_sonal_sena_gujrat"; // Instagram username

followBtn.addEventListener('click', () => {
    // Try deep link
    const appLink = `instagram://user?username=${instaUser}`;
    const webLink = `https://www.instagram.com/${instaUser}/`;

    // For mobile: try app first, then fallback to web
    const timeout = setTimeout(() => {
        window.open(webLink, '_blank');
    }, 1500); 

    // Attempt to open app
    window.location = appLink;

    // Hide modal and show form
    followModal.style.display = 'none';
    formWrapEl.style.display = 'block';

    // ✅ Mark that user has seen follow modal
    localStorage.setItem('hasFollowedInstagram', 'true');
});


// ✅ Format for poster display
const formatDateForPoster = iso => {
  if (!iso) return '';
  const [y, m, d] = iso.split('-');
  return `${d}-${m}-${y}`;
};

// ✅ Save Data with FormData (supports file upload)
const saveData = () => {
  const mobile = get('mobileIn').value.trim();
  if (!mobile) {
    alert("❌ મોબાઈલ નંબર ફરજિયાત છે!");
    return Promise.resolve({ success: false });
  }

  let fd = new FormData();
  fd.append("name", get('nameIn').value);
  fd.append("village", get('villageIn').value);
  fd.append("taluka", get('talukaIn').value);
  fd.append("district", get('districtIn').value);
  fd.append("mobile", mobile);
  fd.append("business", get('businessIn').value);
  fd.append("date", get('dateIn').value);

  if (get('centerImgIn').files[0]) {
    fd.append("photo", get('centerImgIn').files[0]);
  }

  return fetch("index.php", { 
    method: "POST",
    body: fd
  })
  .then(res => res.json())
  .catch(err => {
    console.error(err);
    return { success: false, message: "❌ Server Error!" };
  });
};

// ✅ Apply Button Function
const apply = () => {
  saveData().then(resp => {
    if (resp.success) {
      alert(resp.mode === "insert" ? "✅ નવું ડેટા સેવ થયું!" : "✏️ જૂનું ડેટા અપડેટ થયું!");

      setText('nameOut', get('nameIn').value);
      setText('villageOut', get('villageIn').value);
      setText('talukaOut', get('talukaIn').value);
      setText('districtOut', get('districtIn').value);
      setText('mobileOut', get('mobileIn').value);
      setText('businessOut', get('businessIn').value);
      setText('dateOut', formatDateForPoster(get('dateIn').value));

      get('formWrap').style.display = 'none';
      get('posterWrap').style.display = 'block';
      get('downloadBtn').style.display = 'inline-block';
      get('goToFormBtn').style.display = 'inline-block';
    } else {
      alert("❌ Error: " + (resp.message || "ડેટા સેવ નથી થયું!"));
    }
  });
};

get('goToFormBtn').addEventListener('click', () => {
  get('formWrap').style.display = 'block';
  get('posterWrap').style.display = 'none';
  get('downloadBtn').style.display = 'none';
  get('goToFormBtn').style.display = 'none';
});

get('centerImgIn').addEventListener('change', e => {
  const file = e.target.files[0];
  if (file) {
    const r = new FileReader();
    r.onload = ev => {
      uploadedImageBase64 = ev.target.result;
      get('centerImg').src = uploadedImageBase64;
    };
    r.readAsDataURL(file);
  }
});

get('applyBtn').addEventListener('click', apply);
get('resetBtn').addEventListener('click', () => location.reload());

get('downloadBtn').addEventListener('click', async () => {
  const posterEl = get('poster');
  await html2canvas(posterEl, { backgroundColor: null, useCORS: true, scale: 5 })
    .then(canvas => {
      canvas.toBlob(blob => {
        const link = document.createElement('a');
        link.download = 'nimnuk-patra_ultraHD.png';
        link.href = URL.createObjectURL(blob);
        link.click();
      }, 'image/png', 1.0);
    });
});
</script>
</body>
</html>
