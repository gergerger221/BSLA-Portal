<template>
  <div :class="['relative inline-flex items-center justify-center shrink-0 select-none', customClass]">
    <svg 
      :viewBox="'0 0 120 120'" 
      fill="none" 
      xmlns="http://www.w3.org/2000/svg"
      class="w-full h-full drop-shadow-md"
    >
      <defs>
        <!-- Gradients -->
        <linearGradient id="shieldGrad" x1="60" y1="10" x2="60" y2="110" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#0e2a47" />
          <stop offset="50%" stop-color="#0a1d33" />
          <stop offset="100%" stop-color="#05101d" />
        </linearGradient>

        <linearGradient id="goldGrad" x1="20" y1="20" x2="100" y2="100" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#fef08a" />
          <stop offset="40%" stop-color="#eab308" />
          <stop offset="70%" stop-color="#ca8a04" />
          <stop offset="100%" stop-color="#a16207" />
        </linearGradient>

        <linearGradient id="goldLight" x1="0" y1="0" x2="1" y2="1">
          <stop offset="0%" stop-color="#fef9c3" />
          <stop offset="100%" stop-color="#facc15" />
        </linearGradient>

        <linearGradient id="torchFlames" x1="60" y1="36" x2="60" y2="52" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#ef4444" />
          <stop offset="50%" stop-color="#f97316" />
          <stop offset="100%" stop-color="#eab308" />
        </linearGradient>

        <linearGradient id="ribbonGrad" x1="20" y1="92" x2="100" y2="108" gradientUnits="userSpaceOnUse">
          <stop offset="0%" stop-color="#1e3a8a" />
          <stop offset="50%" stop-color="#1e40af" />
          <stop offset="100%" stop-color="#0f172a" />
        </linearGradient>

        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
          <feGaussianBlur stdDeviation="1.5" result="blur" />
          <feComposite in="SourceGraphic" in2="blur" operator="over" />
        </filter>
      </defs>

      <!-- Outer Ring with Golden Accents -->
      <circle cx="60" cy="60" r="56" fill="url(#shieldGrad)" stroke="url(#goldGrad)" stroke-width="2.5" />
      <circle cx="60" cy="60" r="51" stroke="url(#goldGrad)" stroke-width="0.8" stroke-dasharray="2 1.5" />

      <!-- Circular Text Path Ring -->
      <path id="textPathUpper" d="M 20 60 A 40 40 0 0 1 100 60" fill="none" />
      <path id="textPathLower" d="M 100 60 A 40 40 0 0 1 20 60" fill="none" />

      <!-- Curved Text (SVG Text) -->
      <text font-family="'Cinzel', 'Times New Roman', serif" font-size="6" font-weight="900" fill="#fef08a" letter-spacing="1.2">
        <textPath href="#textPathUpper" startOffset="50%" text-anchor="middle">
          BIRINGAN SCIENCE ACADEMY
        </textPath>
      </text>

      <text font-family="'Cinzel', 'Times New Roman', serif" font-size="5.2" font-weight="800" fill="#cbd5e1" letter-spacing="1">
        <textPath href="#textPathLower" startOffset="50%" text-anchor="middle">
          LEADERSHIP • EXCELLENCE
        </textPath>
      </text>

      <!-- Inner Crest Circle / Shield Base -->
      <circle cx="60" cy="60" r="34" fill="#081422" stroke="url(#goldGrad)" stroke-width="1.5" />
      
      <!-- Laurel Wreath Leaves (Left Side) -->
      <g stroke="url(#goldGrad)" stroke-width="0.8" fill="url(#goldLight)" opacity="0.9">
        <path d="M 33 60 C 31 54, 34 47, 39 42 C 37 46, 37 53, 36 60 Z" />
        <path d="M 35 50 C 33 44, 38 39, 44 36 C 41 41, 40 46, 38 52 Z" />
        <path d="M 40 42 C 39 36, 46 32, 51 31 C 47 35, 45 40, 43 45 Z" />
        <path d="M 33 62 C 31 68, 35 74, 40 78 C 38 74, 37 68, 36 62 Z" />
        <path d="M 36 72 C 35 78, 41 82, 47 84 C 44 80, 42 75, 39 70 Z" />
      </g>

      <!-- Laurel Wreath Leaves (Right Side) -->
      <g stroke="url(#goldGrad)" stroke-width="0.8" fill="url(#goldLight)" opacity="0.9">
        <path d="M 87 60 C 89 54, 86 47, 81 42 C 83 46, 83 53, 84 60 Z" />
        <path d="M 85 50 C 87 44, 82 39, 76 36 C 79 41, 80 46, 82 52 Z" />
        <path d="M 80 42 C 81 36, 74 32, 69 31 C 73 35, 75 40, 77 45 Z" />
        <path d="M 87 62 C 89 68, 85 74, 80 78 C 82 74, 83 68, 84 62 Z" />
        <path d="M 84 72 C 85 78, 79 82, 73 84 C 76 80, 78 75, 81 70 Z" />
      </g>

      <!-- Atomic Science Orbit Rings (Represents Science) -->
      <ellipse cx="60" cy="60" rx="20" ry="7" stroke="#38bdf8" stroke-width="0.75" opacity="0.6" transform="rotate(-30 60 60)" />
      <ellipse cx="60" cy="60" rx="20" ry="7" stroke="#38bdf8" stroke-width="0.75" opacity="0.6" transform="rotate(30 60 60)" />
      <circle cx="75" cy="51" r="1.2" fill="#38bdf8" />
      <circle cx="45" cy="69" r="1.2" fill="#38bdf8" />

      <!-- Center Shield / Crest -->
      <path 
        d="M 48 45 L 72 45 C 72 45, 74 62, 60 74 C 46 62, 48 45, 48 45 Z" 
        fill="#0b1b2d" 
        stroke="url(#goldGrad)" 
        stroke-width="1.2" 
      />

      <!-- Open Book of Knowledge -->
      <g stroke="#f8fafc" stroke-width="0.8" fill="#f1f5f9">
        <!-- Left Page -->
        <path d="M 60 64 C 56 61, 52 61, 49 63 L 49 56 C 53 54, 57 54, 60 57 Z" fill="#e2e8f0" stroke="#94a3b8" />
        <!-- Right Page -->
        <path d="M 60 64 C 64 61, 68 61, 71 63 L 71 56 C 67 54, 63 54, 60 57 Z" fill="#ffffff" stroke="#94a3b8" />
        <!-- Page lines -->
        <line x1="51" y1="58" x2="57" y2="56.5" stroke="#64748b" stroke-width="0.5" />
        <line x1="51" y1="60.5" x2="57" y2="59" stroke="#64748b" stroke-width="0.5" />
        <line x1="63" y1="56.5" x2="69" y2="58" stroke="#64748b" stroke-width="0.5" />
        <line x1="63" y1="59" x2="69" y2="60.5" stroke="#64748b" stroke-width="0.5" />
      </g>

      <!-- Torch of Wisdom / Enlightenment (Above book) -->
      <g>
        <!-- Torch Handle -->
        <path d="M 58.5 54 L 61.5 54 L 60.8 59 L 59.2 59 Z" fill="url(#goldGrad)" stroke="#78350f" stroke-width="0.3" />
        <!-- Torch Bowl -->
        <path d="M 57 53.5 C 57 52.5, 63 52.5, 63 53.5 L 61.5 55 L 58.5 55 Z" fill="url(#goldLight)" stroke="#ca8a04" stroke-width="0.3" />
        <!-- Flame -->
        <path d="M 60 41 C 62 45, 64 47, 62 51 C 61 52.5, 59 52.5, 58 51 C 56 48, 58 45, 60 41 Z" fill="url(#torchFlames)" filter="url(#glow)" />
        <path d="M 60 44 C 61 46.5, 62 48, 61 50 C 60.5 51, 59.5 51, 59 50 C 58 48.5, 59 46.5, 60 44 Z" fill="#fef08a" />
      </g>

      <!-- Three DepEd / Academic Stars -->
      <!-- Center Star -->
      <polygon points="60,26 61.2,29.5 65,29.5 62,31.8 63.2,35.5 60,33.2 56.8,35.5 58,31.8 55,29.5 58.8,29.5" fill="url(#goldGrad)" stroke="#ca8a04" stroke-width="0.2" />
      <!-- Left Star -->
      <polygon points="46,29 47,31.8 50,31.8 47.6,33.6 48.5,36.5 46,34.7 43.5,36.5 44.4,33.6 42,31.8 45,31.8" fill="url(#goldGrad)" stroke="#ca8a04" stroke-width="0.2" transform="scale(0.85) translate(8, 5)" />
      <!-- Right Star -->
      <polygon points="74,29 75,31.8 78,31.8 75.6,33.6 76.5,36.5 74,34.7 71.5,36.5 72.4,33.6 70,31.8 73,31.8" fill="url(#goldGrad)" stroke="#ca8a04" stroke-width="0.2" transform="scale(0.85) translate(13, 5)" />

      <!-- Bottom Foundation Ribbon Banner -->
      <g filter="url(#glow)">
        <!-- Ribbon Shape -->
        <path 
          d="M 34 88 L 42 85 L 78 85 L 86 88 L 82 95 L 78 93 L 60 97 L 42 93 L 38 95 Z" 
          fill="url(#ribbonGrad)" 
          stroke="url(#goldGrad)" 
          stroke-width="0.8" 
        />
        <!-- Ribbon Ends -->
        <path d="M 34 88 L 30 93 L 38 95 L 35 91 Z" fill="#0f172a" stroke="url(#goldGrad)" stroke-width="0.5" />
        <path d="M 86 88 L 90 93 L 82 95 L 85 91 Z" fill="#0f172a" stroke="url(#goldGrad)" stroke-width="0.5" />
      </g>

      <!-- Ribbon Text: EST. 2012 -->
      <text 
        x="60" 
        y="92.5" 
        text-anchor="middle" 
        font-family="'Cinzel', 'Times New Roman', serif" 
        font-size="5.5" 
        font-weight="900" 
        fill="#fef08a" 
        letter-spacing="0.8"
      >
        EST. 2012
      </text>
    </svg>
  </div>
</template>

<script setup>
defineProps({
  customClass: {
    type: String,
    default: 'w-10 h-10'
  }
});
</script>
