#include <SoftwareSerial.h>

#include <ESP8266HTTPClient.h>
#include <ESP8266WiFi.h>

#include <ArduinoJson.h>

// Define custom RX and TX pins
#define SCANNER_RX_PIN D15
#define SCANNER_TX_PIN D12


SoftwareSerial scannerSerial(SCANNER_RX_PIN, SCANNER_TX_PIN); // RX, 

const char* ssid = "ssid";
const char* password = "password";

String dataResult;

const char* apiEndpoint = "http://ip_address/QRAMS/api/api.php";

const char* api_key = "api_key";

const int connectionLED = D8;
const int readyLED = D7;

void setup()
{
  Serial.begin(9600);
  Serial.begin(19200); // SIM900
  scannerSerial.begin(9600);


  pinMode(connectionLED, OUTPUT);
  pinMode(readyLED, OUTPUT);
  
  // Connect to WiFi network
  connectToWiFi();
}
 
String receivedData = ""; // Global variable to store received data
bool isProcessingJob = false;

void loop() {
  // Read from the SoftwareSerial port
  while (scannerSerial.available()) {
    digitalWrite(readyLED, HIGH);
    char receivedChar = scannerSerial.read();
    if (receivedChar != '\n' && receivedChar != '\r') { // Ignore newline characters
      receivedData += receivedChar;
    }
  }

  // If data has been received and the length is 12 characters
  if (receivedData.length() == 12) {
    // Print the entire string
    Serial.print("LRN: ");
    Serial.println(receivedData);

    // Add the received data
    addData(receivedData);

    // Clear the receivedData variable
    receivedData = "";
  } else {
    receivedData = "";
    Serial.println(receivedData);
  }

  // If no data is being processed, turn off the readyLED
  if (!isProcessingJob) {
    digitalWrite(readyLED, LOW);
  }

  delay(500);
}


void addData(String data) {
  WiFiClient client;  // Use WiFiClient instead of HTTPClient

  HTTPClient http;

  // Your server URL
  String url = String(apiEndpoint) + "?action=add&id=" + data + "&api_key=" + api_key;

  http.begin(client, url);

  int httpCode = http.GET();
  if (httpCode > 0) {
    String payload = http.getString();

    // Parsing the JSON payload
    StaticJsonDocument<200> doc; // Adjust the size based on your payload
    DeserializationError error = deserializeJson(doc, payload);
    if (error) {
      Serial.print(F("deserializeJson() failed: "));
      Serial.println(error.f_str());
      return;
    }

    const char* cas = doc["case"];

    if (strcmp(cas, "1") == 0) {
      const char* phone_number = doc["phone_number"];
      const char* sms = doc["SMS"];
      Serial.println(phone_number);

      sendSMS(phone_number, sms);

    } else if (strcmp(cas, "2") == 0) {
      const char* msg = doc["msg"];
      Serial.println(msg);
    }

    Serial.println(payload);

  } else {
    Serial.println("Error on HTTP request");
  }

  http.end();
}

void sendSMS(String phone_number, String msg) {
  Serial.print("\r");
  delay(1000);                    //Wait for a second while the modem sends an "OK"
  Serial.print("AT+CMGF=1\r");    //Because we want to send the SMS in text mode
  delay(1000);

  Serial.print("AT+CMGS=\""+phone_number+"\"\r");    //Start accepting the text for the message

  delay(1000);
  Serial.print(msg +"\r");   //The text for the message
  delay(1000);
  Serial.write(0x1A);  //Equivalent to sending Ctrl+Z 
}



void connectToWiFi()
{
  Serial.println();
  Serial.print("Connecting to ");
  Serial.println(ssid);

  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED)
  {
    delay(500);
    Serial.print(".");
  }

  Serial.println("");
  Serial.println("WiFi connected");
  Serial.print("IP address: ");                   
  Serial.print(WiFi.localIP());

  digitalWrite(connectionLED, HIGH);

}
