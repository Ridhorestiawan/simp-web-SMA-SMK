---
name: Academic Precision
colors:
  surface: '#f8f9fd'
  surface-dim: '#d9dade'
  surface-bright: '#f8f9fd'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f3f7'
  surface-container: '#edeef2'
  surface-container-high: '#e7e8ec'
  surface-container-highest: '#e1e2e6'
  on-surface: '#191c1f'
  on-surface-variant: '#414754'
  inverse-surface: '#2e3134'
  inverse-on-surface: '#eff1f5'
  outline: '#727785'
  outline-variant: '#c1c6d6'
  surface-tint: '#005bc0'
  primary: '#005bbf'
  on-primary: '#ffffff'
  primary-container: '#1a73e8'
  on-primary-container: '#ffffff'
  inverse-primary: '#adc7ff'
  secondary: '#5e5e62'
  on-secondary: '#ffffff'
  secondary-container: '#e3e2e6'
  on-secondary-container: '#646468'
  tertiary: '#5a5e63'
  on-tertiary: '#ffffff'
  tertiary-container: '#73777c'
  on-tertiary-container: '#ffffff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d8e2ff'
  primary-fixed-dim: '#adc7ff'
  on-primary-fixed: '#001a41'
  on-primary-fixed-variant: '#004493'
  secondary-fixed: '#e3e2e6'
  secondary-fixed-dim: '#c7c6ca'
  on-secondary-fixed: '#1a1b1e'
  on-secondary-fixed-variant: '#46474a'
  tertiary-fixed: '#dfe3e8'
  tertiary-fixed-dim: '#c3c7cc'
  on-tertiary-fixed: '#181c20'
  on-tertiary-fixed-variant: '#43474c'
  background: '#f8f9fd'
  on-background: '#191c1f'
  surface-variant: '#e1e2e6'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.02em
  display-lg-mobile:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
  title-sm:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.01em
  code-snippet:
    fontFamily: jetbrainsMono
    fontSize: 13px
    fontWeight: '400'
    lineHeight: 18px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 4px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 32px
  container-max-width: 1440px
---

## Brand & Style

The design system is engineered for a high-performance Learning Management System (LMS) specifically tailored for SMA/SMK environments. The brand personality is **Professional, Systematic, and Trustworthy**, prioritizing clarity and ease of navigation for three distinct user archetypes: Admins, Teachers, and Students.

The visual style is **Corporate Modern**, characterized by:
- **High Information Density:** Clean layouts that allow for complex data (grades, schedules, curricula) to be scanned quickly.
- **Structural Integrity:** Use of rigid alignment and consistent spacing to evoke a sense of institutional reliability.
- **Minimalist Aesthetic:** Stripping away decorative elements like gradients or complex illustrations to focus purely on educational content.
- **Functional Clarity:** Ensuring that the UI feels mature and sophisticated, intentionally avoiding "gamified" or "childish" tropes often found in primary education software.

## Colors

The palette is anchored by a high-trust **Primary Blue**, used specifically for actionable items and brand identifiers. 

- **Primary (#1A73E8):** Used for primary buttons, active states, and focus indicators.
- **Neutral Surface (#F5F6FA):** Applied to card backgrounds, sidebars, and secondary layout containers to create subtle separation from the white page background.
- **Typography Primary (#202124):** High-contrast dark gray for maximum legibility in body text and headings.
- **Typography Secondary (#5F6368):** Medium gray for labels, hints, and meta-data.
- **Semantic Colors:** Green and Red are reserved for grading status, submission success, or error alerts, following the standard professional utility scale.

## Typography

This design system utilizes **Inter** as the sole typeface family to ensure a clean, systematic feel across all platforms. The scale is built on a 14px base for standard body content, optimized for data-heavy portals.

- **Headlines:** Use semi-bold weights with slight negative letter-spacing to appear compact and authoritative.
- **Body Text:** Standardized at 14px (`body-md`) for dashboards and 16px (`body-lg`) for long-form reading materials or lesson content.
- **Labels:** Used for table headers, small captions, and tag text. These are slightly tighter and use a medium weight for clarity.
- **Monospaced:** JetBrains Mono is suggested for any technical SMK subjects involving programming or technical data.

## Layout & Spacing

The layout follows a **Fixed Grid** philosophy for desktop dashboards to ensure consistent information density, while transitioning to a **Fluid Grid** for mobile views.

- **The 8px Grid:** All internal spacing (padding, margins between elements) must be multiples of 4px/8px to maintain mathematical harmony.
- **Desktop:** 12-column grid with 24px gutters. The main navigation sidebar is fixed at 256px, while the content area expands to a maximum of 1440px.
- **Tablet:** 8-column grid with 20px gutters. Sidebars usually collapse into a hamburger menu or narrow icon bar.
- **Mobile:** 4-column fluid grid with 16px outer margins. Vertical stacking is mandatory for all card-based content.

## Elevation & Depth

To maintain a professional and flat aesthetic, depth is communicated through **Tonal Layers** and a single, disciplined shadow style.

- **Base Layer:** The white (#FFFFFF) background represents the lowest level.
- **Surface Layer:** The light gray (#F5F6FA) is used for "Well" areas or section containers that house multiple cards.
- **Card Elevation:** High-priority content containers use a white background with a specific "Soft Shadow": `0 2px 8px rgba(0, 0, 0, 0.08)`. This provides just enough lift to separate content without appearing heavy.
- **Interactive States:** On hover, cards may increase shadow spread slightly (`0 4px 12px rgba(0, 0, 0, 0.12)`) to indicate clickability.
- **Borders:** 1px solid borders in #E0E0E0 are used for input fields and table rows instead of shadows to keep the UI crisp.

## Shapes

The design system utilizes **Soft** roundedness to balance professional rigor with modern approachability.

- **Standard Elements (0.25rem / 4px):** Applied to buttons, input fields, and small tags. This "sharp-soft" look maintains a serious, academic tone.
- **Large Elements (0.5rem / 8px):** Applied to cards, modals, and featured image containers.
- **Full Rounded:** Only used for "Pill" tags (e.g., status indicators like "Completed" or "Pending") and user avatars.

## Components

- **Buttons:** Primary buttons use the Primary Blue with white text. Secondary buttons use a transparent background with a #E0E0E0 border and #202124 text. No gradients allowed.
- **Input Fields:** 1px solid border (#E0E0E0). On focus, the border changes to Primary Blue with a 2px thickness. Labels are always visible above the field in `label-sm`.
- **Cards:** White background, 8px corner radius, and the standard 0 2px 8px shadow. Used for course modules, student profiles, and grade reports.
- **Chips/Status:** Small height (24px or 32px), pill-shaped. Background colors should be very desaturated (e.g., light green for "Passed") with high-contrast dark text.
- **Lists/Tables:** Use alternating row colors (White and #F5F6FA) or simple 1px dividers. Table headers must be in `label-sm` with a light gray background.
- **Navigation:** Vertical sidebar for desktop with icons (24px) paired with `body-md` text. Active state indicated by a 4px vertical bar on the left edge in Primary Blue.
- **LMS Specifics:**
    - *Grade Indicator:* A dedicated circular or box-shaped badge with a bold weight for the score.
    - *Timeline/Progress Bar:* A simple 4px thick horizontal track using #F5F6FA with a Primary Blue fill indicating completion percentage.