# Design Document: Nursing Journal Application

## Overview

The Nursing Journal Application is a web-based nursing report system for RSI Muhammadiyah 2 Kendal that enables nurses to input patient data per shift with unit-specific fields and provides administrators with comprehensive reporting through interactive charts. The system is built with Laravel 10, MySQL, Tailwind CSS, shadcn/ui, and Recharts.

### Key Design Principles

1. **Role-Based Access Control**: Distinct interfaces and permissions for Nurses and Administrators
2. **Shift-Aware Data Entry**: Automatic shift detection based on server time (WIB/UTC+7)
3. **Unit-Specific Forms**: Dynamic form fields based on assigned nursing unit
4. **Data Visualization**: Interactive charts for trend analysis and reporting
5. **Responsive Design**: Mobile-first approach supporting 320px to 1920px viewports
6. **Security First**: HTTPS, CSRF protection, input validation, secure password hashing

---

## Architecture

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Client Layer                              │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  Browser (Blade Templates + Tailwind CSS + shadcn/ui)   │   │
│  │  - Authentication Pages                                  │   │
│  │  - Dashboard                                             │   │
│  │  - Form Input (Shift-aware, Unit-specific)              │   │
│  │  - Reporting (Recharts visualizations)                  │   │
│  │  - Management Pages (Admin only)                        │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ↓ HTTPS
┌─────────────────────────────────────────────────────────────────┐
│                      Application Layer                           │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              Laravel 10 Application                      │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │ Controllers (Request Handling & Business Logic)   │  │   │
│  │  │ - AuthController (Login, Logout, Session Mgmt)   │  │   │
│  │  │ - ReportController (Data retrieval & filtering)  │  │   │
│  │  │ - PatientDataController (CRUD operations)        │  │   │
│  │  │ - UnitController (Unit management)               │  │   │
│  │  │ - UserController (User management)               │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │ Models (Data Representation & Relationships)      │  │   │
│  │  │ - User (with roles: Admin, Nurse)                │  │   │
│  │  │ - Unit                                            │  │   │
│  │  │ - PatientData (shift-specific records)           │  │   │
│  │  │ - Session (custom session tracking)              │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │ Services (Business Logic Encapsulation)           │  │   │
│  │  │ - ShiftDetectionService                           │  │   │
│  │  │ - ReportGenerationService                         │  │   │
│  │  │ - ValidationService                               │  │   │
│  │  │ - AuthenticationService                           │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  │  ┌────────────────────────────────────────────────────┐  │   │
│  │  │ Middleware (Cross-cutting Concerns)               │  │   │
│  │  │ - Authentication (Verify user is logged in)       │  │   │
│  │  │ - Authorization (Verify role-based access)        │  │   │
│  │  │ - CSRF Protection                                 │  │   │
│  │  │ - Rate Limiting (Login attempts)                  │  │   │
│  │  └────────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      Data Layer                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              MySQL Database                             │   │
│  │  - users (authentication & roles)                       │   │
│  │  - units (nursing departments)                          │   │
│  │  - patient_data (shift-specific records)               │   │
│  │  - sessions (session management)                        │   │
│  │  - login_attempts (rate limiting)                       │   │
│  └──────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### Request Flow

```
User Request
    ↓
Route (web.php)
    ↓
Middleware Stack (Auth, CSRF, Rate Limit)
    ↓
Controller Action
    ↓
Service Layer (Business Logic)
    ↓
Model/Database Query
    ↓
Response (JSON or Blade View)
    ↓
Client Rendering
```

---

## Components and Interfaces

### 1. Authentication System

#### Components
- **Login Form**: Username/password input with validation
- **Session Manager**: Track active sessions with timeout
- **Rate Limiter**: Block after 5 failed attempts in 15 minutes
- **Logout Handler**: Clear session and redirect

#### Key Interfaces

**Login Request**
```
POST /login
{
  "username": "string",
  "password": "string"
}

Response (Success):
{
  "success": true,
  "redirect": "/dashboard",
  "message": "Login successful"
}

Response (Error):
{
  "success": false,
  "message": "Invalid credentials",
  "locked_until": "2024-01-15 10:30:00" (if account locked)
}
```

**Session Data Structure**
```
{
  "user_id": integer,
  "username": string,
  "role": "Admin" | "Nurse",
  "unit_id": integer (null for Admin),
  "unit_name": string (null for Admin),
  "login_time": timestamp,
  "last_activity": timestamp,
  "ip_address": string
}
```

### 2. Patient Data Input System

#### Components
- **Dynamic Form Generator**: Renders unit-specific fields
- **Input Validator**: Client-side and server-side validation
- **Shift Detector**: Automatic shift selection based on time
- **Data Persister**: Save to database with metadata
- **Copy-to-Clipboard**: Text output of entered data

#### Unit-Specific Field Definitions

**IGD (Emergency Department)**
```
- Jumlah pasien rawat inap (0-9999)
- Jumlah pasien rawat jalan (0-9999)
- Jumlah pasien pulang paksa (0-9999)
- Keterangan penyakit rawat inap (text)
- Keterangan penyakit rawat jalan (text)
- Total (auto-calculated)
```

**Rawat Inap (Inpatient)**
```
- Jumlah pasien anak (0-9999)
- Jumlah pasien Dalam (0-9999)
- Jumlah pasien Saraf (0-9999)
- Jumlah pasien Obsgyn (0-9999)
- Jumlah pasien Bedah (0-9999)
- Jumlah inden (0-9999)
- Jumlah RPL (0-9999)
- Jumlah pasien pulang (0-9999)
- Total (auto-calculated)
```

**Rawat Jalan (Outpatient)**
```
- Jumlah poli Obgyn (0-9999)
- Jumlah poli Dalam (0-9999)
- Jumlah poli Anak (0-9999)
- Jumlah poli Bedah (0-9999)
- Jumlah poli Saraf (0-9999)
- Jumlah poli Fisioterapi (0-9999)
- Total (auto-calculated)
```

**VK (Delivery Room)**
```
- Jumlah pasien VK (0-9999)
- Keterangan (text)
```

**ICU (Intensive Care Unit)**
```
- Jumlah pasien anak (0-9999)
- Jumlah pasien Dalam (0-9999)
- Jumlah pasien Saraf (0-9999)
- Jumlah pasien Obsgyn (0-9999)
- Jumlah pasien Bedah (0-9999)
- Jumlah pasien Inden (0-9999)
- Jumlah pasien pulang (0-9999)
```

**HCU (High Care Unit)**
```
- Jumlah pasien anak (0-9999)
- Jumlah pasien Dalam (0-9999)
- Jumlah pasien Saraf (0-9999)
- Jumlah pasien Obsgyn (0-9999)
- Jumlah pasien Bedah (0-9999)
- Jumlah pasien Inden (0-9999)
- Jumlah pasien pulang (0-9999)
```

#### Key Interfaces

**Patient Data Submission**
```
POST /patient-data/store
{
  "date": "2024-01-15",
  "shift": "Pagi" | "Siang" | "Malam",
  "unit_id": integer,
  "fields": {
    "field_name": numeric_value,
    ...
  }
}

Response (Success):
{
  "success": true,
  "message": "Data saved successfully",
  "data": { ...submitted data... },
  "text_output": "Formatted text for copying"
}

Response (Duplicate):
{
  "success": false,
  "message": "Data already exists for this date/shift/unit",
  "action": "confirm_update" | "cancel"
}
```

### 3. Shift Detection System

#### Shift Definitions (WIB/UTC+7)
- **Pagi (Morning)**: 07:00:00 - 13:59:59
- **Siang (Afternoon)**: 14:00:00 - 20:59:59
- **Malam (Night)**: 21:00:00 - 06:59:59

#### Implementation
```php
// ShiftDetectionService
public function getCurrentShift(): string {
  $now = Carbon::now('Asia/Jakarta');
  $hour = $now->hour;
  
  if ($hour >= 7 && $hour < 14) return 'Pagi';
  if ($hour >= 14 && $hour < 21) return 'Siang';
  return 'Malam';
}
```

### 4. Reporting System

#### Report Types

**Line Chart Report**
- X-axis: Date (YYYY-MM-DD)
- Y-axis: Patient count
- Multiple lines for different units (when "All Units" selected)
- Color-coded by unit
- Interactive tooltips showing date, unit, count, shift

**Candle Chart Report**
- Monthly view per unit
- Open: First entry of the day
- High: Maximum value in the day
- Low: Minimum value in the day
- Close: Last entry of the day

#### Key Interfaces

**Report Data Request**
```
GET /reports/data?unit_id=1&shift=Pagi&start_date=2024-01-01&end_date=2024-01-31

Response:
{
  "success": true,
  "data": [
    {
      "date": "2024-01-15",
      "unit_id": 1,
      "unit_name": "IGD",
      "shift": "Pagi",
      "total_patients": 45,
      "details": { ...field breakdown... }
    },
    ...
  ],
  "chart_type": "line" | "candle",
  "filters": {
    "unit_id": 1,
    "shift": "Pagi",
    "start_date": "2024-01-01",
    "end_date": "2024-01-31"
  }
}
```

### 5. Unit Management System

#### Components
- **Unit List**: Display all units with status
- **Add Unit Form**: Create new unit
- **Edit Unit Form**: Modify unit details
- **Delete Confirmation**: Warn about related data

#### Key Interfaces

**Unit CRUD Operations**
```
POST /units
{
  "name": "string (2-50 chars, alphanumeric + space)"
}

PUT /units/{id}
{
  "name": "string"
}

DELETE /units/{id}
Response includes count of related patient records

GET /units
Response: Array of all units with status
```

### 6. User Management System

#### Components
- **User List**: Display all nurses with details
- **Add User Form**: Create new nurse account
- **Edit User Form**: Modify nurse details
- **Status Toggle**: Activate/deactivate accounts

#### Key Interfaces

**User CRUD Operations**
```
POST /users
{
  "username": "string (unique)",
  "password": "string (min 8 chars)",
  "full_name": "string",
  "unit_id": integer,
  "role": "Nurse" | "Admin"
}

PUT /users/{id}
{
  "full_name": "string",
  "unit_id": integer,
  "status": "active" | "inactive"
}

GET /users
Response: Array of all users with details
```

### 7. Dashboard System

#### Nurse Dashboard Components
- Welcome message with assigned unit
- Current shift indicator
- Quick access to Form Input
- Recent data entries summary

#### Admin Dashboard Components
- Statistics cards (total units, active users)
- Quick access links (Unit Management, User Management, Reports)
- Recent activity summary

### 8. Styling Design
# Apple — Style Reference
> Precise Canvas, Vivid Product. A stark white presentation surface designed to make premium product imagery pop with singular focus.

**Theme:** light

This design system feels like meticulously crafted white space surrounding vibrant, singular product showcases. It projects an aura of premium precision through a mostly monochrome palette and minimal, crisp UI elements. The interplay of subtly differentiated neutral surfaces creates depth without relying on heavy shadows, reserving saturated color almost exclusively for product imagery or primary calls to action. Large, high-impact typography anchors sections, often accompanied by a delicate sans-serif for body text, creating a strong editorial feel.

## Tokens — Colors

| Name | Value | Token | Role |
|------|-------|-------|------|
| Midnight Graphite | `#1d1d1f` | `--color-midnight-graphite` | Primary heading and body text, button text, icon default. |
| Cloud Mist | `#6b6c6c` | `--color-cloud-mist` | Secondary body text, supporting links, muted icons, footer text. |
| Pure White | `#ffffff` | `--color-pure-white` | Primary page background, elevated card surfaces, clean sections. |
| Frost Gray | `#f3f6f6` | `--color-frost-gray` | Subtle background for navigation, subtle section dividers, tertiary surface. |
| Steel Accent | `#cccfcf` | `--color-steel-accent` | Delicate border colors, subtle outlines for form elements. |
| Dark Charcoal | `#313131` | `--color-dark-charcoal` | Tertiary text, certain icon elements, dark button text. |
| Slate Echo | `#444545` | `--color-slate-echo` | Navigation links, secondary link states, sometimes icon fills. |
| Alabaster | `#e8e8ed` | `--color-alabaster` | Button backgrounds in certain states, subtle background tint. |
| Pure Black | `#000000` | `--color-pure-black` | High-contrast text, specific icons, input text. |
| Light Pearl | `#dedfe2` | `--color-light-pearl` | Call-to-action button backgrounds when muted, form outlines. |
| Ocean Blue | `#0066cc` | `--color-ocean-blue` | Interactive links, primary action buttons, focused states. This is the dominant interactive brand color. |
| Sky Teal | `#00a1b3` | `--color-sky-teal` | Accent color for specific headings, product feature highlights. |
| Royal Violet | `#8668ff` | `--color-royal-violet` | Accent color for specific headings, highlighting unique selling points. |
| Sunset Orange | `#ed6300` | `--color-sunset-orange` | Accent color for specific headings, drawing attention to new features. |
| Flame Orange | `#b64400` | `--color-flame-orange` | Badge backgrounds for 'New' indicators or special offers. |
| Vivid Blue | `#0071e3` | `--color-vivid-blue` | Primary call to action background, navigation highlights, focus outlines. |
| Deep Sea Gradient | `linear-gradient(rgb(0, 76, 148) 45%, rgb(41, 123, 196) 90%)` | `--color-deep-sea-gradient` | Decorative background or hero element for product presentation. |
| Spectrum Gradient | `linear-gradient(90deg, rgb(0, 144, 247) 8%, rgb(186, 98, 252), rgb(242, 65, 107), rgb(245, 86, 0))` | `--color-spectrum-gradient` | High-impact visual elements, product imagery backgrounds, vivid showcases. |

## Tokens — Typography

### SF Pro Text — Primary family for all body text, navigation items, buttons, and most UI elements. Its neutrality and subtly varied weights maintain a consistent, readable tone across the interface. Heavy use of precise letter-spacing adjustments for optical balance at different sizes. · `--font-sf-pro-text`
- **Substitute:** system-ui, sans-serif
- **Weights:** 300, 400, 600
- **Sizes:** 8px, 12px, 14px, 17px, 18px, 20px, 24px, 34px, 44px
- **Line height:** 1.00, 1.18, 1.24, 1.29, 1.33, 1.43, 1.47, 1.50, 1.83, 2.12, 2.41
- **Letter spacing:** -0.031, -0.027, -0.022, -0.02, -0.019, -0.016, -0.011, -0.01, -0.003
- **OpenType features:** `"numr"`
- **Role:** Primary family for all body text, navigation items, buttons, and most UI elements. Its neutrality and subtly varied weights maintain a consistent, readable tone across the interface. Heavy use of precise letter-spacing adjustments for optical balance at different sizes.

### SF Pro Display — Used for large, impactful headlines and display text. Its slightly wider, more open forms are optimized for larger sizes, ensuring legibility and presence in hero sections and key marketing messages. Features tight negative letter-spacing for visual density. · `--font-sf-pro-display`
- **Substitute:** system-ui, sans-serif
- **Weights:** 400, 600
- **Sizes:** 21px, 28px, 40px, 48px, 56px, 80px, 160px
- **Line height:** 0.88, 1.05, 1.07, 1.08, 1.10, 1.14, 1.19, 1.38
- **Letter spacing:** -0.04, -0.015, -0.005, -0.003, 0.007, 0.011
- **OpenType features:** `"numr"`
- **Role:** Used for large, impactful headlines and display text. Its slightly wider, more open forms are optimized for larger sizes, ensuring legibility and presence in hero sections and key marketing messages. Features tight negative letter-spacing for visual density.

### Arial — Fallback for input fields, ensuring broad system compatibility. · `--font-arial`
- **Substitute:** sans-serif
- **Weights:** 400
- **Sizes:** 13px
- **Line height:** 1.20
- **Letter spacing:** 0
- **Role:** Fallback for input fields, ensuring broad system compatibility.

### Type Scale

| Role | Size | Line Height | Letter Spacing | Token |
|------|------|-------------|----------------|-------|
| body | 14px | 1.29 | -0.168px | `--text-body` |
| subheading | 18px | 1.24 | -0.342px | `--text-subheading` |
| heading-sm | 20px | 1.2 | -0.4px | `--text-heading-sm` |
| heading | 24px | 1.18 | -0.288px | `--text-heading` |
| heading-lg | 44px | 1.14 | -0.484px | `--text-heading-lg` |
| display | 80px | 1.07 | -0.8px | `--text-display` |
| display-xl | 160px | 0.88 | -0.8px | `--text-display-xl` |

## Tokens — Spacing & Shapes

**Density:** comfortable

### Spacing Scale

| Name | Value | Token |
|------|-------|-------|
| 4 | 4px | `--spacing-4` |
| 6 | 6px | `--spacing-6` |
| 8 | 8px | `--spacing-8` |
| 9 | 9px | `--spacing-9` |
| 10 | 10px | `--spacing-10` |
| 11 | 11px | `--spacing-11` |
| 13 | 13px | `--spacing-13` |
| 14 | 14px | `--spacing-14` |
| 19 | 19px | `--spacing-19` |
| 20 | 20px | `--spacing-20` |
| 25 | 25px | `--spacing-25` |
| 28 | 28px | `--spacing-28` |
| 30 | 30px | `--spacing-30` |
| 40 | 40px | `--spacing-40` |
| 44 | 44px | `--spacing-44` |
| 83 | 83px | `--spacing-83` |

### Border Radius

| Element | Value |
|---------|-------|
| cards | 28px |
| buttons | 28px |
| default | 12px |
| navigation | 980px |

### Shadows

| Name | Value | Token |
|------|-------|-------|
| subtle | `rgba(0, 0, 0, 0.11) 0px 0px 1px 0px inset` | `--shadow-subtle` |
| xl | `rgba(0, 0, 0, 0.05) 0px 0px 35px 20px` | `--shadow-xl` |

### Layout

- **Section gap:** 70px
- **Card padding:** 14px
- **Element gap:** 10px

## Components

### Text Link
**Role:** Inline navigation and information access.

Uses SF Pro Text, #0066cc (Ocean Blue), normal weight. No background or padding. Border radius 0px. On hover, may show underline or slight color change.

### Primary CTA Button (Filled)
**Role:** Main call to action for key user journeys.

Background: #0071e3 (Vivid Blue). Text: Pure White (#ffffff), SF Pro Text Medium (400), size 17px, line-height 1.18, letter-spacing -0.16px. Border radius: 28px. Padding varies due to content, but typically 11px vertical and 9px horizontal as minimum.

### Tertiary CTA Button (Ghost)
**Role:** Secondary actions or navigation that requires less visual hierarchy.

Background: rgba(0, 0, 0, 0) (transparent). Text: Midnight Graphite (#1d1d1f), SF Pro Text Medium (400), size 17px, line-height 1.18, letter-spacing -0.16px. Border radius: 28px. No visible border.

### Icon Button (Round)
**Role:** Small, interactive elements to trigger actions or navigation, focusing on visual iconography.

Background: rgba(0, 0, 0, 0) (transparent). Icon color: Midnight Graphite (#1d1d1f). Border radius: 28px/32px (pill/circular). No padding on button container, padding is internal to the icon asset.

### Product Feature Card
**Role:** Highlights key features with text and imagery.

Background: Pure White (#ffffff) or Frost Gray (#f3f6f6). Border radius: 28px. No box shadow. Padding: 14px internal consistent spacing.

### Global Navigation Link
**Role:** Top-level navigation items.

Text: Midnight Graphite (#1d1d1f) or Slate Echo (#444545). Font: SF Pro Text, typically 12px or 14px. Letter spacing -0.01em approx. Background is transparent. No border radius. Top and bottom padding of 0px, side padding 10px.

### Highlight Badge
**Role:** Indicates new products or special status.

Background: rgba(0, 0, 0, 0) (transparent). Text: Flame Orange (#b64400), SF Pro Text, 12px. No padding. Border radius 0px.

### Language Selector Input
**Role:** Allows users to choose their region/language.

Background: rgba(0, 0, 0, 0) (transparent). Text: Pure Black (#000000), Arial, 13px. Border: 1px solid Steel Accent (#cccfcf). No border-radius, or 0px. Padding implicit from component structure.

## Do's and Don'ts

### Do
- Prioritize SF Pro Text for all body copy and UI elements at weights 300, 400, and 600, applying precise letter-spacing adjustments as defined in the type scale.
- Use SF Pro Display for headlines and display text (40px and above), leveraging its tighter letter-spacing for visual impact.
- Employ Pure White (#ffffff) for primary content backgrounds and Frost Gray (#f3f6f6) for subtly differentiated sections or navigation.
- Reserve Ocean Blue (#0066cc) or Vivid Blue (#0071e3) for all primary interactive elements like buttons and links.
- Apply a 28px border radius for all cards and primary buttons to maintain a consistent soft edge.
- Maintain comfortable density spacing: 10px `elementGap` between small UI elements and a `sectionGap` of 70px to create ample breathing room between content blocks.
- Use Midnight Graphite (#1d1d1f) for primary text and Cloud Mist (#6b6c6c) for secondary/supporting text to create subtle typographic hierarchy.

### Don't
- Do not introduce new saturated colors outside of the defined accent palette; rely on product imagery for additional color.
- Avoid heavy drop shadows or glows; use subtle surface differentiation (like Pure White on Frost Gray) for depth instead.
- Do not use generic system fonts when SF Pro Text or SF Pro Display are available; they are key to brand identity.
- Do not use border radii smaller than 12px or larger than 980px, except for defined components. Stick to 28px for cards and buttons.
- Avoid arbitrary custom padding values; adhere to the established `elementGap` of 10px, `cardPadding` of 14px, and section spacing of 70px.
- Do not use highly decorative or script fonts; maintain a clean, sans-serif aesthetic throughout.
- Never use dark mode toggles or styles; the aesthetic is strictly light-themed.

## Surfaces

| Level | Name | Value | Purpose |
|-------|------|-------|---------|
| 0 | Pure White | `#ffffff` | Primary base background for the majority of page content and cards. |
| 1 | Frost Gray | `#f3f6f6` | Used for subtle background differentiation in sections or navigation bars, providing a hint of separation from the main content canvas. |
| 2 | Alabaster | `#e8e8ed` | Even subtler background for specific button states or very light contextual blocks. |

## Imagery

Imagery on this site prioritizes professional, high-fidelity product photography and 3D renders. Products are often shown isolated on clean white or gradient backgrounds, focusing on their aesthetics and features. Photography is full-color, with a bright, high-key treatment. Product screenshots are integrated to demonstrate software capabilities, often with a slight perspective to show the device itself. Iconography is minimalist and monochrome, predominantly filled glyphs with a precise, thin stroke appearance. Images are generally contained within sections, not full-bleed, and maintain sharp edges unless part of a hero section with a soft background gradient. The role of imagery is primarily product showcase and feature explanation, with a high density relative to supporting text in many sections.

## Layout

The page maintains a maximum width, centered model, providing clear boundaries for content. The hero section features a large, impactful product image or render often against a gradient background, with central large-scale typography. Following sections alternate between Pure White and Frost Gray backgrounds, creating a subtle visual rhythm. Content is arranged in alternating 2-column text+image layouts or stacked central blocks for headlines and calls to action. A prominent sticky header and secondary navigation remain at the top, offering persistent access to key links. Spacing is comfortable, utilizing generous vertical gaps between sections and internal padding, contributing to a sense of premium simplicity.

## Agent Prompt Guide

Quick Color Reference:
- Primary Text: #1d1d1f (Midnight Graphite)
- Background: #ffffff (Pure White)
- Accent/CTA: #0071e3 (Vivid Blue)
- Secondary Text: #6b6c6c (Cloud Mist)
- Section Background: #f3f6f6 (Frost Gray)

Example Component Prompts:
1. Create a Primary CTA Button: Background Vivid Blue (#0071e3), text Pure White (#ffffff), SF Pro Text Medium (400), 17px, line-height 1.18, letter-spacing -0.16px, border radius 28px, 11px vertical padding, 9px horizontal padding.
2. Design a Product Feature Card: Background Pure White (#ffffff), border radius 28px, 14px padding. Headline SF Pro Display Regular (400), 40px, line-height 1.05, letter-spacing -0.4px, color Midnight Graphite (#1d1d1f). Body text SF Pro Text Regular (400), 17px, line-height 1.29, letter-spacing -0.42px, color Midnight Graphite (#1d1d1f).
3. Create a Global Navigation Link: Text Midnight Graphite (#1d1d1f), SF Pro Text Regular (400), 14px, line-height 1.29, letter-spacing -0.14px. No background, no border. Padding 0px vertical, 10px horizontal.
4. Generate a Hero Headline: 'iPad Air' in SF Pro Display Bold (600), 80px, line-height 1.07, letter-spacing -0.8px, color Midnight Graphite (#1d1d1f). Sub-headline 'Whoosh.' in SF Pro Display Regular (400), 160px, line-height 0.88, letter-spacing -0.8px, in Ocean Blue (#0066cc) or a decorative gradient. Ensure ample vertical sectionGap (70px) and a Pure White background.
5. Implement a Badge: 'New' text, Flame Orange (#b64400), SF Pro Text Regular (400), 12px. No background, transparent padding, 0px border radius.

## Similar Brands

- **Google (Material Design)** — Shares a foundation of clear hierarchy, generous white space, and subtle elevation where shadows are minimal; distinction through surface colors.
- **Stripe** — Similar focus on premium, clean typography and minimal UI, using a few key accent colors against a largely achromatic background.
- **Samsung (product pages)** — Emphasizes product photography with high-contrast UI elements and strong headline typography, often using gradient backgrounds for hero sections.
- **Microsoft (Surface line pages)** — Leverages system fonts heavily, a minimalist aesthetic, and prioritizes clear information hierarchy to promote high-tech products.

## Quick Start

### CSS Custom Properties

```css
:root {
  /* Colors */
  --color-midnight-graphite: #1d1d1f;
  --color-cloud-mist: #6b6c6c;
  --color-pure-white: #ffffff;
  --color-frost-gray: #f3f6f6;
  --color-steel-accent: #cccfcf;
  --color-dark-charcoal: #313131;
  --color-slate-echo: #444545;
  --color-alabaster: #e8e8ed;
  --color-pure-black: #000000;
  --color-light-pearl: #dedfe2;
  --color-ocean-blue: #0066cc;
  --color-sky-teal: #00a1b3;
  --color-royal-violet: #8668ff;
  --color-sunset-orange: #ed6300;
  --color-flame-orange: #b64400;
  --color-vivid-blue: #0071e3;
  --color-deep-sea-gradient: #004c94;
  --gradient-deep-sea-gradient: linear-gradient(rgb(0, 76, 148) 45%, rgb(41, 123, 196) 90%);
  --color-spectrum-gradient: #0090f7;
  --gradient-spectrum-gradient: linear-gradient(90deg, rgb(0, 144, 247) 8%, rgb(186, 98, 252), rgb(242, 65, 107), rgb(245, 86, 0));

  /* Typography — Font Families */
  --font-sf-pro-text: 'SF Pro Text', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-sf-pro-display: 'SF Pro Display', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-arial: 'Arial', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

  /* Typography — Scale */
  --text-body: 14px;
  --leading-body: 1.29;
  --tracking-body: -0.168px;
  --text-subheading: 18px;
  --leading-subheading: 1.24;
  --tracking-subheading: -0.342px;
  --text-heading-sm: 20px;
  --leading-heading-sm: 1.2;
  --tracking-heading-sm: -0.4px;
  --text-heading: 24px;
  --leading-heading: 1.18;
  --tracking-heading: -0.288px;
  --text-heading-lg: 44px;
  --leading-heading-lg: 1.14;
  --tracking-heading-lg: -0.484px;
  --text-display: 80px;
  --leading-display: 1.07;
  --tracking-display: -0.8px;
  --text-display-xl: 160px;
  --leading-display-xl: 0.88;
  --tracking-display-xl: -0.8px;

  /* Typography — Weights */
  --font-weight-light: 300;
  --font-weight-regular: 400;
  --font-weight-semibold: 600;

  /* Spacing */
  --spacing-4: 4px;
  --spacing-6: 6px;
  --spacing-8: 8px;
  --spacing-9: 9px;
  --spacing-10: 10px;
  --spacing-11: 11px;
  --spacing-13: 13px;
  --spacing-14: 14px;
  --spacing-19: 19px;
  --spacing-20: 20px;
  --spacing-25: 25px;
  --spacing-28: 28px;
  --spacing-30: 30px;
  --spacing-40: 40px;
  --spacing-44: 44px;
  --spacing-83: 83px;

  /* Layout */
  --section-gap: 70px;
  --card-padding: 14px;
  --element-gap: 10px;

  /* Border Radius */
  --radius-xl: 12px;
  --radius-3xl: 24px;
  --radius-3xl-2: 28px;
  --radius-3xl-3: 32px;
  --radius-3xl-4: 36px;
  --radius-full: 170px;
  --radius-full-2: 980px;

  /* Named Radii */
  --radius-cards: 28px;
  --radius-buttons: 28px;
  --radius-default: 12px;
  --radius-navigation: 980px;

  /* Shadows */
  --shadow-subtle: rgba(0, 0, 0, 0.11) 0px 0px 1px 0px inset;
  --shadow-xl: rgba(0, 0, 0, 0.05) 0px 0px 35px 20px;

  /* Surfaces */
  --surface-pure-white: #ffffff;
  --surface-frost-gray: #f3f6f6;
  --surface-alabaster: #e8e8ed;
}
```

### Tailwind v4

```css
@theme {
  /* Colors */
  --color-midnight-graphite: #1d1d1f;
  --color-cloud-mist: #6b6c6c;
  --color-pure-white: #ffffff;
  --color-frost-gray: #f3f6f6;
  --color-steel-accent: #cccfcf;
  --color-dark-charcoal: #313131;
  --color-slate-echo: #444545;
  --color-alabaster: #e8e8ed;
  --color-pure-black: #000000;
  --color-light-pearl: #dedfe2;
  --color-ocean-blue: #0066cc;
  --color-sky-teal: #00a1b3;
  --color-royal-violet: #8668ff;
  --color-sunset-orange: #ed6300;
  --color-flame-orange: #b64400;
  --color-vivid-blue: #0071e3;
  --color-deep-sea-gradient: #004c94;
  --color-spectrum-gradient: #0090f7;

  /* Typography */
  --font-sf-pro-text: 'SF Pro Text', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-sf-pro-display: 'SF Pro Display', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-arial: 'Arial', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

  /* Typography — Scale */
  --text-body: 14px;
  --leading-body: 1.29;
  --tracking-body: -0.168px;
  --text-subheading: 18px;
  --leading-subheading: 1.24;
  --tracking-subheading: -0.342px;
  --text-heading-sm: 20px;
  --leading-heading-sm: 1.2;
  --tracking-heading-sm: -0.4px;
  --text-heading: 24px;
  --leading-heading: 1.18;
  --tracking-heading: -0.288px;
  --text-heading-lg: 44px;
  --leading-heading-lg: 1.14;
  --tracking-heading-lg: -0.484px;
  --text-display: 80px;
  --leading-display: 1.07;
  --tracking-display: -0.8px;
  --text-display-xl: 160px;
  --leading-display-xl: 0.88;
  --tracking-display-xl: -0.8px;

  /* Spacing */
  --spacing-4: 4px;
  --spacing-6: 6px;
  --spacing-8: 8px;
  --spacing-9: 9px;
  --spacing-10: 10px;
  --spacing-11: 11px;
  --spacing-13: 13px;
  --spacing-14: 14px;
  --spacing-19: 19px;
  --spacing-20: 20px;
  --spacing-25: 25px;
  --spacing-28: 28px;
  --spacing-30: 30px;
  --spacing-40: 40px;
  --spacing-44: 44px;
  --spacing-83: 83px;

  /* Border Radius */
  --radius-xl: 12px;
  --radius-3xl: 24px;
  --radius-3xl-2: 28px;
  --radius-3xl-3: 32px;
  --radius-3xl-4: 36px;
  --radius-full: 170px;
  --radius-full-2: 980px;

  /* Shadows */
  --shadow-subtle: rgba(0, 0, 0, 0.11) 0px 0px 1px 0px inset;
  --shadow-xl: rgba(0, 0, 0, 0.05) 0px 0px 35px 20px;
}
```


## Data Models

### Database Schema

#### Users Table
```sql
CREATE TABLE users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  role ENUM('Admin', 'Nurse') NOT NULL,
  unit_id BIGINT UNSIGNED,
  status ENUM('active', 'inactive') DEFAULT 'active',
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
);
```

#### Units Table
```sql
CREATE TABLE units (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(255) UNIQUE NOT NULL,
  status ENUM('active', 'inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Patient Data Table
```sql
CREATE TABLE patient_data (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  shift ENUM('Pagi', 'Siang', 'Malam') NOT NULL,
  data JSON NOT NULL,
  total_patients INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_entry (unit_id, date, shift),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
  INDEX idx_date (date),
  INDEX idx_unit_date (unit_id, date),
  INDEX idx_shift (shift)
);
```

#### Sessions Table
```sql
CREATE TABLE sessions (
  id VARCHAR(255) PRIMARY KEY,
  user_id BIGINT UNSIGNED,
  ip_address VARCHAR(45),
  user_agent TEXT,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_id (user_id),
  INDEX idx_last_activity (last_activity)
);
```

#### Login Attempts Table
```sql
CREATE TABLE login_attempts (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  success BOOLEAN DEFAULT FALSE,
  INDEX idx_username_time (username, attempted_at),
  INDEX idx_ip_time (ip_address, attempted_at)
);
```

### Eloquent Models

**User Model**
```php
class User extends Model {
  protected $fillable = ['username', 'password', 'full_name', 'role', 'unit_id', 'status'];
  protected $hidden = ['password'];
  
  public function unit() {
    return $this->belongsTo(Unit::class);
  }
  
  public function patientData() {
    return $this->hasMany(PatientData::class);
  }
  
  public function isAdmin() {
    return $this->role === 'Admin';
  }
  
  public function isNurse() {
    return $this->role === 'Nurse';
  }
}
```

**Unit Model**
```php
class Unit extends Model {
  protected $fillable = ['name', 'status'];
  
  public function users() {
    return $this->hasMany(User::class);
  }
  
  public function patientData() {
    return $this->hasMany(PatientData::class);
  }
  
  public function getFieldDefinition() {
    // Returns unit-specific field definitions
  }
}
```

**PatientData Model**
```php
class PatientData extends Model {
  protected $fillable = ['user_id', 'unit_id', 'date', 'shift', 'data', 'total_patients'];
  protected $casts = ['data' => 'array', 'date' => 'date'];
  
  public function user() {
    return $this->belongsTo(User::class);
  }
  
  public function unit() {
    return $this->belongsTo(Unit::class);
  }
}
```

---

## API Endpoints

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|----------------|
| POST | `/login` | User login | No |
| POST | `/logout` | User logout | Yes |
| GET | `/session/check` | Check session status | Yes |

### Patient Data Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|----------------|------|
| GET | `/patient-data/form` | Get form for current shift | Yes | Nurse |
| POST | `/patient-data/store` | Save patient data | Yes | Nurse |
| GET | `/patient-data/latest` | Get latest entry for unit | Yes | Nurse |

### Report Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|----------------|------|
| GET | `/reports` | Report page | Yes | Admin |
| GET | `/reports/data` | Get report data (JSON) | Yes | Admin |
| GET | `/reports/monthly/{unit}/{month}` | Monthly candle chart data | Yes | Admin |

### Unit Management Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|----------------|------|
| GET | `/units` | List all units | Yes | Admin |
| POST | `/units` | Create unit | Yes | Admin |
| PUT | `/units/{id}` | Update unit | Yes | Admin |
| DELETE | `/units/{id}` | Delete unit | Yes | Admin |

### User Management Endpoints

| Method | Endpoint | Description | Auth Required | Role |
|--------|----------|-------------|----------------|------|
| GET | `/users` | List all users | Yes | Admin |
| POST | `/users` | Create user | Yes | Admin |
| PUT | `/users/{id}` | Update user | Yes | Admin |
| DELETE | `/users/{id}` | Deactivate user | Yes | Admin |

### Dashboard Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|----------------|
| GET | `/dashboard` | Dashboard page | Yes |
| GET | `/dashboard/stats` | Dashboard statistics (JSON) | Yes |

---

## UI Components

### Layout Components

**Main Layout**
- Header with logo, user info, logout button
- Sidebar/Navigation (hamburger on mobile)
- Main content area
- Footer with copyright

**Responsive Navigation**
- Desktop: Horizontal or vertical sidebar
- Mobile (≤768px): Hamburger menu with slide-out drawer

### Form Components

**Patient Data Form**
- Dynamic field rendering based on unit
- Real-time validation with inline error messages
- Auto-calculated total fields
- Submit button with loading state
- Success/error notifications
- Copy-to-clipboard button for text output

**Login Form**
- Username input
- Password input
- Remember me checkbox (optional)
- Submit button
- Error message display
- Account locked message (if applicable)

### Chart Components

**Line Chart**
- Recharts LineChart component
- Multiple lines for different units
- Interactive legend
- Tooltip with date, unit, count, shift
- Responsive container
- Horizontal scroll on mobile

**Candle Chart**
- Recharts ComposedChart with custom candle rendering
- Monthly view
- Open, High, Low, Close values
- Interactive tooltips

### Data Table Components

**Unit Management Table**
- Columns: Name, Status, Actions
- Edit/Delete buttons
- Add new unit button
- Search/filter capability

**User Management Table**
- Columns: Username, Full Name, Unit, Status, Actions
- Edit/Deactivate buttons
- Add new user button
- Search/filter capability

### Notification Components

**Toast Notifications**
- Success messages (green)
- Error messages (red)
- Warning messages (yellow)
- Info messages (blue)
- Auto-dismiss after 3 seconds

**Dialog Components**
- Confirmation dialogs for destructive actions
- Alert dialogs for important information
- Modal forms for data entry

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Invalid Credentials Return Generic Error

*For any* invalid username/password combination, the system SHALL return a generic error message that does not reveal which field (username or password) is incorrect.

**Validates: Requirements 1.2**

### Property 2: Rate Limiting Blocks After Failed Attempts

*For any* sequence of 5 failed login attempts within a 15-minute window, the system SHALL lock the account for 15 minutes and prevent further login attempts.

**Validates: Requirements 1.3**

### Property 3: Session Timeout After Inactivity

*For any* session inactive for 60 or more minutes, the system SHALL automatically terminate the session and redirect the user to the login page.

**Validates: Requirements 1.4**

### Property 4: Session Contains Required User Data

*For any* logged-in user, the session SHALL contain user_id, role (Admin or Nurse), and unit_id (if applicable).

**Validates: Requirements 1.5**

### Property 5: Logout Clears Session Data

*For any* active session, logout SHALL clear all session data from both server and client, and redirect to the login page.

**Validates: Requirements 1.6**

### Property 6: Unit-Specific Form Fields Display Correctly

*For any* nursing unit, the patient data form SHALL display exactly the unit-specific fields defined in the requirements for that unit.

**Validates: Requirements 2.1, 2.8**

### Property 7: Valid Patient Data Persists to Database

*For any* valid numeric values (0-9999) submitted for all required fields, the patient data SHALL be saved to the database with correct metadata (date, shift, unit, user).

**Validates: Requirements 2.2**

### Property 8: Missing Required Fields Trigger Validation

*For any* combination of missing required fields, the form SHALL display inline validation messages for each missing field without clearing previously entered data.

**Validates: Requirements 2.3**

### Property 9: Successful Save Clears Form

*For any* successful patient data save, the system SHALL display a success notification and clear the form for the next entry.

**Validates: Requirements 2.4**

### Property 10: Failed Save Preserves Form Data

*For any* save failure (connection error, server error), the system SHALL display an error message and preserve all data entered in the form.

**Validates: Requirements 2.5**

### Property 11: Duplicate Entry Triggers Confirmation

*For any* attempt to save data for a date/shift/unit combination that already exists, the system SHALL display a confirmation dialog asking whether to update or cancel.

**Validates: Requirements 2.6**

### Property 12: Saved Data Generates Text Output

*For any* successfully saved patient data, the system SHALL generate a formatted text representation that can be copied to clipboard.

**Validates: Requirements 2.7**

### Property 13: Shift Detection Morning Hours

*For any* current time between 07:00:00 and 13:59:59 (WIB/UTC+7), the system SHALL set the default shift to "Pagi".

**Validates: Requirements 3.1**

### Property 14: Shift Detection Afternoon Hours

*For any* current time between 14:00:00 and 20:59:59 (WIB/UTC+7), the system SHALL set the default shift to "Siang".

**Validates: Requirements 3.2**

### Property 15: Shift Detection Night Hours

*For any* current time between 21:00:00 and 06:59:59 (WIB/UTC+7), the system SHALL set the default shift to "Malam".

**Validates: Requirements 3.3**

### Property 16: Shift Dropdown Contains All Options

*For any* form load, the shift dropdown SHALL display exactly three options: Pagi, Siang, and Malam.

**Validates: Requirements 3.4**

### Property 17: Shift Resets on New Form Load

*For any* new form load, the shift SHALL be set to the current time's shift, not the previously selected shift.

**Validates: Requirements 3.5**

### Property 18: Report Page Displays Filter Controls

*For any* report page load, the system SHALL display filter controls for unit, shift, and date range, along with a responsive chart area.

**Validates: Requirements 4.1, 5.1**

### Property 19: Line Chart Renders with Correct Axes

*For any* valid date range, the system SHALL render a line chart with dates on the X-axis and patient counts on the Y-axis.

**Validates: Requirements 4.2**

### Property 20: Unit Filter Displays Only Selected Unit Data

*For any* single unit selection, the chart SHALL display only data for that unit.

**Validates: Requirements 4.3**

### Property 21: All Units Selection Shows Multiple Lines

*For any* "All Units" selection, the chart SHALL display each unit as a separate colored line.

**Validates: Requirements 4.4**

### Property 22: Chart Renders Responsively

*For any* viewport size from 320px to 1920px, the chart SHALL render responsively without unnecessary horizontal scrolling (except intentional chart scrolling on mobile).

**Validates: Requirements 4.5, 8.1**

### Property 23: Tooltip Displays Complete Information

*For any* chart data point, hovering over it SHALL display a tooltip containing date, unit name, patient count, and shift.

**Validates: Requirements 4.6**

### Property 24: Empty Data Shows Informative Message

*For any* filter combination with no data, the system SHALL display an empty chart area with an informative message indicating no data matches the filters.

**Validates: Requirements 4.7, 5.5**

### Property 25: Loading Timeout Displays Error

*For any* data load exceeding 5 seconds, the system SHALL hide the loading indicator and display an error message.

**Validates: Requirements 4.8**

### Property 26: Candle Chart Renders Monthly Data

*For any* month and unit selection, the system SHALL render a candle chart displaying open, high, low, and close values for each day.

**Validates: Requirements 4.9**

### Property 27: Filter Changes Update Chart Quickly

*For any* filter change, the chart SHALL update within 3 seconds without requiring a page reload.

**Validates: Requirements 5.2**

### Property 28: First Report Load Shows Today's Data

*For any* first report page load, the system SHALL display today's data for all units and all shifts as the default filter.

**Validates: Requirements 5.3, 5.4**

### Property 29: Invalid Date Range Shows Validation Error

*For any* start_date greater than end_date, the system SHALL display a validation error and not update the chart.

**Validates: Requirements 5.6**

### Property 30: Filtered Data Matches All Criteria

*For any* filter combination, the displayed data SHALL only include records matching all selected unit, shift, and date range criteria.

**Validates: Requirements 5.7**

### Property 31: Unit List Displays All Units

*For any* set of units in the database, the unit management page SHALL display all units with their names and status.

**Validates: Requirements 6.1**

### Property 32: Valid Unit Name Saves Successfully

*For any* unit name with 2-50 characters containing only letters, numbers, and spaces, the system SHALL save the unit to the database.

**Validates: Requirements 6.2**

### Property 33: Duplicate Unit Name Rejected

*For any* unit name that already exists (case-insensitive comparison), the system SHALL display an error message and prevent the duplicate from being saved.

**Validates: Requirements 6.3, 6.9**

### Property 34: Unit Edit Updates Database

*For any* valid unit name change, the system SHALL update the unit in the database and display a success notification.

**Validates: Requirements 6.4**

### Property 35: Delete Unit with Data Shows Warning

*For any* unit with related patient data, attempting to delete SHALL display a confirmation dialog with a warning about the related data.

**Validates: Requirements 6.5**

### Property 36: Confirmed Unit Delete Removes Record

*For any* confirmed unit deletion, the system SHALL remove the unit from the database and display a success notification.

**Validates: Requirements 6.6**

### Property 37: Cancelled Unit Delete Preserves Record

*For any* cancelled unit deletion, the system SHALL close the dialog without removing the unit, and the unit SHALL remain in the database.

**Validates: Requirements 6.7**

### Property 38: Invalid Unit Name Length Rejected

*For any* unit name with fewer than 2 characters or more than 50 characters, the system SHALL display a validation error.

**Validates: Requirements 6.8**

### Property 39: User List Displays All Users

*For any* set of users in the database, the user management page SHALL display all users with their username, full name, assigned unit, and status.

**Validates: Requirements 7.1**

### Property 40: Valid User Data Saves Successfully

*For any* valid user data (username, password ≥8 chars, full name, unit), the system SHALL save the user to the database with a hashed password.

**Validates: Requirements 7.2**

### Property 41: User Creation Failure Shows Generic Error

*For any* user creation failure (connection error, database error), the system SHALL display a generic error message without exposing technical details.

**Validates: Requirements 7.3**

### Property 42: Duplicate Username Rejected

*For any* username that already exists, the system SHALL display an error message and prevent the duplicate from being saved.

**Validates: Requirements 7.4**

### Property 43: User Has Exactly One Role

*For any* user in the system, they SHALL have exactly one role: either Admin or Nurse.

**Validates: Requirements 7.5**

### Property 44: User Unit Assignment Updates

*For any* change to a user's assigned unit, the system SHALL update the assignment in the database and display a success notification.

**Validates: Requirements 7.6**

### Property 45: Deactivated User Cannot Login

*For any* deactivated user account, login attempts SHALL be prevented and the user SHALL be logged out if currently active.

**Validates: Requirements 7.7**

### Property 46: Reactivated User Can Login

*For any* reactivated user account, login SHALL be allowed and the user SHALL be able to access the system normally.

**Validates: Requirements 7.8**

### Property 47: Password Stored as Hash

*For any* user password, the system SHALL store it as a bcrypt hash, never in plaintext.

**Validates: Requirements 7.9, 10.4**

### Property 48: Mobile Navigation Shows Hamburger Menu

*For any* viewport width of 768px or less, the system SHALL display a hamburger menu instead of the full navigation.

**Validates: Requirements 8.2**

### Property 49: Mobile Chart Scrolls Horizontally

*For any* viewport width of 768px or less, charts on the report page SHALL be scrollable horizontally with clear visual indicators.

**Validates: Requirements 8.4**

### Property 50: Mobile Form Uses Single Column

*For any* viewport width of 768px or less, the patient data form SHALL display in a single column with tap targets of at least 44x44px.

**Validates: Requirements 8.5, 8.6**

### Property 51: Dashboard Shows Role-Appropriate Content

*For any* user role, the dashboard SHALL display information and links appropriate to that role only.

**Validates: Requirements 9.1, 9.4**

### Property 52: Nurse Dashboard Shows Required Information

*For any* nurse user, the dashboard SHALL display their assigned unit, current shift, and a link to the patient data form.

**Validates: Requirements 9.2**

### Property 53: Admin Dashboard Shows Required Statistics

*For any* admin user, the dashboard SHALL display the total number of units, number of active users, and links to management pages.

**Validates: Requirements 9.3**

### Property 54: CSRF Token Present on State-Changing Forms

*For any* form that modifies data, the system SHALL include a CSRF token and validate it on submission.

**Validates: Requirements 10.2**

### Property 55: Malicious Input Sanitized

*For any* input containing SQL injection or XSS attack patterns, the system SHALL sanitize it on the server side and prevent execution.

**Validates: Requirements 10.3**

### Property 56: Role-Based Data Access Control

*For any* nurse user, they SHALL only be able to view and edit patient data for their assigned unit.

**Validates: Requirements 10.5**

### Property 57: Session Cleanup on Logout

*For any* logout or session expiration event, the system SHALL clear all session data from both server and client.

**Validates: Requirements 10.6**

---

## Error Handling

### Client-Side Validation

1. **Required Fields**: Display inline error message
2. **Numeric Range**: Validate 0-9999 for patient counts
3. **Text Length**: Validate 2-50 characters for unit names
4. **Password Strength**: Minimum 8 characters
5. **Date Range**: Start date ≤ End date, max 90 days

### Server-Side Validation

1. **Input Sanitization**: Remove/escape special characters
2. **Type Checking**: Ensure correct data types
3. **Business Logic**: Validate against database constraints
4. **Authorization**: Verify user has permission for action

### Error Responses

```json
{
  "success": false,
  "message": "User-friendly error message",
  "errors": {
    "field_name": ["Error message for field"]
  },
  "code": "ERROR_CODE"
}
```

### Error Codes

- `INVALID_CREDENTIALS`: Login failed
- `ACCOUNT_LOCKED`: Too many login attempts
- `SESSION_EXPIRED`: Session timeout
- `UNAUTHORIZED`: User lacks permission
- `VALIDATION_ERROR`: Input validation failed
- `DUPLICATE_ENTRY`: Unique constraint violation
- `NOT_FOUND`: Resource not found
- `SERVER_ERROR`: Internal server error

---

## Testing Strategy

### Unit Testing

**Authentication Tests**
- Valid login credentials → successful authentication
- Invalid credentials → error message
- Failed login attempts → rate limiting
- Session timeout → automatic logout
- Logout → session cleared

**Patient Data Tests**
- Valid data submission → saved to database
- Missing required fields → validation error
- Duplicate entry → confirmation dialog
- Shift detection → correct shift assigned
- Unit-specific fields → correct fields displayed

**Shift Detection Tests**
- Time 07:00-13:59 → Pagi shift
- Time 14:00-20:59 → Siang shift
- Time 21:00-06:59 → Malam shift
- Shift change boundary → correct shift

**Report Generation Tests**
- Valid date range → data retrieved
- Invalid date range → validation error
- No data for filter → empty chart with message
- Unit filter → correct data displayed
- Shift filter → correct data displayed

**Unit Management Tests**
- Create unit → saved to database
- Duplicate name → error message
- Edit unit → updated in database
- Delete unit with data → confirmation warning
- Delete unit without data → deleted successfully

**User Management Tests**
- Create user → saved with hashed password
- Duplicate username → error message
- Edit user → updated in database
- Deactivate user → login prevented
- Activate user → login allowed

### Integration Testing

**Authentication Flow**
- Login → Dashboard → Logout → Login page

**Data Entry Flow**
- Login as Nurse → Form Input → Submit → Success notification → Data in database

**Reporting Flow**
- Login as Admin → Reports → Apply filters → Chart updates → Export data

**User Management Flow**
- Login as Admin → User Management → Create/Edit/Deactivate user → Verify changes

### Performance Testing

- Page load time < 2 seconds
- Form submission < 1 second
- Chart rendering < 3 seconds
- Report data retrieval < 3 seconds

### Security Testing

- HTTPS enforced
- CSRF tokens validated
- SQL injection prevention
- XSS prevention
- Password hashing verified
- Session security verified

### Responsive Design Testing

- 320px viewport: Mobile layout
- 768px viewport: Tablet layout
- 1024px viewport: Desktop layout
- 1920px viewport: Large desktop layout
- Touch targets ≥ 44x44px on mobile
- No horizontal scrolling (except charts)

---

## Implementation Approach

### Phase 1: Foundation (Week 1)

1. **Database Setup**
   - Create migrations for all tables
   - Set up relationships and indexes
   - Create seeders for initial data

2. **Authentication System**
   - Implement login/logout
   - Session management
   - Rate limiting
   - Password hashing

3. **Base Layout**
   - Create main layout template
   - Navigation structure
   - Responsive design foundation

### Phase 2: Core Features (Week 2-3)

1. **Patient Data Input**
   - Create form component
   - Implement shift detection
   - Unit-specific field rendering
   - Data validation and storage

2. **Dashboard**
   - Nurse dashboard
   - Admin dashboard
   - Quick access links

3. **Unit Management**
   - CRUD operations
   - Validation
   - UI components

### Phase 3: Reporting (Week 4)

1. **Report System**
   - Data retrieval and filtering
   - Line chart implementation
   - Candle chart implementation
   - Interactive features

2. **User Management**
   - CRUD operations
   - Role-based access
   - Status management

### Phase 4: Polish & Security (Week 5)

1. **Security Hardening**
   - CSRF protection
   - Input validation
   - XSS prevention
   - SQL injection prevention

2. **Responsive Design**
   - Mobile optimization
   - Touch interactions
   - Performance optimization

3. **Testing & QA**
   - Unit tests
   - Integration tests
   - User acceptance testing

---

## Technology Stack Details

### Backend

**Laravel 10**
- Eloquent ORM for database operations
- Blade templating engine
- Built-in authentication scaffolding
- Middleware for cross-cutting concerns
- Service providers for dependency injection

**MySQL**
- InnoDB storage engine for ACID compliance
- Proper indexing for query performance
- Foreign key constraints for data integrity

### Frontend

**Tailwind CSS**
- Utility-first CSS framework
- Responsive design utilities
- Dark mode support (optional)
- Component composition

**shadcn/ui**
- Pre-built accessible components
- Customizable with Tailwind
- Form components with validation
- Dialog, dropdown, table components

**Recharts**
- React-based charting library
- Responsive containers
- Interactive tooltips
- Multiple chart types

### Development Tools

- **Vite**: Fast build tool for frontend assets
- **Composer**: PHP dependency management
- **npm**: JavaScript package management
- **PHPUnit**: Testing framework
- **Laravel Tinker**: REPL for debugging

---

## Security Considerations

### Authentication & Authorization

1. **Password Security**
   - Bcrypt hashing with cost factor 12
   - Minimum 8 characters
   - No password hints or recovery via email

2. **Session Management**
   - Secure session cookies (HttpOnly, Secure, SameSite)
   - 60-minute inactivity timeout
   - Session regeneration on login
   - IP address validation

3. **Rate Limiting**
   - 5 failed login attempts → 15-minute lockout
   - Per-IP and per-username tracking
   - Exponential backoff for repeated attempts

### Data Protection

1. **Input Validation**
   - Server-side validation for all inputs
   - Whitelist approach for allowed characters
   - Type checking and range validation

2. **SQL Injection Prevention**
   - Parameterized queries via Eloquent ORM
   - No raw SQL concatenation
   - Input escaping where necessary

3. **XSS Prevention**
   - Blade template auto-escaping
   - Content Security Policy headers
   - Sanitization of user-generated content

### Transport Security

1. **HTTPS**
   - SSL/TLS certificates
   - HTTP to HTTPS redirect
   - HSTS headers

2. **CSRF Protection**
   - Token-based CSRF protection
   - Token validation on state-changing requests
   - SameSite cookie attribute

### Access Control

1. **Role-Based Access Control (RBAC)**
   - Admin role: Full system access
   - Nurse role: Limited to assigned unit
   - Middleware-based authorization

2. **Data Isolation**
   - Nurses can only view/edit their unit's data
   - Admins can view all data
   - Query-level filtering

---

## Deployment Considerations

### Environment Configuration

- `.env` file for sensitive configuration
- Separate configs for development, staging, production
- Database credentials secured
- API keys and secrets managed

### Database Migrations

- Version-controlled migrations
- Rollback capability
- Zero-downtime deployments

### Performance Optimization

- Database query optimization
- Caching strategies (Redis)
- Asset minification and bundling
- CDN for static assets

### Monitoring & Logging

- Application error logging
- User activity logging
- Performance monitoring
- Security event logging

---

## Future Enhancements

1. **Export Functionality**: Export reports to PDF/Excel
2. **Email Notifications**: Alert admins of data anomalies
3. **Mobile App**: Native mobile application
4. **Advanced Analytics**: Predictive analytics and trends
5. **Multi-language Support**: Indonesian and English
6. **Audit Trail**: Complete audit log of all changes
7. **API Documentation**: OpenAPI/Swagger documentation
8. **Two-Factor Authentication**: Enhanced security
9. **Data Backup**: Automated backup and recovery
10. **Performance Dashboard**: System health monitoring

