/**
 * =====================================================
 * IoT Attendance System — ESP32 Check-in
 * =====================================================
 * 
 * Hardware:
 *   - ESP32 Development Board (DevKit v1 หรือเทียบเท่า)
 *   - Keypad 4x4 Matrix หรือ RFID ตามที่ใช้งานจริง
 *   - LCD 16x2 I2C (optional สำหรับแสดงผล)
 * 
 * Libraries ที่ต้องติดตั้งผ่าน Arduino Library Manager:
 *   1. "ArduinoJson" by Benoit Blanchon (v7.x)
 *   → Sketch → Include Library → Manage Libraries → ค้น "ArduinoJson"
 * 
 * Built-in Libraries (ไม่ต้องติดตั้งเพิ่ม — มีใน ESP32 core):
 *   - WiFi.h
 *   - HTTPClient.h
 *   - WiFiClientSecure.h
 * 
 * =====================================================
 * ⚠️  ค่าที่ต้องแก้อยู่ในหัวข้อ USER CONFIGURATION
 * =====================================================
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <ArduinoJson.h>

// =====================================================
// USER CONFIGURATION — แก้ค่าตรงนี้เท่านั้น
// =====================================================

// WiFi
const char* WIFI_SSID     = "YOUR_WIFI_SSID";        // ← ชื่อ WiFi ของคุณ
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";     // ← Password WiFi

// API Endpoint (HTTPS โดเมนคงที่ของ Ngrok)
const char* API_URL = "https://linguini-subscript-unsubtle.ngrok-free.dev/api/checkin.php";

// API Key — ตรงกับในฐานข้อมูลเรียบร้อยแล้ว
const char* API_KEY = "d683b2603092cfb957d82ce73035ce2bdef8df9c7ad013e1f6213250b58f8863";

// =====================================================
// TIMING CONFIGURATION
// =====================================================
const int WIFI_TIMEOUT_MS = 15000;   // รอ WiFi สูงสุด 15 วินาที
const int HTTP_TIMEOUT_MS = 10000;   // รอ HTTP response 10 วินาที
const int MAX_RETRIES     = 3;       // retry สูงสุด 3 ครั้งเมื่อ network error
const int RETRY_DELAY_MS  = 2000;   // รอ 2 วินาทีระหว่าง retry

// =====================================================
// TLS Root CA Certificate (Let's Encrypt ISRG Root X1)
// AlwaysData ใช้ Let's Encrypt → ใช้ Root CA นี้ตรวจสอบ certificate
// Valid until: 2035
// =====================================================
const char* ROOT_CA = \
"-----BEGIN CERTIFICATE-----\n" \
"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\n" \
"-----END CERTIFICATE-----\n";

// =====================================================
// Data Structure: ผลลัพธ์จาก API
// =====================================================
struct CheckInResult {
    bool    success;
    int     httpCode;
    String  studentName;
    String  room;
    String  checkTime;
    String  message;
};

// =====================================================
// ฟังก์ชัน: เชื่อมต่อ WiFi พร้อม Timeout
// =====================================================
bool connectWiFi() {
    Serial.print("[WiFi] Connecting to: ");
    Serial.println(WIFI_SSID);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    unsigned long startTime = millis();
    while (WiFi.status() != WL_CONNECTED) {
        if (millis() - startTime > WIFI_TIMEOUT_MS) {
            Serial.println("\n[WiFi] ERROR: Connection timeout!");
            return false;
        }
        delay(500);
        Serial.print(".");
    }

    Serial.println();
    Serial.print("[WiFi] Connected! IP Address: ");
    Serial.println(WiFi.localIP());
    return true;
}

// =====================================================
// ฟังก์ชัน: ส่ง Check-in ไปยัง API (HTTPS POST + JSON)
// =====================================================
CheckInResult sendCheckIn(const String& studentCode) {
    CheckInResult result = {false, -1, "", "", "", "Unknown error"};

    // ตรวจสอบ WiFi ก่อนส่ง
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[HTTP] WiFi disconnected — reconnecting...");
        if (!connectWiFi()) {
            result.message = "WiFi unavailable";
            return result;
        }
    }

    // --- ตั้งค่า HTTPS Client ---
    WiFiClientSecure tlsClient;
    // หมายเหตุ: สำหรับ Ngrok แนะนำ setInsecure() เพื่อรองรับการ rotate ใบรับรองอัตโนมัติของ Ngrok
    tlsClient.setInsecure();
    // หรือหากต้องการใช้ Root CA แบบเข้มงวด:
    // tlsClient.setCACert(ROOT_CA);

    // --- ตั้งค่า HTTPClient ---
    HTTPClient https;
    https.setTimeout(HTTP_TIMEOUT_MS);
    https.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);

    if (!https.begin(tlsClient, API_URL)) {
        result.message = "Failed to begin HTTPS connection";
        Serial.println("[HTTP] ERROR: " + result.message);
        return result;
    }

    // --- ตั้งค่า Request Headers ---
    https.addHeader("Content-Type", "application/json");
    https.addHeader("Authorization", String("Bearer ") + API_KEY);
    https.addHeader("User-Agent",    "ESP32-IoT-Attendance/1.0");
    https.addHeader("Accept",        "application/json");
    https.addHeader("ngrok-skip-browser-warning", "1"); // ✅ ข้ามหน้าแจ้งเตือนของ Ngrok อัตโนมัติ

    // --- สร้าง JSON Payload ---
    // รูปแบบ: {"student_code":"032789"}
    String payload = "{\"student_code\":\"" + studentCode + "\"}";

    Serial.println("[HTTP] Sending POST...");
    Serial.println("[HTTP] URL    : " + String(API_URL));
    Serial.println("[HTTP] Payload: " + payload);

    // --- ส่ง POST Request ---
    int httpCode = https.POST(payload);
    result.httpCode = httpCode;

    Serial.print("[HTTP] Status : ");
    Serial.println(httpCode);

    if (httpCode > 0) {
        // รับ Response body
        String responseBody = https.getString();
        Serial.println("[HTTP] Response: " + responseBody);

        // --- Parse JSON Response ---
        JsonDocument doc;
        DeserializationError jsonErr = deserializeJson(doc, responseBody);

        if (!jsonErr) {
            result.success     = doc["success"] | false;
            result.message     = doc["message"].as<String>();
            result.studentName = doc["student_name"].as<String>();
            result.room        = doc["room"].as<String>();
            result.checkTime   = doc["time"].as<String>();
        } else {
            Serial.print("[JSON] Parse error: ");
            Serial.println(jsonErr.c_str());
            result.message = "JSON parse error: " + String(jsonErr.c_str());
        }

    } else {
        // HTTPClient error (network level)
        String errStr = https.errorToString(httpCode);
        Serial.println("[HTTP] ERROR: " + errStr);
        result.message = "HTTP error: " + errStr;
    }

    https.end();
    return result;
}

// =====================================================
// ฟังก์ชัน: Retry Wrapper
// Retry เฉพาะ Network Error (httpCode < 0)
// ไม่ retry HTTP 4xx/5xx — เพราะ server ตอบแล้วไม่มีประโยชน์ retry
// =====================================================
CheckInResult sendCheckInWithRetry(const String& studentCode) {
    CheckInResult result;

    for (int attempt = 1; attempt <= MAX_RETRIES; attempt++) {
        if (attempt > 1) {
            Serial.printf("[Retry] Attempt %d/%d — waiting %dms...\n",
                          attempt, MAX_RETRIES, RETRY_DELAY_MS);
            delay(RETRY_DELAY_MS);
        }

        result = sendCheckIn(studentCode);

        // ถ้า server ตอบ (httpCode > 0) ไม่ว่าจะ success หรือไม่ → หยุด retry
        if (result.httpCode > 0) {
            break;
        }

        Serial.println("[Retry] Network error — will retry");
    }

    return result;
}

// =====================================================
// ฟังก์ชัน: แสดงผลบน Serial Monitor
// (เพิ่ม LCD I2C ได้ที่นี่)
// =====================================================
void displayResult(const CheckInResult& result) {
    Serial.println("──────────────────────────────────────");
    if (result.success) {
        Serial.println("[✓] CHECK-IN SUCCESS!");
        Serial.println("    Student : " + result.studentName);
        Serial.println("    Room    : " + result.room);
        Serial.println("    Time    : " + result.checkTime);
    } else {
        Serial.println("[✗] CHECK-IN FAILED!");
        Serial.println("    HTTP    : " + String(result.httpCode));
        Serial.println("    Message : " + result.message);

        // แสดง hint ตาม HTTP status code
        if (result.httpCode == 401) Serial.println("    → API Key ไม่ถูกส่งไป");
        if (result.httpCode == 403) Serial.println("    → API Key ผิด หรือ device ถูก disable");
        if (result.httpCode == 404) Serial.println("    → ไม่พบรหัสนักศึกษานี้ในระบบ");
        if (result.httpCode == 405) Serial.println("    → ส่ง method ผิด (ต้องเป็น POST)");
    }
    Serial.println("──────────────────────────────────────");
}

// =====================================================
// Setup
// =====================================================
void setup() {
    Serial.begin(115200);
    delay(1000);

    Serial.println("==========================================");
    Serial.println("  IoT Attendance System — ESP32");
    Serial.println("  Firmware v1.0");
    Serial.println("==========================================");

    // เชื่อมต่อ WiFi ตอน boot
    if (!connectWiFi()) {
        Serial.println("[WARN] Starting without WiFi — will retry on first check-in");
    }

    Serial.println("[INFO] Ready! กรอกรหัสนักศึกษา 6 หลักใน Serial Monitor แล้วกด Enter");
    Serial.println("[INFO] หรือเชื่อมต่อ Keypad/RFID แล้วแก้ loop() ตามคำแนะนำด้านล่าง");
}

// =====================================================
// Loop
// 
// ⬇️  ตัวอย่างนี้: รับรหัสจาก Serial Monitor (ทดสอบ)
// สำหรับ Keypad จริง: ดูหมายเหตุด้านล่าง
// =====================================================
void loop() {

    // --- Reconnect WiFi ถ้าหลุด ---
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[WiFi] Disconnected! Attempting reconnect...");
        connectWiFi();
        delay(5000);
        return;
    }

    // --- รับรหัสจาก Serial Monitor (สำหรับทดสอบ) ---
    // ในการใช้งานจริงให้เปลี่ยนเป็น: อ่านจาก Keypad หรือ RFID
    if (Serial.available() > 0) {
        String code = Serial.readStringUntil('\n');
        code.trim();

        if (code.length() == 0) return;

        // Validate: ต้องเป็นตัวเลข 6 หลักเท่านั้น
        if (code.length() != 6 || !code.toInt() && code != "000000") {
            // ตรวจสอบว่าทุกตัวเป็นตัวเลข
            bool allDigit = true;
            for (char c : code) {
                if (!isDigit(c)) { allDigit = false; break; }
            }
            if (!allDigit || code.length() != 6) {
                Serial.println("[INPUT] ERROR: รหัสต้องเป็นตัวเลข 6 หลักเท่านั้น");
                return;
            }
        }

        Serial.println("[INPUT] รหัสที่รับ: " + code);

        // ส่งไปยัง API พร้อม retry
        CheckInResult result = sendCheckInWithRetry(code);
        displayResult(result);
    }

    delay(100);
}

// =====================================================
// หมายเหตุ: เชื่อมต่อ Keypad 4x4 จริง
// =====================================================
// 1. ติดตั้ง library "Keypad" by Mark Stanley ผ่าน Library Manager
// 2. เพิ่ม include และ init:
//
//    #include <Keypad.h>
//    const byte ROWS = 4, COLS = 4;
//    char keys[ROWS][COLS] = {
//      {'1','2','3','A'},
//      {'4','5','6','B'},
//      {'7','8','9','C'},
//      {'*','0','#','D'}
//    };
//    byte rowPins[ROWS] = {19, 18, 5, 17};   // ← ปรับตาม wiring จริง
//    byte colPins[COLS] = {16, 4, 2, 15};     // ← ปรับตาม wiring จริง
//    Keypad keypad = Keypad(makeKeymap(keys), rowPins, colPins, ROWS, COLS);
//    String inputCode = "";
//
// 3. ใน loop() แทน Serial.available():
//
//    char key = keypad.getKey();
//    if (key && isDigit(key)) {
//        inputCode += key;
//        Serial.print(key);
//        if (inputCode.length() == 6) {
//            CheckInResult result = sendCheckInWithRetry(inputCode);
//            displayResult(result);
//            inputCode = "";   // reset สำหรับคนถัดไป
//        }
//    }
//    if (key == '*') { inputCode = ""; Serial.println("Cleared"); }
// =====================================================
