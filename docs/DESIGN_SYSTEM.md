# AliveChMS Design System

Welcome to the AliveChMS Design System — a comprehensive Vue 3 component library built for church management applications. This system provides a cohesive set of UI components, composables, utilities, and theming capabilities that enable rapid development while maintaining visual consistency across the entire application.

The design system is built with TypeScript and Vue 3's Composition API, leveraging scoped CSS with CSS custom properties for flexible runtime theming. Every component is designed with accessibility in mind and follows established best practices for modern web development.

## Architecture Overview

The design system is located in `frontend/src/design-system/` and follows a modular architecture that separates concerns into distinct directories. This structure enables tree-shaking, makes the codebase navigable, and ensures that each part of the system can be maintained independently.

### Directory Structure

```
design-system/
├── components/          # Vue component library
│   ├── core/           # Fundamental building blocks
│   ├── cues/           # Feedback and loading states
│   ├── data/           # Data display components
│   ├── forms/          # Form input components
│   └── navigation/     # Navigation components
├── composables/        # Vue composables for reactive logic
├── styles/             # Global CSS (reset, animations)
├── tokens/             # Design tokens (colors, spacing, typography)
└── utils/              # Utility functions
```

### Key Files

| File                                                                         | Purpose                                                                     |
| ---------------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| [`index.ts`](../frontend/src/design-system/index.ts)                         | Master export file — single entry point for all design system exports       |
| [`tokens/index.ts`](../frontend/src/design-system/tokens/index.ts)           | Token injection system — bridges TypeScript tokens to CSS custom properties |
| [`tokens/colors.ts`](../frontend/src/design-system/tokens/colors.ts)         | Color palette and semantic color mappings                                   |
| [`tokens/spacing.ts`](../frontend/src/design-system/tokens/spacing.ts)       | Spacing, radius, shadows, transitions, and z-index scales                   |
| [`tokens/typography.ts`](../frontend/src/design-system/tokens/typography.ts) | Font families, type scale, weights, and line heights                        |

## Getting Started

### Installation and Setup

The design system is already included as part of the frontend project. To use it in your components, import everything from the single entry point:

```typescript
import { ChButton, ChCard, ChInput } from "@/design-system";
```

### Initializing the Design System

In your application's entry point (`main.ts`), you must initialize the CSS custom properties before mounting the Vue app:

```typescript
// main.ts
import { createApp } from "vue";
import { injectCSSVars } from "@/design-system";
import "@/design-system/styles/base.css";
import "@/design-system/styles/animations.css";
import App from "./App.vue";

// Initialize CSS vars BEFORE mounting the app
injectCSSVars();

createApp(App).mount("#app");
```

### Runtime Theming

To apply custom branding or enable dark mode at runtime:

```typescript
import { injectCSSVars } from "@/design-system/tokens";

// Apply church-specific brand color
injectCSSVars({
  "--ch-color-primary": "#e11d48",
});

// Or use the theme composable for dynamic switching
import { useTheme } from "@/design-system";
const { applyOverrides, toggleDarkMode } = useTheme();
```

### Styles to Import

Two CSS files must be imported manually in your entry point:

```typescript
import "@/design-system/styles/base.css"; // Global reset + element defaults
import "@/design-system/styles/animations.css"; // Keyframe definitions
```

---

## Component Catalog

The design system includes 31 components organized into five categories. Each component is fully typed with TypeScript and supports Vue 3's Composition API.

### Core Components

Core components are the fundamental building blocks used throughout every part of the UI. These components should be globally available in most applications.

#### ChButton

The primary interactive element for all clickable actions including form submissions, navigation triggers, confirmations, and destructive operations.

**Key Props:**

| Prop        | Type                                                           | Default     | Description                                              |
| ----------- | -------------------------------------------------------------- | ----------- | -------------------------------------------------------- |
| `variant`   | `'primary' \| 'secondary' \| 'ghost' \| 'danger' \| 'outline'` | `'primary'` | Visual style communicating semantic intent               |
| `size`      | `'sm' \| 'md' \| 'lg'`                                         | `'md'`      | Controls padding, font size, and min-height              |
| `disabled`  | `boolean`                                                      | `false`     | When true, button is non-interactive and visually dimmed |
| `loading`   | `boolean`                                                      | `false`     | Shows animated spinner and prevents click events         |
| `fullWidth` | `boolean`                                                      | `false`     | When true, applies `width: 100%`                         |
| `iconOnly`  | `boolean`                                                      | `false`     | Collapses to square with equal padding for icon buttons  |
| `type`      | `'button' \| 'submit' \| 'reset'`                              | `'button'`  | HTML button type attribute                               |

**Slots:**

- `#icon` — Leading icon displayed before label text
- `#default` — Button label text
- `#trailingIcon` — Icon displayed after label text (for dropdowns, external links)

**Usage:**

```vue
<ChButton variant="primary" @click="saveMember">
  Save Member
</ChButton>

<ChButton variant="danger" :loading="isDeleting" @click="deleteRecord">
  <template #icon><TrashIcon /></template>
  Delete
</ChButton>

<ChButton :iconOnly="true" variant="ghost">
  <SettingsIcon />
</ChButton>
```

---

#### ChCard

A versatile surface container used to group related content and establish visual hierarchy on the page.

**Key Props:**

| Prop        | Type                             | Default | Description                                                         |
| ----------- | -------------------------------- | ------- | ------------------------------------------------------------------- |
| `padding`   | `'none' \| 'sm' \| 'md' \| 'lg'` | `'md'`  | Internal body padding size                                          |
| `shadow`    | `'none' \| 'sm' \| 'md' \| 'lg'` | `'sm'`  | Box shadow depth                                                    |
| `bordered`  | `boolean`                        | `true`  | Whether to show 1px border                                          |
| `hoverable` | `boolean`                        | `false` | Adds hover shadow and subtle lift transform                         |
| `clickable` | `boolean`                        | `false` | Makes fully interactive with pointer cursor and click events        |
| `as`        | `string`                         | `'div'` | HTML element tag to render (`'article'`, `'section'`, `'li'`, etc.) |

**Slots:**

- `#header` — Top section with border-bottom (title, actions)
- `#default` — Main content body (always present)
- `#footer` — Bottom section with border-top and muted background

**Usage:**

```vue
<ChCard>
  <p>Member count: 142</p>
</ChCard>

<ChCard :clickable="true" @click="openProfile(member.id)">
  <MemberSummary :member="member" />
</ChCard>

<ChCard>
  <template #header>
    <h3>Upcoming Events</h3>
    <ChButton variant="ghost" size="sm">View all</ChButton>
  </template>
  <EventList />
  <template #footer>
    <ChButton variant="primary">Add event</ChButton>
  </template>
</ChCard>
```

---

#### ChInput

A controlled, single-line text input with adornment slots, clearable mode, and full error, disabled, and readonly state support.

**Key Props:**

| Prop          | Type                                                                        | Default  | Description                                               |
| ------------- | --------------------------------------------------------------------------- | -------- | --------------------------------------------------------- |
| `modelValue`  | `string \| number`                                                          | —        | Controlled value (bind with v-model)                      |
| `type`        | `'text' \| 'email' \| 'password' \| 'number' \| 'tel' \| 'url' \| 'search'` | `'text'` | HTML input type                                           |
| `placeholder` | `string`                                                                    | —        | Placeholder text shown when empty                         |
| `size`        | `'sm' \| 'md' \| 'lg'`                                                      | `'md'`   | Input size affecting padding, font, and height            |
| `disabled`    | `boolean`                                                                   | `false`  | Disables the input entirely                               |
| `readonly`    | `boolean`                                                                   | `false`  | Makes input read-only                                     |
| `error`       | `string \| boolean`                                                         | —        | Error state — string shows message, boolean shows styling |
| `clearable`   | `boolean`                                                                   | `false`  | Shows × button to clear input value                       |
| `id`          | `string`                                                                    | —        | HTML id attribute for label connection                    |
| `name`        | `string`                                                                    | —        | HTML name attribute for form serialization                |
| `maxlength`   | `number`                                                                    | —        | Maximum character length                                  |

**Slots:**

- `#leading` — Icon or content before the input text (search icon, currency symbol)
- `#trailing` — Icon or content after the input text (password toggle, validation icon)

**Emits:**

- `update:modelValue` — Fired on every keystroke
- `focus` — Fired when input gains focus
- `blur` — Fired when input loses focus
- `clear` — Fired when clear button is clicked
- `enter` — Fired when Enter key is pressed

**Usage:**

```vue
<ChInput v-model="searchQuery" placeholder="Search members..." />

<ChInput v-model="email" :error="errors.email" type="email" />

<ChInput v-model="password" :type="showPassword ? 'text' : 'password'">
  <template #trailing>
    <button @click="showPassword = !showPassword">
      <EyeIcon />
    </button>
  </template>
</ChInput>
```

---

#### ChTextarea

A multi-line text input component built on the same architecture as ChInput, supporting auto-resize, character counting, and all the same state management.

**Key Props:**

| Prop         | Type      | Default | Description                        |
| ------------ | --------- | ------- | ---------------------------------- |
| `modelValue` | `string`  | —       | Controlled value                   |
| `rows`       | `number`  | `4`     | Initial number of visible rows     |
| `autosize`   | `boolean` | `false` | Auto-expands height to fit content |
| `maxlength`  | `number`  | —       | Maximum character count            |
| `showCount`  | `boolean` | `false` | Displays character count           |

---

#### ChAvatar

Displays user images or initials with fallback handling, support for multiple sizes, and status indicators.

**Key Props:**

| Prop       | Type                                        | Default | Description                 |
| ---------- | ------------------------------------------- | ------- | --------------------------- |
| `src`      | `string`                                    | —       | Image source URL            |
| `alt`      | `string`                                    | —       | Alt text for accessibility  |
| `initials` | `string`                                    | —       | Fallback text when no image |
| `size`     | `'xs' \| 'sm' \| 'md' \| 'lg' \| 'xl'`      | `'md'`  | Avatar size                 |
| `status`   | `'online' \| 'offline' \| 'busy' \| 'away'` | —       | Status indicator            |

---

#### ChBadge

A compact label component for status indicators, counts, and categorization.

**Key Props:**

| Prop      | Type                                                                     | Default     | Description                        |
| --------- | ------------------------------------------------------------------------ | ----------- | ---------------------------------- |
| `variant` | `'primary' \| 'success' \| 'warning' \| 'danger' \| 'info' \| 'neutral'` | `'neutral'` | Color scheme                       |
| `size`    | `'sm' \| 'md'`                                                           | `'sm'`      | Badge size                         |
| `dot`     | `boolean`                                                                | `false`     | Shows as a dot only                |
| `pulse`   | `boolean`                                                                | `false`     | Adds pulse animation for attention |

---

#### ChDivider

A visual separator for content sections with customizable orientation and styling.

**Key Props:**

| Prop          | Type                         | Default        | Description         |
| ------------- | ---------------------------- | -------------- | ------------------- |
| `orientation` | `'horizontal' \| 'vertical'` | `'horizontal'` | Divider orientation |
| `variant`     | `'solid' \| 'dashed'`        | `'solid'`      | Line style          |

---

### Form Components

Form components provide comprehensive input handling for user data collection, including selects, checkboxes, radios, and specialized inputs.

#### ChSelect

A fully custom dropdown select with search, single/multi-select, option groups, and keyboard navigation. Does not use the native `<select>` element for full styling control.

**Key Props:**

| Prop           | Type                                       | Default               | Description                         |
| -------------- | ------------------------------------------ | --------------------- | ----------------------------------- |
| `modelValue`   | `string \| number \| (string \| number)[]` | —                     | Selected value(s)                   |
| `options`      | `SelectOption[] \| SelectGroup[]`          | —                     | Available options                   |
| `placeholder`  | `string`                                   | `'Select...'`         | Placeholder text                    |
| `multiple`     | `boolean`                                  | `false`               | Enables multi-select mode           |
| `searchable`   | `boolean`                                  | `false`               | Enables search/filter               |
| `disabled`     | `boolean`                                  | `false`               | Disables the select                 |
| `error`        | `string \| boolean`                        | —                     | Error state                         |
| `size`         | `'sm' \| 'md' \| 'lg'`                     | `'md'`                | Select size                         |
| `maxHeight`    | `number`                                   | `256`                 | Dropdown max height in pixels       |
| `emptyMessage` | `string`                                   | `'No options found.'` | Message when search returns nothing |
| `maxTags`      | `number`                                   | `3`                   | Tags shown before "+N more"         |

**Option Shape:**

```typescript
interface SelectOption {
  value: string | number;
  label: string;
  hint?: string;
  disabled?: boolean;
}

interface SelectGroup {
  group: string;
  options: SelectOption[];
}
```

**Usage:**

```vue
<ChSelect
  v-model="form.group"
  :options="groupOptions"
  placeholder="Select group"
/>

<ChSelect
  v-model="form.roles"
  :options="roleOptions"
  :multiple="true"
  searchable
  placeholder="Assign roles..."
/>

<ChSelect
  v-model="form.category"
  :options="[
    { group: 'Income', options: [{ value: 'tithe', label: 'Tithe' }] },
    { group: 'Expense', options: [{ value: 'salaries', label: 'Salaries' }] },
  ]"
/>
```

---

#### ChCheckbox

A styled checkbox input with indeterminate state support and label integration.

**Key Props:**

| Prop            | Type                   | Default | Description                          |
| --------------- | ---------------------- | ------- | ------------------------------------ |
| `modelValue`    | `boolean \| boolean[]` | —       | Checked state                        |
| `value`         | `string \| number`     | —       | Value when used in checkbox group    |
| `label`         | `string`               | —       | Label text displayed beside checkbox |
| `disabled`      | `boolean`              | `false` | Disables the checkbox                |
| `indeterminate` | `boolean`              | `false` | Shows indeterminate state (dash)     |

---

#### ChRadio

A styled radio button group with horizontal and vertical layouts.

**Key Props:**

| Prop         | Type               | Default | Description          |
| ------------ | ------------------ | ------- | -------------------- |
| `modelValue` | `string \| number` | —       | Selected value       |
| `options`    | `RadioOption[]`    | —       | Available options    |
| `disabled`   | `boolean`          | `false` | Disables all options |
| `inline`     | `boolean`          | `false` | Horizontal layout    |

---

#### ChSwitch

A toggle switch component for boolean settings, with animated transitions.

**Key Props:**

| Prop         | Type           | Default | Description         |
| ------------ | -------------- | ------- | ------------------- |
| `modelValue` | `boolean`      | —       | On/off state        |
| `disabled`   | `boolean`      | `false` | Disables the switch |
| `size`       | `'sm' \| 'md'` | `'md'`  | Switch size         |

---

#### ChSlider

A range slider input for numeric values with configurable min/max/step.

**Key Props:**

| Prop         | Type      | Default | Description            |
| ------------ | --------- | ------- | ---------------------- |
| `modelValue` | `number`  | —       | Current value          |
| `min`        | `number`  | `0`     | Minimum value          |
| `max`        | `number`  | `100`   | Maximum value          |
| `step`       | `number`  | `1`     | Increment step         |
| `showValue`  | `boolean` | `true`  | Displays current value |
| `disabled`   | `boolean` | `false` | Disables the slider    |

---

#### ChFileUpload

A drag-and-drop file upload component with preview, validation, and progress tracking.

**Key Props:**

| Prop         | Type             | Default | Description                |
| ------------ | ---------------- | ------- | -------------------------- |
| `modelValue` | `File \| File[]` | —       | Uploaded file(s)           |
| `accept`     | `string`         | —       | Accepted MIME types        |
| `multiple`   | `boolean`        | `false` | Allows multiple files      |
| `maxSize`    | `number`         | —       | Maximum file size in bytes |
| `maxFiles`   | `number`         | `1`     | Maximum number of files    |
| `disabled`   | `boolean`        | `false` | Disables upload            |

**Usage with MIME presets:**

```vue
<script setup>
import { MIME, ACCEPT_PRESETS } from "@/design-system";
</script>

<template>
  <ChFileUpload :accept="ACCEPT_PRESETS.images" />
  <ChFileUpload :accept="MIME.PDF" />
  <ChFileUpload :accept="[MIME.PDF, MIME.JPEG, MIME.PNG].join(',')" />
</template>
```

---

#### ChDatePicker

A calendar-based date selection component with range support and localization.

**Key Props:**

| Prop         | Type                       | Default    | Description              |
| ------------ | -------------------------- | ---------- | ------------------------ |
| `modelValue` | `Date \| Date[] \| string` | —          | Selected date(s)         |
| `mode`       | `'single' \| 'range'`      | `'single'` | Selection mode           |
| `minDate`    | `Date`                     | —          | Earliest selectable date |
| `maxDate`    | `Date`                     | —          | Latest selectable date   |
| `disabled`   | `boolean`                  | `false`    | Disables the picker      |

---

#### ChModal

An accessible dialog modal with focus trapping, scroll lock, keyboard dismissal, and a clean slot structure.

**Key Props:**

| Prop         | Type                                             | Default | Description                                    |
| ------------ | ------------------------------------------------ | ------- | ---------------------------------------------- |
| `open`       | `boolean`                                        | —       | Controls modal visibility (use v-model:open)   |
| `title`      | `string`                                         | —       | Modal title displayed in header                |
| `subtitle`   | `string`                                         | —       | Additional header description                  |
| `size`       | `'xs' \| 'sm' \| 'md' \| 'lg' \| 'xl' \| 'full'` | `'md'`  | Modal width                                    |
| `persistent` | `boolean`                                        | `false` | Prevents closing on backdrop click             |
| `hideClose`  | `boolean`                                        | `false` | Hides close button in header                   |
| `scrollable` | `boolean`                                        | `true`  | Makes body scrollable with fixed header/footer |

**Slots:**

- `#header` — Extra content in header (step indicators)
- `#default` — Modal body content
- `#footer` — Footer actions

**Usage:**

```vue
<ChModal v-model:open="showConfirm" title="Delete Member" size="sm">
  <p>Are you sure you want to delete <strong>Kwame Asante</strong>?</p>
  <template #footer>
    <ChButton variant="ghost" @click="showConfirm = false">Cancel</ChButton>
    <ChButton variant="danger" @click="confirmDelete">Delete</ChButton>
  </template>
</ChModal>
```

---

#### ChFormField

A wrapper component that provides consistent label, helper text, and error message display around any input.

**Key Props:**

| Prop       | Type      | Default | Description              |
| ---------- | --------- | ------- | ------------------------ |
| `label`    | `string`  | —       | Field label text         |
| `required` | `boolean` | `false` | Shows required indicator |
| `helper`   | `string`  | —       | Helper text below input  |
| `error`    | `string`  | —       | Error message            |
| `disabled` | `boolean` | `false` | Disables the field       |

**Usage:**

```vue
<ChFormField label="Email Address" required :error="errors.email">
  <ChInput v-model="form.email" type="email" />
</ChFormField>
```

---

#### ChStepperWizard

A multi-step wizard component with validation gating and progress tracking.

**Key Props:**

| Prop     | Type             | Default | Description                               |
| -------- | ---------------- | ------- | ----------------------------------------- |
| `wizard` | `WizardInstance` | —       | Instance from useStepperWizard composable |

**Usage:**

```vue
<script setup>
import { useStepperWizard } from "@/design-system";

const wizard = useStepperWizard([
  { id: "personal", label: "Personal Info" },
  { id: "family", label: "Family Details" },
  { id: "confirm", label: "Confirm" },
]);
</script>

<template>
  <ChStepperWizard :wizard="wizard">
    <ChStepperStep step-id="personal" :wizard="wizard">
      <PersonalForm v-model="form.personal" />
    </ChStepperStep>
  </ChStepperWizard>
</template>
```

---

#### ChTimeline

A vertical timeline component for displaying chronological events or activities.

**Key Props:**

| Prop    | Type             | Default | Description      |
| ------- | ---------------- | ------- | ---------------- |
| `items` | `TimelineItem[]` | —       | Timeline entries |

---

#### ChTimelineItem

An individual entry in the timeline with icon, content, and timestamp.

---

### Data Components

Data components are designed for displaying and interacting with structured data, including tables, charts, and statistics.

#### ChTable

A feature-rich data table with sorting, filtering, pagination, row selection, and slot-based customization.

**Key Props:**

| Prop         | Type             | Default               | Description               |
| ------------ | ---------------- | --------------------- | ------------------------- |
| `data`       | `T[]`            | —                     | Array of data rows        |
| `columns`    | `ColumnDef<T>[]` | —                     | Column definitions        |
| `sortable`   | `boolean`        | `true`                | Enables column sorting    |
| `selectable` | `boolean`        | `false`               | Enables row selection     |
| `pagination` | `boolean`        | `true`                | Shows pagination controls |
| `pageSize`   | `number`         | `10`                  | Rows per page             |
| `loading`    | `boolean`        | `false`               | Shows loading skeleton    |
| `emptyText`  | `string`         | `'No data available'` | Message when no data      |

**Column Definition:**

```typescript
interface ColumnDef<T> {
  key: keyof T | string;
  label: string;
  sortable?: boolean;
  width?: string;
  align?: "left" | "center" | "right";
  format?: (value: any, row: T) => string;
}
```

**Usage:**

```vue
<ChTable
  :data="members"
  :columns="columns"
  selectable
  @row-select="handleSelect"
>
  <template #cell-actions="{ row }">
    <ChButton size="sm" variant="ghost" @click="edit(row)">
      Edit
    </ChButton>
  </template>
</ChTable>
```

---

#### ChStatCard

A card component designed for displaying key metrics and statistics with visual indicators.

**Key Props:**

| Prop      | Type                                              | Default     | Description                           |
| --------- | ------------------------------------------------- | ----------- | ------------------------------------- |
| `title`   | `string`                                          | —           | Statistic label                       |
| `value`   | `string \| number`                                | —           | Statistic value                       |
| `change`  | `number`                                          | —           | Percentage change (positive/negative) |
| `trend`   | `'up' \| 'down' \| 'neutral'`                     | —           | Trend direction                       |
| `icon`    | `object`                                          | —           | Icon component                        |
| `variant` | `'default' \| 'success' \| 'warning' \| 'danger'` | `'default'` | Color variant                         |

**Usage:**

```vue
<ChStatCard
  title="Total Members"
  :value="stats.totalMembers"
  :change="5.2"
  trend="up"
/>
```

---

#### ChChart

A wrapper around Chart.js for rendering various chart types with consistent styling.

**Key Props:**

| Prop      | Type                                     | Default   | Description            |
| --------- | ---------------------------------------- | --------- | ---------------------- |
| `type`    | `'line' \| 'bar' \| 'pie' \| 'doughnut'` | —         | Chart type             |
| `data`    | `ChartData`                              | —         | Chart data object      |
| `options` | `ChartOptions`                           | —         | Chart.js options       |
| `height`  | `string \| number`                       | `'300px'` | Chart container height |

---

#### ChDataList

A list-based display for key-value data with optional grouping and sorting.

**Key Props:**

| Prop      | Type             | Default | Description       |
| --------- | ---------------- | ------- | ----------------- |
| `items`   | `DataListItem[]` | —       | List items        |
| `columns` | `number`         | `2`     | Number of columns |
| `dense`   | `boolean`        | `false` | Compact spacing   |

---

#### ChTableExportDialog

A dialog component for exporting table data in various formats (CSV, Excel, PDF, Print).

**Key Props:**

| Prop      | Type             | Default         | Description        |
| --------- | ---------------- | --------------- | ------------------ |
| `open`    | `boolean`        | —               | Dialog visibility  |
| `columns` | `ExportColumn[]` | —               | Exportable columns |
| `title`   | `string`         | `'Export Data'` | Dialog title       |

---

### Navigation Components

Navigation components provide the structure for app layout and user movement through the interface.

#### ChSidebar

The main application sidebar with collapsible support, sections, and item management.

**Key Props:**

| Prop             | Type        | Default   | Description      |
| ---------------- | ----------- | --------- | ---------------- |
| `items`          | `NavItem[]` | —         | Navigation items |
| `collapsed`      | `boolean`   | `false`   | Collapsed state  |
| `width`          | `string`    | `'260px'` | Expanded width   |
| `collapsedWidth` | `string`    | `'72px'`  | Collapsed width  |

**Usage:**

```vue
<ChSidebar :items="navItems" v-model:collapsed="sidebarCollapsed">
  <template #logo>
    <img src="/logo.svg" alt="Church Logo" />
  </template>
</ChSidebar>
```

---

#### ChSidebarItem

An individual navigation item within the sidebar, supporting nested items and active state.

**Key Props:**

| Prop       | Type               | Default | Description             |
| ---------- | ------------------ | ------- | ----------------------- |
| `label`    | `string`           | —       | Item label              |
| `to`       | `string`           | —       | Route path (vue-router) |
| `icon`     | `object`           | —       | Icon component          |
| `badge`    | `string \| number` | —       | Badge count             |
| `children` | `NavItem[]`        | —       | Nested items            |

---

#### ChTopbar

The top navigation bar with user menu, notifications, and search.

**Key Props:**

| Prop                | Type         | Default | Description             |
| ------------------- | ------------ | ------- | ----------------------- |
| `title`             | `string`     | —       | Page title              |
| `user`              | `TopbarUser` | —       | User object for menu    |
| `showSearch`        | `boolean`    | `true`  | Shows search input      |
| `showNotifications` | `boolean`    | `true`  | Shows notification bell |

**TopbarUser Type:**

```typescript
interface TopbarUser {
  name: string;
  email: string;
  avatar?: string;
  role?: string;
}
```

---

#### ChTabs

A tabbed navigation component for switching between related content panels.

**Key Props:**

| Prop         | Type                | Default  | Description               |
| ------------ | ------------------- | -------- | ------------------------- |
| `modelValue` | `string`            | —        | Active tab id             |
| `tabs`       | `Tab[]`             | —        | Tab definitions           |
| `variant`    | `'line' \| 'pills'` | `'line'` | Tab style                 |
| `grow`       | `boolean`           | `false`  | Tabs expand to fill width |

**Tab Type:**

```typescript
interface Tab {
  id: string;
  label: string;
  icon?: Component;
  disabled?: boolean;
}
```

---

### Feedback Components

Feedback components communicate system status, loading states, and notifications to users.

#### ChSpinner

A CSS-only loading spinner with customizable size and color.

**Key Props:**

| Prop    | Type                   | Default          | Description  |
| ------- | ---------------------- | ---------------- | ------------ |
| `size`  | `'sm' \| 'md' \| 'lg'` | `'md'`           | Spinner size |
| `color` | `string`               | `'currentColor'` | Spin color   |

**Usage:**

```vue
<ChSpinner size="lg" />
```

---

#### ChSkeleton

A placeholder component that displays animated shapes while content is loading.

**Key Props:**

| Prop        | Type                                    | Default   | Description     |
| ----------- | --------------------------------------- | --------- | --------------- |
| `variant`   | `'text' \| 'circular' \| 'rectangular'` | `'text'`  | Skeleton shape  |
| `width`     | `string`                                | —         | Custom width    |
| `height`    | `string`                                | —         | Custom height   |
| `animation` | `'pulse' \| 'wave' \| 'none'`           | `'pulse'` | Animation style |

---

#### ChProgress

A linear or circular progress indicator for tracking operation completion.

**Key Props:**

| Prop        | Type                     | Default    | Description                 |
| ----------- | ------------------------ | ---------- | --------------------------- |
| `value`     | `number`                 | —          | Progress percentage (0-100) |
| `variant`   | `'linear' \| 'circular'` | `'linear'` | Progress style              |
| `showValue` | `boolean`                | `false`    | Displays percentage         |
| `color`     | `string`                 | —          | Progress bar color          |

---

#### ChToast

A notification component for displaying brief messages with semantic variants.

**Key Props:**

| Prop       | Type                                           | Default  | Description             |
| ---------- | ---------------------------------------------- | -------- | ----------------------- |
| `variant`  | `'success' \| 'warning' \| 'danger' \| 'info'` | `'info'` | Toast type              |
| `title`    | `string`                                       | —        | Bold heading            |
| `message`  | `string`                                       | —        | Main message            |
| `duration` | `number`                                       | `4500`   | Auto-dismiss delay (ms) |
| `action`   | `ToastAction`                                  | —        | Call-to-action button   |

---

#### ChToastContainer

The container component that renders all toast notifications. Should be placed once at the app root.

**Key Props:**

| Prop       | Type                                                           | Default       | Description    |
| ---------- | -------------------------------------------------------------- | ------------- | -------------- |
| `position` | `'top-right' \| 'top-left' \| 'bottom-right' \| 'bottom-left'` | `'top-right'` | Toast position |

**Usage:**

```vue
<!-- App.vue -->
<template>
  <router-view />
  <ChToastContainer position="top-right" />
</template>
```

---

#### ChPullToRefresh

A mobile-friendly pull-to-refresh component for triggering data reload on touch devices.

**Key Props:**

| Prop         | Type                  | Default | Description              |
| ------------ | --------------------- | ------- | ------------------------ |
| `onRefresh`  | `() => Promise<void>` | —       | Refresh callback         |
| `threshold`  | `number`              | `64`    | Pull distance to trigger |
| `resistance` | `number`              | `0.4`   | Pull resistance factor   |

---

## Composables

The design system provides six Vue composables that encapsulate reusable reactive logic. Each composable is a function you call inside `<script setup>` or `setup()`.

### useTheme

Manages runtime theme customization including dark mode and custom branding colors. The composable uses CSS custom properties to enable instant theme changes without re-rendering.

**Purpose:** Runtime theming, dark mode toggle, per-church branding

**Returns:**

| Property           | Type                                       | Description                   |
| ------------------ | ------------------------------------------ | ----------------------------- |
| `applyOverrides`   | `(overrides: ThemeOverrides) => void`      | Applies theme overrides       |
| `setVar`           | `(varName: string, value: string) => void` | Sets a single CSS variable    |
| `resetTheme`       | `() => void`                               | Removes all overrides         |
| `removeOverride`   | `(varName: string) => void`                | Removes single override       |
| `getVar`           | `(varName: string) => string`              | Reads computed variable value |
| `currentOverrides` | `Readonly<Ref<ThemeOverrides>>`            | Active overrides              |
| `isDarkMode`       | `Readonly<Ref<boolean>>`                   | Dark mode state               |
| `toggleDarkMode`   | `() => void`                               | Toggles dark mode             |
| `applyDarkMode`    | `(enabled: boolean) => void`               | Sets dark mode explicitly     |

**Usage:**

```typescript
import { useTheme } from "@/design-system";

const { applyOverrides, toggleDarkMode, isDarkMode } = useTheme();

// Apply church branding
onMounted(async () => {
  const church = await fetchChurchSettings(churchId);
  applyOverrides({
    "--ch-color-primary": church.brandColor,
    "--ch-color-primary-hover": church.brandColorDark,
  });
});

// Toggle dark mode
function handleDarkModeToggle() {
  toggleDarkMode();
}
```

---

### useToast

Manages the application toast notification queue. The toast state lives in module-level refs, making it a true singleton — any component can push a toast and it will appear regardless of where it's called from.

**Purpose:** Displaying notifications, success messages, errors, warnings

**Returns:**

| Property     | Type                                 | Description                  |
| ------------ | ------------------------------------ | ---------------------------- |
| `toasts`     | `Readonly<Ref<Toast[]>>`             | Active toast array           |
| `push`       | `(options: ToastOptions) => string`  | Adds toast, returns ID       |
| `dismiss`    | `(id: string) => void`               | Removes toast by ID          |
| `dismissAll` | `() => void`                         | Removes all toasts           |
| `pause`      | `(id: string) => void`               | Pauses auto-dismiss on hover |
| `resume`     | `(id: string) => void`               | Resumes auto-dismiss         |
| `success`    | `(message: string, opts?) => string` | Convenience: green toast     |
| `error`      | `(message: string, opts?) => string` | Convenience: red toast       |
| `warning`    | `(message: string, opts?) => string` | Convenience: amber toast     |
| `info`       | `(message: string, opts?) => string` | Convenience: blue toast      |

**Usage:**

```typescript
import { useToast } from '@/design-system'

const toast = useToast()

// Simple notifications
toast.success('Member saved successfully.')
toast.error('Failed to load contributions.')

// With options
toast.push({
  variant: 'warning',
  title: 'Session expiring',
  message: 'You will be logged out in 5 minutes.',
  duration: 8000,
  action: { label: 'Stay logged in', onClick: refreshSession },
})

// From API services
import { useToast } from '@/design-system'
const toast = useToast()

// In an API catch block
catch (error) {
  toast.error('Failed to save. Please try again.')
}
```

---

### useModal

Manages a modal's open/close state and passes data in and out without prop-drilling. Supports both local (per-component) and shared (singleton) usage patterns.

**Purpose:** Opening dialogs, confirmation dialogs, form modals, data passing

**Returns:**

| Property       | Type                           | Description                            |
| -------------- | ------------------------------ | -------------------------------------- |
| `isOpen`       | `Ref<boolean>`                 | Modal visibility state                 |
| `data`         | `Readonly<Ref<TData \| null>>` | Data payload passed to modal           |
| `open`         | `(payload?: TData) => void`    | Opens modal with optional data         |
| `close`        | `(result?: TData) => void`     | Closes modal with optional result      |
| `waitForClose` | `() => Promise<TData \| null>` | Returns Promise that resolves on close |

**Usage:**

```typescript
// Pattern 1: Local modal
const modal = useModal<Member>()
modal.open({ id: 5, name: 'Kwame' })
const result = await modal.waitForClose()
if (result) updateMember(result)

// Pattern 2: Shared singleton (in separate file)
// modals/editMemberModal.ts
export const editMemberModal = useModal<Member>()

// In component A - open
editMemberModal.open(row)

// In component B - mount
<ChModal v-model:open="editMemberModal.isOpen.value" title="Edit Member">
  <MemberForm :member="editMemberModal.data.value" @saved="editMemberModal.close" />
</ChModal>
```

---

### useStepperWizard

Manages multi-step wizard forms with validation gating, progress tracking, and navigation controls.

**Purpose:** Multi-step forms, registration wizards, checkout flows

**Returns:**

| Property       | Type                                 | Description                  |
| -------------- | ------------------------------------ | ---------------------------- |
| `steps`        | `WizardStep[]`                       | Step definitions             |
| `currentIdx`   | `Ref<number>`                        | Current step index (0-based) |
| `currentStep`  | `ComputedRef<WizardStep>`            | Current step object          |
| `completed`    | `Readonly<Ref<Set<number>>>`         | Completed step indices       |
| `errors`       | `Readonly<Ref<Map<number, string>>>` | Validation errors            |
| `isValidating` | `Ref<boolean>`                       | Async validation state       |
| `isFirstStep`  | `ComputedRef<boolean>`               | First step check             |
| `isLastStep`   | `ComputedRef<boolean>`               | Last step check              |
| `isComplete`   | `ComputedRef<boolean>`               | All steps complete           |
| `progress`     | `ComputedRef<number>`                | Progress percentage          |
| `next`         | `() => Promise<boolean>`             | Advances with validation     |
| `back`         | `() => void`                         | Goes to previous step        |
| `goTo`         | `(idx: number) => void`              | Jumps to step                |
| `reset`        | `() => void`                         | Resets wizard to start       |
| `isReachable`  | `(idx: number) => boolean`           | Checks step accessibility    |

**Usage:**

```typescript
import { useStepperWizard } from "@/design-system";

const wizard = useStepperWizard([
  {
    id: "personal",
    label: "Personal Info",
    validate: () => validatePersonalForm(),
  },
  { id: "family", label: "Family Details" },
  {
    id: "confirm",
    label: "Confirm",
    optional: true,
  },
]);

// In template:
// <ChStepperWizard :wizard="wizard">
//   <ChStepperStep step-id="personal" :wizard="wizard">
```

---

### useTableExport

Handles all table export and print operations with graceful degradation. Supports CSV (native), Excel (requires exceljs), PDF (requires jspdf), and Print formats.

**Purpose:** Exporting data to CSV, Excel, PDF, printing tables

**Returns:**

| Property      | Type                                              | Description              |
| ------------- | ------------------------------------------------- | ------------------------ |
| `exportData`  | `(config: ExportConfig) => Promise<ExportResult>` | Main export function     |
| `isExporting` | `Ref<boolean>`                                    | Export in progress state |
| `exportError` | `Ref<string \| null>`                             | Error message            |

**Usage:**

```typescript
import { useTableExport } from "@/design-system";

const { exportData, isExporting, exportError } = useTableExport();

async function handleExport(format: "csv" | "excel" | "pdf" | "print") {
  const result = await exportData({
    format,
    rows: memberRows,
    columns: [
      { key: "name", label: "Full Name" },
      { key: "status", label: "Status" },
      { key: "email", label: "Email" },
    ],
    filename: "members-report",
    title: "Member Report",
    subtitle: "As of " + new Date().toLocaleDateString(),
  });

  if (!result.success) {
    console.error(result.error);
  }
}
```

---

### usePullToRefresh

Tracks pull-down touch gestures on scroll containers and fires callbacks when users pull past a threshold. Includes resistance calculations for natural feel.

**Purpose:** Mobile pull-to-refresh functionality

**Returns:**

| Property         | Type                              | Description               |
| ---------------- | --------------------------------- | ------------------------- |
| `pullDistance`   | `Readonly<Ref<number>>`           | Current pull offset in px |
| `phase`          | `Readonly<Ref<PullPhase>>`        | Interaction phase         |
| `attach`         | `(el: HTMLElement) => () => void` | Attaches touch listeners  |
| `triggerRefresh` | `() => void`                      | Programmatic refresh      |

**Pull Phases:**

- `idle` — No interaction happening
- `pulling` — User is actively dragging down
- `ready` — Threshold crossed, release triggers refresh
- `refreshing` — Callback is running
- `completing` — Refresh finished, brief pause

**Usage:**

```typescript
import { usePullToRefresh } from "@/design-system";

const { pullDistance, phase, attach, triggerRefresh } = usePullToRefresh({
  onRefresh: async () => {
    await fetchData(); // Reload data
  },
  threshold: 64,
  resistance: 0.4,
});

onMounted(() => {
  const cleanup = attach(scrollContainerRef.value);
  onUnmounted(cleanup);
});
```

---

## Utility Functions

The design system exports utility functions organized by category in [`utils/index.ts`](../frontend/src/design-system/utils/index.ts). These are pure functions (no side effects) except for DOM helpers.

### File Upload Helpers

Constants for MIME types and pre-built accept strings.

**MIME Constants:**

```typescript
import { MIME, ACCEPT_PRESETS } from "@/design-system";

// Individual types
MIME.PDF; // 'application/pdf'
MIME.JPEG; // 'image/jpeg'
MIME.PNG; // 'image/png'
MIME.DOC; // 'application/msword'
MIME.XLSX; // 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'

// Presets
ACCEPT_PRESETS.documents; // PDF, Word, plain text
ACCEPT_PRESETS.spreadsheets; // Excel files
ACCEPT_PRESETS.images; // JPEG, PNG, GIF
ACCEPT_PRESETS.all; // All supported types
```

---

### String Utilities

| Function      | Signature                                    | Description              |
| ------------- | -------------------------------------------- | ------------------------ |
| `truncate`    | `(str: string, maxLength: number) => string` | Truncates with ellipsis  |
| `capitalize`  | `(str: string) => string`                    | Capitalizes first letter |
| `toKebabCase` | `(str: string) => string`                    | Converts to kebab-case   |

**Usage:**

```typescript
import { truncate, capitalize, toKebabCase } from "@/design-system";

truncate("This is a long string", 10); // 'This is...'
capitalize("hello world"); // 'Hello world'
toKebabCase("HelloWorld"); // 'hello-world'
toKebabCase("Hello World"); // 'hello-world'
```

---

### Number Utilities

| Function              | Signature                                                             | Description           |
| --------------------- | --------------------------------------------------------------------- | --------------------- |
| `formatCurrency`      | `(amount: number, currency?: string, locale?: string) => string`      | Formats as currency   |
| `formatPercentage`    | `(value: number, decimalPlaces?: number) => string`                   | Formats as percentage |
| `formatCompactNumber` | `(number: number, decimalPlaces?: number, locale?: string) => string` | Formats as 1.2k, 1.5M |

**Usage:**

```typescript
import {
  formatCurrency,
  formatPercentage,
  formatCompactNumber,
} from "@/design-system";

formatCurrency(1234.56); // 'GH₵1,234.56'
formatCurrency(1234.56, "USD"); // '$1,234.56'
formatCurrency(1234.56, "EUR", "de-DE"); // '1.234,56 €'

formatPercentage(0.1234); // '12%'
formatPercentage(0.1234, 2); // '12.34%'

formatCompactNumber(1234); // '1.2K'
formatCompactNumber(1234567); // '1.2M'
```

---

### Date Utilities

| Function             | Signature                                                    | Description          |
| -------------------- | ------------------------------------------------------------ | -------------------- |
| `formatDate`         | `(date: Date \| string \| number, format: string) => string` | Formats with tokens  |
| `formatRelativeTime` | `(date: Date \| string \| number) => string`                 | Relative time string |

**Usage:**

```typescript
import { formatDate, formatRelativeTime } from "@/design-system";

formatDate(new Date(2025, 6, 14), "DD/MM/YYYY"); // '14/07/2025'
formatDate(new Date(2025, 6, 14, 9, 5), "YYYY-MM-DD HH:mm"); // '2025-07-14 09:05'

formatRelativeTime(Date.now() - 3_600_000); // '1 hour ago'
formatRelativeTime(Date.now() + 86_400_000); // 'in 1 day'
```

---

### Array Utilities

| Function  | Signature                                                                              | Description          |
| --------- | -------------------------------------------------------------------------------------- | -------------------- |
| `groupBy` | `<T extends Record<string, unknown>>(array: T[], key: keyof T) => Record<string, T[]>` | Groups by property   |
| `shuffle` | `<T>(array: T[]) => T[]`                                                               | Fisher-Yates shuffle |

**Usage:**

```typescript
import { groupBy, shuffle } from "@/design-system";

groupBy(members, "status");
// { Active: [...], Inactive: [...] }

shuffle([1, 2, 3, 4, 5]); // New shuffled array
```

---

### DOM Utilities

| Function          | Signature                                                                                                                  | Description                  |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------- | ---------------------------- |
| `isInViewport`    | `(element: HTMLElement) => boolean`                                                                                        | Checks if element is visible |
| `scrollToElement` | `(element: HTMLElement, behavior?: ScrollBehavior, block?: ScrollLogicalPosition, inline?: ScrollLogicalPosition) => void` | Scrolls element into view    |

---

### Validation Utilities

| Function       | Signature                    | Description                    |
| -------------- | ---------------------------- | ------------------------------ |
| `isValidEmail` | `(email: string) => boolean` | Validates email format         |
| `isValidPhone` | `(phone: string) => boolean` | Validates phone (10-14 digits) |
| `isValidUrl`   | `(url: string) => boolean`   | Validates absolute URL         |

**Usage:**

```typescript
import { isValidEmail, isValidPhone, isValidUrl } from "@/design-system";

isValidEmail("john@example.com"); // true
isValidPhone("0244123456"); // true (Ghana format)
isValidPhone("+233244123456"); // true
isValidUrl("https://example.com"); // true
```

---

### Color Utilities

| Function       | Signature                                                           | Description         |
| -------------- | ------------------------------------------------------------------- | ------------------- |
| `hexToRgb`     | `(hex: string) => { r: number; g: number; b: number } \| null`      | Converts hex to RGB |
| `rgbToHex`     | `(r: number, g: number, b: number) => string`                       | Converts RGB to hex |
| `isLightColor` | `(color: string \| { r: number; g: number; b: number }) => boolean` | Checks luminance    |

**Usage:**

```typescript
import { hexToRgb, rgbToHex, isLightColor } from "@/design-system";

hexToRgb("#4f46e5"); // { r: 79, g: 70, b: 229 }
rgbToHex(79, 70, 229); // '#4f46e5'

isLightColor("#ffffff"); // true
isLightColor("#4f46e5"); // false → use white text
```

---

### Function Utilities

| Function   | Signature                                                                                                 | Description            |
| ---------- | --------------------------------------------------------------------------------------------------------- | ---------------------- |
| `debounce` | `<T extends (...args: unknown[]) => unknown>(func: T, delay: number) => (...args: Parameters<T>) => void` | Delays invocation      |
| `throttle` | `<T extends (...args: unknown[]) => unknown>(func: T, delay: number) => (...args: Parameters<T>) => void` | Rate-limits invocation |

**Usage:**

```typescript
import { debounce, throttle } from "@/design-system";

const debouncedSearch = debounce(fetchResults, 300);
searchInput.addEventListener("input", debouncedSearch);

const throttledScroll = throttle(onScroll, 100);
window.addEventListener("scroll", throttledScroll);
```

---

## Theming System

The theming system uses CSS custom properties (variables) to enable runtime customization without re-rendering components. All design tokens are injected as CSS variables on the document root.

### Design Tokens

Tokens are organized into categories that cover every visual aspect of the design system.

#### Colors

The color system has two layers:

1. **Primitive Palette** — Raw color values (e.g., `#4f46e5`, `#ffffff`)
2. **Semantic Mappings** — Intent-mapped colors (e.g., `color-primary`, `color-text`)

**Semantic Color Tokens:**

| Token                       | Purpose                     | Default   |
| --------------------------- | --------------------------- | --------- |
| `--ch-color-primary`        | Main interactive color      | `#4f46e5` |
| `--ch-color-primary-hover`  | Hover state                 | `#4338ca` |
| `--ch-color-primary-active` | Active/pressed              | `#3730a3` |
| `--ch-color-primary-subtle` | Light background tint       | `#eef2ff` |
| `--ch-color-bg`             | Page background             | `#ffffff` |
| `--ch-color-bg-subtle`      | Slightly off-white sections | `#f8fafc` |
| `--ch-color-surface`        | Cards, panels               | `#ffffff` |
| `--ch-color-border`         | Default borders             | `#e2e8f0` |
| `--ch-color-text`           | Primary text                | `#0f172a` |
| `--ch-color-text-muted`     | Secondary text              | `#64748b` |
| `--ch-color-success`        | Success states              | `#16a34a` |
| `--ch-color-warning`        | Warning states              | `#d97706` |
| `--ch-color-danger`         | Error/danger states         | `#e11d48` |
| `--ch-color-info`           | Info states                 | `#2563eb` |

#### Typography

| Token                | Example Value                              |
| -------------------- | ------------------------------------------ |
| `--ch-font-sans`     | `"Plus Jakarta Sans", "Inter", sans-serif` |
| `--ch-font-display`  | `"Lora", "Georgia", serif`                 |
| `--ch-font-mono`     | `"JetBrains Mono", monospace`              |
| `--ch-text-xs`       | `0.75rem`                                  |
| `--ch-text-sm`       | `0.875rem`                                 |
| `--ch-text-base`     | `1rem`                                     |
| `--ch-text-lg`       | `1.125rem`                                 |
| `--ch-text-xl`       | `1.25rem`                                  |
| `--ch-text-2xl`      | `1.5rem`                                   |
| `--ch-font-normal`   | `400`                                      |
| `--ch-font-medium`   | `500`                                      |
| `--ch-font-semibold` | `600`                                      |

#### Spacing

The spacing scale uses a 4px base unit:

| Token          | Value           |
| -------------- | --------------- |
| `--ch-space-1` | `0.25rem` (4px) |
| `--ch-space-2` | `0.5rem` (8px)  |
| `--ch-space-4` | `1rem` (16px)   |
| `--ch-space-6` | `1.5rem` (24px) |
| `--ch-space-8` | `2rem` (32px)   |

#### Radius

| Token              | Value     |
| ------------------ | --------- |
| `--ch-radius-sm`   | `0.25rem` |
| `--ch-radius-lg`   | `0.5rem`  |
| `--ch-radius-xl`   | `0.75rem` |
| `--ch-radius-full` | `9999px`  |

#### Shadows

| Token            | Purpose                |
| ---------------- | ---------------------- |
| `--ch-shadow-sm` | Default card           |
| `--ch-shadow-md` | Hovered card, dropdown |
| `--ch-shadow-lg` | Modal, sticky nav      |
| `--ch-shadow-xl` | Side drawer            |

#### Z-Index Scale

| Token             | Value | Use Case      |
| ----------------- | ----- | ------------- |
| `--ch-z-base`     | `0`   | Normal flow   |
| `--ch-z-dropdown` | `100` | Dropdowns     |
| `--ch-z-modal`    | `400` | Modals        |
| `--ch-z-toast`    | `500` | Notifications |
| `--ch-z-tooltip`  | `600` | Tooltips      |

### Dark Mode Support

Dark mode is built into the theming system with semantic color mappings for dark backgrounds. Use the `useTheme` composable to toggle:

```typescript
import { useTheme } from '@/design-system'
const { toggleDarkMode, isDarkMode } = useTheme()

// Toggle on button click
<button @click="toggleDarkMode">
  {{ isDarkMode ? 'Light Mode' : 'Dark Mode' }}
</button>
```

### Runtime Theme Customization

Apply custom branding at runtime:

```typescript
import { useTheme } from "@/design-system";
const { applyOverrides } = useTheme();

// Apply custom primary color
applyOverrides({
  "--ch-color-primary": "#7c3aed",
  "--ch-color-primary-hover": "#6d28d9",
});

// Apply to specific element (for multi-tenant)
const tenantRoot = document.getElementById("tenant-app");
applyOverrides({ "--ch-color-primary": tenant.brandColor }, tenantRoot);
```

---

## Best Practices

The following guidelines ensure consistent usage and optimal performance when using the design system.

### Using Semantic Colors

Always use semantic color tokens (`--ch-color-primary`, `--ch-color-text-muted`) rather than hardcoded hex values. This ensures components automatically adapt to theme changes and maintains visual consistency.

```vue
<!-- Good: Uses semantic tokens -->
<div class="title">
  This text uses semantic color
</div>

<style scoped>
.title {
  color: var(--ch-color-text);
  background: var(--ch-color-bg-subtle);
}
</style>

<!-- Bad: Hardcoded values -->
<style scoped>
.title {
  color: #0f172a;
  background: #f8fafc;
}
</style>
```

### Slot-Based Composition

Prefer slot-based composition over prop-based customization. Slots provide more flexibility while keeping components composable:

```vue
<!-- Good: Using slots for flexible content -->
<ChCard>
  <template #header>
    <h3>Member Details</h3>
  </template>
  <MemberInfo :member="member" />
  <template #footer>
    <ChButton variant="primary">Save</ChButton>
  </template>
</ChCard>
```

### Accessibility

All components include accessibility features. Follow these guidelines:

1. **Labels** — Always provide accessible labels for form inputs
2. **Keyboard navigation** — Components handle keyboard events; avoid disabling focus
3. **Error messages** — Use the `error` prop to communicate issues to screen readers
4. **Aria attributes** — Components set appropriate ARIA attributes automatically

```vue
<!-- Good: Proper form field with label -->
<ChFormField label="Email Address" required :error="errors.email">
  <ChInput v-model="email" id="email" type="email" />
</ChFormField>

<!-- Good: Loading state communicated to AT -->
<ChButton :loading="isSaving" @click="save">
  Save
</ChButton>
```

### Loading States

Always provide loading feedback for async operations. Use the `loading` prop on buttons and the skeleton components for content placeholders:

```vue
<!-- Button loading state -->
<ChButton :loading="isSubmitting" @click="submit">
  Submit
</ChButton>

<!-- Table loading state -->
<ChTable :data="members" :columns="columns" :loading="isLoading" />

<!-- Skeleton for content loading -->
<ChSkeleton variant="rectangular" height="200px" />
```

### TypeScript Usage

Take advantage of TypeScript types exported by the design system:

```typescript
import {
  type SelectOption,
  type ColumnDef,
  type WizardStep,
} from "@/design-system";

const options: SelectOption[] = [
  { value: "1", label: "Option 1" },
  { value: "2", label: "Option 2" },
];

const columns: ColumnDef<Member>[] = [
  { key: "name", label: "Name", sortable: true },
  { key: "email", label: "Email" },
];
```

---

## Version Information

This documentation describes the AliveChMS Design System. For the latest updates and component details, refer to the source files in `frontend/src/design-system/`.

**Last Updated:** March 2026
