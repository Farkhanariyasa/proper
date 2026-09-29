# UI & Styling Design System Specification (`design.md`)

This document defines the styling tokens, visual guidelines, component design patterns, and layout structures extracted from the e-Pengantar Kerja (PAK Integration & Conversion Module) application screenshots.

---

## 1. Color Palette

### Primary & Brand Colors
* **Primary Deep Navy:** `#0E385E` / `#163A5F`
  * Applied to the global top navigation header bar, login banner backgrounds, and primary workflow buttons (`Submit`, `Sign in with Kemnaker Account`, `Submit Verifikasi`).
* **Secondary Brand Blue:** `#1F5A88` / `#2B6CB0`
  * Applied to active menu items, selected sidebar pills, and link states.
* **Document Action Blue:** `#1E40AF` / `#1E3A8A`
  * Applied to secondary document triggers such as `LIHAT PERHITUNGAN` and `Preview PAK INTEGRASI`.

### Functional & Semantic Colors
* **Action Success (Green):** `#28A745` / `#1E8E3E`
  * Applied to table action buttons (`Aksi` inspect/detail button with document icon).
* **Action Danger / Rejection (Red):** `#DC3545` / `#C82333`
  * Applied to return/correction buttons (`Periksa Kembali`) and notification counter badges on the header.
* **Action Warning (Amber/Yellow):** `#FFC107` / `#D97706`
  * Applied to revision triggers (`Revisi`).
* **Neutral Dark / Text Primary:** `#1F2937` / `#212529`
  * Applied to headings, labels, and table content.
* **Neutral Light / Borders:** `#E5E7EB` / `#DEE2E6`
  * Applied to table dividing borders, input strokes, and card separators.
* **Neutral Surface (Backgrounds):**
  * Application Canvas: `#F8FAFC` to `#FFFFFF`.
  * Card / Modal Container: `#FFFFFF`.
  * Read-only Input Field Background: `#F1F5F9` / `#E9ECEF`.

---

## 2. Typography

* **Font Family:** System UI Sans-Serif (`Inter`, `Segoe UI`, `Roboto`, `Helvetica Neue`, Arial).
* **Scale & Hierarchy:**
  * **H1 / Page Title:** `20px` – `22px` | Semi-Bold (`font-weight: 600`)
    * Examples: `List Angka Kredit Terakhir`, `Verifikasi PAK Integrasi`, `Periode Pengajuan Nilai SKP`.
  * **H2 / Modal & Card Title:** `16px` – `18px` | Bold (`font-weight: 700`)
    * Examples: `Tambah Data`, `Verifikasi Evaluasi Kinerja Pegawai`.
  * **Section Group Header:** `12px` – `13px` | Semi-Bold, Uppercase (`letter-spacing: 0.05em`)
    * Examples: `TIM PENILAI`, `PEJABAT PENILAI KINERJA`.
  * **Form Labels & Table Headings:** `12px` – `13px` | Semi-Bold / Medium (`font-weight: 500` / `600`).
  * **Table Body / Form Values:** `12px` – `13px` | Regular (`font-weight: 400`).
  * **Captions & Metadata:** `10px` – `11px` | Regular (`font-weight: 400`)
    * Examples: `Showing 1 to 4 of 4 entries`, notification timestamps (`4 minutes ago`).

---

## 3. UI Component Specifications

### 3.1. Global App Bar (Header)
* **Background:** Solid Navy `#0E385E`.
* **Height:** `56px` – `64px`.
* **Left Section:**
  * Institution logo & white branding typography `ePengantar Kerja`.
* **Right Section:**
  * Navigation links / greeting text.
  * Notification Bell icon featuring an absolute-positioned red pill badge (`#DC3545`) with white count text.
  * User profile avatar circle with initials/photo and dropdown trigger arrow.

### 3.2. Sidebar & Section Navigation
* **Container:** Fixed or off-canvas layout on white background (`#FFFFFF`).
* **Group Headers:** Uppercase light-gray or dark-slate label with collapsible chevron icon at the right edge.
* **Nav Items:**
  * Height: `36px` – `40px` per row.
  * Left icon: `3x3` grid module icon (`#475569`).
  * Text: `13px`, Dark Slate (`#334155`).
  * Active item: Light blue rounded pill background (`#E0F2FE` or `#E2E8F0`) with darker text (`#0284C7` or `#0F172A`).

### 3.3. Data Filter Toolbar
* **Layout:** Multi-column CSS grid (2 to 4 columns depending on viewport width).
* **Fields:** Standard text search, dropdown selects (`Semua` / `All`), and datepicker ranges (`dd/mm/yyyy` to `dd/mm/yyyy`).
* **Submit Action:** Right-aligned blue button (`Cari`) at the bottom-right corner of the filter box.

### 3.4. Data Tables (DataGrid)
* **Table Wrapper:** Borderless or faint single-pixel border container on white background.
* **Header Row (`<th>`):**
  * Background: `#F8FAFC` or `#FFFFFF`.
  * Border bottom: `1px solid #DEE2E6`.
  * Text: `12px`, bold, aligned left (numeric columns aligned right or center).
* **Body Rows (`<td>`):**
  * Height: `44px` – `52px` per row.
  * Border bottom: `1px solid #F1F5F9`.
  * Hover state: Subdued light gray (`#F8FAFC`).
* **Table Action Buttons:**
  * Square / compact rounded rectangle (`32px x 32px`).
  * Background: Emerald green (`#28A745`) with white center icon.
* **Pagination Bar:**
  * Left: `Showing 1 to X of Y entries` + `Show [X] entries` selector.
  * Right: Numbered pagination boxes (`Previous`, `1`, `2`, `Next`) with active blue indicator box.

### 3.5. Form Controls & Inputs
* **Text Input & Select Elements:**
  * Height: `38px`.
  * Border: `1px solid #CBD5E1`, border-radius `4px`.
  * Padding: `6px 12px`.
  * Focus: Outline glow in primary blue (`#2563EB`) with zero displacement.
  * Disabled/Read-only: Light gray fill (`#F1F5F9`), text color `#64748B`.
* **File Upload Component (`Choose File`):**
  * Native styled file input button (`Choose File`) paired with dynamic file name indicator label.
  * Supported hint below: `Format file foto/pdf (*jpg, *pdf)`.

### 3.6. Modals & Dialog Overlays
* **Overlay Backdrop:** Semi-transparent dark mask (`rgba(0, 0, 0, 0.45)`).
* **Modal Body (`.modal-card`):**
  * Background: `#FFFFFF`.
  * Border-radius: `6px` – `8px`.
  * Padding: `24px`.
  * Max width: Centered `560px` for standard inputs, `800px` – `900px` for two-column verification dossiers.
  * Close control: Top-right dismissal `×` icon.

### 3.7. Buttons & Action Links
| Button Type | Background Color | Text Color | Border Radius | Padding | Use Case |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Primary Navy** | `#0E385E` | `#FFFFFF` | `4px` | `8px 20px` | `Submit`, `Simpan`, `Submit Verifikasi` |
| **Action Green** | `#28A745` | `#FFFFFF` | `4px` | `6px 8px` | Table row action (`Aksi` button) |
| **Danger Red** | `#DC3545` | `#FFFFFF` | `4px` | `8px 16px` | `Periksa Kembali` |
| **Warning Yellow** | `#FFC107` | `#000000` | `4px` | `8px 16px` | `Revisi` |
| **Document Blue** | `#1E40AF` | `#FFFFFF` | `4px` | `6px 14px` | `LIHAT PERHITUNGAN`, `Preview PAK` |
| **Neutral Secondary** | `#6C757D` / `#94A3B8` | `#FFFFFF` | `4px` | `8px 16px` | `Batal`, `Tutup` |

---

## 4. Spacing, Elevation & Layout Patterns

* **Spacing Baseline:** `4px` / `8px` grid system.
  * Form field margin bottom: `12px` – `16px`.
  * Container padding: `16px` (mobile/tablet) to `24px` – `32px` (desktop canvas).
* **Elevation / Box Shadows:**
  * Cards & Panels: Subtle elevation `box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)`.
  * Modals: High elevation `box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04)`.
* **Information Density:** Compact to Medium. Structured to preserve vertical real estate across government management dashboards and tabular reviews.