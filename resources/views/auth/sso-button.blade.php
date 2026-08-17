<div style="margin-top: 1.5rem;">
    {{-- Divider --}}
    <div style="position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 1.25rem;">
        <div style="position: absolute; inset: 0; display: flex; align-items: center;">
            <div style="width: 100%; border-top: 1px solid #e5e7eb;"></div>
        </div>
        <div style="position: relative; padding: 0 0.75rem; background: inherit;">
            <span style="font-size: 0.75rem; font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; background-color: #ffffff; padding: 0 0.5rem; border-radius: 4px;">atau</span>
        </div>
    </div>

    {{-- SSO Button --}}
    <a href="{{ route('sipetra.login') }}"
       style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 0.625rem 1.25rem; 
              font-size: 0.875rem; font-weight: 500; color: #374151; text-decoration: none;
              background-color: #ffffff; border: 1px solid #d1d5db; border-radius: 0.5rem; 
              box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); 
              transition: all 0.2s ease; cursor: pointer;"
       onmouseover="this.style.backgroundColor='#f9fafb'; this.style.borderColor='#60a5fa'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)';"
       onmouseout="this.style.backgroundColor='#ffffff'; this.style.borderColor='#d1d5db'; this.style.boxShadow='0 1px 2px 0 rgba(0,0,0,0.05)';">

        {{-- Logo BPS --}}
        <img src="{{ file_exists(public_path('assets/logo_bps.png')) ? asset('assets/logo_bps.png') : (file_exists(public_path('images/logo_bps.png')) ? asset('images/logo_bps.png') : asset('assets/logo.svg')) }}" 
             alt="Logo BPS" 
             style="width: 20px; height: 20px; object-fit: contain; margin-right: 0.75rem; flex-shrink: 0;"
             loading="lazy">

        {{-- Label --}}
        <span style="font-weight: 600; color: #1f2937;">Masuk dengan SIPETRA SSO</span>

        {{-- Arrow --}}
        <svg style="width: 16px; height: 16px; margin-left: 0.5rem; color: #9ca3af; flex-shrink: 0;" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Info text --}}
    <p style="margin-top: 0.75rem; text-align: center; font-size: 0.75rem; color: #6b7280;">
        Login terpusat menggunakan akun BPS Kabupaten Demak
    </p>
</div>
