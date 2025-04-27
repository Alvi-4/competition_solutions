# competition_solutions
# arduino codes for user end
#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

// WiFi credentials
const char* ssid = "YOUR_WIFI_SSID";
const char* password = "YOUR_WIFI_PASSWORD";

// Server details
const char* serverUrl = "http://your-server-address/api/orders";
String apiKey = "YOUR_API_KEY"; // For authentication

// Button pins
#define BUTTON_1 12
#define BUTTON_2 14
#define BUTTON_3 27
#define BUTTON_4 26

// Button states
int button1State = 0;
int button2State = 0;
int button3State = 0;
int button4State = 0;
int lastButton2State = 0;
unsigned long button2PressTime = 0;
const long longPressTime = 1000; // 1 second for long press

// Menu system
String menuItems[] = {"Cheeseburger", "Margherita Pizza", "Spaghetti", "Caesar Salad", "Soda", "Chocolate Cake"};
float menuPrices[] = {8.99, 12.99, 10.50, 7.99, 2.50, 5.99};
int menuSize = 6;
int currentMenuItem = 0;
int currentPage = 0; // 0=menu, 1=quantity, 2=cart, 3=order confirmation

// Cart system
String cartItems[10];
int cartQuantities[10];
float cartPrices[10];
int cartSize = 0;
int currentQuantity = 1;

// Table number (would be set per device)
int tableNumber = 1;

// Connection status
bool wifiConnected = false;
unsigned long lastConnectionAttempt = 0;
const long connectionInterval = 30000; // Try to reconnect every 30 seconds

void setup() {
  Serial.begin(115200);
  
  // Initialize display
  if(!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println(F("SSD1306 allocation failed"));
    for(;;);
  }
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(WHITE);
  
  // Initialize buttons
  pinMode(BUTTON_1, INPUT_PULLUP);
  pinMode(BUTTON_2, INPUT_PULLUP);
  pinMode(BUTTON_3, INPUT_PULLUP);
  pinMode(BUTTON_4, INPUT_PULLUP);
  
  // Connect to WiFi
  connectToWiFi();
  
  // Show welcome screen
  displayWelcomeScreen();
  delay(2000);
  showMainMenu();
}

void loop() {
  // Maintain WiFi connection
  if (WiFi.status() != WL_CONNECTED && millis() - lastConnectionAttempt > connectionInterval) {
    connectToWiFi();
  }
  
  // Read button states
  button1State = digitalRead(BUTTON_1);
  button2State = digitalRead(BUTTON_2);
  button3State = digitalRead(BUTTON_3);
  button4State = digitalRead(BUTTON_4);
  
  // Handle button 1 (menu/reset)
  if (button1State == LOW) {
    delay(200); // debounce
    if (currentPage != 0) {
      // Reset to main menu
      currentPage = 0;
      showMainMenu();
    }
  }
  
  // Handle button 2 (select/confirm)
  if (button2State == LOW && lastButton2State == HIGH) {
    button2PressTime = millis();
  }
  
  if (button2State == HIGH && lastButton2State == LOW) {
    // Button 2 released
    if (millis() - button2PressTime < longPressTime) {
      // Short press
      if (currentPage == 0) {
        // Menu selection
        currentPage = 1;
        currentQuantity = 1;
        showQuantityScreen();
      } else if (currentPage == 1) {
        // Add to cart
        addToCart();
        currentPage = 0;
        showMainMenu();
      } else if (currentPage == 2) {
        // Show order confirmation (would actually send to cloud)
        currentPage = 3;
        showOrderConfirmation();
      }
    } else {
      // Long press - submit order from cart view
      if (currentPage == 2) {
        if (WiFi.status() == WL_CONNECTED) {
          sendOrderToServer();
        } else {
          displayErrorMessage("No WiFi Connection!");
          delay(2000);
          showCart();
          return;
        }
        currentPage = 3;
        showOrderConfirmation();
      }
    }
  }
  lastButton2State = button2State;
  
  // Handle button 3 (up/increment)
  if (button3State == LOW) {
    delay(200); // debounce
    if (currentPage == 0) {
      // Menu navigation up
      currentMenuItem = (currentMenuItem - 1 + menuSize) % menuSize;
      showMainMenu();
    } else if (currentPage == 1) {
      // Quantity increment
      currentQuantity++;
      showQuantityScreen();
    } else if (currentPage == 2) {
      // Cart view - could implement navigation if needed
    }
  }
  
  // Handle button 4 (down/decrement)
  if (button4State == LOW) {
    delay(200); // debounce
    if (currentPage == 0) {
      // Menu navigation down
      currentMenuItem = (currentMenuItem + 1) % menuSize;
      showMainMenu();
    } else if (currentPage == 1) {
      // Quantity decrement (minimum 1)
      if (currentQuantity > 1) {
        currentQuantity--;
        showQuantityScreen();
      }
    } else if (currentPage == 2) {
      // Cart view - could implement navigation if needed
    }
  }
  
  delay(50);
}

void connectToWiFi() {
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println("Connecting to WiFi...");
  display.display();
  
  WiFi.begin(ssid, password);
  Serial.println("Connecting to WiFi...");
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 20) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    wifiConnected = true;
    Serial.println("");
    Serial.println("WiFi connected");
    Serial.println("IP address: ");
    Serial.println(WiFi.localIP());
    
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Connected!");
    display.println("IP: " + WiFi.localIP().toString());
    display.display();
    delay(1000);
  } else {
    wifiConnected = false;
    Serial.println("WiFi connection failed");
    
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println("WiFi Failed!");
    display.println("Offline Mode");
    display.println("Orders will be queued");
    display.display();
    delay(1000);
  }
  
  lastConnectionAttempt = millis();
}

void displayWelcomeScreen() {
  display.clearDisplay();
  display.setCursor(0,0);
  display.setTextSize(2);
  display.println("Bistro 92");
  display.setTextSize(1);
  display.println("\nSmart Ordering System");
  display.println("\nTable #" + String(tableNumber));
  display.display();
}

void showMainMenu() {
  display.clearDisplay();
  display.setCursor(0,0);
  display.println("MAIN MENU");
  display.drawLine(0, 10, 128, 10, WHITE);
  
  // Display connection status
  display.setCursor(90, 0);
  display.println(WiFi.status() == WL_CONNECTED ? "Online" : "Offline");
  
  for (int i = 0; i < menuSize; i++) {
    if (i == currentMenuItem) {
      display.print("> ");
    } else {
      display.print("  ");
    }
    display.print(menuItems[i]);
    
    // Right-align the price
    String priceStr = "$" + String(menuPrices[i], 2);
    int xPos = 128 - (priceStr.length() * 6); // 6 pixels per character
    display.setCursor(xPos, 10 + (i * 8));
    display.println(priceStr);
  }
  
  display.display();
}

void showQuantityScreen() {
  display.clearDisplay();
  display.setCursor(0,0);
  display.println("QUANTITY");
  display.drawLine(0, 10, 128, 10, WHITE);
  
  display.println("\nItem: " + menuItems[currentMenuItem]);
  display.println("Price: $" + String(menuPrices[currentMenuItem], 2));
  display.println("Qty: " + String(currentQuantity));
  
  float total = menuPrices[currentMenuItem] * currentQuantity;
  display.println("Total: $" + String(total, 2));
  
  display.println("\nPress to confirm");
  
  display.display();
}

void addToCart() {
  // Simple implementation - would need to handle duplicates in real system
  if (cartSize < 10) {
    cartItems[cartSize] = menuItems[currentMenuItem];
    cartQuantities[cartSize] = currentQuantity;
    cartPrices[cartSize] = menuPrices[currentMenuItem];
    cartSize++;
    showCart();
  }
}

void showCart() {
  currentPage = 2;
  display.clearDisplay();
  display.setCursor(0,0);
  display.println("YOUR CART");
  display.drawLine(0, 10, 128, 10, WHITE);
  
  float total = 0;
  for (int i = 0; i < cartSize; i++) {
    display.print(cartItems[i] + " x" + String(cartQuantities[i]));
    
    // Right-align the price
    float itemTotal = cartPrices[i] * cartQuantities[i];
    total += itemTotal;
    String priceStr = "$" + String(itemTotal, 2);
    int xPos = 128 - (priceStr.length() * 6); // 6 pixels per character
    display.setCursor(xPos, 10 + (i * 8));
    display.println(priceStr);
  }
  
  // Display total
  display.drawLine(0, 10 + (cartSize * 8), 128, 10 + (cartSize * 8), WHITE);
  display.setCursor(0, 12 + (cartSize * 8));
  display.print("TOTAL: ");
  display.setCursor(128 - (String(total, 2).length() * 6) - 6, 12 + (cartSize * 8));
  display.println("$" + String(total, 2));
  
  display.println("\nLong press to order");
  display.println("Short press to add more");
  
  display.display();
}

void showOrderConfirmation() {
  display.clearDisplay();
  display.setCursor(0,0);
  display.println("ORDER SENT!");
  display.drawLine(0, 10, 128, 10, WHITE);
  
  display.println("\nTable #" + String(tableNumber));
  display.println("\nYour order is being prepared");
  
  if (WiFi.status() != WL_CONNECTED) {
    display.println("\n(Offline - will send when connected)");
  }
  
  display.display();
  delay(3000);
  
  // Reset cart for next order
  cartSize = 0;
  
  showMainMenu();
  currentPage = 0;
}

void sendOrderToServer() {
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    
    // Create JSON payload
    String payload = "{\"table\": " + String(tableNumber) + ", \"items\": [";
    for (int i = 0; i < cartSize; i++) {
      payload += "{\"name\": \"" + cartItems[i] + "\", \"quantity\": " + String(cartQuantities[i]) + ", \"price\": " + String(cartPrices[i]) + "}";
      if (i < cartSize - 1) payload += ",";
    }
    payload += "]}";
    
    http.begin(serverUrl);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Authorization", "Bearer " + apiKey);
    
    int httpResponseCode = http.POST(payload);
    
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println(httpResponseCode);
      Serial.println(response);
    } else {
      Serial.print("Error on sending POST: ");
      Serial.println(httpResponseCode);
      displayErrorMessage("Server Error: " + String(httpResponseCode));
      delay(2000);
      showCart();
      return;
    }
    
    http.end();
  } else {
    // In a real implementation, you would queue orders for when connection is restored
    displayErrorMessage("No Connection! Retrying...");
    connectToWiFi();
    if (WiFi.status() == WL_CONNECTED) {
      sendOrderToServer(); // Retry
    } else {
      delay(2000);
      showCart();
      return;
    }
  }
}

void displayErrorMessage(String message) {
  display.clearDisplay();
  display.setCursor(0,0);
  display.println("ERROR");
  display.drawLine(0, 10, 128, 10, WHITE);
  
  display.println("\n" + message);
  display.println("\nPress any button");
  
  display.display();
}
