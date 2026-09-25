/*
  SmartPlant CARE - Prueba individual del módulo relé
  ====================================================
  Sketch mínimo para comprobar SOLO el relé, sin WiFi ni sensores.

  Conexión (NodeMCU-32S):
    Módulo relé: VCC -> VIN (5V) | GND -> GND | IN -> P26
    Para la prueba NO hace falta conectar la bomba: alcanza con escuchar el "clic"
    y ver el LED del módulo. Si querés probarla también:
      (+) de la fuente 5V -> COM | NO -> (+) de la bomba | (-) de la bomba -> (-) de la fuente

  Cómo usarlo:
    1. Subí este sketch y abrí el Monitor Serie a 115200 baudios.
    2. Al arrancar hace 3 clics automáticos (1 s encendido, 1 s apagado).
    3. Después lo controlás escribiendo en el Monitor Serie y apretando Enter:
         1 -> encender     0 -> apagar     t -> alternar     p -> pulso de 3 s

  Si algo no anda:
    - Hace clic al revés (queda encendido cuando dice APAGADO): cambiá RELAY_ACTIVE_LOW a false.
    - No hace clic ni prende el LED: revisá VCC en VIN (5V, no 3.3V) y que GND sea común.
      Algunos módulos de 5V no se activan con la señal de 3.3V del ESP32; en ese caso
      hace falta un módulo compatible con 3.3V o un transistor en la entrada IN.
*/

#define RELAY_PIN 26

// Igual que en el firmware principal: casi todos los módulos baratos se activan con LOW.
const bool RELAY_ACTIVE_LOW = true;

bool encendido = false;

void setRele(bool on) {
  encendido = on;
  digitalWrite(RELAY_PIN, (on == RELAY_ACTIVE_LOW) ? LOW : HIGH);
  Serial.println(on ? "Relé ENCENDIDO" : "Relé APAGADO");
}

void setup() {
  Serial.begin(115200);

  // Apagado antes de configurar el pin, para que no dé un pulso al arrancar
  digitalWrite(RELAY_PIN, RELAY_ACTIVE_LOW ? HIGH : LOW);
  pinMode(RELAY_PIN, OUTPUT);

  delay(500);
  Serial.println("\n=== Prueba de relé (GPIO 26) ===");
  Serial.println("Autotest: 3 clics...");
  for (int i = 0; i < 3; i++) {
    setRele(true);
    delay(1000);
    setRele(false);
    delay(1000);
  }
  Serial.println("Listo. Escribí 1 (encender), 0 (apagar), t (alternar) o p (pulso de 3 s).");
}

void loop() {
  if (!Serial.available()) return;

  char c = Serial.read();
  if (c == '1') {
    setRele(true);
  } else if (c == '0') {
    setRele(false);
  } else if (c == 't' || c == 'T') {
    setRele(!encendido);
  } else if (c == 'p' || c == 'P') {
    setRele(true);
    delay(3000);
    setRele(false);
  }
}
