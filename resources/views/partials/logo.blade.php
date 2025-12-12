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

<style>
.nixi-logo-link {
    display: flex;
    align-items: center;
    text-decoration: none;
    line-height: 1;
    margin-right: 15px;
    transition: opacity 0.3s ease;
    flex-shrink: 0;
}

.nixi-logo-link:hover {
    opacity: 0.9;
}

.nixi-logo {
    height: 40px;
    width: auto;
    max-width: 140px;
    object-fit: contain;
    display: block;
}

/* Ensure navbar container uses flexbox for proper alignment */
.navbar > .container-fluid {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
}

/* Responsive adjustments for mobile */
@media (max-width: 991px) {
    .nixi-logo {
        height: 35px;
        max-width: 120px;
    }
    
    .nixi-logo-link {
        margin-right: 10px;
    }
    
    /* On mobile, ensure logo and brand are on same line */
    .navbar > .container-fluid {
        flex-wrap: nowrap;
    }
}

@media (max-width: 576px) {
    .nixi-logo {
        height: 30px;
        max-width: 100px;
    }
    
    .nixi-logo-link {
        margin-right: 8px;
    }
    
    /* Reduce navbar-brand font size on very small screens */
    .navbar-brand {
        font-size: 0.9rem;
    }
}

@media (max-width: 400px) {
    .nixi-logo {
        height: 28px;
        max-width: 85px;
    }
    
    .nixi-logo-link {
        margin-right: 5px;
    }
    
    .navbar-brand {
        font-size: 0.85rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>

