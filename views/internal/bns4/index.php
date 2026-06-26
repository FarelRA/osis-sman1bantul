<?php
// Start session for admin authentication
session_start();

// Admin credentials (change these for production)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // Change this!

// Helper function to check if user is authenticated
function isAdminAuthenticated() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

// Handle Admin Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'admin-login') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input && 
        isset($input['username']) && $input['username'] === ADMIN_USERNAME &&
        isset($input['password']) && $input['password'] === ADMIN_PASSWORD) {
        
        $_SESSION['admin_authenticated'] = true;
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
    
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Handle Admin Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'admin-logout') {
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle Admin Get All RSVPs
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api']) && $_GET['api'] === 'admin-rsvps') {
    if (!isAdminAuthenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $dataFile = __DIR__ . '/data/rsvps.json';
    if (!file_exists($dataFile)) {
        if (!is_dir(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
        file_put_contents($dataFile, json_encode([]));
    }
    
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $rsvps]);
    exit;
}

// Handle Admin Delete RSVP
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && isset($_GET['api']) && $_GET['api'] === 'admin-delete-rsvp') {
    if (!isAdminAuthenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    $dataFile = __DIR__ . '/data/rsvps.json';
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    
    $rsvps = array_filter($rsvps, function($rsvp) use ($input) {
        return $rsvp['id'] !== $input['id'];
    });
    
    file_put_contents($dataFile, json_encode(array_values($rsvps), JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle Admin Add RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'admin-add-rsvp') {
    if (!isAdminAuthenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $dataFile = __DIR__ . '/data/rsvps.json';
    if (!file_exists($dataFile)) {
        if (!is_dir(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
        file_put_contents($dataFile, json_encode([]));
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $attendance = $input['attendance'] ?? 'yes';
    
    if (!$input || empty($input['name']) || empty($input['osis'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }
    
    if (($attendance === 'yes' || $attendance === 'maybe') && empty($input['phone'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Phone is required for attendees']);
        exit;
    }
    
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    $newRSVP = [
        'id' => uniqid(),
        'attendance' => htmlspecialchars($input['attendance'] ?? 'yes'),
        'name' => htmlspecialchars($input['name']),
        'osis' => htmlspecialchars($input['osis']),
        'phone' => htmlspecialchars($input['phone'] ?? ''),
        'guests' => max(1, (int)($input['guests'] ?? 1)),
        'message' => htmlspecialchars($input['message'] ?? ''),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $rsvps[] = $newRSVP;
    file_put_contents($dataFile, json_encode($rsvps, JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    http_response_code(201);
    echo json_encode(['success' => true, 'data' => $newRSVP]);
    exit;
}

// Handle Admin Update RSVP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'admin-update-rsvp') {
    if (!isAdminAuthenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'ID is required']);
        exit;
    }
    
    $dataFile = __DIR__ . '/data/rsvps.json';
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    
    $found = false;
    foreach ($rsvps as &$rsvp) {
        if ($rsvp['id'] === $input['id']) {
            // Update fields if provided
            if (isset($input['name'])) $rsvp['name'] = htmlspecialchars($input['name']);
            if (isset($input['osis'])) $rsvp['osis'] = htmlspecialchars($input['osis']);
            if (isset($input['phone'])) $rsvp['phone'] = htmlspecialchars($input['phone']);
            if (isset($input['attendance'])) $rsvp['attendance'] = htmlspecialchars($input['attendance']);
            if (isset($input['guests'])) $rsvp['guests'] = max(1, (int)$input['guests']);
            if (isset($input['message'])) $rsvp['message'] = htmlspecialchars($input['message']);
            
            // Handle check-in/check-out timestamps (allow clearing by setting empty string)
            if (isset($input['checked_in_at'])) {
                $rsvp['checked_in_at'] = empty($input['checked_in_at']) ? null : htmlspecialchars($input['checked_in_at']);
            }
            if (isset($input['checked_out_at'])) {
                $rsvp['checked_out_at'] = empty($input['checked_out_at']) ? null : htmlspecialchars($input['checked_out_at']);
            }
            
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'RSVP not found']);
        exit;
    }
    
    file_put_contents($dataFile, json_encode($rsvps, JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Handle QR Code Scan (Check-in / Check-out)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api']) && $_GET['api'] === 'scan') {
    if (!isAdminAuthenticated()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id']) || empty($input['action'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'ID and action are required']);
        exit;
    }
    
    $action = $input['action'];
    if ($action !== 'checkin' && $action !== 'checkout') {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use "checkin" or "checkout"']);
        exit;
    }
    
    $dataFile = __DIR__ . '/data/rsvps.json';
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    
    $found = false;
    foreach ($rsvps as &$rsvp) {
        if ($rsvp['id'] === $input['id']) {
            if ($action === 'checkin') {
                $rsvp['checked_in_at'] = date('Y-m-d H:i:s');
            } else {
                $rsvp['checked_out_at'] = date('Y-m-d H:i:s');
            }
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'RSVP not found']);
        exit;
    }
    
    file_put_contents($dataFile, json_encode($rsvps, JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'action' => $action, 'timestamp' => date('Y-m-d H:i:s')]);
    exit;
}

// Handle Asset Requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['asset'])) {
    $assetFile = __DIR__ . '/asset/' . basename($_GET['asset']);
    if (file_exists($assetFile)) {
        $mime = mime_content_type($assetFile);
        header('Content-Type: ' . $mime);
        readfile($assetFile);
        exit;
    }
    http_response_code(404);
    exit;
}

// Handle API POST Request (Submit RSVP)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataFile = __DIR__ . '/data/rsvps.json';
    if (!file_exists($dataFile)) {
        if (!is_dir(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
        file_put_contents($dataFile, json_encode([]));
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $attendance = $input['attendance'] ?? 'yes';
    
    if (!$input || empty($input['name']) || empty($input['osis'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input data']);
        exit;
    }
    
    if (($attendance === 'yes' || $attendance === 'maybe') && empty($input['phone'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['error' => 'Phone is required for attendees']);
        exit;
    }
    
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    $newRSVP = [
        'id' => uniqid(),
        'attendance' => htmlspecialchars($input['attendance'] ?? 'yes'),
        'name' => htmlspecialchars($input['name']),
        'osis' => htmlspecialchars($input['osis']),
        'phone' => htmlspecialchars($input['phone']),
        'guests' => max(1, (int)($input['guests'] ?? 1)),
        'message' => htmlspecialchars($input['message'] ?? ''),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $rsvps[] = $newRSVP;
    file_put_contents($dataFile, json_encode($rsvps, JSON_PRETTY_PRINT));
    
    header('Content-Type: application/json');
    http_response_code(201);
    echo json_encode(['success' => true, 'data' => $newRSVP]);
    exit;
}

// Handle API GET Request (Fetch Stats)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['api']) && $_GET['api'] === 'stats') {
    $dataFile = __DIR__ . '/data/rsvps.json';
    if (!file_exists($dataFile)) {
        if (!is_dir(dirname($dataFile))) mkdir(dirname($dataFile), 0777, true);
        file_put_contents($dataFile, json_encode([]));
    }
    
    $rsvps = json_decode(file_get_contents($dataFile), true) ?: [];
    
    // Count ALL form submissions as "Registered" (even 'no' attendants)
    $totalRSVPs = count($rsvps);
    
    // Only sum guests for people actually attending (yes/maybe)
    $totalGuests = array_reduce($rsvps, function($carry, $item) {
        $attending = isset($item['attendance']) ? ($item['attendance'] === 'yes' || $item['attendance'] === 'maybe') : true;
        if ($attending) {
            return $carry + (int)($item['guests'] ?? 0);
        }
        return $carry;
    }, 0);
    
    header('Content-Type: application/json');
    echo json_encode([
        'totalRSVPs' => $totalRSVPs,
        'totalGuests' => $totalGuests
    ]);
    exit;
}

// Handle Admin Page
if (isset($_GET['admin'])) {
    $isAuthenticated = isAdminAuthenticated();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel - Bro &amp; Sis 4</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            curtain: { DEFAULT: '#6B0F1A', dark: '#3D0A10', light: '#8B1A2B', deep: '#2A0508' },
            gold: { DEFAULT: '#C9A84C', light: '#E8D48B', dark: '#A07E2E', pale: '#F5E6B8' },
            cream: { DEFAULT: '#F5ECD7', dark: '#E8DCC0' },
            parchment: '#F2E6C9',
            theatre: { bg: '#0D0507', stage: '#1A0A0E' }
          },
          fontFamily: {
            display: ['Playfair Display', 'serif'],
            body: ['Cormorant Garamond', 'serif'],
            sans: ['Inter', 'sans-serif']
          }
        }
      }
    };
  </script>
  <style>
    [x-cloak] { display: none !important; }
    .glass {
      background: rgba(13, 5, 7, 0.6);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(201, 168, 76, 0.15);
    }
    .parchment {
      background: linear-gradient(175deg, #F5ECD7 0%, #F2E6C9 25%, #E8DCC0 50%, #F2E6C9 75%, #F5ECD7 100%);
      border: 2px solid rgba(160,126,46,0.4);
      box-shadow: inset 0 0 60px rgba(160,120,60,0.15), 0 8px 40px rgba(0,0,0,0.4);
    }
    .text-gradient {
      background: linear-gradient(135deg, #C9A84C 0%, #E8D48B 40%, #C9A84C 60%, #F5E6B8 80%, #C9A84C 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
  </style>
</head>
<body class="bg-theatre-bg text-cream font-body min-h-screen" x-data="adminApp()" x-cloak>
  
  <?php if (!$isAuthenticated): ?>
  <!-- Login Form -->
  <div class="min-h-screen flex items-center justify-center px-4" style="background: linear-gradient(180deg, #0D0507 0%, #1A0A0E 50%, #0D0507 100%);">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="font-display text-4xl font-bold text-gradient mb-2">Admin Panel</h1>
        <p class="text-cream/50 font-body">Bro &amp; Sis 4 - The Greatest Iftar Show</p>
      </div>
      
      <div class="glass rounded-xl p-8 shadow-2xl">
        <form @submit.prevent="login()">
          <div class="mb-6">
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Username</label>
            <input type="text" x-model="loginForm.username" required
                   class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none" />
          </div>
          
          <div class="mb-6">
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Password</label>
            <input type="password" x-model="loginForm.password" required
                   class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none" />
          </div>
          
          <div x-show="loginError" class="mb-4 text-red-400 text-sm text-center" x-text="loginError"></div>
          
          <button type="submit" :disabled="loggingIn"
                  class="w-full py-3 bg-gradient-to-r from-gold-dark via-gold to-gold-light text-theatre-bg font-display font-bold text-lg rounded-lg hover:shadow-[0_0_20px_rgba(201,168,76,0.3)] transition-all disabled:opacity-50">
            <span x-text="loggingIn ? 'Logging in...' : 'Login'"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
  
  <?php else: ?>
  <!-- Admin Dashboard -->
  <div class="min-h-screen" style="background: linear-gradient(180deg, #0D0507 0%, #1A0A0E 50%, #0D0507 100%);">
    
    <!-- Header (Dynamic based on view) -->
    <div class="glass border-b border-gold/10 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-3 md:px-4 py-3 md:py-4">
        <div class="flex items-center justify-between gap-2">
          <!-- Left: Title (changes based on view) -->
          <div class="flex items-center gap-2 md:gap-3 flex-shrink min-w-0">
            <!-- Back button (only in scanner view) -->
            <button x-show="currentView === 'scanner'" @click="currentView = 'list'" 
                    class="px-3 py-2 bg-cream/10 hover:bg-cream/20 text-cream border border-cream/30 rounded-lg transition-all flex items-center gap-2 flex-shrink-0">
              <i class="fa-solid fa-arrow-left"></i>
              <span class="hidden sm:inline">Back</span>
            </button>
            
            <div class="flex-shrink min-w-0">
              <h1 class="font-display text-lg md:text-2xl font-bold text-gradient truncate">
                <span x-show="currentView === 'list'">Admin Panel</span>
                <span x-show="currentView === 'scanner'">QR Scanner</span>
              </h1>
              <p x-show="currentView === 'list'" class="text-cream/40 text-xs md:text-sm font-sans hidden sm:block">Bro &amp; Sis 4 Management</p>
            </div>
          </div>
          
          <!-- Right: Actions (only in list view) -->
          <div x-show="currentView === 'list'" class="flex items-center gap-2 flex-shrink-0">
            <!-- Live Indicator - Compact on mobile -->
            <div class="flex items-center gap-1.5 md:gap-2 px-2 md:px-3 py-1.5 md:py-2 bg-green-900/20 border border-green-700/30 rounded-lg">
              <div class="relative">
                <div class="w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full animate-pulse"></div>
                <div class="absolute inset-0 w-1.5 h-1.5 md:w-2 md:h-2 bg-green-500 rounded-full animate-ping"></div>
              </div>
              <span class="text-green-400 font-sans text-[10px] md:text-xs uppercase tracking-wider">Live</span>
            </div>
            
            <!-- Scanner Button -->
            <button @click="currentView = 'scanner'"
                    class="px-3 md:px-4 py-2 bg-green-700/20 hover:bg-green-700/30 text-green-400 border border-green-700/30 rounded-lg font-sans text-sm transition-all flex items-center gap-2">
              <i class="fa-solid fa-qrcode"></i>
              <span class="hidden sm:inline">Scanner</span>
            </button>
            
            <!-- Desktop: Add & Logout buttons -->
            <button @click="showAddForm = true"
                    class="hidden md:flex px-4 py-2 bg-gold/20 hover:bg-gold/30 text-gold border border-gold/30 rounded-lg font-sans text-sm transition-all items-center gap-2">
              <i class="fa-solid fa-plus"></i>
              <span>Add</span>
            </button>
            
            <button @click="logout()"
                    class="hidden md:flex px-4 py-2 bg-curtain/20 hover:bg-curtain/30 text-cream border border-curtain/30 rounded-lg font-sans text-sm transition-all items-center gap-2">
              <i class="fa-solid fa-right-from-bracket"></i>
              <span>Logout</span>
            </button>
            
            <!-- Mobile: Menu Button -->
            <div class="relative md:hidden" x-data="{ open: false }" @click.away="open = false">
              <button @click="open = !open"
                      class="px-3 py-2 bg-cream/10 hover:bg-cream/20 text-cream border border-cream/30 rounded-lg transition-all">
                <i class="fa-solid fa-bars"></i>
              </button>
              
              <!-- Dropdown Menu -->
              <div x-show="open" 
                   x-transition:enter="transition ease-out duration-100"
                   x-transition:enter-start="transform opacity-0 scale-95"
                   x-transition:enter-end="transform opacity-100 scale-100"
                   x-transition:leave="transition ease-in duration-75"
                   x-transition:leave-start="transform opacity-100 scale-100"
                   x-transition:leave-end="transform opacity-0 scale-95"
                   class="absolute right-0 mt-2 w-48 glass rounded-lg shadow-xl border border-gold/20 overflow-hidden"
                   style="display: none;">
                <button @click="showAddForm = true; open = false"
                        class="w-full px-4 py-3 text-left text-cream hover:bg-gold/10 transition-all flex items-center gap-3 border-b border-cream/10">
                  <i class="fa-solid fa-plus text-gold"></i>
                  <span class="font-sans text-sm">Add Registrant</span>
                </button>
                <button @click="logout()"
                        class="w-full px-4 py-3 text-left text-cream hover:bg-curtain/10 transition-all flex items-center gap-3">
                  <i class="fa-solid fa-right-from-bracket text-curtain-light"></i>
                  <span class="font-sans text-sm">Logout</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- List View (Stats + Tickets) -->
    <div x-show="currentView === 'list'" class="max-w-7xl mx-auto px-3 md:px-4 py-4 md:py-8">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-6 mb-4 md:mb-8">
        <div class="glass rounded-lg md:rounded-xl p-3 md:p-6 text-center">
          <div class="font-display text-2xl md:text-4xl font-bold text-gradient mb-1 md:mb-2" x-text="rsvps.length"></div>
          <div class="font-sans text-xs md:text-sm text-cream/40 uppercase tracking-wider">Total Registrations</div>
        </div>
        <div class="glass rounded-lg md:rounded-xl p-3 md:p-6 text-center">
          <div class="font-display text-2xl md:text-4xl font-bold text-gradient mb-1 md:mb-2" x-text="attendingCount"></div>
          <div class="font-sans text-xs md:text-sm text-cream/40 uppercase tracking-wider">Attending</div>
        </div>
        <div class="glass rounded-lg md:rounded-xl p-3 md:p-6 text-center">
          <div class="font-display text-2xl md:text-4xl font-bold text-gradient mb-1 md:mb-2" x-text="notAttendingCount"></div>
          <div class="font-sans text-xs md:text-sm text-cream/40 uppercase tracking-wider">Not Attending</div>
        </div>
      </div>
      
      <!-- RSVPs List -->
      <div class="space-y-6">
        <template x-for="rsvp in sortedRsvps" :key="rsvp.id">
          <div :class="rsvp.attendance === 'no' ? 'bg-gradient-to-br from-gray-800 to-gray-900 opacity-60' : 'parchment'" 
               class="rounded-xl overflow-hidden shadow-2xl relative"
               :style="rsvp.attendance === 'no' ? 'border: 2px solid rgba(100,100,100,0.4);' : ''">
            
            <!-- NOT ATTENDING Stamp (only for non-attending) -->
            <div x-show="rsvp.attendance === 'no' && editingId !== rsvp.id" 
                 class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-30 pointer-events-none">
              <div class="transform -rotate-12 border-4 border-red-900/60 rounded-lg px-8 py-4 bg-red-950/40">
                <p class="font-display text-4xl font-black text-red-800/80 tracking-wider uppercase">Not Attending</p>
              </div>
            </div>
            
            <!-- CHECKED IN Stamp (only for checked-in attendees) -->
            <div x-show="rsvp.checked_in_at && rsvp.attendance !== 'no' && editingId !== rsvp.id" 
                 class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-30 pointer-events-none">
              <div class="transform rotate-12 border-4 border-green-500/70 rounded-lg px-8 py-4 bg-green-700/40">
                <p class="font-display text-4xl font-black text-green-400/90 tracking-wider uppercase">Checked In</p>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="absolute top-4 right-4 z-10 flex gap-2">
              <!-- Edit Button (shown when not editing) -->
              <button x-show="editingId !== rsvp.id" @click="startEdit(rsvp)"
                      class="w-8 h-8 rounded-full bg-gold/80 hover:bg-gold text-theatre-bg flex items-center justify-center transition-all">
                <i class="fa-solid fa-pen text-sm"></i>
              </button>
              
              <!-- Save Button (shown when editing) -->
              <button x-show="editingId === rsvp.id" @click="saveEdit()" :disabled="editing"
                      class="w-8 h-8 rounded-full bg-green-700/80 hover:bg-green-600 text-cream flex items-center justify-center transition-all disabled:opacity-50">
                <i class="fa-solid fa-check text-sm"></i>
              </button>
              
              <!-- Cancel Button (shown when editing) -->
              <button x-show="editingId === rsvp.id" @click="cancelEdit()"
                      class="w-8 h-8 rounded-full bg-gray-700/80 hover:bg-gray-600 text-cream flex items-center justify-center transition-all">
                <i class="fa-solid fa-times text-sm"></i>
              </button>
              
              <!-- Delete Button -->
              <button @click="deleteRSVP(rsvp.id)" 
                      class="w-8 h-8 rounded-full bg-red-900/80 hover:bg-red-800 text-cream flex items-center justify-center transition-all">
                <i class="fa-solid fa-trash text-sm"></i>
              </button>
            </div>
            
            <!-- Semicircle cutouts -->
            <div class="absolute left-0 top-[68%] -translate-y-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-theatre-bg z-20"
                 :class="rsvp.attendance === 'no' ? 'border-r border-gray-600' : 'border-r border-gold/30'"></div>
            <div class="absolute right-0 top-[68%] -translate-y-1/2 translate-x-1/2 w-8 h-8 rounded-full bg-theatre-bg z-20"
                 :class="rsvp.attendance === 'no' ? 'border-l border-gray-600' : 'border-l border-gold/30'"></div>
            
            <!-- Header -->
            <div class="relative px-6 py-5 text-center" 
                 :style="rsvp.attendance === 'no' ? 'background: linear-gradient(135deg, #1a1a1a, #2a2a2a, #1a1a1a); border-bottom: 2px solid #444;' : 'background: linear-gradient(135deg, #2a050b, #590a14, #2a050b); border-bottom: 2px solid #8B6914;'">
              <p class="font-sans text-[10px] tracking-[0.4em] uppercase mb-1"
                 :class="rsvp.attendance === 'no' ? 'text-gray-500' : 'text-gold'">Admit One &bull; VIP Guest</p>
              <p class="font-display text-2xl font-bold uppercase tracking-wider"
                 :class="rsvp.attendance === 'no' ? 'text-gray-400' : 'text-cream'">The Greatest Iftar Show</p>
            </div>
            
            <!-- Main Body -->
            <div class="px-4 md:px-6 py-4 md:py-6 flex flex-col md:flex-row gap-4 md:gap-6 items-start justify-between">
              
              <!-- Attendee Details -->
              <div class="flex-1 space-y-3 md:space-y-5 w-full">
                <!-- Name Field -->
                <div>
                  <p class="font-sans text-[10px] uppercase tracking-widest"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Guest Name</p>
                  <!-- Display Mode -->
                  <p x-show="editingId !== rsvp.id" class="font-display text-xl md:text-2xl font-bold leading-none mt-1"
                     :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" x-text="rsvp.name"></p>
                  <!-- Edit Mode -->
                  <input x-show="editingId === rsvp.id" type="text" x-model="editForm.name"
                         class="w-full px-3 py-2 mt-1 rounded bg-black/20 border border-gold/30 text-cream font-display text-lg md:text-xl font-bold focus:border-gold/50 focus:outline-none" />
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <!-- OSIS Field -->
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest"
                       :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Generation</p>
                    <!-- Display Mode -->
                    <p x-show="editingId !== rsvp.id" class="font-display text-lg font-bold leading-tight mt-0.5"
                       :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'">OSIS <span x-text="rsvp.osis"></span></p>
                    <!-- Edit Mode -->
                    <select x-show="editingId === rsvp.id" x-model="editForm.osis"
                            class="w-full px-3 py-2 mt-0.5 rounded bg-black/20 border border-gold/30 text-cream font-display text-base font-bold focus:border-gold/50 focus:outline-none">
                      <option value="60">60</option>
                      <option value="61">61</option>
                      <option value="62">62</option>
                    </select>
                  </div>
                  
                  <!-- Guests Field -->
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest"
                       :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Admitting</p>
                    <!-- Display Mode -->
                    <p x-show="editingId !== rsvp.id" class="font-display text-lg font-bold leading-tight mt-0.5"
                       :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'">
                      <span x-text="rsvp.guests"></span> 
                      <span class="text-xs font-body font-normal uppercase tracking-wider"
                            :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Seat(s)</span>
                    </p>
                    <!-- Edit Mode -->
                    <input x-show="editingId === rsvp.id" type="number" x-model.number="editForm.guests" min="1" max="10"
                           class="w-full px-3 py-2 mt-0.5 rounded bg-black/20 border border-gold/30 text-cream font-display text-base font-bold focus:border-gold/50 focus:outline-none" />
                  </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <!-- Phone Field -->
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest"
                       :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Contact</p>
                    <!-- Display Mode -->
                    <p x-show="editingId !== rsvp.id" class="font-display text-base font-bold leading-none mt-1"
                       :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" x-text="rsvp.phone"></p>
                    <!-- Edit Mode -->
                    <input x-show="editingId === rsvp.id" type="tel" x-model="editForm.phone"
                           class="w-full px-3 py-2 mt-1 rounded bg-black/20 border border-gold/30 text-cream font-display text-sm font-bold focus:border-gold/50 focus:outline-none" />
                  </div>
                  
                  <!-- Attendance Field -->
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest"
                       :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Attendance</p>
                    <!-- Display Mode -->
                    <p x-show="editingId !== rsvp.id" class="font-display text-base font-bold leading-none mt-1 capitalize"
                       :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" x-text="rsvp.attendance"></p>
                    <!-- Edit Mode -->
                    <select x-show="editingId === rsvp.id" x-model="editForm.attendance"
                            class="w-full px-3 py-2 mt-1 rounded bg-black/20 border border-gold/30 text-cream font-display text-sm font-bold capitalize focus:border-gold/50 focus:outline-none">
                      <option value="yes">Yes</option>
                      <option value="maybe">Maybe</option>
                      <option value="no">No</option>
                    </select>
                  </div>
                </div>
                
                <!-- Message Field -->
                <div>
                  <p class="font-sans text-[10px] uppercase tracking-widest"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Message</p>
                  <!-- Display Mode -->
                  <p x-show="editingId !== rsvp.id" 
                     class="font-body text-sm leading-relaxed mt-1"
                     :class="rsvp.message ? '' : 'italic opacity-50'"
                     :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" 
                     x-text="rsvp.message || 'No message'"></p>
                  <!-- Edit Mode -->
                  <textarea x-show="editingId === rsvp.id" x-model="editForm.message" rows="3"
                            class="w-full px-3 py-2 mt-1 rounded bg-black/20 border border-gold/30 text-cream font-body text-sm resize-none focus:border-gold/50 focus:outline-none"></textarea>
                </div>
                
                <!-- Registered (Not Editable) -->
                <div>
                  <p class="font-sans text-[10px] uppercase tracking-widest"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Registered</p>
                  <p class="font-mono text-xs leading-none mt-1"
                     :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" x-text="rsvp.timestamp"></p>
                </div>
                
                <!-- Check-in / Check-out Status -->
                <div class="grid grid-cols-2 gap-4">
                  <!-- Check-in Status -->
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest"
                       :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Checked In</p>
                    <!-- Display Mode -->
                    <p x-show="editingId !== rsvp.id" 
                       class="font-mono text-xs leading-none mt-1"
                       :class="rsvp.checked_in_at ? '' : 'italic opacity-50'"
                       :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" 
                       x-text="rsvp.checked_in_at || 'Not yet'"></p>
                    <!-- Edit Mode -->
                    <input x-show="editingId === rsvp.id" type="text" x-model="editForm.checked_in_at" placeholder="Leave empty to clear"
                           class="w-full px-2 py-1 mt-1 rounded bg-black/20 border border-gold/30 text-cream font-mono text-xs focus:border-gold/50 focus:outline-none" />
                  </div>
                  
                  <!-- Check-out Status -->
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest"
                       :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Checked Out</p>
                    <!-- Display Mode -->
                    <p x-show="editingId !== rsvp.id" 
                       class="font-mono text-xs leading-none mt-1"
                       :class="rsvp.checked_out_at ? '' : 'italic opacity-50'"
                       :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" 
                       x-text="rsvp.checked_out_at || 'Not yet'"></p>
                    <!-- Edit Mode -->
                    <input x-show="editingId === rsvp.id" type="text" x-model="editForm.checked_out_at" placeholder="Leave empty to clear"
                           class="w-full px-2 py-1 mt-1 rounded bg-black/20 border border-gold/30 text-cream font-mono text-xs focus:border-gold/50 focus:outline-none" />
                  </div>
                </div>
                
                <!-- Edit Error Message -->
                <div x-show="editingId === rsvp.id && editError" class="text-red-400 text-sm" x-text="editError"></div>
              </div>
              
              <!-- QR Code -->
              <div class="flex flex-col items-center justify-center shrink-0 md:border-l-2 border-dashed md:pl-6"
                   :class="rsvp.attendance === 'no' ? 'border-gray-600' : 'border-gold/30'">
                <div class="bg-white p-2 rounded-lg shadow-md mb-2"
                     :class="rsvp.attendance === 'no' ? 'border border-gray-500 opacity-50' : 'border border-gold/40'">
                  <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=120x120&color=3D0A10&data=' + rsvp.id" :alt="'QR Code for ' + rsvp.name" class="w-28 h-28" />
                </div>
                <p class="font-mono text-[9px] tracking-widest font-bold"
                   :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'" x-text="rsvp.id"></p>
              </div>
            </div>
            
            <!-- Dashed Line -->
            <div class="relative flex items-center w-full px-4">
              <div class="h-[2px] w-full border-t-2 border-dashed"
                   :class="rsvp.attendance === 'no' ? 'border-gray-600' : 'border-gold/40'"></div>
            </div>
            
            <!-- Footer -->
            <div class="px-6 py-5"
                 :class="rsvp.attendance === 'no' ? 'bg-black/30' : 'bg-black/5'">
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="border-r pr-2"
                     :class="rsvp.attendance === 'no' ? 'border-gray-600' : 'border-gold/20'">
                  <i class="fa-regular fa-calendar-days text-base mb-1"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'"></i>
                  <p class="font-sans text-[9px] uppercase tracking-wider"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Date</p>
                  <p class="font-display text-sm font-bold leading-tight mt-0.5"
                     :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'">Sat, 7 Mar 2026</p>
                </div>
                <div class="border-r px-2"
                     :class="rsvp.attendance === 'no' ? 'border-gray-600' : 'border-gold/20'">
                  <i class="fa-regular fa-clock text-base mb-1"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'"></i>
                  <p class="font-sans text-[9px] uppercase tracking-wider"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Time</p>
                  <p class="font-display text-sm font-bold leading-tight mt-0.5"
                     :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'">15:30 - 19:00 WIB</p>
                </div>
                <div class="pl-2">
                  <i class="fa-solid fa-location-dot text-base mb-1"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'"></i>
                  <p class="font-sans text-[9px] uppercase tracking-wider"
                     :style="rsvp.attendance === 'no' ? 'color:#666;' : 'color:#8B6914;'">Venue</p>
                  <p class="font-display text-sm font-bold leading-tight mt-0.5"
                     :style="rsvp.attendance === 'no' ? 'color:#888;' : 'color:#3D0A10;'">Desa Wisata Kajii</p>
                </div>
              </div>
            </div>
          </div>
        </template>
        
        <div x-show="rsvps.length === 0" class="text-center py-12 text-cream/40">
          <i class="fa-solid fa-inbox text-4xl mb-4"></i>
          <p class="font-body text-lg">No registrations yet</p>
        </div>
      </div>
    </div>
    <!-- End List View -->
    
    <!-- Scanner View (Full Screen) -->
    <div x-show="currentView === 'scanner'" class="flex-1 flex flex-col items-center justify-center p-3 md:p-6">
      <!-- Camera Feed -->
      <div x-show="!scannedRsvp" class="w-full max-w-2xl">
          <div id="qr-reader" class="rounded-xl overflow-hidden border-2 border-gold/30 mb-4"></div>
          <p class="text-center text-cream/50 text-sm font-sans">Position the QR code within the camera frame</p>
        </div>
        
        <!-- Scanned Person Details & Action Buttons -->
        <div x-show="scannedRsvp" class="w-full max-w-md">
          <div class="glass rounded-xl p-6 md:p-8 mb-6">
            <div class="text-center mb-6">
              <i class="fa-solid fa-user-check text-5xl text-gold mb-4"></i>
              <h2 class="font-display text-2xl md:text-3xl font-bold text-cream mb-2" x-text="scannedRsvp?.name"></h2>
              <p class="text-cream/60 font-sans">OSIS <span x-text="scannedRsvp?.osis"></span></p>
              <p class="text-cream/60 font-sans text-sm mt-1" x-text="scannedRsvp?.phone"></p>
            </div>
            
            <!-- Current Status -->
            <div class="mb-6 p-4 rounded-lg bg-theatre-stage border border-gold/20">
              <p class="text-cream/50 text-xs uppercase tracking-wider mb-2 font-sans">Current Status</p>
              <div class="space-y-1">
                <p class="text-cream text-sm">
                  <span class="font-bold">Checked In:</span> 
                  <span x-text="scannedRsvp?.checked_in_at || 'Not yet'"></span>
                </p>
                <p class="text-cream text-sm">
                  <span class="font-bold">Checked Out:</span> 
                  <span x-text="scannedRsvp?.checked_out_at || 'Not yet'"></span>
                </p>
              </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="space-y-3">
              <button @click="performCheckin()" :disabled="scanResult"
                      class="w-full py-4 bg-gradient-to-r from-green-700 to-green-600 hover:from-green-600 hover:to-green-500 text-white font-display font-bold text-lg rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                <i class="fa-solid fa-arrow-right-to-bracket text-xl"></i>
                <span>Check In</span>
              </button>
              
              <button @click="performCheckout()" :disabled="scanResult"
                      class="w-full py-4 bg-gradient-to-r from-red-700 to-red-600 hover:from-red-600 hover:to-red-500 text-white font-display font-bold text-lg rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-3">
                <i class="fa-solid fa-arrow-right-from-bracket text-xl"></i>
                <span>Check Out</span>
              </button>
              
              <button @click="skipScan()" :disabled="scanResult"
                      class="w-full py-3 bg-cream/10 hover:bg-cream/20 text-cream border border-cream/30 rounded-lg font-sans transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                Skip / Cancel
              </button>
            </div>
          </div>
          
          <!-- Result Message -->
          <div x-show="scanResult" 
               class="glass rounded-xl p-4 mb-4"
               :class="scanSuccess ? 'border-2 border-green-500/50' : 'border-2 border-red-500/50'">
            <div class="flex items-center gap-3">
              <i class="fa-solid text-2xl" 
                 :class="scanSuccess ? 'fa-circle-check text-green-400' : 'fa-circle-xmark text-red-400'"></i>
              <div class="flex-1">
                <p class="font-display text-lg font-bold" 
                   :class="scanSuccess ? 'text-green-300' : 'text-red-300'" 
                   x-text="scanResultTitle"></p>
                <p class="text-cream/80 text-sm" x-text="scanResultMessage"></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- End Scanner View -->
    
    <!-- Add Form Modal -->
    <div x-show="showAddForm" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-0 md:p-4 bg-black/80"
         @click.self="showAddForm = false">
      <div class="glass rounded-none md:rounded-xl p-4 md:p-8 w-full md:max-w-lg h-full md:h-auto md:max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4 md:mb-6">
          <h2 class="font-display text-xl md:text-2xl font-bold text-gradient">Add New Registrant</h2>
          <button @click="showAddForm = false" class="text-cream/50 hover:text-cream p-2">
            <i class="fa-solid fa-times text-lg md:text-xl"></i>
          </button>
        </div>
        
        <form @submit.prevent="addRSVP()" class="space-y-3 md:space-y-4">
          <div>
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Full Name</label>
            <input type="text" x-model="addForm.name" required
                   class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none" />
          </div>
          
          <div>
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">OSIS Generation</label>
            <select x-model="addForm.osis" required
                    class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none">
              <option value="">Select...</option>
              <option value="60">60</option>
              <option value="61">61</option>
              <option value="62">62</option>
            </select>
          </div>
          
          <div>
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Phone</label>
            <input type="tel" x-model="addForm.phone" required
                   class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none" />
          </div>
          
          <div>
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Attendance</label>
            <select x-model="addForm.attendance" required
                    class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none">
              <option value="yes">Yes</option>
              <option value="maybe">Maybe</option>
              <option value="no">No</option>
            </select>
          </div>
          
          <div>
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Number of Guests</label>
            <input type="number" x-model.number="addForm.guests" min="1" max="10" required
                   class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none" />
          </div>
          
          <div>
            <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Message (optional)</label>
            <textarea x-model="addForm.message" rows="3"
                      class="w-full px-4 py-3 rounded-lg bg-theatre-bg border border-gold/20 text-cream focus:border-gold/50 focus:outline-none resize-none"></textarea>
          </div>
          
          <div x-show="addError" class="text-red-400 text-sm" x-text="addError"></div>
          
          <button type="submit" :disabled="adding"
                  class="w-full py-3 bg-gradient-to-r from-gold-dark via-gold to-gold-light text-theatre-bg font-display font-bold text-lg rounded-lg hover:shadow-[0_0_20px_rgba(201,168,76,0.3)] transition-all disabled:opacity-50">
            <span x-text="adding ? 'Adding...' : 'Add Registrant'"></span>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
  
  <script>
    function adminApp() {
      return {
        loginForm: { username: '', password: '' },
        loginError: '',
        loggingIn: false,
        rsvps: [],
        showAddForm: false,
        addForm: { name: '', osis: '', phone: '', attendance: 'yes', guests: 1, message: '' },
        addError: '',
        adding: false,
        editingId: null,
        editForm: {},
        editError: '',
        editing: false,
        currentView: 'list',
        scannedRsvp: null,
        scanResult: false,
        scanSuccess: false,
        scanResultTitle: '',
        scanResultMessage: '',
        html5QrCode: null,
        isScanning: false,
        pollingInterval: null,
        lastUpdate: null,
        
        get attendingCount() {
          return this.rsvps.filter(r => r.attendance === 'yes' || r.attendance === 'maybe').length;
        },
        
        get notAttendingCount() {
          return this.rsvps.filter(r => r.attendance === 'no').length;
        },
        
        get sortedRsvps() {
          return [...this.rsvps].sort((a, b) => {
            // If both have check-in times, sort by most recent first
            if (a.checked_in_at && b.checked_in_at) {
              return new Date(b.checked_in_at) - new Date(a.checked_in_at);
            }
            // If only a has check-in time, a comes first
            if (a.checked_in_at && !b.checked_in_at) {
              return -1;
            }
            // If only b has check-in time, b comes first
            if (!a.checked_in_at && b.checked_in_at) {
              return 1;
            }
            // If neither has check-in time, maintain original order
            return 0;
          });
        },
        
        init() {
          <?php if ($isAuthenticated): ?>
          this.fetchRSVPs();
          
          // Start periodic polling for live updates (every 5 seconds)
          this.pollingInterval = setInterval(() => {
            this.fetchRSVPs();
          }, 5000);
          
          // Watch for scanner view opening
          this.$watch('currentView', (value) => {
            if (value === 'scanner') {
              // Delay to ensure DOM is ready
              setTimeout(() => this.initScanner(), 100);
            } else if (value === 'list' && this.html5QrCode) {
              // Stop scanner when leaving scanner view
              this.stopScanner();
            }
          });
          <?php endif; ?>
        },
        
        async login() {
          this.loginError = '';
          this.loggingIn = true;
          try {
            const res = await fetch('?api=admin-login', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(this.loginForm)
            });
            if (res.ok) {
              window.location.reload();
            } else {
              this.loginError = 'Invalid credentials';
            }
          } catch (e) {
            this.loginError = 'Login failed';
          } finally {
            this.loggingIn = false;
          }
        },
        
        async logout() {
          await fetch('?api=admin-logout', { method: 'POST' });
          window.location.reload();
        },
        
        async fetchRSVPs() {
          try {
            const res = await fetch('?api=admin-rsvps');
            const data = await res.json();
            this.rsvps = data.data || [];
            this.lastUpdate = new Date();
          } catch (e) {
            console.error('Failed to fetch RSVPs', e);
          }
        },
        
        async deleteRSVP(id) {
          if (!confirm('Are you sure you want to delete this registration?')) return;
          try {
            const res = await fetch('?api=admin-delete-rsvp', {
              method: 'DELETE',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id })
            });
            if (res.ok) {
              this.fetchRSVPs();
            }
          } catch (e) {
            alert('Failed to delete');
          }
        },
        
        async addRSVP() {
          this.addError = '';
          this.adding = true;
          try {
            const res = await fetch('?api=admin-add-rsvp', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(this.addForm)
            });
            if (res.ok) {
              this.showAddForm = false;
              this.addForm = { name: '', osis: '', phone: '', attendance: 'yes', guests: 1, message: '' };
              this.fetchRSVPs();
            } else {
              const data = await res.json();
              this.addError = data.error || 'Failed to add';
            }
          } catch (e) {
            this.addError = 'Failed to add registrant';
          } finally {
            this.adding = false;
          }
        },
        
        startEdit(rsvp) {
          this.editingId = rsvp.id;
          this.editForm = {
            id: rsvp.id,
            name: rsvp.name,
            osis: rsvp.osis,
            phone: rsvp.phone,
            attendance: rsvp.attendance,
            guests: rsvp.guests,
            message: rsvp.message || '',
            checked_in_at: rsvp.checked_in_at || '',
            checked_out_at: rsvp.checked_out_at || ''
          };
          this.editError = '';
        },
        
        cancelEdit() {
          this.editingId = null;
          this.editForm = {};
          this.editError = '';
        },
        
        async saveEdit() {
          this.editError = '';
          this.editing = true;
          try {
            const res = await fetch('?api=admin-update-rsvp', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(this.editForm)
            });
            if (res.ok) {
              this.editingId = null;
              this.editForm = {};
              this.fetchRSVPs();
            } else {
              const data = await res.json();
              this.editError = data.error || 'Failed to update';
            }
          } catch (e) {
            this.editError = 'Failed to update registrant';
          } finally {
            this.editing = false;
          }
        },
        
        async initScanner() {
          if (this.isScanning) return;
          
          // Reset state
          this.scannedRsvp = null;
          this.scanResult = false;
          
          // Wait for DOM to be ready
          await this.$nextTick();
          
          try {
            this.html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = {
              fps: 10,
              qrbox: { width: 250, height: 250 },
              aspectRatio: 1.0
            };
            
            await this.html5QrCode.start(
              { facingMode: "environment" },
              config,
              (decodedText, decodedResult) => {
                this.handleScan(decodedText);
              },
              (errorMessage) => {
                // Ignore scan errors (happens continuously when no QR detected)
              }
            );
            
            this.isScanning = true;
          } catch (err) {
            console.error('Failed to start scanner:', err);
            this.scanResult = true;
            this.scanSuccess = false;
            this.scanResultTitle = 'Camera Error';
            this.scanResultMessage = 'Failed to access camera. Please check permissions.';
          }
        },
        
        async stopScanner() {
          if (this.html5QrCode && this.isScanning) {
            try {
              await this.html5QrCode.stop();
              this.html5QrCode.clear();
            } catch (err) {
              console.error('Error stopping scanner:', err);
            }
          }
          this.isScanning = false;
          this.scannedRsvp = null;
          this.scanResult = false;
          this.html5QrCode = null;
        },
        
        async handleScan(decodedText) {
          if (!this.isScanning) return;
          
          // Pause scanning and find the RSVP
          this.isScanning = false;
          
          const rsvp = this.rsvps.find(r => r.id === decodedText);
          
          if (!rsvp) {
            this.scanResult = true;
            this.scanSuccess = false;
            this.scanResultTitle = 'Not Found';
            this.scanResultMessage = 'QR code not recognized. Please check the code.';
            setTimeout(() => { 
              this.scanResult = false;
              this.isScanning = true; 
            }, 2000);
            return;
          }
          
          // Show scanned person details and wait for manual action
          this.scannedRsvp = rsvp;
        },
        
        async performCheckin() {
          if (!this.scannedRsvp) return;
          
          this.scanResult = false;
          
          try {
            const res = await fetch('?api=scan', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ 
                id: this.scannedRsvp.id, 
                action: 'checkin' 
              })
            });
            
            if (res.ok) {
              // Refresh the RSVP list
              await this.fetchRSVPs();
              
              // Immediately return to scan view
              this.scannedRsvp = null;
              this.scanResult = false;
              this.isScanning = true;
            } else {
              const data = await res.json();
              this.scanResult = true;
              this.scanSuccess = false;
              this.scanResultTitle = 'Error';
              this.scanResultMessage = data.error || 'Failed to check in';
            }
          } catch (err) {
            console.error('Check-in error:', err);
            this.scanResult = true;
            this.scanSuccess = false;
            this.scanResultTitle = 'Error';
            this.scanResultMessage = 'Failed to process check-in. Please try again.';
          }
        },
        
        async performCheckout() {
          if (!this.scannedRsvp) return;
          
          this.scanResult = false;
          
          try {
            const res = await fetch('?api=scan', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ 
                id: this.scannedRsvp.id, 
                action: 'checkout' 
              })
            });
            
            if (res.ok) {
              // Refresh the RSVP list
              await this.fetchRSVPs();
              
              // Immediately return to scan view
              this.scannedRsvp = null;
              this.scanResult = false;
              this.isScanning = true;
            } else {
              const data = await res.json();
              this.scanResult = true;
              this.scanSuccess = false;
              this.scanResultTitle = 'Error';
              this.scanResultMessage = data.error || 'Failed to check out';
            }
          } catch (err) {
            console.error('Check-out error:', err);
            this.scanResult = true;
            this.scanSuccess = false;
            this.scanResultTitle = 'Error';
            this.scanResultMessage = 'Failed to process check-out. Please try again.';
          }
        },
        
        skipScan() {
          // Resume scanning without taking action
          this.scannedRsvp = null;
          this.scanResult = false;
          this.isScanning = true;
        }
      };
    }
  </script>
</body>
</html>
    <?php
    exit;
}

// Otherwise, serve the HTML View below:
?>
<!DOCTYPE html>
<html lang="en" class="overflow-x-hidden">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Bro &amp; Sis 4 — The Greatest Iftar Show</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            curtain: { DEFAULT: '#6B0F1A', dark: '#3D0A10', light: '#8B1A2B', deep: '#2A0508' },
            gold: { DEFAULT: '#C9A84C', light: '#E8D48B', dark: '#A07E2E', pale: '#F5E6B8' },
            cream: { DEFAULT: '#F5ECD7', dark: '#E8DCC0' },
            parchment: '#F2E6C9',
            theatre: { bg: '#0D0507', stage: '#1A0A0E' }
          },
          fontFamily: {
            display: ['Playfair Display', 'serif'],
            body: ['Cormorant Garamond', 'serif'],
            sans: ['Inter', 'sans-serif']
          }
        }
      }
    };
  </script>
  <style>
    [x-cloak] { display: none !important; }

    /* ============================================
       CURTAIN SYSTEM — Real sliding panels
       ============================================ */
    .curtain-overlay {
      position: fixed;
      inset: 0;
      z-index: 100;
      overflow: hidden;
    }

    .curtain-panel {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 52%;
      transition: transform 2.2s cubic-bezier(0.77, 0, 0.18, 1);
      will-change: transform;
    }

    .curtain-panel-left {
      left: 0;
      transform: translateX(0);
      background:
        linear-gradient(135deg, #3D0A10 0%, #6B0F1A 30%, #8B1A2B 50%, #6B0F1A 70%, #3D0A10 100%);
    }

    .curtain-panel-right {
      right: 0;
      transform: translateX(0);
      background:
        linear-gradient(225deg, #3D0A10 0%, #6B0F1A 30%, #8B1A2B 50%, #6B0F1A 70%, #3D0A10 100%);
    }

    /* Velvet sheen overlay */
    .curtain-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at 30% 20%, rgba(255,255,255,0.08) 0%, transparent 50%),
        radial-gradient(ellipse at 70% 60%, rgba(255,255,255,0.04) 0%, transparent 40%);
      pointer-events: none;
    }

    /* Vertical fabric folds */
    .curtain-folds {
      position: absolute;
      inset: 0;
      background:
        repeating-linear-gradient(90deg,
          transparent 0px, transparent 28px,
          rgba(0,0,0,0.15) 28px, rgba(0,0,0,0.15) 30px,
          transparent 30px, transparent 42px,
          rgba(255,255,255,0.04) 42px, rgba(255,255,255,0.04) 44px,
          transparent 44px, transparent 70px
        );
      pointer-events: none;
    }

    /* Gathered fabric at bottom */
    .curtain-panel::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: -10%;
      right: -10%;
      height: 180px;
      background:
        radial-gradient(ellipse at 20% 100%, rgba(60,10,16,0.9) 0%, transparent 60%),
        radial-gradient(ellipse at 50% 100%, rgba(60,10,16,0.7) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 100%, rgba(60,10,16,0.9) 0%, transparent 60%);
      pointer-events: none;
    }

    /* Golden valance across top */
    .curtain-valance {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 60px;
      z-index: 103;
      background:
        linear-gradient(180deg, #A07E2E 0%, #C9A84C 40%, #E8D48B 55%, #C9A84C 70%, #8B6914 100%);
      border-bottom: 3px solid #A07E2E;
      box-shadow: 0 6px 20px rgba(0,0,0,0.6);
      transition: transform 2.2s cubic-bezier(0.77, 0, 0.18, 1), opacity 1.2s ease 0.3s;
    }

    /* Valance swag scallops */
    .curtain-valance::after {
      content: '';
      position: absolute;
      bottom: -18px;
      left: 0;
      right: 0;
      height: 18px;
      background:
        radial-gradient(ellipse 60px 18px at 30px 0px, #A07E2E 98%, transparent 100%);
      background-size: 60px 18px;
    }

    /* Fringe under valance */
    .curtain-fringe {
      position: absolute;
      bottom: -32px;
      left: 0;
      right: 0;
      height: 14px;
      background:
        repeating-linear-gradient(90deg,
          #C9A84C 0px, #C9A84C 2px,
          transparent 2px, transparent 6px
        );
      mask-image: linear-gradient(180deg, #000 0%, transparent 100%);
      -webkit-mask-image: linear-gradient(180deg, #000 0%, transparent 100%);
    }

    /* OPENED STATE */
    .curtain-opened .curtain-panel-left {
      transform: translateX(-100%);
    }
    .curtain-opened .curtain-panel-right {
      transform: translateX(100%);
    }
    .curtain-opened .curtain-valance {
      transform: translateY(-120px);
      opacity: 0;
      transition-delay: 0.3s;
    }
    .curtain-opened .curtain-center {
      opacity: 0;
      transform: scale(0.8);
      pointer-events: none;
      transition: all 0.6s ease;
    }

    /* DISMISSED — fully remove from rendering */
    .curtain-done {
      display: none !important;
    }

    /* Curtain center invitation */
    .curtain-center {
      position: absolute;
      inset: 0;
      z-index: 102;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.6s ease 0.2s;
    }

    /* Pulse ring on button */
    @keyframes pulseRing {
      0% { transform: scale(1); opacity: 0.6; }
      100% { transform: scale(1.8); opacity: 0; }
    }
    .pulse-ring {
      animation: pulseRing 2s ease-out infinite;
    }

    /* ============================================
       ANIMATIONS
       ============================================ */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInScale {
      from { opacity: 0; transform: scale(0.85); }
      to { opacity: 1; transform: scale(1); }
    }
    @keyframes spotlight {
      0%, 100% { opacity: 0.3; transform: scale(1); }
      50% { opacity: 0.6; transform: scale(1.15); }
    }
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-12px); }
    }
    @keyframes shimmer {
      0% { background-position: -200% center; }
      100% { background-position: 200% center; }
    }
    @keyframes sparkle {
      0%, 100% { opacity: 0; transform: scale(0); }
      50% { opacity: 1; transform: scale(1); }
    }
    @keyframes bounceIn {
      0% { opacity: 0; transform: scale(0.3); }
      50% { transform: scale(1.08); }
      70% { transform: scale(0.95); }
      100% { opacity: 1; transform: scale(1); }
    }
    @keyframes cardDrift {
      0%, 100% { transform: translateY(0) rotate(var(--rot, 12deg)); }
      50% { transform: translateY(-18px) rotate(calc(var(--rot, 12deg) + 4deg)); }
    }
    @keyframes chessDrift {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    @keyframes sparkleBurst {
      0%, 100% { opacity: 0; transform: scale(0) rotate(0deg); }
      50% { opacity: 0.9; transform: scale(1) rotate(180deg); }
    }
    @keyframes envelopeFloat {
      0%, 100% { transform: translateY(0) rotate(-3deg); }
      50% { transform: translateY(-14px) rotate(1deg); }
    }
    @keyframes sealStamp {
      0% { transform: scale(1.5) rotate(-20deg); opacity: 0; }
      60% { transform: scale(0.95) rotate(2deg); opacity: 1; }
      100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }
    @keyframes gentleSwing {
      0%, 100% { transform: rotate(-2deg); }
      50% { transform: rotate(2deg); }
    }
    @keyframes goldShine {
      0% { left: -100%; }
      100% { left: 200%; }
    }
    @keyframes tickerScroll {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }
    @keyframes revealSlideUp {
      from { clip-path: inset(100% 0 0 0); opacity: 0; }
      to { clip-path: inset(0 0 0 0); opacity: 1; }
    }
    @keyframes flickerGlow {
      0%, 100% { opacity: 0.3; }
      25% { opacity: 0.5; }
      50% { opacity: 0.2; }
      75% { opacity: 0.6; }
    }
    @keyframes letterPull {
      0% { transform: translateY(0); }
      100% { transform: translateY(-30px); }
    }
    @keyframes cardFan {
      0% { transform: rotate(0deg) translateX(0); opacity: 0; }
      100% { transform: rotate(var(--fan-rot, 0deg)) translateX(var(--fan-x, 0)); opacity: 1; }
    }
    @keyframes typewriter {
      from { width: 0; }
      to { width: 100%; }
    }

    /* Animation utilities */
    .anim-fade-up { animation: fadeInUp 0.8s ease-out both; }
    .anim-fade-scale { animation: fadeInScale 0.7s ease-out both; }
    .anim-bounce-in { animation: bounceIn 0.8s ease-out both; }
    .anim-float { animation: float 4s ease-in-out infinite; }
    .anim-spotlight { animation: spotlight 4s ease-in-out infinite; }
    .anim-sparkle { animation: sparkle 2s ease-in-out infinite; }

    /* Scroll reveal */
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* Stagger children */
    .reveal-stagger > .reveal:nth-child(1) { transition-delay: 0.1s; }
    .reveal-stagger > .reveal:nth-child(2) { transition-delay: 0.2s; }
    .reveal-stagger > .reveal:nth-child(3) { transition-delay: 0.3s; }
    .reveal-stagger > .reveal:nth-child(4) { transition-delay: 0.4s; }
    .reveal-stagger > .reveal:nth-child(5) { transition-delay: 0.5s; }
    .reveal-stagger > .reveal:nth-child(6) { transition-delay: 0.6s; }

    /* Custom scrollbar */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: #0D0507; }
    ::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, #6B0F1A, #C9A84C);
      border-radius: 4px;
    }

    /* Text gradient */
    .text-gradient {
      background: linear-gradient(135deg, #C9A84C 0%, #E8D48B 40%, #C9A84C 60%, #F5E6B8 80%, #C9A84C 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Glass morphism */
    .glass {
      background: rgba(13, 5, 7, 0.6);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(201, 168, 76, 0.15);
    }
    .glass-gold {
      background: rgba(201, 168, 76, 0.08);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(201, 168, 76, 0.2);
    }

    /* ============================================
       PARCHMENT — Realistic aged paper
       ============================================ */
    .parchment {
      background:
        radial-gradient(ellipse at 15% 10%, rgba(180,140,80,0.15) 0%, transparent 50%),
        radial-gradient(ellipse at 85% 90%, rgba(160,120,60,0.2) 0%, transparent 50%),
        radial-gradient(ellipse at 50% 50%, rgba(242,230,201,0.05) 0%, transparent 80%),
        linear-gradient(175deg, #F5ECD7 0%, #F2E6C9 25%, #E8DCC0 50%, #F2E6C9 75%, #F5ECD7 100%);
      border: 2px solid rgba(160,126,46,0.4);
      box-shadow:
        inset 0 0 60px rgba(160,120,60,0.15),
        inset 0 0 120px rgba(140,100,40,0.08),
        0 8px 40px rgba(0,0,0,0.4),
        0 2px 8px rgba(0,0,0,0.3);
      position: relative;
    }
    /* Aged edges */
    .parchment::before {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: inherit;
      background:
        radial-gradient(ellipse at 0% 0%, rgba(120,80,30,0.2) 0%, transparent 30%),
        radial-gradient(ellipse at 100% 0%, rgba(120,80,30,0.15) 0%, transparent 25%),
        radial-gradient(ellipse at 0% 100%, rgba(120,80,30,0.18) 0%, transparent 28%),
        radial-gradient(ellipse at 100% 100%, rgba(120,80,30,0.22) 0%, transparent 32%);
      pointer-events: none;
    }
    /* Subtle paper grain */
    .parchment::after {
      content: '';
      position: absolute;
      inset: 0;
      border-radius: inherit;
      opacity: 0.03;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
      pointer-events: none;
    }

    /* ============================================
       CURTAIN TEXTURE BACKGROUNDS
       ============================================ */
    .curtain-texture {
      background:
        radial-gradient(ellipse at 50% 0%, rgba(139,26,43,0.3) 0%, transparent 60%),
        radial-gradient(ellipse at 20% 80%, rgba(107,15,26,0.2) 0%, transparent 50%),
        linear-gradient(180deg, #0D0507 0%, #1A0A0E 50%, #0D0507 100%);
    }

    /* Side curtain drapes (for sections) */
    .curtain-drape-left,
    .curtain-drape-right {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 80px;
      pointer-events: none;
      z-index: 1;
    }
    .curtain-drape-left {
      left: 0;
      background: linear-gradient(90deg, rgba(107,15,26,0.4) 0%, rgba(107,15,26,0.1) 50%, transparent 100%);
    }
    .curtain-drape-right {
      right: 0;
      background: linear-gradient(270deg, rgba(107,15,26,0.4) 0%, rgba(107,15,26,0.1) 50%, transparent 100%);
    }
    @media (max-width: 768px) {
      .curtain-drape-left, .curtain-drape-right { width: 30px; }
    }

    /* ============================================
       ORNAMENTAL DIVIDER
       ============================================ */
    .ornament-divider {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      padding: 20px 0;
    }
    .ornament-line {
      height: 1px;
      width: 80px;
      background: linear-gradient(90deg, transparent, rgba(201,168,76,0.5), transparent);
    }
    .ornament-diamond {
      width: 8px;
      height: 8px;
      background: #C9A84C;
      transform: rotate(45deg);
      opacity: 0.6;
    }

    /* ============================================
       PLAYING CARDS — Realistic mini cards
       ============================================ */
    .playing-card {
      width: 56px;
      height: 80px;
      perspective: 600px;
      position: absolute;
      z-index: 2;
      pointer-events: none;
      filter: drop-shadow(2px 4px 8px rgba(0,0,0,0.5));
    }
    /* Static playing card (used inside positioned containers, not floating) */
    .playing-card-static {
      width: 56px;
      height: 80px;
      perspective: 600px;
      position: relative;
      pointer-events: none;
      filter: drop-shadow(2px 4px 8px rgba(0,0,0,0.5));
    }
    .playing-card .card-inner {
      width: 100%;
      height: 100%;
      background: linear-gradient(145deg, #fff 0%, #f8f4ec 50%, #ede5d5 100%);
      border-radius: 6px;
      position: relative;
      border: 1px solid rgba(200,180,140,0.6);
      overflow: hidden;
    }
    .playing-card .card-inner::after {
      content: '';
      position: absolute;
      inset: 3px;
      border: 1px solid rgba(201,168,76,0.35);
      border-radius: 4px;
      pointer-events: none;
    }
    .playing-card .card-corner {
      position: absolute;
      font-size: 11px;
      line-height: 1;
      font-weight: 700;
      font-family: 'Playfair Display', serif;
    }
    .playing-card .card-corner.top { top: 5px; left: 6px; }
    .playing-card .card-corner.bottom { bottom: 5px; right: 6px; transform: rotate(180deg); }
    .playing-card .card-center {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 26px;
      line-height: 1;
      opacity: 0.85;
    }
    .playing-card .card-red { color: #B91C1C; }
    .playing-card .card-black { color: #1a1a1a; }

    /* Card back pattern */
    .card-back-pattern {
      background:
        repeating-conic-gradient(#6B0F1A 0% 25%, #8B1A2B 0% 50%) 0 0 / 10px 10px;
      border: 3px solid #C9A84C;
    }

    /* ============================================
       CHESS PIECES
       ============================================ */
    .chess-piece {
      position: absolute;
      opacity: 0.06;
      pointer-events: none;
      animation: chessDrift 8s ease-in-out infinite;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    /* ============================================
       SPARKLE BURST
       ============================================ */
    .sparkle-burst {
      position: absolute;
      width: 20px;
      height: 20px;
      pointer-events: none;
    }
    .sparkle-burst::before,
    .sparkle-burst::after {
      content: '';
      position: absolute;
      background: #C9A84C;
    }
    .sparkle-burst::before {
      width: 2px;
      height: 100%;
      left: 50%;
      transform: translateX(-50%);
    }
    .sparkle-burst::after {
      height: 2px;
      width: 100%;
      top: 50%;
      transform: translateY(-50%);
    }

    /* ============================================
       FILM STRIP
       ============================================ */
    .film-strip {
      height: 36px;
      background:
        repeating-linear-gradient(90deg,
          transparent 0px, transparent 6px,
          rgba(201,168,76,0.15) 6px, rgba(201,168,76,0.15) 18px,
          transparent 18px, transparent 24px
        ),
        linear-gradient(180deg, rgba(201,168,76,0.08) 3px, transparent 3px, transparent 33px, rgba(201,168,76,0.08) 33px);
    }

    /* ============================================
       STICKY NOTE — like poster P.S.
       ============================================ */
    .sticky-note {
      background: linear-gradient(135deg, #FFFDE7 0%, #FFF9C4 30%, #FFF59D 100%);
      transform: rotate(-2deg);
      box-shadow: 3px 3px 12px rgba(0,0,0,0.25), inset 0 -2px 6px rgba(0,0,0,0.05);
      position: relative;
    }
    .sticky-note::before {
      content: '';
      position: absolute;
      top: -8px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 16px;
      background: rgba(200,200,180,0.5);
      border-radius: 2px;
    }
    .sticky-note::after {
      content: '';
      position: absolute;
      inset: 0;
      background: repeating-linear-gradient(
        transparent 0px, transparent 24px,
        rgba(0,0,0,0.03) 24px, rgba(0,0,0,0.03) 25px
      );
      pointer-events: none;
    }

    /* ============================================
       THEATER TICKET — Vintage admission
       ============================================ */
    .theater-ticket {
      background: linear-gradient(135deg, #FFF8E7, #F5ECD7, #E8DCC0);
      border: 1px dashed rgba(160,126,46,0.5);
      position: relative;
      overflow: hidden;
    }
    .theater-ticket::before {
      content: '';
      position: absolute;
      left: -6px;
      top: 50%;
      width: 12px;
      height: 12px;
      background: #0D0507;
      border-radius: 50%;
      transform: translateY(-50%);
    }
    .theater-ticket::after {
      content: '';
      position: absolute;
      right: -6px;
      top: 50%;
      width: 12px;
      height: 12px;
      background: #0D0507;
      border-radius: 50%;
      transform: translateY(-50%);
    }

    /* ============================================
       CHECKERED FLOOR
       ============================================ */
    .checkered-floor {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 100px;
      background:
        conic-gradient(from 0deg at 50% 50%, #1a1a1a 0deg 90deg, #2a2a2a 90deg 180deg, #1a1a1a 180deg 270deg, #2a2a2a 270deg 360deg);
      background-size: 40px 40px;
      transform: perspective(500px) rotateX(65deg);
      transform-origin: bottom;
      opacity: 0.15;
      mask-image: linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 100%);
      -webkit-mask-image: linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 100%);
    }

    /* ============================================
       FORM STYLES
       ============================================ */
    .form-input {
      background: rgba(13, 5, 7, 0.5);
      border: 1px solid rgba(201, 168, 76, 0.2);
      transition: all 0.3s ease;
      color: #F5ECD7;
    }
    .form-input:focus {
      border-color: rgba(201, 168, 76, 0.5);
      box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.1), inset 0 0 20px rgba(201, 168, 76, 0.03);
      outline: none;
    }
    .form-input::placeholder {
      color: rgba(245, 236, 215, 0.3);
    }

    /* Detail card hover lift */
    .detail-card {
      transition: transform 0.4s ease, box-shadow 0.4s ease;
    }
    .detail-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 40px rgba(201, 168, 76, 0.15);
    }

    /* ============================================
       SPARKLE TRAIL PARTICLE
       ============================================ */
    .sparkle-particle {
      position: fixed;
      pointer-events: none;
      z-index: 90;
      width: 8px;
      height: 8px;
      background: #C9A84C;
      clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
    }

    /* ============================================
       MOUSE SPOTLIGHT
       ============================================ */
    .mouse-spotlight {
      position: fixed;
      pointer-events: none;
      z-index: 40;
      width: 500px;
      height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,0.06) 0%, rgba(201,168,76,0.02) 30%, transparent 70%);
      transform: translate(-50%, -50%);
      transition: left 0.15s ease-out, top 0.15s ease-out;
    }

    /* ============================================
       ENVELOPE — Like poster
       ============================================ */
    .envelope-decoration {
      position: absolute;
      pointer-events: none;
      animation: envelopeFloat 6s ease-in-out infinite;
    }

    /* ============================================
       FLOATING CARD (parallax)
       ============================================ */
    .floating-card {
      animation: cardDrift 6s ease-in-out infinite;
    }

    /* ============================================
       TILT CARD (3D hover)
       ============================================ */
    .tilt-card {
      transform-style: preserve-3d;
      transition: transform 0.2s ease-out;
    }

    /* Parallax layer */
    .parallax-layer {
      transition: transform 0.1s linear;
    }

    /* ============================================
       GOLDEN BORDER FILIGREE
       ============================================ */
    .gold-border-top {
      position: relative;
    }
    .gold-border-top::before {
      content: '';
      position: absolute;
      top: 0;
      left: 10%;
      right: 10%;
      height: 2px;
      background: linear-gradient(90deg, transparent, #C9A84C, transparent);
    }

    /* ============================================
       INTERACTIVE HOVER STATES
       ============================================ */
    .hover-glow {
      transition: all 0.3s ease;
    }
    .hover-glow:hover {
      box-shadow: 0 0 20px rgba(201,168,76,0.3), 0 0 40px rgba(201,168,76,0.1);
    }

    /* ============================================
       SCROLL PROGRESS BAR
       ============================================ */
    .scroll-progress {
      position: fixed;
      top: 0;
      left: 0;
      height: 3px;
      background: linear-gradient(90deg, #6B0F1A, #C9A84C, #E8D48B);
      z-index: 200;
      transition: width 0.1s linear;
    }

    /* ============================================
       MAGNETIC BUTTON
       ============================================ */
    .magnetic-btn {
      transition: transform 0.2s ease-out;
    }

    /* ============================================
       COUNTER FLIP (digit style)
       ============================================ */
    .counter-digit {
      display: inline-block;
      min-width: 0.6em;
      text-align: center;
    }

    /* Card suit watermark */
    .suit-watermark {
      position: absolute;
      font-size: clamp(120px, 15vw, 200px);
      opacity: 0.015;
      pointer-events: none;
      font-family: serif;
      line-height: 1;
      color: #C9A84C;
    }

    /* Section theatrical frame */
    .theatrical-frame {
      position: relative;
      border: 1px solid rgba(201,168,76,0.1);
      box-shadow: inset 0 0 80px rgba(13,5,7,0.5);
    }
    .theatrical-frame::before,
    .theatrical-frame::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      border-color: rgba(201,168,76,0.3);
      border-style: solid;
    }
    .theatrical-frame::before {
      top: 12px;
      left: 12px;
      border-width: 2px 0 0 2px;
    }
    .theatrical-frame::after {
      bottom: 12px;
      right: 12px;
      border-width: 0 2px 2px 0;
    }
  </style>
</head>

<body class="bg-theatre-bg text-cream font-body overflow-x-hidden" x-data="rsvpApp()" x-cloak>

  <!-- Scroll progress bar -->
  <div class="scroll-progress" :style="`width: ${scrollProgress}%`" x-show="curtainOpened"></div>

  <!-- Mouse spotlight (desktop) -->
  <div class="mouse-spotlight hidden md:block" x-ref="mouseSpotlight"></div>

  <!-- ================================================
       CURTAIN OVERLAY — Click to Open
       ================================================ -->
  <div class="curtain-overlay" :class="{ 'curtain-opened': curtainOpened, 'curtain-done': curtainDismissed }">

    <!-- Valance -->
    <div class="curtain-valance">
      <div class="curtain-fringe"></div>
    </div>

    <!-- Left Panel -->
    <div class="curtain-panel curtain-panel-left">
      <div class="curtain-folds"></div>
    </div>

    <!-- Right Panel -->
    <div class="curtain-panel curtain-panel-right">
      <div class="curtain-folds"></div>
    </div>

    <!-- Center content — invitation to open -->
    <div class="curtain-center">
      <div class="text-center px-6 max-w-md relative">
        <!-- Spotlights behind text -->
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-80 h-80 rounded-full bg-gradient-radial opacity-20"
             style="background: radial-gradient(circle, rgba(201,168,76,0.3) 0%, transparent 70%); animation: spotlight 3s ease-in-out infinite;"></div>

        <!-- Envelope icon -->
        <div class="mb-6 relative inline-block" style="animation: envelopeFloat 3s ease-in-out infinite;">
          <svg width="80" height="60" viewBox="0 0 80 60" class="drop-shadow-lg">
            <rect x="2" y="12" width="76" height="46" rx="3" fill="#C4956A" stroke="#A07E2E" stroke-width="1.5"/>
            <polygon points="2,12 40,40 78,12" fill="#D4A574" stroke="#A07E2E" stroke-width="1"/>
            <polygon points="2,58 30,35" fill="none" stroke="#A07E2E" stroke-width="0.5" opacity="0.5"/>
            <polygon points="78,58 50,35" fill="none" stroke="#A07E2E" stroke-width="0.5" opacity="0.5"/>
            <!-- Letter peeking out -->
            <rect x="15" y="2" width="50" height="30" rx="2" fill="#F5ECD7" stroke="#E8DCC0" stroke-width="1"
                  style="animation: letterPull 2s ease-in-out infinite alternate;"/>
            <line x1="22" y1="10" x2="58" y2="10" stroke="#C9A84C" stroke-width="0.8" opacity="0.4"/>
            <line x1="22" y1="16" x2="50" y2="16" stroke="#C9A84C" stroke-width="0.8" opacity="0.3"/>
          </svg>

        </div>

        <p class="font-body text-gold-light/80 text-lg tracking-[0.2em] uppercase mb-3" style="animation: fadeInUp 1s ease-out;">
          You are cordially invited
        </p>
        <h1 class="font-display text-4xl md:text-5xl font-bold text-gradient mb-2" style="animation: fadeInUp 1s ease-out 0.2s both;">
          Bro &amp; Sis 4
        </h1>
        <p class="font-display text-xl text-cream/70 italic mb-8" style="animation: fadeInUp 1s ease-out 0.4s both;">
          The Greatest Iftar Show
        </p>

        <!-- Open button -->
        <button @click="openCurtain()"
                class="magnetic-btn relative inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-gold-dark via-gold to-gold-light text-theatre-bg font-display font-bold text-lg rounded-sm tracking-wide hover:shadow-[0_0_30px_rgba(201,168,76,0.4)] transition-all duration-300 group"
                @mouseenter="magneticEnter($event)" @mouseleave="magneticLeave($event)" @mousemove="magneticMove($event)"
                style="animation: fadeInUp 1s ease-out 0.6s both;">
          <span class="relative z-10">Open The Curtain</span>
          <i class="fa-solid fa-arrow-right relative z-10 text-lg group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
          <!-- Pulse ring -->
          <span class="absolute inset-0 rounded-sm border-2 border-gold pulse-ring"></span>
          <span class="absolute inset-0 rounded-sm border-2 border-gold pulse-ring" style="animation-delay: 0.7s;"></span>
        </button>


      </div>
    </div>
  </div>

  <!-- ================================================
       FLOATING DECORATIONS (fixed, parallax)
       ================================================ -->
  <template x-if="curtainOpened">
    <div class="fixed inset-0 pointer-events-none z-[5] overflow-hidden">
      <!-- Playing cards (desktop) -->
      <div class="hidden lg:block">
        <!-- King of Hearts - top left -->
        <div class="playing-card floating-card" style="top:8%; left:3%; --rot:15deg; animation-delay:0s;">
          <div class="card-inner">
            <span class="card-corner top card-red">K<br/>&#9829;</span>
            <span class="card-center card-red">&#9829;</span>
            <span class="card-corner bottom card-red">K<br/>&#9829;</span>
          </div>
        </div>
        <!-- Queen of Spades - top right -->
        <div class="playing-card floating-card" style="top:15%; right:4%; --rot:-18deg; animation-delay:1.2s;">
          <div class="card-inner">
            <span class="card-corner top card-black">Q<br/>&#9824;</span>
            <span class="card-center card-black">&#9824;</span>
            <span class="card-corner bottom card-black">Q<br/>&#9824;</span>
          </div>
        </div>
        <!-- 10 of Diamonds -->
        <div class="playing-card floating-card" style="top:45%; left:2%; --rot:22deg; animation-delay:2.5s;">
          <div class="card-inner">
            <span class="card-corner top card-red">10<br/>&#9830;</span>
            <span class="card-center card-red">&#9830;</span>
            <span class="card-corner bottom card-red">10<br/>&#9830;</span>
          </div>
        </div>
        <!-- Ace of Clubs -->
        <div class="playing-card floating-card" style="top:55%; right:3%; --rot:-10deg; animation-delay:1.8s;">
          <div class="card-inner">
            <span class="card-corner top card-black">A<br/>&#9827;</span>
            <span class="card-center card-black" style="font-size:32px;">&#9827;</span>
            <span class="card-corner bottom card-black">A<br/>&#9827;</span>
          </div>
        </div>
        <!-- Jack of Hearts -->
        <div class="playing-card floating-card" style="top:75%; left:4%; --rot:-14deg; animation-delay:3.2s;">
          <div class="card-inner">
            <span class="card-corner top card-red">J<br/>&#9829;</span>
            <span class="card-center card-red">&#9829;</span>
            <span class="card-corner bottom card-red">J<br/>&#9829;</span>
          </div>
        </div>
        <!-- 7 of Spades -->
        <div class="playing-card floating-card" style="top:85%; right:5%; --rot:20deg; animation-delay:0.8s;">
          <div class="card-inner">
            <span class="card-corner top card-black">7<br/>&#9824;</span>
            <span class="card-center card-black">&#9824;</span>
            <span class="card-corner bottom card-black">7<br/>&#9824;</span>
          </div>
        </div>
      </div>

      <!-- Chess pieces (desktop) -->
      <div class="hidden lg:block">
        <div class="chess-piece" style="top:12%; right:8%; font-size:50px; animation-delay:0s;">&#9818;</div>
        <div class="chess-piece" style="top:70%; left:6%; font-size:45px; animation-delay:2s;">&#9820;</div>
        <div class="chess-piece" style="top:40%; right:6%; font-size:38px; animation-delay:4s;">&#9822;</div>
        <div class="chess-piece" style="top:90%; right:10%; font-size:42px; animation-delay:1s;">&#9819;</div>
      </div>

      <!-- Sparkle bursts (desktop) -->
      <div class="hidden md:block">
        <div class="sparkle-burst" style="top:5%; left:15%; animation: sparkleBurst 3s ease-in-out infinite;"></div>
        <div class="sparkle-burst" style="top:25%; right:12%; animation: sparkleBurst 3s ease-in-out infinite 0.8s;"></div>
        <div class="sparkle-burst" style="top:50%; left:8%; animation: sparkleBurst 3s ease-in-out infinite 1.6s;"></div>
        <div class="sparkle-burst" style="top:65%; right:15%; animation: sparkleBurst 3s ease-in-out infinite 2.4s;"></div>
        <div class="sparkle-burst" style="top:80%; left:12%; animation: sparkleBurst 3s ease-in-out infinite 1.2s;"></div>
        <div class="sparkle-burst" style="top:92%; right:8%; animation: sparkleBurst 3s ease-in-out infinite 2s;"></div>
      </div>

      <!-- Mobile floating symbols -->
      <div class="md:hidden">
        <div class="absolute top-[10%] right-4 text-gold/10 text-3xl" style="animation: float 5s ease-in-out infinite;">&#9829;</div>
        <div class="absolute top-[40%] left-3 text-gold/10 text-2xl" style="animation: float 5s ease-in-out infinite 1.5s;">&#9824;</div>
        <div class="absolute top-[70%] right-3 text-gold/10 text-2xl" style="animation: float 5s ease-in-out infinite 3s;">&#9830;</div>
      </div>
    </div>
  </template>

  <!-- ================================================
       NAVBAR — Sticky glass
       ================================================ -->
  <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
       :class="scrolled ? 'glass shadow-lg shadow-black/20' : 'bg-transparent'"
       x-show="contentReady && !heroInView"
       x-transition:enter="transition-all duration-700"
       x-transition:enter-start="opacity-0 -translate-y-full"
       x-transition:enter-end="opacity-100 translate-y-0">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
      <a href="#" class="flex items-center gap-3">
        <img src="?asset=logo.png" alt="Bro & Sis" class="h-9 w-auto" />
        <span class="font-display tracking-wide leading-none" style="color: #D4AF37;">
          Bro <span class="italic">&amp;</span> Sis
        </span>
      </a>
      <div class="hidden sm:flex items-center gap-6">
        <a href="#about" class="text-cream/70 hover:text-gold transition-colors font-sans text-sm tracking-wide">About</a>
        <a href="#details" class="text-cream/70 hover:text-gold transition-colors font-sans text-sm tracking-wide">Details</a>
        <a href="#rsvp" class="px-5 py-2 bg-gradient-to-r from-gold-dark to-gold text-theatre-bg font-sans text-sm font-semibold rounded-sm hover:shadow-[0_0_20px_rgba(201,168,76,0.3)] transition-all">
          Claim Your Seat
        </a>
      </div>
      <!-- Mobile menu button -->
      <button @click="mobileMenu = !mobileMenu" class="sm:hidden flex flex-col justify-center items-center w-10 h-10 gap-1.5" aria-label="Toggle menu">
        <span class="block w-5 h-0.5 bg-gold transition-all duration-300" :class="mobileMenu ? 'rotate-45 translate-y-[4px]' : ''"></span>
        <span class="block w-5 h-0.5 bg-gold transition-all duration-300" :class="mobileMenu ? 'opacity-0' : ''"></span>
        <span class="block w-5 h-0.5 bg-gold transition-all duration-300" :class="mobileMenu ? '-rotate-45 -translate-y-[4px]' : ''"></span>
      </button>
    </div>
    <!-- Mobile dropdown menu -->
    <div x-show="mobileMenu" x-transition:enter="transition-all duration-300 ease-out"
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition-all duration-200 ease-in"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
         class="sm:hidden glass border-t border-gold/10 px-4 py-4 flex flex-col gap-3" @click.away="mobileMenu = false">
      <a href="#about" @click="mobileMenu = false" class="text-cream/70 hover:text-gold transition-colors font-sans text-sm tracking-wide py-2">About</a>
      <a href="#details" @click="mobileMenu = false" class="text-cream/70 hover:text-gold transition-colors font-sans text-sm tracking-wide py-2">Details</a>
      <a href="#rsvp" @click="mobileMenu = false" class="px-5 py-2.5 bg-gradient-to-r from-gold-dark to-gold text-theatre-bg font-sans text-sm font-semibold rounded-sm text-center hover:shadow-[0_0_20px_rgba(201,168,76,0.3)] transition-all">
        Claim Your Seat
      </a>
    </div>
  </nav>

  <!-- ================================================
       HERO SECTION
       ================================================ -->
  <section x-ref="hero" class="relative z-[10] min-h-[100dvh] flex items-center justify-center overflow-hidden curtain-texture"
           x-show="contentReady"
           x-transition:enter="transition-opacity duration-1000"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100">

    <!-- Spotlight effects -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full opacity-30"
         style="background: radial-gradient(circle, rgba(201,168,76,0.2) 0%, transparent 60%); animation: spotlight 5s ease-in-out infinite;"></div>
    <div class="absolute top-20 left-1/4 w-[400px] h-[400px] rounded-full opacity-20"
         style="background: radial-gradient(circle, rgba(107,15,26,0.3) 0%, transparent 60%); animation: spotlight 7s ease-in-out infinite 2s;"></div>
    <div class="absolute top-20 right-1/4 w-[400px] h-[400px] rounded-full opacity-20"
         style="background: radial-gradient(circle, rgba(107,15,26,0.3) 0%, transparent 60%); animation: spotlight 7s ease-in-out infinite 4s;"></div>

    <!-- Top curtain drape -->
    <div class="absolute top-0 left-0 right-0 h-32 pointer-events-none z-[1]"
         style="background: linear-gradient(180deg, rgba(107,15,26,0.5) 0%, rgba(107,15,26,0.2) 40%, transparent 100%);"></div>

    <!-- Side drapes -->
    <div class="curtain-drape-left"></div>
    <div class="curtain-drape-right"></div>

    <!-- Suit watermark -->
    <div class="suit-watermark" style="top: 10%; left: 0;">&#9829;</div>
    <div class="suit-watermark" style="bottom: 5%; right: 0;">&#9824;</div>

    <!-- Main content — Parchment scroll -->
    <div class="relative z-10 w-full max-w-2xl mx-auto px-4 py-8">
      <div class="parchment rounded-lg p-8 md:p-12 relative w-full flex flex-col justify-center my-auto"
           style="animation: fadeInScale 1s ease-out 0.3s both;">

        <!-- Corner ornaments -->
        <div class="absolute top-3 left-3 w-6 h-6 border-t-2 border-l-2 border-gold/40 rounded-tl-sm"></div>
        <div class="absolute top-3 right-3 w-6 h-6 border-t-2 border-r-2 border-gold/40 rounded-tr-sm"></div>
        <div class="absolute bottom-3 left-3 w-6 h-6 border-b-2 border-l-2 border-gold/40 rounded-bl-sm"></div>
        <div class="absolute bottom-3 right-3 w-6 h-6 border-b-2 border-r-2 border-gold/40 rounded-br-sm"></div>

        <!-- Content -->
        <div class="text-center relative z-10">
          <p class="font-body text-curtain/60 tracking-[0.25em] uppercase text-sm mb-6"
             style="animation: fadeInUp 0.8s ease-out 0.6s both;">
            Hello, You're invited to
          </p>

          <h1 class="font-display text-5xl md:text-7xl font-black mb-2"
              style="color: #6B0F1A; animation: fadeInUp 0.8s ease-out 0.8s both;">
            <span class="block">Bro <span class="italic">&amp;</span> Sis</span>
            <span class="block text-3xl md:text-4xl tracking-[0.25em] mt-2">4</span>
          </h1>

          <!-- Subtitle with divider lines -->
          <div class="flex items-center justify-center gap-4 mb-6" style="animation: fadeInUp 0.8s ease-out 1s both;">
            <div class="h-px w-12 bg-gradient-to-r from-transparent to-gold-dark/50"></div>
            <p class="font-display text-xl md:text-2xl italic" style="color: #8B1A2B;">
              The Greatest Iftar Show
            </p>
            <div class="h-px w-12 bg-gradient-to-l from-transparent to-gold-dark/50"></div>
          </div>

          <p class="font-body text-base md:text-lg leading-relaxed mb-8 max-w-md mx-auto"
             style="color: #5a4a35; animation: fadeInUp 0.8s ease-out 1.2s both;">
            You are cordially invited to a special iftar gathering dedicated to the beloved families of
            <strong class="text-curtain">OSIS 60, 61, &amp; 62</strong>.
            Let's celebrate together in joy and unity.
          </p>

          <!-- Save The Date badge -->
          <div class="inline-block mb-6" style="animation: fadeInUp 0.8s ease-out 1.4s both;">
            <div class="relative">
              <p class="font-display text-3xl md:text-4xl italic" style="color: #6B0F1A;">Save The Date!</p>
              <div class="flex items-center justify-center gap-3 mt-2">
                <div class="flex flex-col items-center">
                  <span class="h-px w-16 bg-curtain/30 mb-1"></span>
                  <span class="font-display text-sm tracking-[0.3em] uppercase" style="color: #6B0F1A;">Saturday</span>
                </div>
                <span class="font-display text-5xl md:text-6xl font-black" style="color: #6B0F1A;">7</span>
                <div class="flex flex-col items-center">
                  <span class="h-px w-16 bg-curtain/30 mb-1"></span>
                  <span class="font-display text-sm tracking-[0.3em] uppercase" style="color: #6B0F1A;">March</span>
                </div>
              </div>
            </div>
          </div>

          <!-- CTA Buttons -->
          <div class="hidden sm:flex flex-col sm:flex-row items-center justify-center gap-3" style="animation: fadeInUp 0.8s ease-out 1.8s both;">
            <a href="#rsvp"
               class="magnetic-btn group relative px-8 py-3 bg-gradient-to-r from-curtain-dark via-curtain to-curtain-light text-cream font-display font-bold tracking-wide rounded-sm overflow-hidden hover:shadow-[0_0_25px_rgba(107,15,26,0.4)] transition-all duration-300"
               @mouseenter="magneticEnter($event)" @mouseleave="magneticLeave($event)" @mousemove="magneticMove($event)">
              <span class="relative z-10">Claim Your Seat</span>

              <!-- Shine effect -->
              <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
            </a>
            <a href="#about"
               class="px-8 py-3 border border-gold/40 text-gold-dark font-display tracking-wide rounded-sm hover:bg-gold/10 transition-all duration-300">
              Learn More
            </a>
          </div>
        </div>
      </div>

    
  </section>

  <!-- ================================================
       ORNAMENTAL DIVIDER
       ================================================ -->
  <div class="ornament-divider reveal relative z-[10]" x-show="contentReady">
    <div class="ornament-line"></div>
    <div class="ornament-diamond"></div>
    <div class="ornament-line"></div>
    <div class="ornament-diamond" style="width:6px;height:6px;"></div>
    <div class="ornament-line"></div>
  </div>

  <!-- ================================================
       COUNTDOWN
       ================================================ -->
  <section class="relative z-[10] py-16 overflow-hidden" x-show="contentReady"
           x-data="countdown()">
    <!-- Sparkle accents -->
    <div class="sparkle-burst absolute top-4 left-[20%]" style="animation: sparkleBurst 3s ease-in-out infinite;"></div>
    <div class="sparkle-burst absolute bottom-4 right-[20%]" style="animation: sparkleBurst 3s ease-in-out infinite 1.5s;"></div>

    <div class="max-w-3xl mx-auto px-4 text-center">
      <p class="font-display text-xl text-gold/60 italic mb-8 reveal">The curtain rises in...</p>
      <div class="grid grid-cols-4 gap-3 md:gap-6 reveal-stagger">
        <template x-for="unit in [
          { value: days, label: 'Days' },
          { value: hours, label: 'Hours' },
          { value: minutes, label: 'Minutes' },
          { value: seconds, label: 'Seconds' }
        ]">
          <div class="reveal">
            <div class="glass rounded-lg p-3 sm:p-4 md:p-6 hover-glow flex flex-col items-center justify-center min-w-0">
              <div class="font-display text-2xl sm:text-3xl md:text-5xl font-bold text-gradient mb-1 truncate text-center w-full" x-text="String(unit.value).padStart(2, '0')"></div>
              <div class="font-sans text-[10px] md:text-xs text-cream/40 tracking-widest uppercase truncate w-full text-center mx-auto" x-text="unit.label"></div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </section>

  <!-- Film strip divider -->
  <div class="film-strip reveal" x-show="contentReady"></div>

  <!-- ================================================
       ABOUT SECTION
       ================================================ -->
  <section id="about" class="relative py-20 md:py-28 overflow-hidden" x-show="contentReady">
    <!-- Side drapes -->
    <div class="curtain-drape-left"></div>
    <div class="curtain-drape-right"></div>

    <!-- Theater ticket decoration (top right, desktop) -->
    <div class="hidden lg:block absolute top-12 right-16 transform rotate-12 opacity-40" style="animation: gentleSwing 6s ease-in-out infinite;">
      <div class="theater-ticket px-3 py-2 rounded-sm">
        <p class="font-sans text-[9px] text-curtain/60 tracking-widest uppercase">Admit One</p>
        <p class="font-display text-[10px] text-curtain font-bold">The Greatest Iftar Show</p>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-8 sm:px-10">
      <div class="grid md:grid-cols-2 gap-12 md:gap-16 items-center">

        <!-- Poster image -->
        <div class="relative reveal">
          <div class="tilt-card rounded-lg overflow-hidden shadow-2xl border border-gold/20"
               @mousemove="tiltCard($event)" @mouseleave="resetTilt($event)">
            <img src="?asset=poster.jpg" alt="Bro & Sis 4 Event Poster"
                 class="w-full h-auto" loading="lazy" />
            <!-- Shine overlay on hover -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/0 via-white/5 to-white/0 opacity-0 hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
          </div>
          <!-- Sparkle particles around poster -->
          <div class="sparkle-burst absolute -top-3 -right-3" style="animation: sparkleBurst 3s ease-in-out infinite 0.5s;"></div>
          <div class="sparkle-burst absolute -bottom-3 -left-3" style="animation: sparkleBurst 3s ease-in-out infinite 2s;"></div>
        </div>

        <!-- Description -->
        <div>
          <h2 class="font-display text-3xl md:text-4xl font-bold text-gradient mb-6 reveal">
            The Show Awaits
          </h2>
          <div class="space-y-4 text-cream/70 text-lg leading-relaxed reveal-stagger">
            <p class="reveal">
              <strong class="text-gold">Bro &amp; Sis 4: The Greatest Iftar Show</strong> is a special iftar gathering
              that brings together the OSIS family for an evening of celebration, unity, and theatrical wonder.
            </p>
            <p class="reveal">
              This year, we transform our iftar into a grand theater experience. Expect surprises, performances,
              and memories that will last a lifetime.
            </p>
          </div>

          

          <!-- P.S. Sticky note — like the poster -->
          <div class="mt-8 inline-block reveal">
            <div class="sticky-note rounded-sm px-5 py-4 max-w-xs" style="animation: gentleSwing 8s ease-in-out infinite;">
              <p class="text-amber-900 font-body text-base leading-relaxed relative z-10">
                <strong class="font-bold">P.S.</strong> Wear your best theater-inspired look.
                Someone might steal the spotlight.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Ornamental divider -->
  <div class="ornament-divider reveal" x-show="contentReady">
    <div class="ornament-line"></div>
    <div class="ornament-diamond"></div>
    <div class="ornament-line"></div>
    <div class="ornament-diamond" style="width:6px;height:6px;"></div>
    <div class="ornament-line"></div>
  </div>

  <!-- ================================================
       EVENT DETAILS
       ================================================ -->
  <section id="details" class="relative py-20 md:py-28 overflow-hidden" x-show="contentReady">
    <!-- Checkered floor -->
    <div class="checkered-floor"></div>

    <!-- Side drapes -->
    <div class="curtain-drape-left"></div>
    <div class="curtain-drape-right"></div>

    <!-- Chess piece accents -->
    <div class="hidden lg:block chess-piece" style="bottom:15%; left:5%; font-size:60px; opacity:0.04; animation-delay:1s;">&#9818;</div>
    <div class="hidden lg:block chess-piece" style="bottom:20%; right:5%; font-size:50px; opacity:0.04; animation-delay:3s;">&#9822;</div>

    <!-- Sparkles -->
    <div class="sparkle-burst absolute top-10 right-[25%]" style="animation: sparkleBurst 3s ease-in-out infinite 0.5s;"></div>
    <div class="sparkle-burst absolute bottom-20 left-[30%]" style="animation: sparkleBurst 3s ease-in-out infinite 2s;"></div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 relative z-10">
      <h2 class="font-display text-3xl md:text-4xl font-bold text-gradient text-center mb-12 reveal">
        Event Details
      </h2>

      <!-- Mobile: List layout -->
      <div class="md:hidden space-y-4 reveal-stagger">
        <!-- Date -->
        <div class="glass rounded-lg p-5 flex items-center gap-4 reveal hover-glow">
          <div class="w-12 h-12 rounded-full bg-curtain/20 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-calendar-days text-gold text-xl" aria-hidden="true"></i>
          </div>
          <div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider">Date</p>
            <p class="font-display text-lg text-cream font-bold">Saturday, 7 March 2026</p>
          </div>
        </div>
        <!-- Time -->
        <div class="glass rounded-lg p-5 flex items-center gap-4 reveal hover-glow">
          <div class="w-12 h-12 rounded-full bg-curtain/20 flex items-center justify-center flex-shrink-0">
            <i class="fa-regular fa-clock text-gold text-xl" aria-hidden="true"></i>
          </div>
          <div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider">Time</p>
            <p class="font-display text-lg text-cream font-bold">15:30 &#8211; 19:00 WIB</p>
          </div>
        </div>
        <!-- Location -->
        <div class="glass rounded-lg p-5 flex items-center gap-4 reveal hover-glow">
          <div class="w-12 h-12 rounded-full bg-curtain/20 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-location-dot text-gold text-xl" aria-hidden="true"></i>
          </div>
          <div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider">Venue</p>
            <p class="font-display text-lg text-cream font-bold">Desa Wisata Kajii</p>
          </div>
        </div>
        <!-- Contribution -->
        <div class="glass rounded-lg p-5 flex items-center gap-4 reveal hover-glow">
          <div class="w-12 h-12 rounded-full bg-curtain/20 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-ticket text-gold text-xl" aria-hidden="true"></i>
          </div>
          <div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider">Contribution</p>
            <p class="font-display text-lg text-cream font-bold">Rp 20.000 / person</p>
          </div>
        </div>
      </div>

      <!-- Desktop: Card grid -->
      <div class="hidden md:grid grid-cols-4 gap-6 reveal-stagger">
        <!-- Date Card -->
        <div class="reveal">
          <div class="tilt-card detail-card glass rounded-xl p-6 text-center h-full"
               @mousemove="tiltCard($event)" @mouseleave="resetTilt($event)">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-curtain/20 flex items-center justify-center">
              <i class="fa-solid fa-calendar-days text-gold text-2xl" aria-hidden="true"></i>
            </div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider mb-2">Date</p>
            <p class="font-display text-xl text-cream font-bold">Saturday</p>
            <p class="font-display text-3xl text-gradient font-black">7</p>
            <p class="font-display text-xl text-cream font-bold">March</p>
          </div>
        </div>
        <!-- Time Card -->
        <div class="reveal">
          <div class="tilt-card detail-card glass rounded-xl p-6 text-center h-full"
               @mousemove="tiltCard($event)" @mouseleave="resetTilt($event)">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-curtain/20 flex items-center justify-center">
              <i class="fa-regular fa-clock text-gold text-2xl" aria-hidden="true"></i>
            </div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider mb-2">Time</p>
            <p class="font-display text-xl text-cream font-bold leading-tight">15:30</p>
            <p class="text-cream/30 text-sm my-1">&#8212;</p>
            <p class="font-display text-xl text-cream font-bold leading-tight">19:00</p>
            <p class="font-sans text-xs text-cream/40 mt-1">WIB</p>
          </div>
        </div>
        <!-- Location Card -->
        <div class="reveal">
          <div class="tilt-card detail-card glass rounded-xl p-6 text-center h-full"
               @mousemove="tiltCard($event)" @mouseleave="resetTilt($event)">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-curtain/20 flex items-center justify-center">
              <i class="fa-solid fa-location-dot text-gold text-2xl" aria-hidden="true"></i>
            </div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider mb-2">Venue</p>
            <p class="font-display text-xl text-cream font-bold">Desa Wisata</p>
            <p class="font-display text-xl text-gradient font-bold">Kajii</p>
          </div>
        </div>
        <!-- Contribution Card -->
        <div class="reveal">
          <div class="tilt-card detail-card glass rounded-xl p-6 text-center h-full"
               @mousemove="tiltCard($event)" @mouseleave="resetTilt($event)">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-curtain/20 flex items-center justify-center">
              <i class="fa-solid fa-ticket text-gold text-2xl" aria-hidden="true"></i>
            </div>
            <p class="font-sans text-xs text-cream/40 uppercase tracking-wider mb-2">Contribution</p>
            <p class="font-display text-2xl text-gradient font-black">20K</p>
            <p class="font-sans text-xs text-cream/40 mt-1">per person</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================
       TICKER STRIP — Scrolling marquee
       ================================================ -->
  <div class="relative py-3 overflow-hidden bg-curtain-dark/30 border-y border-gold/10 reveal" x-show="contentReady">
    <div class="flex whitespace-nowrap" style="animation: tickerScroll 15s linear infinite;">
      <template x-for="i in 2">
        <span class="flex items-center gap-8 text-gold/30 font-display text-sm tracking-widest uppercase mr-8">
          <span>&#9829;</span>
          <span>Bro &amp; Sis 4</span>
          <span>&#9824;</span>
          <span>The Greatest Iftar Show</span>
          <span>&#9830;</span>
          <span>Saturday 7 March</span>
          <span>&#9827;</span>
          <span>Desa Wisata Kajii</span>
          <span>&#9829;</span>
          <span>15:30 &#8211; 19:00</span>
          <span>&#9824;</span>
          <span>OSIS 60, 61, &amp; 62</span>
          <span>&#9830;</span>
          <span>Theater-Inspired</span>
          <span>&#9827;</span>
        </span>
      </template>
    </div>
  </div>

  <!-- ================================================
       RSVP FORM SECTION
       ================================================ -->
  <section id="rsvp" class="relative py-20 md:py-28 curtain-texture" x-show="contentReady">
    <!-- Spotlight -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[500px] h-[500px] rounded-full opacity-20"
         style="background: radial-gradient(circle, rgba(201,168,76,0.15) 0%, transparent 60%); animation: spotlight 5s ease-in-out infinite;"></div>

    <!-- Side drapes -->
    <div class="curtain-drape-left"></div>
    <div class="curtain-drape-right"></div>

    <!-- Envelope decoration (top-right, desktop) -->
    <div class="hidden lg:block envelope-decoration" style="top: 8%; right: 8%;">
      <svg width="100" height="80" viewBox="0 0 100 80" opacity="0.3">
        <rect x="5" y="18" width="90" height="58" rx="4" fill="#C4956A" stroke="#A07E2E" stroke-width="1"/>
        <polygon points="5,18 50,52 95,18" fill="#D4A574" stroke="#A07E2E" stroke-width="0.8"/>
        <rect x="20" y="4" width="60" height="35" rx="2" fill="#F5ECD7" stroke="#E8DCC0" stroke-width="0.8"/>
      </svg>
    </div>

    <!-- Playing card decoration -->
    <div class="hidden lg:block absolute top-16 left-12 opacity-30" style="animation: gentleSwing 5s ease-in-out infinite;">
      <div class="playing-card-static">
        <div class="card-inner">
          <span class="card-corner top card-red">A<br/>&#9829;</span>
          <span class="card-center card-red" style="font-size:28px;">&#9829;</span>
          <span class="card-corner bottom card-red">A<br/>&#9829;</span>
        </div>
      </div>
    </div>

    <!-- Sparkles -->
    <div class="sparkle-burst absolute top-16 left-[20%]" style="animation: sparkleBurst 3s ease-in-out infinite;"></div>
    <div class="sparkle-burst absolute bottom-16 right-[25%]" style="animation: sparkleBurst 3s ease-in-out infinite 1.5s;"></div>
    <div class="sparkle-burst absolute top-[40%] right-[10%]" style="animation: sparkleBurst 3s ease-in-out infinite 2.5s;"></div>

    <div class="max-w-lg mx-auto px-4 sm:px-6 relative z-10">

      <!-- Stats bar -->
      <div class="flex items-center justify-center gap-8 mb-10 reveal">
        <div class="text-center">
          <div class="font-display text-3xl font-bold text-gradient" x-text="animatedStats.rsvps"></div>
          <div class="font-sans text-xs text-cream/40 tracking-wider uppercase">Registered</div>
        </div>
        <div class="w-px h-10 bg-gold/20"></div>
        <div class="text-center">
          <div class="font-display text-3xl font-bold text-gradient" x-text="animatedStats.guests"></div>
          <div class="font-sans text-xs text-cream/40 tracking-wider uppercase">Total Guests</div>
        </div>
      </div>

      <!-- FORM (not submitted) -->
      <div x-show="!submitted" class="reveal">
        <div class="glass rounded-xl shadow-2xl theatrical-frame relative z-40">
          <!-- Curtain-pattern header -->
          <div class="relative px-6 py-5 overflow-hidden"
               style="background: linear-gradient(135deg, #3D0A10 0%, #6B0F1A 50%, #3D0A10 100%);">
            <div class="absolute inset-0 opacity-10"
                 style="background: repeating-linear-gradient(90deg, transparent 0px, transparent 15px, rgba(255,255,255,0.05) 15px, rgba(255,255,255,0.05) 16px);"></div>
            <h2 class="font-display text-xl text-center text-cream tracking-[0.2em] relative z-10">YOUR TICKET DETAILS</h2>
          </div>

          <!-- Form body -->
          <div class="p-6 md:p-8 space-y-6">
            <!-- Name -->
            <div>
              <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Full Name</label>
              <input type="text" x-model="form.name"
                     class="form-input w-full px-4 py-3 rounded-lg font-body text-lg"
                     placeholder="Enter your full name" />
            </div>

            <!-- OSIS Generation -->
            <div>
              <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-3 block">OSIS Generation</label>
              <div class="grid grid-cols-3 gap-3">
                <template x-for="gen in ['60', '61', '62']">
                  <button @click="form.osis = gen" type="button"
                          class="relative py-3 rounded-lg font-display font-bold text-lg transition-all duration-300 overflow-hidden"
                          :class="form.osis === gen
                            ? 'bg-gradient-to-r from-gold-dark via-gold to-gold-light text-theatre-bg shadow-[0_0_20px_rgba(201,168,76,0.3)]'
                            : 'glass text-cream/60 hover:text-cream hover:border-gold/30'">
                    <span class="relative z-10" x-text="gen"></span>
                    <div x-show="form.osis === gen"
                         class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent"
                         style="animation: goldShine 2s ease-in-out infinite;"></div>
                  </button>
                </template>
              </div>
            </div>

            <!-- Phone (Moved up, required for all) -->
            <div>
              <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Phone / WhatsApp</label>
              <input type="tel" x-model="form.phone"
                     class="form-input w-full px-4 py-3 rounded-lg font-body text-lg"
                     placeholder="08xxxxxxxxxx" />
            </div>

            <!-- Attendance Dropdown (Custom) -->
            <div x-data="{ open: false, options: {
                   'yes': 'I shall grace the event with my presence!',
                   'maybe': 'The fates are undecided, but I hope to appear!',
                   'no': 'Alas, my journey takes me elsewhere this time.'
                 }}">
              <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Your Attendance State</label>
              <div class="relative">
                <!-- Selected Value Display -->
                <button type="button" @click="open = !open" @click.away="open = false"
                        class="form-input w-full px-4 py-3 rounded-lg font-body text-lg text-left bg-theatre-bg cursor-pointer flex justify-between items-center focus:ring-1 focus:ring-gold/50 transition-all border border-gold/20"
                        :class="form.attendance === '' ? 'text-cream/50' : 'text-cream'">
                  <span x-text="form.attendance === '' ? 'Reveal your intentions...' : options[form.attendance]" class="truncate pr-4"></span>
                  <i class="fa-solid fa-chevron-down text-gold/50 transition-transform duration-300" :class="open ? 'rotate-180' : ''"></i>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 translate-y-2"
                     class="absolute bottom-full mb-2 z-50 w-full rounded-lg bg-[#1a0f0a] border border-gold/30 shadow-[0_-10px_30px_rgba(0,0,0,0.8)] overflow-hidden">
                  <template x-for="(label, value) in options" :key="value">
                    <button type="button" @click="form.attendance = value; open = false"
                            class="w-full px-4 py-3 text-left font-body text-base md:text-lg hover:bg-gold/10 transition-colors border-b border-gold/10 last:border-b-0"
                            :class="form.attendance === value ? 'text-gold bg-gold/5' : 'text-cream/80'">
                      <span x-text="label"></span>
                    </button>
                  </template>
                </div>
              </div>
            </div>

            <!-- Yes/Maybe State (Guests) -->
            <div x-show="form.attendance === 'yes' || form.attendance === 'maybe'"
                 x-cloak
                 x-transition:enter="transition-all duration-500 delay-100"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-6">
              
              <!-- Guests -->
              <div>
                <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-3 block">Number of Guests</label>
                <div class="flex items-center justify-center gap-6">
                  <button @click="form.guests = Math.max(1, form.guests - 1)" type="button"
                          class="w-12 h-12 rounded-full glass flex items-center justify-center text-gold text-2xl font-bold hover:bg-gold/10 transition-all active:scale-90">
                    &minus;
                  </button>
                  <span class="font-display text-4xl font-bold text-gradient w-16 text-center" x-text="form.guests"></span>
                  <button @click="form.guests = Math.min(10, form.guests + 1)" type="button"
                          class="w-12 h-12 rounded-full glass flex items-center justify-center text-gold text-2xl font-bold hover:bg-gold/10 transition-all active:scale-90">
                    +
                  </button>
                </div>
              </div>

              <!-- Fee reminder -->
              <div class="glass-gold rounded-lg p-4 text-center">
                <p class="font-body text-sm text-gold/70">
                  Contribution: <strong class="text-gold font-display">Rp 20.000</strong> &times;
                  <span x-text="form.guests"></span> guest<span x-show="form.guests > 1">s</span> =
                  <strong class="text-gold font-display">Rp <span x-text="(form.guests * 20000).toLocaleString('id-ID')"></span></strong>
                </p>
              </div>
            </div>

            <!-- Message (Shown for all) -->
            <div x-show="form.attendance !== ''" x-cloak>
              <label class="font-sans text-xs text-cream/50 uppercase tracking-wider mb-2 block">Message <span class="text-cream/30">(optional)</span></label>
              <textarea x-model="form.message" rows="3"
                        class="form-input w-full px-4 py-3 rounded-lg font-body text-base resize-none"
                        placeholder="Any message for the committee..."></textarea>
            </div>

            <!-- Error -->
            <div x-show="errorMsg" x-transition class="text-red-400 text-sm text-center font-sans" x-text="errorMsg"></div>

            <!-- Submit -->
            <div x-show="form.attendance !== ''" x-cloak>
                <button @click="submitRSVP()" :disabled="submitting" type="button"
                        class="magnetic-btn group relative w-full py-4 bg-gradient-to-r from-gold-dark via-gold to-gold-light text-theatre-bg font-display font-bold text-lg tracking-wide rounded-lg overflow-hidden hover:shadow-[0_0_30px_rgba(201,168,76,0.4)] transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                        @mouseenter="magneticEnter($event)" @mouseleave="magneticLeave($event)" @mousemove="magneticMove($event)">
                  <span class="relative z-10" x-text="submitting ? 'Sending...' : (form.attendance === 'no' ? 'Send My Regards' : 'Claim My Seat')"></span>
                  <div class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent"></div>
                </button>
            </div>
</div>
        </div>
      </div>

      <!-- SUCCESS STATE -->
      <div x-show="submitted" x-transition:enter="transition-all duration-700"
           x-transition:enter-start="opacity-0 scale-90"
           x-transition:enter-end="opacity-100 scale-100"
           class="text-center">
        <!-- Attend / Maybe Success: Golden Ticket -->
        <div x-show="form.attendance !== 'no'">
          <!-- Sparkle celebration -->
          <div class="relative inline-block mb-6">
            <div class="sparkle-burst absolute -top-4 -left-4" style="animation: sparkleBurst 1.5s ease-in-out infinite;"></div>
            <div class="sparkle-burst absolute -top-4 -right-4" style="animation: sparkleBurst 1.5s ease-in-out infinite 0.3s;"></div>
            <div class="sparkle-burst absolute -bottom-4 left-1/2 -translate-x-1/2" style="animation: sparkleBurst 1.5s ease-in-out infinite 0.6s;"></div>

            <div class="w-20 h-20 mx-auto rounded-full bg-gradient-to-br from-gold-dark to-gold flex items-center justify-center shadow-[0_0_30px_rgba(201,168,76,0.3)]"
                 style="animation: bounceIn 0.8s ease-out;">
              <i class="fa-solid fa-check text-theatre-bg text-4xl" aria-hidden="true"></i>
            </div>
          </div>

          <h3 class="font-display text-3xl text-gradient font-bold mb-2" style="animation: fadeInUp 0.6s ease-out 0.3s both;">You're In!</h3>
          <p class="text-cream/50 font-body mb-8" style="animation: fadeInUp 0.6s ease-out 0.5s both;">Your seat has been claimed. See you at the show!</p>

          <!-- Ticket confirmation -->
          <div class="parchment rounded-xl max-w-md mx-auto overflow-hidden relative shadow-[0_20px_50px_rgba(0,0,0,0.5)] border border-gold/30" style="animation: fadeInUp 0.6s ease-out 0.7s both;">

            <!-- Semicircle cutouts (Middle for separating stub) -->
            <div class="absolute left-0 top-[68%] -translate-y-1/2 -translate-x-1/2 w-8 h-8 rounded-full bg-theatre-bg z-20 border-r border-gold/30 shadow-inner"></div>
            <div class="absolute right-0 top-[68%] -translate-y-1/2 translate-x-1/2 w-8 h-8 rounded-full bg-theatre-bg z-20 border-l border-gold/30 shadow-inner"></div>

            <!-- Header (Dark Red / Gold) -->
            <div class="relative px-6 py-5 text-center" style="background: linear-gradient(135deg, #2a050b, #590a14, #2a050b); border-bottom: 2px solid #8B6914;">
              <p class="font-sans text-[10px] text-gold tracking-[0.4em] uppercase mb-1">Admit One &bull; VIP Guest</p>
              <p class="font-display text-2xl text-cream font-bold uppercase tracking-wider" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">The Greatest Iftar Show</p>
            </div>

            <!-- Main Body: Attendee Info + QR Code -->
            <div class="px-6 py-6 relative z-10 flex flex-col sm:flex-row gap-6 items-center justify-between">
              
              <!-- Attendee Details (Left) -->
              <div class="flex-1 space-y-5 w-full text-left">
                <div>
                  <p class="font-sans text-[10px] uppercase tracking-widest" style="color:#8B6914;">Guest Name</p>
                  <p class="font-display text-2xl font-bold leading-none mt-1 truncate max-w-[200px]" style="color:#3D0A10;" x-text="form.name"></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest" style="color:#8B6914;">Generation</p>
                    <p class="font-display text-lg font-bold leading-tight mt-0.5" style="color:#3D0A10;">OSIS <span x-text="form.osis"></span></p>
                  </div>
                  <div>
                    <p class="font-sans text-[10px] uppercase tracking-widest" style="color:#8B6914;">Admitting</p>
                    <p class="font-display text-lg font-bold leading-tight mt-0.5" style="color:#3D0A10;">
                      <span x-text="form.guests"></span> 
                      <span class="text-xs font-body font-normal uppercase tracking-wider" style="color:#8B6914;">Seat(s)</span>
                    </p>
                  </div>
                </div>
                
                <div>
                  <p class="font-sans text-[10px] uppercase tracking-widest" style="color:#8B6914;">Contact</p>
                  <p class="font-display text-base font-bold leading-none mt-1" style="color:#3D0A10;" x-text="form.phone"></p>
                </div>
              </div>

              <!-- QR Code (Right) -->
              <div class="flex flex-col items-center justify-center shrink-0 sm:border-l-2 border-dashed sm:pl-6 border-gold/30">
                <div class="bg-white p-2 rounded-lg shadow-md mb-2 border border-gold/40">
                  <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=100x100&color=3D0A10&data=' + form.id" alt="Ticket QR" class="w-24 h-24" />
                </div>
                <p class="font-mono text-[9px] tracking-widest font-bold" style="color:#3D0A10;" x-text="form.id"></p>
              </div>
            </div>

            <!-- Dashed Line Separator (Horizontal) -->
            <div class="relative flex items-center w-full px-4">
              <div class="h-[2px] w-full border-t-2 border-dashed border-gold/40"></div>
            </div>

            <!-- Footer: Event Info -->
            <div class="px-6 py-5 bg-black/5">
              <div class="grid grid-cols-3 gap-2 text-center">
                <div class="border-r border-gold/20 pr-2">
                  <i class="fa-regular fa-calendar-days text-base mb-1" style="color:#8B6914;"></i>
                  <p class="font-sans text-[9px] uppercase tracking-wider" style="color:#8B6914;">Date</p>
                  <p class="font-display text-sm font-bold leading-tight mt-0.5" style="color:#3D0A10;">Sat, 7 Mar<br>2026</p>
                </div>
                <div class="border-r border-gold/20 px-2">
                  <i class="fa-regular fa-clock text-base mb-1" style="color:#8B6914;"></i>
                  <p class="font-sans text-[9px] uppercase tracking-wider" style="color:#8B6914;">Time</p>
                  <p class="font-display text-sm font-bold leading-tight mt-0.5" style="color:#3D0A10;">15:30 -<br>19:00 WIB</p>
                </div>
                <div class="pl-2">
                  <i class="fa-solid fa-location-dot text-base mb-1" style="color:#8B6914;"></i>
                  <p class="font-sans text-[9px] uppercase tracking-wider" style="color:#8B6914;">Venue</p>
                  <p class="font-display text-sm font-bold leading-tight mt-0.5" style="color:#3D0A10;">Desa Wisata<br>Kajii</p>
                </div>
              </div>
            </div>

          </div>
          <button @click="window.location.reload()" class="mt-8 text-cream/50 hover:text-gold text-sm underline transition-colors">Submit Another Response</button>
        </div>

        <!-- Regrets / No Success State -->
        <div x-show="form.attendance === 'no'">
            <div class="w-20 h-20 mx-auto rounded-full bg-curtain-dark/40 border border-gold/20 flex items-center justify-center mb-6 text-gold/50 shadow-[inset_0_0_15px_rgba(201,168,76,0.1)]"
                 style="animation: bounceIn 0.8s ease-out;">
              <i class="fa-solid fa-masks-theater text-4xl"></i>
            </div>
            
            <h3 class="font-display text-3xl text-gradient italic mb-4" style="animation: fadeInUp 0.6s ease-out 0.3s both;">"The show must go on..."</h3>
            
            <p class="font-body text-cream/70 text-lg leading-relaxed px-4 mb-8" style="animation: fadeInUp 0.6s ease-out 0.5s both;">
              Your regards have been sent to the committee.<br>
              Your presence will be truly missed in the audience, but we hope our paths cross again soon.
            </p>
            
            <button @click="window.location.reload()" class="mt-4 text-cream/50 hover:text-gold text-sm transition-colors uppercase tracking-widest font-sans" style="animation: fadeInUp 0.6s ease-out 0.7s both;"><i class="fa-solid fa-arrow-left mr-2"></i> Return to Poster</button>
        </div>
      </div>
    </div>
  </section>

  <!-- ================================================
       ALPINE.JS APP
       ================================================ -->
  <script>
    function rsvpApp() {
      return {
        curtainOpened: false,
        curtainDismissed: false,
        contentReady: false,
        heroInView: true,
        scrolled: false,
        scrollProgress: 0,
        submitted: false,
        submitting: false,
        errorMsg: '',
        mobileMenu: false,
        form: { attendance: '', name: '', osis: '', phone: '', guests: 1, message: '' },
        stats: { rsvps: 0, guests: 0 },
        animatedStats: { rsvps: 0, guests: 0 },

        init() {
          this.fetchStats();

          // Lock scroll behind curtain
          document.body.style.overflow = 'hidden';

          // Desktop enhancements
          if (window.innerWidth >= 768) {
            this.$nextTick(() => {
              this.setupMouseSpotlight();
            });
          }
        },

        openCurtain() {
          this.curtainOpened = true;
          document.body.style.overflow = '';

          // Show content after curtain starts opening
          setTimeout(() => {
            this.contentReady = true;
            this.$nextTick(() => {
              this.setupHeroObserver();
            });
          }, 600);

          // Setup scroll interactions after content is ready
          setTimeout(() => {
            this.setupReveal();
            this.setupScrollTracking();
            if (window.innerWidth >= 768) {
              this.setupSparkleTrail();
              this.setupParallax();
            }
          }, 1200);

          // Remove curtain overlay from DOM
          setTimeout(() => {
            this.curtainDismissed = true;
          }, 3000);
        },

        setupScrollTracking() {
          window.addEventListener('scroll', () => {
            this.scrolled = window.scrollY > 60;

            // Scroll progress bar
            const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            this.scrollProgress = scrollHeight > 0 ? (window.scrollY / scrollHeight) * 100 : 0;
          }, { passive: true });
        },

        setupHeroObserver() {
          const hero = this.$refs.hero;
          if (!hero) { this.heroInView = false; return; }

          // Fallback: if IntersectionObserver is unavailable, always show navbar.
          if (!('IntersectionObserver' in window)) { this.heroInView = false; return; }

          // Disconnect old observer (if any)
          if (this._heroObserver) {
            try { this._heroObserver.disconnect(); } catch (e) { /* ignore */ }
          }

          this._heroObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
              if (entry.target !== hero) return;
              this.heroInView = entry.isIntersecting;
              if (this.heroInView) this.mobileMenu = false;
            });
          }, { threshold: 0.1 });

          this._heroObserver.observe(hero);
        },

        setupReveal() {
          const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                // Animate counters in stats bar
                if (entry.target.closest('#rsvp')) {
                  this.animateCounter('rsvps', this.stats.rsvps);
                  this.animateCounter('guests', this.stats.guests);
                }
              }
            });
          }, { threshold: 0.1 });

          document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
        },

        setupMouseSpotlight() {
          const spotlight = this.$refs.mouseSpotlight;
          if (!spotlight) return;
          document.addEventListener('mousemove', (e) => {
            spotlight.style.left = e.clientX + 'px';
            spotlight.style.top = e.clientY + 'px';
          }, { passive: true });
        },

        setupSparkleTrail() {
          let lastTime = 0;
          document.addEventListener('mousemove', (e) => {
            const now = Date.now();
            if (now - lastTime < 100) return;
            lastTime = now;

            // Create sparkle particle
            const particle = document.createElement('div');
            particle.className = 'sparkle-particle';
            const angle = Math.random() * Math.PI * 2;
            const distance = Math.random() * 30 + 10;
            particle.style.left = (e.clientX + Math.cos(angle) * distance) + 'px';
            particle.style.top = (e.clientY + Math.sin(angle) * distance) + 'px';
            particle.style.opacity = Math.random() * 0.5 + 0.3;
            const size = Math.random() * 6 + 4;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            document.body.appendChild(particle);

            // Animate and remove
            particle.animate([
              { transform: 'scale(1) rotate(0deg)', opacity: particle.style.opacity },
              { transform: `scale(0) rotate(${Math.random() * 360}deg)`, opacity: 0 }
            ], { duration: 800, easing: 'ease-out' });

            setTimeout(() => particle.remove(), 800);
          }, { passive: true });
        },

        setupParallax() {
          // Intentionally empty — parallax via inline transform was overriding
          // CSS keyframe animations (cardDrift, chessDrift). The floating
          // decorations now rely solely on their CSS animations.
        },

        // Magnetic button effect
        magneticEnter(e) {
          e.currentTarget.style.transition = 'transform 0.2s ease-out';
        },
        magneticMove(e) {
          const btn = e.currentTarget;
          const rect = btn.getBoundingClientRect();
          const x = e.clientX - rect.left - rect.width / 2;
          const y = e.clientY - rect.top - rect.height / 2;
          btn.style.transform = `translate(${x * 0.15}px, ${y * 0.15}px)`;
        },
        magneticLeave(e) {
          e.currentTarget.style.transform = 'translate(0, 0)';
        },

        tiltCard(e) {
          const card = e.currentTarget;
          const rect = card.getBoundingClientRect();
          const x = e.clientX - rect.left;
          const y = e.clientY - rect.top;
          const centerX = rect.width / 2;
          const centerY = rect.height / 2;
          const rotateX = (y - centerY) / centerY * -8;
          const rotateY = (x - centerX) / centerX * 8;
          card.style.transform = `perspective(800px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        },
        resetTilt(e) {
          e.currentTarget.style.transform = 'perspective(800px) rotateX(0) rotateY(0)';
        },

        animateCounter(key, target) {
          if (target === 0) { this.animatedStats[key] = 0; return; }
          const duration = 1200;
          const start = performance.now();
          const from = this.animatedStats[key] || 0;

          const step = (now) => {
            const progress = Math.min((now - start) / duration, 1);
            const ease = 1 - Math.pow(1 - progress, 3);
            this.animatedStats[key] = Math.round(from + (target - from) * ease);
            if (progress < 1) requestAnimationFrame(step);
          };
          requestAnimationFrame(step);
        },

        async fetchStats() {
          try {
            const res = await fetch('<?= parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?>?api=stats');
            const data = await res.json();
            this.stats.rsvps = data.totalRSVPs || 0;
            this.stats.guests = data.totalGuests || 0;
          } catch (e) { /* silent */ }
        },

        async submitRSVP() {
          this.errorMsg = '';
          if (!this.form.attendance) { this.errorMsg = 'Please reveal your intentions first.'; return; }
          if (!this.form.name.trim()) { this.errorMsg = 'Please enter your name.'; return; }
          if (!this.form.osis) { this.errorMsg = 'Please select your OSIS generation.'; return; }
          if (!this.form.phone.trim()) { this.errorMsg = 'Please enter your phone number.'; return; }

          this.submitting = true;
          try {
            const res = await fetch('<?= parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) ?>?api=stats', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(this.form)
            });
            if (!res.ok) throw new Error('Server error');
            const data = await res.json();
            this.form.id = data.data.id;
            this.submitted = true;
            this.fetchStats();
          } catch (e) {
            this.errorMsg = 'Something went wrong. Please try again.';
          } finally {
            this.submitting = false;
          }
        },

        resetForm() {
          this.submitted = false;
          this.form = { name: '', osis: '', phone: '', guests: 1, message: '' };
          this.errorMsg = '';
        }
      };
    }

    function countdown() {
      return {
        days: 0, hours: 0, minutes: 0, seconds: 0,
        init() {
          this.update();
          setInterval(() => this.update(), 1000);
        },
        update() {
          const target = new Date('2026-03-07T15:30:00+07:00').getTime();
          const now = Date.now();
          const diff = Math.max(0, target - now);
          this.days = Math.floor(diff / (1000 * 60 * 60 * 24));
          this.hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
          this.minutes = Math.floor((diff / (1000 * 60)) % 60);
          this.seconds = Math.floor((diff / 1000) % 60);
        }
      };
    }
  </script>
</body>
</html>
