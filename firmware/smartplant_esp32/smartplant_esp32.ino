/*
  SmartPlant CARE - ESP32 Firmware NodeMCU-32S (Optimizado)
  ============================================
  Sensores incluidos:
  - Sensor de humedad capacitivo (Conectado a P32 / GPIO 32)
  - Sensor de luz BH-1750 (I2C: SDA P21, SCL P22)
  - Sensor DHT22 - temperatura y humedad ambiente (GPIO 4)
  - Sensor de pH PH-4502C (salida analógica en GPIO 34)

  Conexiones NodeMCU-32S:
  - Humedad Capacitivo: VCC -> 3.3V | GND -> GND | AO -> P32
  - Sensor BH-1750: VCC -> VIN (o 5V) | GND -> GND | SDA -> P21 | SCL -> P22
  - DHT22: VCC -> 3.3V | GND -> GND | DATA -> P4 (con resistencia pull-up de 10k a 3.3V)
  - PH-4502C: VCC -> 5V | GND -> GND | Po (salida analógica) -> P34

  IMPORTANTE: el sensor PH-4502C necesita calibrarse con soluciones buffer de
  pH 7 y pH 4 (ver PH_NEUTRAL_VOLTAGE y PH_SLOPE_V_PER_PH_UNIT más abajo).
  Requiere instalar la librería "DHT sensor library" (Adafruit) desde el
  Arduino Library Manager, además de "Adafruit Unified Sensor".
*/

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <Wire.h>
#include <BH1750.h>
#include <DHT.h>

#define SOIL_PIN 32 // Pin P32 en NodeMCU-32S
#define DHT_PIN 4   // Pin P4 en NodeMCU-32S
#define DHT_TYPE DHT22
#define PH_PIN 34   // Pin P34 (ADC1, solo entrada) en NodeMCU-32S

// ── Calibración del sensor de pH (ajustar con buffers reales) ──
const float ADC_MAX_VALUE = 4095.0;     // ESP32 ADC de 12 bits
const float ADC_REF_VOLTAGE = 3.3;      // Referencia de tensión del ADC
const float PH_NEUTRAL_VOLTAGE = 2.5;   // Tensión medida en buffer pH 7.0
const float PH_SLOPE_V_PER_PH_UNIT = 0.18; // Variación de tensión por unidad de pH (calibrar con buffer pH 4.0)

const char *WIFI_SSID = "placa";
const char *WIFI_PASS = "12345678";

const char *ENDPOINT = "http://192.168.2.144:8080/hardware/api?action=telemetry";
const char *DEVICE_CODE = "SPC-DEMO-FICUS-001";
const char *API_KEY     = "67056c1cf743379b4c09b2cf018805e3";

BH1750 lightMeter;
DHT dht(DHT_PIN, DHT_TYPE);
bool bh1750Activo = false;
unsigned long ultimoEnvio = 0;
const unsigned long INTERVALO_ENVIO = 5000; // 5 segundos sin bloquear el loop con delay()
unsigned long ultimoReintentoBH1750 = 0;

// Últimos valores válidos del DHT22 (se conservan si una lectura falla)
float ultimaTemperatura = 24.0;
float ultimaHumedadAmbiente = 55.0;

int leerHumedadCapacitiva() {
  int raw = analogRead(SOIL_PIN);
  int percent = map(raw, 3200, 1200, 0, 100);
  return constrain(percent, 0, 100);
}

void leerDHT22(float &temperatura, float &humedadAmbiente) {
  float t = dht.readTemperature();
  float h = dht.readHumidity();

  if (!isnan(t)) ultimaTemperatura = t;
  if (!isnan(h)) ultimaHumedadAmbiente = h;

  temperatura = ultimaTemperatura;
  humedadAmbiente = ultimaHumedadAmbiente;
}

float leerPH() {
  int raw = analogRead(PH_PIN);
  float voltaje = raw * (ADC_REF_VOLTAGE / ADC_MAX_VALUE);
  float ph = 7.0 - ((voltaje - PH_NEUTRAL_VOLTAGE) / PH_SLOPE_V_PER_PH_UNIT);
  return constrain(ph, 0.0, 14.0);
}

void setup() {
  Serial.begin(115200);
  Serial.println("\n==============================================");
  Serial.println("  SmartPlant CARE - ESP32 NodeMCU-32S Setup   ");
  Serial.println("==============================================");

  // Inicializar I2C a 400kHz (Fast Mode)
  Wire.begin(21, 22);
  Wire.setClock(400000);

  dht.begin();

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

      float temperatura = 0;
      float humedadAmbiente = 0;
      leerDHT22(temperatura, humedadAmbiente);

      float ph = leerPH();

      Serial.printf(
        "\n[Lectura NodeMCU-32S] Humedad (P32): %d%% (raw %d) | Luz (BH1750): %d lx | Temp: %.1fC | Hum. amb: %.1f%% | pH: %.2f\n",
        humedadSuelo, rawAnalog, luzLux, temperatura, humedadAmbiente, ph
      );

      #if ARDUINOJSON_VERSION_MAJOR >= 7
        JsonDocument doc;
      #else
        StaticJsonDocument<512> doc;
      #endif

      doc["device_code"]       = DEVICE_CODE;
      doc["api_key"]           = API_KEY;
      doc["humedad_suelo"]     = humedadSuelo;
      doc["luz_ambiental"]     = luzLux;
      doc["temperatura"]       = temperatura;
      doc["humedad_ambiente"]  = humedadAmbiente;
      doc["ph"]                = ph;
      doc["bateria"]           = 100;
      doc["ip"]                = WiFi.localIP().toString();

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

