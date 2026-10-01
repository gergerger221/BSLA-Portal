# UI Modal & Popup Design Rule

## Core Directive:
**NEVER use browser-native dialogs (`alert()`, `confirm()`, `prompt()`) anywhere in the application.**

## Required Standard for all UI Dialogs & Alerts:
1. **Custom Popup Modals Only**:
   - Every confirmation, deletion, lock action, warning, error, and notification must use a custom Vue modal component or inline popup dialog.
2. **Visual & Aesthetic Specifications**:
   - **Backdrop**: Semi-transparent dark blur (`bg-black/60 backdrop-blur-sm`).
   - **Card**: Rounded corners (`rounded-3xl`), border accents (`border border-slate-200`), and depth shadows (`shadow-2xl`).
   - **State-Aware Icons & Badges**: Prominently display matching Lucide icons inside colored badge containers (e.g., Emerald for Success/Declare, Amber for Warnings/Unlocking, Rose for Deletions, Purple for Academic Actions).
   - **Informative Content**: Clear headers, subtitles, and bulleted consequence lists so the user understands the impact of their action.
   - **Interactive Buttons**: Styled Cancel (ghost/slate) and primary Action buttons with interactive loading spinners (`animate-spin`) during async operations.
3. **Applies To**:
   - Confirmations (Locks, approvals, transfers, deletions).
   - Validation & Error reporting.
   - Toast/Banner notices.
