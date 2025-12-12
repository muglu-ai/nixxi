<div class="nixi-logo-container">
    <a href="{{ url('/') }}" class="nixi-logo-link" title="NIXI - Empowering Netizens">
        @if(file_exists(public_path('images/nixi-logo.jpg')))
            <img src="{{ asset('images/nixi-logo.jpg') }}" alt="NIXI - Empowering Netizens" class="nixi-logo">
        @elseif(file_exists(public_path('images/nixi-logo.png')))
            <img src="{{ asset('images/nixi-logo.png') }}" alt="NIXI - Empowering Netizens" class="nixi-logo">
        @elseif(file_exists(public_path('images/logo.jpg')))
            <img src="{{ asset('images/logo.jpg') }}" alt="NIXI - Empowering Netizens" class="nixi-logo">
        @elseif(file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="NIXI - Empowering Netizens" class="nixi-logo">
        @endif
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
    object-fit: contain;
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

