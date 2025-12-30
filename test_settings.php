<?php

/**
 * Settings Test Page
 * 
 * Quick test to verify settings are loading correctly
 * Access at: http://www.onechurch.com/test_settings.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/ORM.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/core/Settings.php';
require_once __DIR__ . '/core/SettingsHelper.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">

<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Settings Test - AliveChMS</title>
   <style>
      body {
         font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
         max-width: 1200px;
         margin: 40px auto;
         padding: 20px;
         background: #f5f5f5;
      }

      .container {
         background: white;
         padding: 30px;
         border-radius: 8px;
         box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      h1 {
         color: #333;
         border-bottom: 3px solid #0d6efd;
         padding-bottom: 10px;
      }

      h2 {
         color: #666;
         margin-top: 30px;
      }

      .test-section {
         margin: 20px 0;
         padding: 15px;
         background: #f8f9fa;
         border-left: 4px solid #0d6efd;
      }

      .success {
         color: #198754;
         font-weight: bold;
      }

      .error {
         color: #dc3545;
         font-weight: bold;
      }

      table {
         width: 100%;
         border-collapse: collapse;
         margin: 15px 0;
      }

      th,
      td {
         padding: 12px;
         text-align: left;
         border-bottom: 1px solid #ddd;
      }

      th {
         background: #0d6efd;
         color: white;
      }

      tr:hover {
         background: #f8f9fa;
      }

      code {
         background: #e9ecef;
         padding: 2px 6px;
         border-radius: 3px;
         font-family: 'Courier New', monospace;
      }

      .api-test {
         margin: 20px 0;
         padding: 15px;
         background: #fff3cd;
         border-left: 4px solid #ffc107;
      }
   </style>
</head>

<body>
   <div class="container">
      <h1>🔧 AliveChMS Settings Test</h1>
      <p>This page tests if the settings system is working correctly.</p>

      <!-- Test 1: Database Connection -->
      <div class="test-section">
         <h2>1. Database Connection</h2>
         <?php
         try {
            $db = Database::getInstance();
            echo '<p class="success">✓ Database connection successful</p>';
         } catch (Exception $e) {
            echo '<p class="error">✗ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
         }
         ?>
      </div>

      <!-- Test 2: Settings Table -->
      <div class="test-section">
         <h2>2. Settings Table</h2>
         <?php
         try {
            $orm = new ORM();
            $settings = $orm->getAll('settings');
            echo '<p class="success">✓ Settings table exists with ' . count($settings) . ' records</p>';

            if (count($settings) === 0) {
               echo '<p class="error">⚠ Warning: Settings table is empty. Run initial_setup.sql to populate it.</p>';
            }
         } catch (Exception $e) {
            echo '<p class="error">✗ Settings table error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p>Run <code>initial_setup.sql</code> to create the settings table.</p>';
         }
         ?>
      </div>

      <!-- Test 3: SettingsHelper -->
      <div class="test-section">
         <h2>3. SettingsHelper Functions</h2>
         <?php
         try {
            $churchName = SettingsHelper::getChurchName();
            $currency = SettingsHelper::getCurrencySymbol();
            $timezone = SettingsHelper::getTimezone();

            echo '<p class="success">✓ SettingsHelper working correctly</p>';
            echo '<table>';
            echo '<tr><th>Setting</th><th>Value</th></tr>';
            echo '<tr><td>Church Name</td><td>' . htmlspecialchars($churchName) . '</td></tr>';
            echo '<tr><td>Currency Symbol</td><td>' . htmlspecialchars($currency) . '</td></tr>';
            echo '<tr><td>Timezone</td><td>' . htmlspecialchars($timezone) . '</td></tr>';
            echo '<tr><td>Date Format</td><td>' . htmlspecialchars(SettingsHelper::getDateFormat()) . '</td></tr>';
            echo '<tr><td>Language</td><td>' . htmlspecialchars(SettingsHelper::getLanguage()) . '</td></tr>';
            echo '</table>';
         } catch (Exception $e) {
            echo '<p class="error">✗ SettingsHelper error: ' . htmlspecialchars($e->getMessage()) . '</p>';
         }
         ?>
      </div>

      <!-- Test 4: All Settings -->
      <div class="test-section">
         <h2>4. All Settings in Database</h2>
         <?php
         try {
            $allSettings = Settings::getAll();
            echo '<p class="success">✓ Retrieved ' . count($allSettings) . ' settings</p>';

            if (count($allSettings) > 0) {
               echo '<table>';
               echo '<tr><th>Key</th><th>Value</th><th>Type</th><th>Category</th></tr>';
               foreach ($allSettings as $setting) {
                  echo '<tr>';
                  echo '<td><code>' . htmlspecialchars($setting['key']) . '</code></td>';
                  echo '<td>' . htmlspecialchars(substr((string)$setting['value'], 0, 50)) . '</td>';
                  echo '<td>' . htmlspecialchars($setting['type']) . '</td>';
                  echo '<td>' . htmlspecialchars($setting['category']) . '</td>';
                  echo '</tr>';
               }
               echo '</table>';
            }
         } catch (Exception $e) {
            echo '<p class="error">✗ Error retrieving settings: ' . htmlspecialchars($e->getMessage()) . '</p>';
         }
         ?>
      </div>

      <!-- Test 5: API Endpoint -->
      <div class="api-test">
         <h2>5. Public API Endpoint Test</h2>
         <p>Test the <code>/public/settings</code> API endpoint:</p>
         <p><strong>URL:</strong> <a href="<?= $_ENV['APP_URL'] ?? 'http://www.onechurch.com' ?>/public/settings" target="_blank">
               <?= $_ENV['APP_URL'] ?? 'http://www.onechurch.com' ?>/public/settings
            </a></p>
         <p>Click the link above to test the API endpoint. It should return JSON with public settings.</p>

         <h3>JavaScript Test:</h3>
         <button onclick="testAPI()" style="padding: 10px 20px; background: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer;">
            Test API Endpoint
         </button>
         <div id="apiResult" style="margin-top: 15px; padding: 15px; background: white; border-radius: 4px; display: none;">
            <h4>API Response:</h4>
            <pre id="apiResponse" style="background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto;"></pre>
         </div>
      </div>

      <!-- Test 6: Currency Formatting -->
      <div class="test-section">
         <h2>6. Currency Formatting</h2>
         <?php
         try {
            $amount = 1234.56;
            $formatted = SettingsHelper::formatCurrency($amount);
            echo '<p class="success">✓ Currency formatting working</p>';
            echo '<p>Amount: <code>' . $amount . '</code> → Formatted: <strong>' . htmlspecialchars($formatted) . '</strong></p>';
         } catch (Exception $e) {
            echo '<p class="error">✗ Currency formatting error: ' . htmlspecialchars($e->getMessage()) . '</p>';
         }
         ?>
      </div>

      <!-- Summary -->
      <div class="test-section" style="border-left-color: #198754;">
         <h2>✅ Summary</h2>
         <p>If all tests above show <span class="success">✓</span>, your settings system is working correctly!</p>
         <p><strong>Next Steps:</strong></p>
         <ol>
            <li>If settings table is empty, run <code>initial_setup.sql</code></li>
            <li>Update settings in the dashboard at <code>/public/dashboard/settings.php</code></li>
            <li>Test the API endpoint by clicking the button above</li>
            <li>Delete this test file (<code>test_settings.php</code>) when done</li>
         </ol>
      </div>
   </div>

   <script>
      async function testAPI() {
         const resultDiv = document.getElementById('apiResult');
         const responseDiv = document.getElementById('apiResponse');

         resultDiv.style.display = 'block';
         responseDiv.textContent = 'Loading...';

         try {
            const response = await fetch('<?= $_ENV['APP_URL'] ?? 'http://www.onechurch.com' ?>/public/settings');
            const data = await response.json();

            responseDiv.textContent = JSON.stringify(data, null, 2);
            responseDiv.style.color = response.ok ? '#198754' : '#dc3545';
         } catch (error) {
            responseDiv.textContent = 'Error: ' + error.message;
            responseDiv.style.color = '#dc3545';
         }
      }
   </script>
</body>

</html>