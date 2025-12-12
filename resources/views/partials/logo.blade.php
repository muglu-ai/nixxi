<div class="nixi-logo-container">
    <a href="{{ url('/') }}" class="nixi-logo-link" title="NIXI - Empowering Netizens">
        <svg class="nixi-logo" viewBox="0 0 180 50" xmlns="http://www.w3.org/2000/svg">
            <!-- Letter 'n' -->
            <text x="2" y="28" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="#1f3ba0">n</text>
            
            <!-- Letter 'i' (first) -->
            <text x="22" y="28" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="#1f3ba0">i</text>
            <rect x="24" y="5" width="5" height="5" fill="#1f3ba0" rx="0.5"/>
            
            <!-- Letter 'x' - Three overlapping diagonal bars -->
            <!-- Orange bar (leftmost, upward) -->
            <line x1="42" y1="35" x2="54" y2="12" stroke="#ff6b35" stroke-width="5.5" stroke-linecap="round"/>
            <!-- Green bar (middle, upward) -->
            <line x1="46" y1="35" x2="58" y2="12" stroke="#2ecc71" stroke-width="5.5" stroke-linecap="round"/>
            <!-- Dark blue bar (rightmost, downward) -->
            <line x1="42" y1="12" x2="58" y2="35" stroke="#1f3ba0" stroke-width="5.5" stroke-linecap="round"/>
            
            <!-- Letter 'i' (second) -->
            <text x="64" y="28" font-family="Arial, sans-serif" font-size="28" font-weight="bold" fill="#1f3ba0">i</text>
            <rect x="66" y="5" width="5" height="5" fill="#1f3ba0" rx="0.5"/>
            
            <!-- Tagline "Empowering Netizens" -->
            <text x="2" y="42" font-family="Arial, sans-serif" font-size="9" fill="#9ca3af" letter-spacing="0.3">Empowering Netizens</text>
        </svg>
    </a>
</div>

<style>
.nixi-logo-container {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1050;
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 12px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.nixi-logo-container:hover {
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.nixi-logo-link {
    display: block;
    text-decoration: none;
    line-height: 1;
}

.nixi-logo {
    width: 130px;
    height: auto;
    display: block;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .nixi-logo-container {
        background: rgba(30, 30, 30, 0.95);
    }
    
    .nixi-logo-container:hover {
        background: rgba(30, 30, 30, 1);
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .nixi-logo-container {
        top: 10px;
        left: 10px;
        padding: 6px 10px;
    }
    
    .nixi-logo {
        width: 100px;
    }
}

@media (max-width: 480px) {
    .nixi-logo-container {
        top: 8px;
        left: 8px;
        padding: 5px 8px;
    }
    
    .nixi-logo {
        width: 85px;
    }
}
</style>

