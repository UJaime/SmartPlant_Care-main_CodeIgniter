/*
  SmartPlant CARE - ESP32 Firmware NodeMCU-32S (Optimizado)
  ============================================
  Sensores incluidos:
  - Sensor de humedad capacitivo (Conectado a P32 / GPIO 32)
  - Sensor de luz BH-1750 (I2C: SDA P21, SCL P22)

  Conexiones NodeMCU-32S:
  - Humedad Capacitivo: VCC -> 3.3V | GND -> GND | AO -> P32
  - Sensor BH-1750: VCC -> VIN (o 5V) | GND -> GND | SDA -> P21 | SCL -> P22
*/

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <BH1750.h>

#define SOIL_PIN 32 // Pin P32 en NodeMCU-32S

const char *WIFI_SSID = "placa";
const char *WIFI_PASS = "12345678";

const char *ENDPOINT = "http://192.168.2.144:8080/hardware/api?action=telemetry";
const char *DEVICE_CODE = "SPC-DEMO-FICUS-001";
const char *API_KEY     = "67056c1cf743379b4c09b2cf018805e3";

BH1750 lightMeter;
bool bh1750Activo = false;
unsigned long ultimoEnvio = 0;
const unsigned long INTERVALO_ENVIO = 5000; // 5 segundos sin bloquear el loop con delay()
unsigned long ultimoReintentoBH1750 = 0;

int leerHumedadCapacitiva() {
  int raw = analogRead(SOIL_PIN);
  int percent = map(raw, 3200, 1200, 0, 100);
  return constrain(percent, 0, 100);
}

void setup() {
  Serial.begin(115200);
  Serial.println("\n==============================================");
  Serial.println("  SmartPlant CARE - ESP32 NodeMCU-32S Setup   ");
  Serial.println("==============================================");

  // Inicializar I2C a 400kHz (Fast Mode)
  Wire.begin(21, 22);
  Wire.setClock(400000);

  if (lightMeter.begin(BH1750::CONTINUOUS_HIGH_RES_MODE)) {
    bh1750Activo = true;
    Serial.println("✓ Sensor BH1750 inicializado correctamente.");
  } else {
    bh1750Activo = false;
    Serial.println("⚠ BH1750 no detectado en bus I2C.");
  }

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);

  Serial.printf("Conectando a WiFi [%s]", WIFI_SSID);
  int intentos = 0;
  while (WiFi.status() != WL_CONNECTED && intentos < 20) {
    delay(250);
    Serial.print(".");
    intentos++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n[OK] ¡Conectado al WiFi!");
    Serial.print("[OK] IP ESP32: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n[ERROR] No se pudo conectar al WiFi.");
  }
}

void loop() {
  unsigned long ahora = millis();

  // Enviar datos periódicamente usando millis() en vez de delay(5000)
  if (ahora - ultimoEnvio >= INTERVALO_ENVIO) {
    ultimoEnvio = ahora;

    if (WiFi.status() == WL_CONNECTED) {
      int rawAnalog = analogRead(SOIL_PIN);
      int humedadSuelo = leerHumedadCapacitiva();
      
      int luzLux = 0;
      if (bh1750Activo) {
        luzLux = (int)lightMeter.readLightLevel();
        if (luzLux < 0) luzLux = 0;
      } else {
        // Reintentar BH1750 máximo una vez cada 10s para no bloquear el bus I2C
        if (ahora - ultimoReintentoBH1750 >= 10000) {
          ultimoReintentoBH1750 = ahora;
          if (lightMeter.begin(BH1750::CONTINUOUS_HIGH_RES_MODE)) {
            bh1750Activo = true;
            luzLux = (int)lightMeter.readLightLevel();
          }
        }
      }

      Serial.printf("\n[Lectura NodeMCU-32S] Humedad (P32): %d%% (raw %d) | Luz (BH1750): %d lx\n", humedadSuelo, rawAnalog, luzLux);

      #if ARDUINOJSON_VERSION_MAJOR >= 7
        JsonDocument doc;
      #else
        StaticJsonDocument<512> doc;
      #endif

      doc["device_code"]    = DEVICE_CODE;
      doc["api_key"]        = API_KEY;
      doc["humedad_suelo"]  = humedadSuelo;
      doc["luz_ambiental"]  = luzLux;
      doc["bateria"]        = 100;
      doc["ip"]             = WiFi.localIP().toString();

      String body;
      serializeJson(doc, body);

      HTTPClient http;
      http.begin(ENDPOINT);
      http.setTimeout(1500); // Timeout rápido de 1.5s para no congelar si la red/IP falla
      http.addHeader("Content-Type", "application/json");

      int httpCode = http.POST(body);
      if (httpCode > 0) {
        Serial.printf("[Servidor Web] Lecturas enviadas OK (HTTP %d)\n", httpCode);
      } else {
        Serial.printf("[Error HTTP] %s\n", http.errorToString(httpCode).c_str());
      }

      http.end();
    } else {
      Serial.println("[Reconectando WiFi...]");
      WiFi.reconnect();
    }
  }
}

