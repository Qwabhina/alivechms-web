# Design System Documentation Integration Plan

## Executive Summary

This document outlines the comprehensive integration plan for all newly added components, composables, and utilities into the AliveCHMS design system documentation.

**Current Status:**
- ✅ 11 new components implemented and committed
- ✅ 2 new composables implemented and committed  
- ✅ Enhanced utilities (date.ts, currency formatting) implemented
- ✅ Build passing (2207 modules, 86 chunks)
- ⚠️ **Documentation coverage: 67% → Target: 100%**

---

## Phase 1: Core Components Documentation (HIGH PRIORITY)

### 1.1 Update CoreView.vue

**Add documentation for:**
- **ChIcon** - Icon component with 60+ Lucide presets
- **ChTooltip** - CSS-only tooltip with 4 placements
- **ChPopover** - Floating popup with click/hover/focus triggers
- **ChDropdown** - Dropdown menu with search functionality

**Location:** `frontend/src/views/docs/CoreView.vue`

**New Sections to Add:**
```typescript
// After ChDivider section (around line 2200)

// ============================================================
// ChIcon COMPONENT
// ============================================================
/** Icon size demo */
const iconSizeDemo = ref<'xs' | 'sm' | 'md' | 'lg' | 'xl'>('md')

/** Icon color variants */
const iconColors = ['default', 'primary', 'success', 'warning', 'danger', 'info', 'muted', 'inherit']

// ============================================================
// ChTooltip COMPONENT
// ============================================================
/** Tooltip placement demo */
const tooltipPlacement = ref<TooltipPlacement>('top')

// ============================================================
// ChPopover COMPONENT
// ============================================================
/** Popover open state */
const popoverOpen = ref(false)

// ============================================================
// ChDropdown COMPONENT
// ============================================================
/** Dropdown items */
const dropdownItems = ref([
  { value: 'edit', label: 'Edit', icon: editIcon },
  { value: 'delete', label: 'Delete', variant: 'danger' as const },
])
```

---

### 1.2 Create InteractiveView.vue

**Purpose:** Dedicated documentation for interactive components (Tooltip, Popover, Dropdown)

**File:** `frontend/src/views/docs/InteractiveView.vue`

**Sections:**
1. **Tooltip Demonstrations**
   - Basic tooltip with content
   - Tooltip with title
   - All 4 placements (top, bottom, left, right)
   - Rich content with slots
   - Delay configuration

2. **Popover Demonstrations**
   - Click-triggered popover
   - Hover-triggered popover
   - Focus-triggered popover
   - Modal popover with backdrop
   - Header/footer slots

3. **Dropdown Demonstrations**
   - Basic dropdown menu
   - Dropdown with search
   - Dropdown with custom items
   - Dropdown with dividers
   - Controlled open state

**Route:** `/docs/interactive`

---

## Phase 2: Navigation Components (HIGH PRIORITY)

### 2.1 Update NavigationView.vue

**Add documentation for:**
- **ChBreadcrumb** - Hierarchical navigation
- **ChCommandPalette** - Keyboard-driven command menu (Ctrl+K)

**New Sections:**

```typescript
// =============================================================================
// SECTION: ChBreadcrumb - Hierarchical Navigation
// =============================================================================

/** Breadcrumb items */
const breadcrumbItems = ref([
  { label: 'Home', href: '/' },
  { label: 'Members', href: '/members' },
  { label: 'John Doe', href: '/members/1' },
])

/** Separator styles */
const breadcrumbSeparators = ['/', '>', 'chevron', 'arrow']

// =============================================================================
// SECTION: ChCommandPalette - Keyboard Command Menu
// =============================================================================

/** Command palette open state */
const commandPaletteOpen = ref(false)

/** Available commands */
const commands = ref([
  { id: 'dashboard', label: 'Dashboard', icon: HomeIcon },
  { id: 'members', label: 'Members', icon: UsersIcon },
  { id: 'contributions', label: 'Contributions', icon: DollarSignIcon },
])
```

---

## Phase 3: Data Display Components (MEDIUM PRIORITY)

### 3.1 Update DataView.vue

**Add documentation for:**
- **ChAccordion** - Collapsible content sections
- **ChCarousel** - Image/content slider
- **ChEmptyState** - Empty state displays

**New Sections:**

```typescript
// =============================================================================
// SECTION: ChAccordion - Collapsible Content
// =============================================================================

/** Accordion state */
const accordionValue = ref('section-1')

/** Accordion items */
const accordionItems = ref([
  { value: 'section-1', title: 'Getting Started', content: '...' },
  { value: 'section-2', title: 'Account Settings', content: '...' },
])

// =============================================================================
// SECTION: ChCarousel - Image Slider
// =============================================================================

/** Carousel slides */
const carouselSlides = ref([
  { src: '/img/slide1.jpg', alt: 'Church event 1', title: 'Annual Conference' },
  { src: '/img/slide2.jpg', alt: 'Church event 2', title: 'Youth Camp' },
])

// =============================================================================
// SECTION: ChEmptyState - Empty States
// =============================================================================

/** Empty state presets */
const emptyStateIcons = ['search', 'inbox', 'folder', 'calendar', 'users', 'document']
```

---

## Phase 4: Composables & Utilities (MEDIUM PRIORITY)

### 4.1 Create ComposablesView.vue

**Purpose:** Documentation for all composables and utility functions

**File:** `frontend/src/views/docs/ComposablesView.vue`

**Sections:**

1. **useValidation**
   - Basic field validation
   - Built-in validators (required, email, phone, etc.)
   - Custom validators
   - Async validation
   - Form-level validation with useForm

2. **useLocalStorage**
   - Basic localStorage usage
   - TTL (time-to-live) support
   - Cross-tab synchronization
   - useSessionStorage alternative

3. **Date Utilities**
   - Format dates (format, formatISO, formatRelative)
   - Parse dates
   - Compare dates (isToday, isSameDay, etc.)
   - Add/subtract time (addDays, addMonths, etc.)
   - Start/end of periods (startOfMonth, endOfYear, etc.)
   - Calculate differences (differenceInDays, etc.)

4. **Enhanced Currency Formatting**
   - formatCurrency with options
   - formatCurrencyCompact
   - formatNumber
   - Locale support

**Route:** `/docs/composables`

---

## Phase 5: Layout Updates (CRITICAL)

### 5.1 Update DocLayout.vue

**Add to sidebar navigation:**

```typescript
const sidebarSections: NavSection[] = [
  // ... existing sections
  
  {
    id: 'interactive',
    label: 'Interactive',
    icon: MousePointer,
    items: [
      { label: 'Tooltip', to: '/docs/interactive#tooltip', icon: Info },
      { label: 'Popover', to: '/docs/interactive#popover', icon: MessageSquare },
      { label: 'Dropdown', to: '/docs/interactive#dropdown', icon: ChevronDown },
    ]
  },
  
  {
    id: 'composables',
    label: 'Composables & Utils',
    icon: Code,
    items: [
      { label: 'useValidation', to: '/docs/composables#use-validation', icon: Check },
      { label: 'useLocalStorage', to: '/docs/composables#local-storage', icon: Database },
      { label: 'Date Utilities', to: '/docs/composables#date-utils', icon: Calendar },
      { label: 'Currency Formatting', to: '/docs/composables#currency', icon: DollarSign },
    ]
  },
]
```

---

## Implementation Priority

| Priority | Task | Estimated Time | Dependencies |
|----------|------|---------------|--------------|
| P0 | Update DocLayout.vue navigation | 30 min | None |
| P0 | Add ChIcon to CoreView.vue | 1 hour | None |
| P1 | Create InteractiveView.vue | 3 hours | ChTooltip, ChPopover, ChDropdown |
| P1 | Update NavigationView.vue | 2 hours | ChBreadcrumb, ChCommandPalette |
| P2 | Update DataView.vue | 2 hours | ChAccordion, ChCarousel, ChEmptyState |
| P2 | Create ComposablesView.vue | 4 hours | useValidation, useLocalStorage, date.ts |
| P3 | Update CoreView.vue (remaining) | 2 hours | All core components |

**Total Estimated Time: 14.5 hours**

---

## File Structure After Integration

```
frontend/src/
├── layouts/
│   └── DocLayout.vue (UPDATED)
└── views/docs/
    ├── IntroductionView.vue
    ├── InstallationView.vue
    ├── FoundationView.vue
    ├── CoreView.vue (UPDATED - add ChIcon, ChTooltip, ChPopover, ChDropdown)
    ├── FormsView.vue
    ├── FormPatternsView.vue
    ├── DataView.vue (UPDATED - add ChAccordion, ChCarousel, ChEmptyState)
    ├── DataPatternsView.vue
    ├── NavigationView.vue (UPDATED - add ChBreadcrumb, ChCommandPalette)
    ├── FeedbackView.vue
    ├── IconsView.vue
    ├── UtilitiesView.vue
    ├── LayoutPatternsView.vue
    ├── ChangelogView.vue
    ├── InteractiveView.vue (NEW)
    └── ComposablesView.vue (NEW)
```

---

## Documentation Standards

Each new section must include:

1. **Component/Composable Header**
   - Title with component name
   - Brief description
   - When to use / When NOT to use

2. **Interactive Demo**
   - Live, working example
   - Multiple variants/states
   - Real-world church management context

3. **Code Examples**
   - Basic usage
   - Advanced configurations
   - Copy-to-clipboard functionality

4. **API Reference**
   - Props table (name, type, default, description)
   - Events table
   - Slots table
   - Composable return values

5. **Accessibility Notes**
   - ARIA attributes
   - Keyboard navigation
   - Screen reader support

---

## Success Metrics

- [ ] 100% component documentation coverage
- [ ] All new components have interactive demos
- [ ] All composables have usage examples
- [ ] Navigation updated with new sections
- [ ] Build still passes without errors
- [ ] Documentation site renders correctly
- [ ] All code examples are functional

---

## Next Steps

1. Start with Phase 5 (DocLayout.vue) - enables navigation
2. Proceed with Phase 1 (CoreView.vue updates)
3. Create InteractiveView.vue (Phase 1 continuation)
4. Update NavigationView.vue (Phase 2)
5. Update DataView.vue (Phase 3)
6. Create ComposablesView.vue (Phase 4)
7. Final review and testing
