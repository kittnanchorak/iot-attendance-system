<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตัวจำลองเครื่อง IoT (IoT Simulator)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Noto+Sans+Thai:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Noto Sans Thai', sans-serif;
            background-color: #0f0f12;
            color: #fafafa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .simulator-card {
            background-color: #18181b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h2 { margin-top: 0; color: #818cf8; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #a1a1aa; }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            background-color: #27272a;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1.2rem;
            text-align: center;
            letter-spacing: 2px;
            box-sizing: border-box;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: #818cf8;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: #818cf8;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        button:hover { background-color: #6366f1; }
        #responseArea {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: #27272a;
            font-size: 0.85rem;
            display: none;
            word-wrap: break-word;
        }
        .success { border-left: 4px solid #34d399; }
        .error { border-left: 4px solid #f87171; }
    </style>
</head>
<body>

    <div class="simulator-card">
        <h2>📱 ตัวจำลอง Keypad IoT</h2>
        <p style="text-align: center; font-size: 0.85rem; color: #a1a1aa; margin-bottom: 25px;">
            จำลองการกดรหัสนักศึกษา (6 หลัก) ส่งไปที่ API
        </p>
        
        <div class="form-group">
            <label for="studentCode">รหัสนักศึกษา (เช่น 032789):</label>
            <input type="text" id="studentCode" placeholder="xxxxxx" maxlength="6" autocomplete="off">
        </div>
        
        <button id="sendBtn" onclick="sendToAPI()">ตกลง (Send to API)</button>

        <div id="responseArea"></div>
    </div>

    <script>
        // 🔑 API Key (ต้องตรงกับในฐานข้อมูล)
        const API_KEY = "d683b2603092cfb957d82ce73035ce2bdef8df9c7ad013e1f6213250b58f8863";

        async function sendToAPI() {
            const codeInput = document.getElementById('studentCode');
            const responseArea = document.getElementById('responseArea');
            const code = codeInput.value.trim();

            if (!code) {
                alert("กรุณากรอกรหัสนักศึกษา");
                return;
            }

            responseArea.style.display = 'block';
            responseArea.innerHTML = "กำลังส่งข้อมูล...";
            responseArea.className = "";

            try {
                // ส่ง POST request ไปที่ checkin.php
                const response = await fetch('api/checkin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + API_KEY
                    },
                    body: JSON.stringify({
                        student_code: code
                    })
                });

                const data = await response.json();

                if (data.success) {
                    responseArea.className = "success";
                    responseArea.innerHTML = `
                        <strong>✅ สำเร็จ!</strong><br>
                        ชื่อ: ${data.student_name}<br>
                        ห้อง: ${data.room}<br>
                        เวลาเข้าเรียน: ${data.time}
                    `;
                    codeInput.value = ''; // เคลียร์ช่องให้คนต่อไป
                } else {
                    responseArea.className = "error";
                    responseArea.innerHTML = `
                        <strong>❌ ผิดพลาด:</strong><br>
                        ${data.message}
                    `;
                }

            } catch (error) {
                responseArea.className = "error";
                responseArea.innerHTML = `
                    <strong>⚠️ เชื่อมต่อไม่ได้:</strong><br>
                    ${error.message}
                `;
            }
        }

        // กด Enter เพื่อส่ง
        document.getElementById('studentCode').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                sendToAPI();
            }
        });
    </script>
</body>
</html>
