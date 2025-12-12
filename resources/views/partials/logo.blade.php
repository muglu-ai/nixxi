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
    transition: all 0.3s ease;
    flex-shrink: 0;
    padding: 6px 10px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.1);
}

.nixi-logo-link:hover {
    opacity: 0.9;
    background: rgba(255, 255, 255, 0.15);
}

/* Fixed logo container for login/register pages */
.nixi-logo-fixed {
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1050;
}

.nixi-logo-fixed .nixi-logo-link {
    background: rgba(255, 255, 255, 0.95);
    padding: 8px 12px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-right: 0;
}

.nixi-logo-fixed .nixi-logo-link:hover {
    background: rgba(255, 255, 255, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
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
    
    .nixi-logo-fixed {
        top: 10px;
        left: 10px;
    }
    
    .nixi-logo-fixed .nixi-logo-link {
        padding: 6px 10px;
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
    
    .nixi-logo-fixed {
        top: 8px;
        left: 8px;
    }
    
    .nixi-logo-fixed .nixi-logo-link {
        padding: 5px 8px;
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
    
    .nixi-logo-fixed {
        top: 8px;
        left: 8px;
    }
    
    .nixi-logo-fixed .nixi-logo-link {
        padding: 5px 8px;
    }
}
</style>

