<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'lecturer' && $_SESSION['role'] !== 'admin')) {
    header("Location: login");
    exit;
}

$db = get_db_connection();
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

try {
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if (!$course) {
        die("Course not found.");
    }
    
    // Fallback if no permanent token
    $token = $course['permanent_token'] ?: 'COURSE_' . $course['id'];

} catch (PDOException $e) {
    die("Database Error");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Gateway - <?php echo htmlspecialchars($course['course_code']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .print-container {
            background: white;
            width: 210mm;
            min-height: 297mm; /* A4 format */
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40mm 20mm;
            box-sizing: border-box;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h1 {
            font-size: 64px;
            font-weight: 950;
            color: #0f172a;
            margin: 0;
            letter-spacing: -2px;
            line-height: 1;
        }

        h2 {
            font-size: 28px;
            font-weight: 800;
            color: #3b82f6;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 20px 0 60px;
        }

        #qrcode-container {
            padding: 40px;
            background: white;
            border: 8px solid #0f172a;
            border-radius: 40px;
            display: inline-block;
            margin-bottom: 60px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
        }

        #qrcode img {
            display: block;
            margin: 0 auto;
        }

        .instructions {
            font-size: 20px;
            color: #64748b;
            font-weight: 600;
            max-width: 80%;
            line-height: 1.6;
        }

        .print-btn {
            position: fixed;
            bottom: 40px;
            right: 40px;
            background: #0f172a;
            color: white;
            border: none;
            padding: 20px 40px;
            border-radius: 100px;
            font-size: 18px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            font-family: inherit;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        @media print {
            body {
                background: white;
                display: block;
            }
            .print-container {
                box-shadow: none;
                width: 100%;
                height: 100%;
                padding: 0;
                margin: 0;
            }
            .print-btn {
                display: none;
            }
            #qrcode-container {
                border-radius: 0;
                border: 10px solid #000;
                margin-top: 50px;
            }
            h1 { color: black; }
            h2 { color: black; }
        }
    </style>
</head>
<body>

    <div class="print-container">
        <h1><?php echo htmlspecialchars($course['course_code']); ?></h1>
        <h2>Master Attendance Gateway</h2>

        <div id="qrcode-container">
            <div id="qrcode"></div>
        </div>

        <p class="instructions">
            Scan this gateway using your AttendEase mobile portal to verify your presence. <br>
            <strong>Note:</strong> Attendance is only recorded when the lecturer has initialized an active broadcast session.
        </p>
    </div>

    <button class="print-btn" onclick="window.print()">🖨️ Print Gateway</button>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            new QRCode(document.getElementById("qrcode"), {
                text: "<?php echo $token; ?>",
                width: 500,
                height: 500,
                colorDark : "#0f172a",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            
            // Auto trigger print after short delay to let QR code render
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
