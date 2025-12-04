<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to NIXI</title>
  <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
  <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">

    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
 <style>
    :root {
      --green: #2ecc71;
      --dark: #222;
      --bg: #ecfdf1;
    }

    body {
      margin: 0;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg);
      font-family: 'Valera round', sans-serif;
      overflow: hidden;
    }

    .container {
      text-align: center;
      animation: fadeIn 1.5s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .scene {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 100px;
      position: relative;
      margin-bottom: 2rem;
    }

    svg {
      width: 100px;
      height: 140px;
    }

    .person {
      animation: float 3s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }

    .plug {
      width: 60px;
      height: 30px;
      background: var(--dark);
      border-radius: 6px;
      position: relative;
      animation: pulse 2s infinite ease-in-out;
    }

    .plug::before, .plug::after {
      content: "";
      position: absolute;
      width: 8px;
      height: 14px;
      background: var(--dark);
      top: 8px;
      border-radius: 2px;
    }

    .plug::before { left: 6px; }
    .plug::after { right: 6px; }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.1); opacity: 0.9; }
    }

    .wire {
      position: absolute;
      height: 4px;
      width: 200px;
      background: var(--green);
      top: 65px;
      border-radius: 4px;
      z-index: -1;
      animation: glow 2s ease-in-out infinite;
    }

    .wire.left {
      left: calc(50% - 160px);
      transform-origin: right center;
      animation-delay: 0.2s;
    }

    .wire.right {
      right: calc(50% - 160px);
      transform-origin: left center;
      animation-delay: 0.5s;
    }

    @keyframes glow {
      0%, 100% { box-shadow: 0 0 5px var(--green); opacity: 0.8; }
      50% { box-shadow: 0 0 15px var(--green); opacity: 1; }
    }

    .bolt {
      position: absolute;
      top: 30px;
      font-size: 1.5rem;
      color: var(--green);
      animation: flash 1.5s infinite;
    }

    .bolt.left { left: 45%; transform: rotate(-20deg); }
    .bolt.right { right: 45%; transform: rotate(20deg); }

    @keyframes flash {
      0%,100% { opacity: 0; transform: scale(0.8); }
      50% { opacity: 1; transform: scale(1.1); }
    }

    h1 {
      color: var(--dark);
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    p {
      color: #555;
      margin-bottom: 1.5rem;
    }

    .btn {
      display: inline-block;
      background: var(--green);
      color: white;
      text-decoration: none;
      padding: 0.8rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #27ae60;
    }

    .brand {
      margin-top: 1rem;
      font-weight: 500;
      color: var(--green);
      letter-spacing: 1px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="scene">
      <!-- Left Person -->
      <svg class="person" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <circle cx="32" cy="12" r="8" fill="#2ecc71"/>
        <rect x="26" y="20" width="12" height="20" fill="#2ecc71" rx="3"/>
        <rect x="16" y="40" width="8" height="20" fill="#2ecc71" rx="2"/>
        <rect x="40" y="40" width="8" height="20" fill="#2ecc71" rx="2"/>
        <rect x="10" y="25" width="8" height="6" fill="#2ecc71" transform="rotate(-20 10 25)"/>
      </svg>

      <!-- Plug Connection -->
      <div class="plug"></div>

      <!-- Right Person -->
      <svg class="person" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <circle cx="32" cy="12" r="8" fill="#2ecc71"/>
        <rect x="26" y="20" width="12" height="20" fill="#2ecc71" rx="3"/>
        <rect x="16" y="40" width="8" height="20" fill="#2ecc71" rx="2"/>
        <rect x="40" y="40" width="8" height="20" fill="#2ecc71" rx="2"/>
        <rect x="46" y="25" width="8" height="6" fill="#2ecc71" transform="rotate(20 46 25)"/>
      </svg>

      <!-- Connection Glow and Bolts -->
      <div class="wire left"></div>
      <div class="wire right"></div>
      <div class="bolt left">⚡</div>
      <div class="bolt right">⚡</div>
    </div>

    <h1>Welcome to NIXI</h1>
    <p>Connecting you to the future of India's Internet.</p>
    <a href="https://nixi.in/" class="btn">View Website</a>
    <div class="brand">National Internet Exchange of India</div>
  </div>
</body>
</html>
