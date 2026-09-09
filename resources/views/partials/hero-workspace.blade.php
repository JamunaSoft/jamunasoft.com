<div class="studio-board" aria-label="{{ __('Explore what we can build together') }}">
    <div class="studio-board-top"><span class="studio-mark">JS</span><span>{{ __('IDEA → DESIGN → BUILD → GROW') }}</span></div>
    <div class="studio-board-title">{{ __('One partner.') }}<br><span>{{ __('Every digital detail.') }}</span></div>
    <div class="studio-paths">
        @foreach ([['01', __('Build your business'), __('Websites & custom software'), route('services.index'), '↗︎'], ['02', __('Make it work smarter'), __('Automation & integrations'), route('solutions.index'), '⌘'], ['03', __('Keep it running'), __('Domains, hosting & support'), route('hosting'), '◎']] as [$number, $title, $description, $url, $symbol])
            <a href="{{ $url }}" class="studio-path">
                <span class="studio-path-number">{{ $number }}</span>
                <span><strong>{{ $title }}</strong><small>{{ $description }}</small></span>
                <span class="studio-path-symbol" aria-hidden="true">{{ $symbol }}</span>
            </a>
        @endforeach
    </div>
    <div class="studio-board-bottom"><span>{{ __('From the first conversation to what comes next.') }}</span><span aria-hidden="true">↗︎</span></div>
</div>
