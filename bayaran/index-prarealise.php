<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Segera Hadir</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --bg: #0f172a;
      --primary: #38bdf8;
      --text: #e2e8f0;
      --accent: #f472b6;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      min-height: 100vh;
      padding: 2rem;
      animation: fadeIn 1s ease-out;
    }

    .container {
      max-width: 600px;
    }

    h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      animation: pulse 2s infinite;
      color: var(--primary);
    }

    p {
      font-size: 1.2rem;
      color: var(--text);
      margin-bottom: 2rem;
    }

    .icon {
      width: 80px;
      height: 80px;
      margin: 1.5rem auto;
      animation: pulseIcon 2s infinite;
      filter: drop-shadow(0 0 5px var(--accent));
    }

    footer {
      font-size: 0.9rem;
      color: #94a3b8;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.05); opacity: 0.8; }
    }

    @keyframes pulseIcon {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.15); }
    }
  </style>
</head>
<body>
  <div class="container"> 
    <!--<img src="https://cdn-icons-png.flaticon.com/512/9026/9026812.png" alt="Rocket Icon" class="icon" /> -->
    <img src="https://bayaran.smpistora.sch.id/new/uploads/logo/1747735569_b6f8718770b5fc3a9c7d.png" alt="istora1999" class="icon" />
    <h1>Tampilan Baru Segera Hadir</h1>
    <p>Kami sedang melakukan migrasi data sekaligus merombak tampilan.<br> Segera hadir di tahun ajaran baru 2025/2026!</p>
    <footer>© 2025 SMP Islam Tambora</footer>
  </div>
</body>
</html>
