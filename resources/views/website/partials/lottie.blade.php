@php
    $lottieEmoji = [
        'bear' => '🐻', 'balloon' => '🎈', 'rainbow' => '🌈', 'sparkles' => '✨', 'star' => '⭐',
        'heart' => '💗', 'check' => '✅', 'rocket' => '🚀', 'package' => '📦',
        'money' => '💸', 'lock' => '🔒', 'hug' => '🤗', 'hearts-face' => '🥰', 'love-letter' => '💌',
        'sun' => '🌞', 'cloud' => '☁️', 'sleepy' => '😴', 'party' => '🎉', 'chick' => '🐥',
        'gift' => '🎁', 'kite' => '🪁', 'purple-heart' => '💜', 'fire' => '🔥',
        'hatching-chick' => '🐣', 'alarm-clock' => '⏰',
    ];
@endphp
<span class="bb-lottie {{ $class ?? '' }}"
      data-lottie="{{ asset('animations/'.$name.'.json') }}"
      @if(!empty($hover)) data-lottie-hover @endif
      @if(isset($loop) && ! $loop) data-lottie-loop="false" @endif
      aria-hidden="true"><span class="bb-lottie-fallback">{{ $lottieEmoji[$name] ?? '' }}</span></span>
