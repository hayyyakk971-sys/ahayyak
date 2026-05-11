#include <WiFi.h>          // مكتبة الاتصال بالواي فاي | Library for WiFi connectivity
#include <HTTPClient.h>    // مكتبة إرسال طلبات الـ HTTP للسيرفر | Library for sending HTTP requests to server
#include <DHT.h>           // مكتبة حساس الحرارة والرطوبة | Library for DHT temperature & humidity sensor
#include <ArduinoJson.h>   // مكتبة التعامل مع بيانات JSON | Library for handling JSON data formats
#include <TFT_eSPI.h>      // مكتبة تشغيل شاشة الـ TFT | Library for controlling the TFT display

// ========== WiFi & Server ==========
const char* ssid = "Abdullah";          // اسم شبكة الواي فاي الخاصة بك | Your WiFi network name
const char* password = "Fujsoc@15";     // كلمة سر الواي فاي | Your WiFi network password
const char* serverName = "http://192.168.1.118/مـشـروع_حــــيـــاك_فـريـق_ويـل_هـوسـت/api/update.php"; // رابط ملف الـ PHP لاستقبال البيانات | URL of the PHP API to receive data

// --- إعدادات الموقت للإرسال للسيرفر ---
unsigned long lastSendTime = 0;        // متغير لتخزين وقت آخر إرسال ناجح | Variable to store the time of the last data send
const long sendInterval = 5000;        // فاصل زمني 5 ثوانٍ بين كل إرسال | 5-second interval between each server update

#define DHTPIN 13                      // تعريف دبوس حساس DHT على رقم 13 | Define DHT sensor pin on GPIO 13
#define DHTTYPE DHT22                  // تحديد نوع الحساس DHT22 | Specify sensor type as DHT22
DHT dht(DHTPIN, DHTTYPE);              // إنشاء كائن الحساس | Initialize DHT sensor object

#define TOUCH_PIN 33                   // دبوس حساس اللمس لتغيير الشاشات | Touch sensor pin for toggling displays
#define MQ135_PIN 34                   // دبوس حساس جودة الهواء (AQI) | Air Quality sensor pin (Analog)
#define PIR_PIN 14                     // دبوس حساس الحركة | PIR Motion sensor pin
#define SOUND_PIN 35                   // دبوس حساس الصوت | Sound sensor pin (Analog)
#define IR_PIN 27                      // دبوس حساس الأشعة تحت الحمراء للأجسام | IR Obstacle sensor pin
#define TRIGGER_PIN 5                  // دبوس إرسال الموجات فوق الصوتية | Ultrasound Trigger pin
#define ECHO_PIN 22                    // دبوس استقبال الموجات فوق الصوتية | Ultrasound Echo pin

// ========== Screen & Data Setup ==========
TFT_eSPI tft = TFT_eSPI();             // إنشاء كائن الشاشة | Initialize TFT screen object
#define SCREEN_WIDTH  480              // عرض الشاشة بالبكسل | Screen width in pixels
#define SCREEN_HEIGHT 320              // طول الشاشة بالبكسل | Screen height in pixels

#define HISTORY_SIZE 20                // عدد النقاط المخزنة للرسم البياني | Number of data points for the graphs
float tempHistory[HISTORY_SIZE], humHistory[HISTORY_SIZE], aqiHistory[HISTORY_SIZE], crowdHistory[HISTORY_SIZE]; // مصفوفات تخزين البيانات السابقة | Arrays to store historical data
int historyIndex = 0;                  // مؤشر موقع البيانات الحالي في المصفوفة | Current index pointer for the history arrays

int displayMode = 0;                   // وضع العرض (0 للبيانات، 1 للرسوم) | Display mode (0 for Overview, 1 for Charts)
unsigned long lastTouchTime = 0;       // لتخزين وقت آخر لمسة لتجنب التكرار | Stores last touch time for debounce/double-tap
const int doubleTapDelay = 400;        // المهلة الزمنية للمسة المزدوجة | Delay threshold for detecting a double-tap

float temperature, humidity;           // متغيرات الحرارة والرطوبة | Variables for Temp & Humidity
int aqi, soundRaw, distance;           // متغيرات الهواء والصوت والمسافة | Variables for AQI, Sound, and Distance
bool motion, obstacle;                 // متغيرات الحركة والعوائق | Boolean variables for Motion and Obstacles
int overcrowdingPercent;               // نسبة الازدحام المحسوبة | Calculated overcrowding percentage

int ultraCount = 0;                    // عداد استقرار قراءة المسافة | Counter for ultrasonic reading stability
int pirCount = 0;                      // عداد استقرار قراءة الحركة | Counter for PIR motion stability
int irCount = 0;                       // عداد استقرار قراءة العوائق | Counter for IR obstacle stability

// دالة حساب المسافة باستخدام السونار | Function to calculate distance via Ultrasound
long getDistance() {
  digitalWrite(TRIGGER_PIN, LOW); delayMicroseconds(2);   // تصفير الدبوس | Clear the trigger pin
  digitalWrite(TRIGGER_PIN, HIGH); delayMicroseconds(10); // إرسال نبضة 10 ميكرو ثانية | Send 10µs pulse
  digitalWrite(TRIGGER_PIN, LOW);                         // إيقاف النبضة | Stop the pulse
  long d = pulseIn(ECHO_PIN, HIGH, 20000) * 0.034 / 2;    // حساب المسافة بناءً على سرعة الصوت | Calc distance based on speed of sound
  return (d == 0) ? 400 : d;                              // إرجاع 400 إذا كانت القراءة خارج المدى | Return 400 if out of range
}

// ========== دالة إرسال البيانات للسيرفر ==========
void sendDataToServer() {
  if (WiFi.status() == WL_CONNECTED) {                    // التأكد من اتصال الواي فاي | Check if WiFi is connected
    HTTPClient http;                                      // إنشاء كائن العميل | Create HTTP client object
    http.begin(serverName);                               // بدء الاتصال بالرابط | Start connection to URL
    http.addHeader("Content-Type", "application/x-www-form-urlencoded"); // تحديد نوع البيانات المرسلة | Set content type header

    // تجميع البيانات في نص واحد للإرسال | Constructing the POST data string
    String httpRequestData = "temp=" + String(temperature) + 
                             "&hum=" + String(humidity) + 
                             "&aqi=" + String(aqi) + 
                             "&crowd=" + String(overcrowdingPercent) +
                             "&sound=" + String(soundRaw) +
                             "&dist=" + String(distance);

    int httpResponseCode = http.POST(httpRequestData);    // إرسال طلب POST واستلام الكود | Send POST request and get response code
    
    Serial.print("HTTP Response code: ");                 // طباعة كود الاستجابة (200 يعني نجاح) | Print response code (200 = Success)
    Serial.println(httpResponseCode);
    
    http.end();                                           // إغلاق الاتصال | Close HTTP connection
  } else {
    Serial.println("WiFi Disconnected");                  // تنبيه في حال انقطاع الشبكة | Error if WiFi is lost
  }
}

void setup() {
  Serial.begin(115200);                                   // بدء الاتصال التسلسلي للمراقبة | Start Serial monitor
  dht.begin();                                            // تشغيل حساس الحرارة | Start DHT sensor
  pinMode(TOUCH_PIN, INPUT);                              // ضبط دبوس اللمس كمدخل | Set touch pin as input
  pinMode(PIR_PIN, INPUT);                                // ضبط دبوس الحركة كمدخل | Set PIR pin as input
  pinMode(IR_PIN, INPUT);                                 // ضبط دبوس الـ IR كمدخل | Set IR pin as input
  pinMode(SOUND_PIN, INPUT);                              // ضبط دبوس الصوت كمدخل | Set sound pin as input
  pinMode(TRIGGER_PIN, OUTPUT);                           // ضبط دبوس التريجر كمخرج | Set Trigger pin as output
  pinMode(ECHO_PIN, INPUT);                               // ضبط دبوس الإيكو كمدخل | Set Echo pin as input
  
  tft.init();                                             // تشغيل الشاشة | Initialize the display
  tft.setRotation(1);                                     // تدوير الشاشة للوضع العرضي | Set screen rotation to landscape
  tft.fillScreen(TFT_WHITE);                              // تلوين الخلفية بالأبيض | Fill screen with white

  tft.setTextColor(TFT_BLACK);                            // ضبط لون النص للأسود | Set text color to black
  tft.drawCentreString("HAYYAK STATION", 240, 140, 4);    // عرض رسالة الترحيب | Display welcome message
  
  WiFi.begin(ssid, password);                             // بدء محاولة الاتصال بالواي فاي | Start WiFi connection
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); } // انتظار الاتصال | Wait for connection
  tft.fillScreen(TFT_WHITE);                              // مسح الشاشة بعد الاتصال | Clear screen after connecting
  Serial.println("\nConnected to WiFi");                  // تأكيد الاتصال | Confirm connection
}

void loop() {
  // 1. قراءة الحساسات | Sensor Reading
  temperature = dht.readTemperature();                    // قراءة درجة الحرارة | Read Temperature
  humidity = dht.readHumidity();                          // قراءة الرطوبة | Read Humidity
  aqi = map(analogRead(MQ135_PIN), 0, 4095, 0, 500);      // تحويل قراءة الهواء لنطاق 0-500 | Map air quality to 0-500
  soundRaw = analogRead(SOUND_PIN);                       // قراءة شدة الصوت | Read raw sound level
  motion = digitalRead(PIR_PIN);                          // قراءة وجود حركة | Read PIR motion state
  obstacle = (digitalRead(IR_PIN) == LOW);                // قراءة وجود جسم قريب | Read IR obstacle state
  distance = getDistance();                               // قراءة المسافة من السونار | Get distance from sonar

  // حساب الازدحام | Crowding Logic
  if (distance <= 100) ultraCount++; else ultraCount--;   // زيادة العداد إذا وجد جسم قريب | Increment if object is near
  ultraCount = constrain(ultraCount, 0, 10);              // حصر العداد بين 0 و 10 | Keep counter within 0-10
  if (motion == HIGH) pirCount++; else pirCount--;        // زيادة عداد الحركة | Increment if motion detected
  pirCount = constrain(pirCount, 0, 10);
  if (obstacle == true) irCount++; else irCount--;        // زيادة عداد عائق الـ IR | Increment if IR detects object
  irCount = constrain(irCount, 0, 10);

  // حساب أوزان الازدحام لكل حساس | Calculate crowding weights for each sensor
  int crowd_Ultra = (ultraCount > 5) ? 30 : map(ultraCount, 0, 5, 0, 10);
  int crowd_PIR   = (pirCount > 5)   ? 30 : map(pirCount, 0, 5, 0, 10);
  int crowd_IR    = (irCount > 5)    ? 20 : map(irCount, 0, 5, 0, 10);
  
  int crowd_Sound = 0;                                    // وزن الصوت في الازدحام | Sound weight in crowding
  if (soundRaw > 1500) crowd_Sound = 20; else if (soundRaw > 500) crowd_Sound = 15;

  overcrowdingPercent = constrain(crowd_Ultra + crowd_PIR + crowd_IR + crowd_Sound, 0, 100); // النسبة الكلية للازدحام | Final crowding percentage

  // تحديث الذاكرة للرسم البياني | Update History for Graphing
  tempHistory[historyIndex] = temperature;                // تخزين الحرارة في المصفوفة | Store temp in history
  humHistory[historyIndex] = humidity;                    // تخزين الرطوبة | Store humidity in history
  aqiHistory[historyIndex] = (float)aqi;                  // تخزين جودة الهواء | Store AQI in history
  crowdHistory[historyIndex] = (float)overcrowdingPercent; // تخزين نسبة الازدحام | Store crowd % in history
  historyIndex = (historyIndex + 1) % HISTORY_SIZE;       // تحريك المؤشر للعنصر التالي | Move index to next position

  // 2. إرسال البيانات للسيرفر كل 5 ثوانٍ | Data Transmission
  if (millis() - lastSendTime >= sendInterval) {
    sendDataToServer();                                   // استدعاء دالة الإرسال | Call sending function
    lastSendTime = millis();                              // تحديث وقت الإرسال الأخير | Reset timer
  }

  // 3. تحديث الشاشة | Screen Update
  checkTouchToggle();                                     // فحص إذا تم لمس الشاشة | Check for user touch
  if (displayMode == 0) drawOverview(); else drawCharts(); // اختيار واجهة العرض | Choose which UI to draw

  delay(50);                                              // تأخير بسيط لاستقرار العمليات | Small delay for stability
}

// دالة فحص لمس الشاشة للتبديل | Function to check for touch interaction
void checkTouchToggle() {
  static bool lastState = LOW;                            // تخزين الحالة السابقة للمس | Store last touch state
  bool currentState = digitalRead(TOUCH_PIN);             // قراءة الحالة الحالية | Read current touch state
  if (currentState == HIGH && lastState == LOW) {         // اكتشاف بداية اللمس | Detect touch press
    unsigned long now = millis();
    if (now - lastTouchTime < doubleTapDelay) {           // إذا كانت اللمسة سريعة (مزدوجة) | If it's a double tap
      displayMode = !displayMode;                         // تغيير وضع العرض | Toggle display mode
      tft.fillScreen(TFT_WHITE);                          // مسح الشاشة بالكامل | Clear screen
      delay(200);                                         // منع الارتداد | Debounce delay
    }
    lastTouchTime = now;
  }
  lastState = currentState;
}

// دالة رسم واجهة البيانات العامة | Function to draw the Overview UI
void drawOverview() {
  tft.fillRect(0, 0, 480, 50, TFT_NAVY);                  // رسم شريط العنوان العلوي | Draw top header bar
  tft.setTextColor(TFT_YELLOW);                           // لون العنوان أصفر | Set header text to yellow
  tft.drawCentreString("HAYYAK", 240, 12, 4);             // كتابة اسم المشروع | Print project name
  tft.setTextColor(TFT_BLACK, TFT_WHITE);                 // نص أسود بخلفية بيضاء | Black text on white background
  tft.setTextSize(2);                                     // حجم الخط | Set text size
  // عرض كافة قيم الحساسات في مواقع محددة | Print all sensor values at fixed coordinates
  tft.setCursor(30, 70);  tft.printf("Temp: %.1f C", temperature);
  tft.setCursor(30, 110); tft.printf("Hum:  %.1f %%", humidity);
  tft.setCursor(30, 150); tft.printf("AQI:  %d", aqi);
  tft.setCursor(30, 190); tft.printf("Dist: %d cm", distance);
  tft.setCursor(260, 70);  tft.printf("Motion: %s", motion ? "ON" : "OFF");
  tft.setCursor(260, 110); tft.printf("IR Obj: %s", obstacle ? "YES" : "NO");
  tft.setCursor(260, 150); tft.printf("Sound:  %d", soundRaw);
  tft.setCursor(260, 190); tft.printf("Crowd:  %d%%", overcrowdingPercent);
}

// دالة رسم واجهة الرسوم البيانية | Function to draw the Charts UI
void drawCharts() {
  tft.fillRect(0, 0, 480, 50, TFT_NAVY);                  // شريط العنوان العلوي | Header bar
  tft.setTextColor(TFT_YELLOW);
  tft.drawCentreString("REAL-TIME CHARTS", 240, 12, 4);
  // رسم الأربعة رسوم بيانية في أماكنها | Draw the 4 graphs in their grid positions
  drawSingleGraph(20, 70, 200, 90, tempHistory, "Temp C", TFT_RED, 0, 50);
  drawSingleGraph(250, 70, 200, 90, humHistory, "Hum %", TFT_BLUE, 0, 100);
  drawSingleGraph(20, 190, 200, 90, aqiHistory, "AQI", TFT_ORANGE, 0, 500);
  drawSingleGraph(250, 190, 200, 90, crowdHistory, "Crowd %", TFT_PURPLE, 0, 100);
}

// دالة رسم رسم بياني واحد | Generic function to draw a single graph
void drawSingleGraph(int x, int y, int w, int h, float* data, const char* label, uint16_t color, float minV, float maxV) {
  tft.drawRect(x, y, w, h, TFT_BLACK);                    // رسم إطار الرسم البياني | Draw graph border
  tft.setTextColor(TFT_BLACK);
  tft.drawString(label, x, y - 18, 2);                    // كتابة اسم الرسم فوقه | Draw label above graph
  for (int i = 0; i < HISTORY_SIZE - 1; i++) {            // تكرار لرسم الخطوط بين النقاط | Loop to draw lines between points
    int idx = (historyIndex + i) % HISTORY_SIZE;          // حساب موقع العنصر الحالي | Calc current data index
    int nIdx = (idx + 1) % HISTORY_SIZE;                  // حساب موقع العنصر التالي | Calc next data index
    int y1 = y + h - map(data[idx], minV, maxV, 0, h);    // تحويل القيمة لموقع رأسي (1) | Map value to Y coordinate 1
    int y2 = y + h - map(data[nIdx], minV, maxV, 0, h);   // تحويل القيمة لموقع رأسي (2) | Map value to Y coordinate 2
    tft.drawLine(x + (i * (w / HISTORY_SIZE)), y1, x + ((i + 1) * (w / HISTORY_SIZE)), y2, color); // رسم الخط | Draw line segment
  }
}